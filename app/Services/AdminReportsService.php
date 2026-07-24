<?php

namespace App\Services;

use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminReportsService
{
    public function summaryStats(): array
    {
        $userActivity = $this->userActivityStats();

        return [
            'totalUsers' => $userActivity['total'],
            'totalBooks' => Book::query()->count(),
            'totalRequests' => BorrowRequest::query()->count(),
            'pendingUsers' => User::query()->where('verified', false)->count(),
            'pendingRequests' => BorrowRequest::query()->where('status', 'pending')->count(),
            'userActivity' => $userActivity,
        ];
    }

    public function monthlyStats(string $table, string $dateField, int $months = 6): array
    {
        $stats = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $date = now()->subMonths($i)->format('Y-m');
            $stats[$date] = 0;
        }

        $startDate = now()->subMonths($months - 1)->startOfMonth()->toDateString();

        $results = DB::table($table)
            ->selectRaw("DATE_FORMAT({$dateField}, '%Y-%m') as month, COUNT(*) as count")
            ->where($dateField, '>=', $startDate)
            ->groupBy('month')
            ->pluck('count', 'month');

        foreach ($results as $month => $count) {
            if (isset($stats[$month])) {
                $stats[$month] = (int) $count;
            }
        }

        return $stats;
    }

    public function topBooks(int $limit = 10): array
    {
        return BorrowRequest::query()
            ->selectRaw('book_title as title, book_author as author, COUNT(*) as borrow_count')
            ->where('status', 'approved')
            ->groupBy('book_id', 'book_title', 'book_author')
            ->orderByDesc('borrow_count')
            ->limit($limit)
            ->get()
            ->all();
    }

    public function userActivityStats(): array
    {
        return [
            'total' => User::query()->count(),
            'today' => User::query()->where('last_login', '>=', now()->startOfDay())->count(),
            'this_week' => User::query()->where('last_login', '>=', now()->subDays(7))->count(),
            'this_month' => User::query()->where('last_login', '>=', now()->subDays(30))->count(),
        ];
    }

    public function exportCsv(string $type): string
    {
        $handle = fopen('php://temp', 'r+');

        if ($type === 'books') {
            fputcsv($handle, ['ID', 'Title', 'Author', 'Category', 'Owner', 'Status', 'Views', 'Times Borrowed', 'Created At']);

            Book::query()->orderByDesc('created_at')->chunk(200, function ($books) use ($handle) {
                foreach ($books as $book) {
                    fputcsv($handle, [
                        $book->id,
                        $book->title,
                        $book->author,
                        $book->category,
                        $book->owner_name,
                        $book->status,
                        $book->views ?? 0,
                        $book->times_borrowed ?? 0,
                        $book->created_at,
                    ]);
                }
            });
        } elseif ($type === 'requests') {
            fputcsv($handle, ['ID', 'Book', 'Borrower', 'Owner', 'Status', 'Request Date', 'Due Date', 'Return Date', 'Duration']);

            BorrowRequest::query()->orderByDesc('request_date')->chunk(200, function ($requests) use ($handle) {
                foreach ($requests as $request) {
                    fputcsv($handle, [
                        $request->id,
                        $request->book_title,
                        $request->borrower_name,
                        $request->owner_name,
                        $request->status,
                        $request->request_date,
                        $request->expected_return_date,
                        $request->returned_at,
                        $request->duration_days,
                    ]);
                }
            });
        } else {
            fputcsv($handle, ['ID', 'Name', 'Email', 'Department', 'Session', 'Phone', 'Room', 'Status', 'Verified', 'Created At']);

            User::query()->orderByDesc('created_at')->chunk(200, function ($users) use ($handle) {
                foreach ($users as $user) {
                    fputcsv($handle, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->department,
                        $user->session,
                        $user->phone,
                        $user->room_number,
                        $user->status,
                        $user->verified ? 'Yes' : 'No',
                        $user->created_at,
                    ]);
                }
            });
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv ?: '';
    }
}
