<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\User;
use App\Models\Wishlist;

class BorrowRequestService
{
    public function __construct(
        private BookQueryService $bookQueryService,
        private NotificationService $notificationService,
    ) {
    }

    public function hasPendingRequest(string $bookId, string $userId): bool
    {
        return BorrowRequest::query()
            ->where('book_id', $bookId)
            ->where('borrower_id', $userId)
            ->where('status', 'pending')
            ->exists();
    }

    public function createRequest(
        Book $book,
        User $borrower,
        User $owner,
        string $borrowerName,
        int $duration,
        string $message = '',
    ): BorrowRequest {
        $requestId = 'REQ' . time() . bin2hex(random_bytes(4));

        $borrowRequest = BorrowRequest::create([
            'id' => $requestId,
            'book_id' => $book->id,
            'book_title' => $book->title,
            'book_author' => $book->author,
            'book_cover' => $book->cover_image,
            'owner_id' => $book->owner_id,
            'owner_name' => $owner->name,
            'owner_email' => $owner->email,
            'borrower_id' => $borrower->id,
            'borrower_name' => $borrowerName,
            'borrower_email' => $borrower->email,
            'status' => 'pending',
            'request_date' => now(),
            'expected_return_date' => now()->addDays($duration),
            'duration_days' => $duration,
            'message' => $message,
            'updated_at' => now(),
        ]);

        $book->update([
            'status' => 'reserved',
            'updated_at' => now(),
        ]);

        $this->notificationService->create(
            $book->owner_id,
            'borrow_request',
            'New Borrow Request',
            "{$borrowerName} wants to borrow \"{$book->title}\"",
            '/requests/?id=' . $requestId,
        );

        return $borrowRequest;
    }

    public function fileReturn(BorrowRequest $borrowRequest, string $userId, string $userName, array $data): bool
    {
        $history = $borrowRequest->history ?? [];
        $history[] = [
            'action' => 'return_filed',
            'timestamp' => now()->toDateTimeString(),
            'user_id' => $userId,
            'user_name' => $userName,
            'notes' => $data['notes'] ?? '',
            'condition' => $data['return_condition'] ?? 'same',
            'rating' => $data['rating'] ?? 0,
        ];

        $confirmationToken = bin2hex(random_bytes(32));

        $borrowRequest->update([
            'status' => 'pending_return',
            'history' => $history,
            'returned_at' => now(),
            'actual_return_date' => now(),
            'notes' => $data['notes'] ?? null,
            'return_condition' => $data['return_condition'] ?? null,
            'returned_by' => $userId,
            'returned_by_name' => $userName,
            'rating' => $data['rating'] ?? 0,
            'return_confirmation_token' => $confirmationToken,
            'return_confirmation_status' => 'pending_owner',
            'return_confirmation_sent_at' => now(),
            'updated_at' => now(),
        ]);

        $this->notificationService->create(
            $borrowRequest->owner_id,
            'return_pending',
            'Book Return Awaiting Confirmation',
            $userName . ' has filed a return for "' . $borrowRequest->book_title . '". Please confirm physical receipt.',
            '/confirm-return/?token=' . $confirmationToken,
        );

        return true;
    }

    public function confirmReturn(BorrowRequest $borrowRequest, string $action, ?string $rejectReason = null): string
    {
        if ($action === 'confirm') {
            $history = $borrowRequest->history ?? [];
            $history[] = [
                'action' => 'return_confirmed',
                'timestamp' => now()->toDateTimeString(),
                'user_id' => $borrowRequest->owner_id,
                'user_name' => $borrowRequest->owner_name,
                'notes' => 'Owner confirmed physical receipt',
            ];

            $borrowRequest->update([
                'status' => 'returned',
                'return_confirmation_status' => 'confirmed',
                'return_confirmed_at' => now(),
                'return_confirmation_token' => null,
                'history' => $history,
                'updated_at' => now(),
            ]);

            Book::where('id', $borrowRequest->book_id)->update([
                'status' => 'available',
                'updated_at' => now(),
            ]);

            Wishlist::where('book_id', $borrowRequest->book_id)->update([
                'notified' => false,
                'updated_at' => now(),
            ]);

            $this->notificationService->create(
                $borrowRequest->borrower_id,
                'return_confirmed',
                'Return Confirmed',
                'Your return of "' . $borrowRequest->book_title . '" has been confirmed by the owner.',
                '/requests/?id=' . $borrowRequest->id,
            );

            $this->notificationService->create(
                $borrowRequest->owner_id,
                'book_returned',
                'Book Return Complete',
                '"' . $borrowRequest->book_title . '" is now marked as available.',
                '/requests/?id=' . $borrowRequest->id,
            );

            return 'confirmed';
        }

        $history = $borrowRequest->history ?? [];
        $history[] = [
            'action' => 'return_rejected',
            'timestamp' => now()->toDateTimeString(),
            'user_id' => $borrowRequest->owner_id,
            'user_name' => $borrowRequest->owner_name,
            'notes' => $rejectReason ?: 'Owner indicated they did not physically receive the book',
        ];

        $borrowRequest->update([
            'status' => 'approved',
            'return_confirmation_status' => 'rejected',
            'return_rejected_at' => now(),
            'return_reject_reason' => $rejectReason,
            'return_confirmation_token' => null,
            'history' => $history,
            'updated_at' => now(),
        ]);

        $this->notificationService->create(
            $borrowRequest->borrower_id,
            'return_rejected',
            'Return Not Confirmed',
            'The owner of "' . $borrowRequest->book_title . '" has not received the book. Please contact them.',
            '/requests/?id=' . $borrowRequest->id,
        );

        return 'rejected';
    }

    public function getActiveBorrowsForUser(string $userId)
    {
        return BorrowRequest::query()
            ->select('borrow_requests.*', 'books.cover_image')
            ->join('books', 'borrow_requests.book_id', '=', 'books.id')
            ->where('borrow_requests.borrower_id', $userId)
            ->whereIn('borrow_requests.status', ['approved', 'borrowed'])
            ->whereNull('borrow_requests.returned_at')
            ->orderByDesc('borrow_requests.request_date')
            ->get();
    }

    public function getPastBorrowsForUser(string $userId, int $limit = 10)
    {
        return BorrowRequest::query()
            ->select('borrow_requests.*', 'books.cover_image')
            ->join('books', 'borrow_requests.book_id', '=', 'books.id')
            ->where('borrow_requests.borrower_id', $userId)
            ->where('borrow_requests.status', 'returned')
            ->orderByDesc('borrow_requests.returned_at')
            ->limit($limit)
            ->get();
    }

    public function getReceivedRequestsForUser(string $userId)
    {
        return BorrowRequest::query()
            ->with('borrower')
            ->where('owner_id', $userId)
            ->orderByDesc('request_date')
            ->get();
    }

    public function getSentRequestsForUser(string $userId)
    {
        return BorrowRequest::query()
            ->with('owner')
            ->where('borrower_id', $userId)
            ->orderByDesc('request_date')
            ->get();
    }

    /**
     * @param  \Illuminate\Support\Collection<int, BorrowRequest>  $receivedRequests
     * @return array{pending: int, approved: int, rejected: int, returned: int}
     */
    public function getReceivedStats($receivedRequests): array
    {
        return [
            'pending' => $receivedRequests->where('status', 'pending')->count(),
            'approved' => $receivedRequests->where('status', 'approved')->count(),
            'rejected' => $receivedRequests->where('status', 'rejected')->count(),
            'returned' => $receivedRequests->where('status', 'returned')->count(),
        ];
    }

    public function approveRequest(BorrowRequest $borrowRequest, string $ownerId): bool
    {
        if ($borrowRequest->owner_id !== $ownerId || $borrowRequest->status !== 'pending') {
            return false;
        }

        $borrowRequest->update([
            'status' => 'approved',
            'approved_at' => now(),
            'approved_by' => $ownerId,
            'updated_at' => now(),
        ]);

        Book::where('id', $borrowRequest->book_id)->update([
            'status' => 'borrowed',
            'updated_at' => now(),
        ]);

        $this->notificationService->create(
            $borrowRequest->borrower_id,
            'request_approved',
            'Borrow Request Approved',
            "Your request for '{$borrowRequest->book_title}' has been approved",
            '/requests/?id=' . $borrowRequest->id,
        );

        return true;
    }

    public function rejectRequest(BorrowRequest $borrowRequest, string $ownerId, string $reason): bool
    {
        if ($borrowRequest->owner_id !== $ownerId || $borrowRequest->status !== 'pending') {
            return false;
        }

        $borrowRequest->update([
            'status' => 'rejected',
            'rejected_at' => now(),
            'rejected_by' => $ownerId,
            'rejection_reason' => $reason,
            'updated_at' => now(),
        ]);

        Book::where('id', $borrowRequest->book_id)->update([
            'status' => 'available',
            'updated_at' => now(),
        ]);

        $message = "Your request for '{$borrowRequest->book_title}' has been rejected";
        if ($reason !== '') {
            $message .= ': ' . $reason;
        }

        $this->notificationService->create(
            $borrowRequest->borrower_id,
            'request_rejected',
            'Borrow Request Rejected',
            $message,
            '/requests/?id=' . $borrowRequest->id,
        );

        return true;
    }

    public function thumbCoverUrl(?string $coverImage): string
    {
        return $this->bookQueryService->resolveCoverPath($coverImage);
    }
}
