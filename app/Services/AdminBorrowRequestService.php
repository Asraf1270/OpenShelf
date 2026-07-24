<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class AdminBorrowRequestService
{
    public function __construct(
        private NotificationService $notificationService,
        private MailerService $mailerService,
    ) {
    }

    public function paginate(Request $request, int $perPage = 15): LengthAwarePaginator
    {
        return $this->filteredQuery($request)
            ->orderByDesc('request_date')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function stats(): array
    {
        return [
            'total' => BorrowRequest::query()->count(),
            'pending' => BorrowRequest::query()->where('status', 'pending')->count(),
            'approved' => BorrowRequest::query()->where('status', 'approved')->count(),
            'rejected' => BorrowRequest::query()->where('status', 'rejected')->count(),
            'returned' => BorrowRequest::query()->where('status', 'returned')->count(),
            'overdue' => BorrowRequest::query()
                ->whereIn('status', ['approved', 'borrowed'])
                ->where('expected_return_date', '<', now())
                ->count(),
        ];
    }

    public function enrichOverdueStatus($requests)
    {
        $now = now();

        return $requests->map(function (BorrowRequest $borrowRequest) use ($now) {
            if (in_array($borrowRequest->status, ['approved', 'borrowed'], true) && $borrowRequest->expected_return_date) {
                $dueDate = Carbon::parse($borrowRequest->expected_return_date);

                if ($dueDate->lt($now)) {
                    $borrowRequest->overdue = true;
                    $borrowRequest->overdue_days = (int) $dueDate->diffInDays($now);
                } else {
                    $borrowRequest->overdue = false;
                    $borrowRequest->days_until_due = (int) $now->diffInDays($dueDate);
                }
            }

            return $borrowRequest;
        });
    }

    public function updateStatus(BorrowRequest $borrowRequest, string $status, string $adminId, string $adminName, array $additionalData = []): bool
    {
        $history = $borrowRequest->history ?? [];
        $history[] = [
            'action' => $status . '_by_admin',
            'timestamp' => now()->toDateTimeString(),
            'admin_id' => $adminId,
            'admin_name' => $adminName,
            'data' => $additionalData,
        ];

        $updates = [
            'status' => $status,
            'history' => $history,
            'updated_at' => now(),
        ];

        if ($status === 'approved') {
            $updates['approved_at'] = now();
            $updates['approved_by'] = $adminId;
        } elseif ($status === 'rejected') {
            $updates['rejected_at'] = now();
            $updates['rejected_by'] = $adminId;
            $updates['rejection_reason'] = $additionalData['reason'] ?? '';
        } elseif ($status === 'closed') {
            $updates['notes'] = $additionalData['notes'] ?? '';
        }

        $borrowRequest->update($updates);

        if ($status === 'approved') {
            Book::where('id', $borrowRequest->book_id)->update([
                'status' => 'borrowed',
                'updated_at' => now(),
            ]);
        } elseif (in_array($status, ['rejected', 'closed'], true)) {
            $book = Book::query()->find($borrowRequest->book_id);

            if ($book && ($book->status ?? '') === 'reserved') {
                Book::where('id', $borrowRequest->book_id)->update([
                    'status' => 'available',
                    'updated_at' => now(),
                ]);
            }
        }

        $this->createStatusNotification($borrowRequest, $status);
        $this->sendStatusEmail($borrowRequest, $status, $additionalData);

        return true;
    }

    public function extendReturnDate(BorrowRequest $borrowRequest, int $additionalDays, string $adminId, string $reason = ''): bool
    {
        if (! in_array($borrowRequest->status, ['approved', 'borrowed'], true)) {
            return false;
        }

        $oldDate = $borrowRequest->expected_return_date;
        $newDate = Carbon::parse($oldDate)->addDays($additionalDays);

        $history = $borrowRequest->history ?? [];
        $history[] = [
            'action' => 'extended_by_admin',
            'timestamp' => now()->toDateTimeString(),
            'admin_id' => $adminId,
            'additional_days' => $additionalDays,
            'reason' => $reason,
        ];

        $borrowRequest->update([
            'expected_return_date' => $newDate,
            'history' => $history,
            'updated_at' => now(),
        ]);

        $this->notificationService->create(
            $borrowRequest->borrower_id,
            'return_date_extended',
            'Return Date Extended',
            "Your return date for '{$borrowRequest->book_title}' has been extended by {$additionalDays} days",
            '/requests/?id=' . $borrowRequest->id,
        );

        if (! empty($borrowRequest->borrower_email)) {
            $this->mailerService->sendTemplate(
                $borrowRequest->borrower_email,
                $borrowRequest->borrower_name,
                'overdue',
                [
                    'subject' => 'Return Date Extended for ' . $borrowRequest->book_title,
                    'user_name' => $borrowRequest->borrower_name,
                    'book_title' => $borrowRequest->book_title,
                    'due_date' => $newDate->toDateTimeString(),
                    'new_due_date' => $newDate->toDateTimeString(),
                    'additional_days' => $additionalDays,
                    'reason' => $reason,
                    'base_url' => config('app.url'),
                ],
                $borrowRequest->borrower_id,
            );
        }

        return true;
    }

    public function exportCsv(Request $request): string
    {
        $rows = $this->filteredQuery($request)
            ->orderByDesc('request_date')
            ->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'ID', 'Book', 'Author', 'Borrower', 'Owner', 'Status', 'Request Date', 'Due Date',
        ]);

        foreach ($rows as $row) {
            fputcsv($handle, [
                $row->id,
                $row->book_title,
                $row->book_author,
                $row->borrower_name,
                $row->owner_name,
                $row->status,
                $row->request_date?->format('Y-m-d H:i:s'),
                $row->expected_return_date?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv ?: '';
    }

    private function filteredQuery(Request $request): Builder
    {
        $status = $request->query('status', 'all');
        $search = trim((string) $request->query('search', ''));
        $fromDate = $request->query('from');
        $toDate = $request->query('to');

        $query = BorrowRequest::query();

        if ($status !== 'all') {
            if ($status === 'overdue') {
                $query->whereIn('status', ['approved', 'borrowed'])
                    ->where('expected_return_date', '<', now());
            } else {
                $query->where('status', $status);
            }
        }

        if ($search !== '') {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('book_title', 'like', "%{$search}%")
                    ->orWhere('borrower_name', 'like', "%{$search}%")
                    ->orWhere('owner_name', 'like', "%{$search}%");
            });
        }

        if (! empty($fromDate)) {
            $query->where('request_date', '>=', $fromDate . ' 00:00:00');
        }

        if (! empty($toDate)) {
            $query->where('request_date', '<=', $toDate . ' 23:59:59');
        }

        return $query;
    }

    private function createStatusNotification(BorrowRequest $borrowRequest, string $status): void
    {
        $map = [
            'approved' => ['request_approved', 'Borrow Request Approved', "Your request for '{$borrowRequest->book_title}' has been approved by admin"],
            'rejected' => ['request_rejected', 'Borrow Request Rejected', "Your request for '{$borrowRequest->book_title}' has been rejected by admin"],
            'closed' => ['request_closed', 'Borrow Request Closed', "Your borrow request for '{$borrowRequest->book_title}' has been closed by admin"],
        ];

        if (! isset($map[$status])) {
            return;
        }

        [$type, $title, $message] = $map[$status];

        $this->notificationService->create(
            $borrowRequest->borrower_id,
            $type,
            $title,
            $message,
            '/requests/?id=' . $borrowRequest->id,
        );
    }

    private function sendStatusEmail(BorrowRequest $borrowRequest, string $status, array $additionalData = []): void
    {
        if (empty($borrowRequest->borrower_email)) {
            return;
        }

        if ($status === 'approved') {
            $owner = User::query()->find($borrowRequest->owner_id);

            $this->mailerService->sendTemplate(
                $borrowRequest->borrower_email,
                $borrowRequest->borrower_name,
                'request_approved',
                [
                    'subject' => 'Your Borrow Request Has Been Approved!',
                    'user_name' => $borrowRequest->borrower_name,
                    'borrower_name' => $borrowRequest->borrower_name,
                    'book_title' => $borrowRequest->book_title,
                    'owner_name' => $borrowRequest->owner_name,
                    'owner_room' => $owner?->room_number ?? 'N/A',
                    'owner_phone' => $owner?->phone ?? 'N/A',
                    'due_date' => $borrowRequest->expected_return_date,
                    'base_url' => config('app.url'),
                ],
                $borrowRequest->borrower_id,
            );

            return;
        }

        if ($status === 'rejected') {
            $this->mailerService->sendTemplate(
                $borrowRequest->borrower_email,
                $borrowRequest->borrower_name,
                'request_rejected',
                [
                    'subject' => 'Update on Your Borrow Request',
                    'user_name' => $borrowRequest->borrower_name,
                    'borrower_name' => $borrowRequest->borrower_name,
                    'book_title' => $borrowRequest->book_title,
                    'rejection_reason' => $additionalData['reason'] ?? 'No reason provided',
                    'base_url' => config('app.url'),
                ],
                $borrowRequest->borrower_id,
            );
        }
    }
}
