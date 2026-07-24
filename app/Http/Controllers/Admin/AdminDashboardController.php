<?php

namespace App\Http\Controllers\Admin;

use App\Models\Book;
use App\Models\BorrowRequest;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class AdminDashboardController extends AdminController
{
    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        $admin = $this->requireAdmin($request);

        if ($admin instanceof \Illuminate\Http\RedirectResponse) {
            return $admin;
        }

        $totalUsers = User::query()->count();
        $totalBooks = Book::query()->count();
        $totalRequests = BorrowRequest::query()->count();

        $availableBooks = Book::query()->where('status', 'available')->count();
        $borrowedBooks = Book::query()->where('status', 'borrowed')->count();

        $pendingUsers = User::query()
            ->where('verified', false)
            ->where('status', '!=', 'rejected')
            ->count();

        $pendingRequests = BorrowRequest::query()->where('status', 'pending')->count();
        $approvedRequests = BorrowRequest::query()->where('status', 'approved')->count();
        $rejectedRequests = BorrowRequest::query()->where('status', 'rejected')->count();
        $returnedRequests = BorrowRequest::query()->where('status', 'returned')->count();

        $userGrowth = $this->dailyGrowth(User::query(), 'created_at');
        $bookGrowth = $this->dailyGrowth(Book::query(), 'created_at');

        $topCategories = Book::query()
            ->selectRaw("COALESCE(NULLIF(category, ''), 'Uncategorized') as category, COUNT(*) as count")
            ->groupBy('category')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'category');

        $recentUsers = User::query()
            ->latest('created_at')
            ->limit(8)
            ->get(['id', 'name', 'email', 'created_at']);

        $recentRequests = BorrowRequest::query()
            ->latest('request_date')
            ->limit(8)
            ->get([
                'id',
                'book_title',
                'borrower_name',
                'status',
                'request_date',
            ]);

        $recentActivities = $this->buildRecentActivities($recentUsers, $recentRequests);

        return view('admin.dashboard', [
            'admin' => $admin,
            'greeting' => $this->greeting(),
            'todayLabel' => now()->format('l, F j, Y'),
            'totalUsers' => $totalUsers,
            'totalBooks' => $totalBooks,
            'totalRequests' => $totalRequests,
            'availableBooks' => $availableBooks,
            'borrowedBooks' => $borrowedBooks,
            'pendingUsers' => $pendingUsers,
            'pendingRequests' => $pendingRequests,
            'approvedRequests' => $approvedRequests,
            'rejectedRequests' => $rejectedRequests,
            'returnedRequests' => $returnedRequests,
            'userGrowth' => $userGrowth,
            'bookGrowth' => $bookGrowth,
            'userGrowthPercent' => $this->growthPercent($userGrowth),
            'bookGrowthPercent' => $this->growthPercent($bookGrowth),
            'topCategories' => $topCategories,
            'recentActivities' => $recentActivities,
        ]);
    }

    private function dailyGrowth($query, string $dateColumn, int $days = 30): array
    {
        $startDate = now()->subDays($days - 1)->startOfDay();

        $results = $query
            ->selectRaw('DATE(' . $dateColumn . ') as day, COUNT(*) as count')
            ->where($dateColumn, '>=', $startDate)
            ->groupBy(DB::raw('DATE(' . $dateColumn . ')'))
            ->orderBy('day')
            ->pluck('count', 'day');

        $growth = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i)->toDateString();
            $growth[$date] = (int) ($results[$date] ?? 0);
        }

        return $growth;
    }

    private function buildRecentActivities(Collection $recentUsers, Collection $recentRequests): Collection
    {
        $userActivities = $recentUsers->map(function (User $user) {
            return [
                'title' => 'New User Registration',
                'description' => $user->name . ' (' . $user->email . ') joined OpenShelf',
                'timestamp' => $user->created_at,
                'icon' => 'fa-user-plus',
                'color' => '#4C9F8A',
            ];
        });

        $requestActivities = $recentRequests->map(function (BorrowRequest $request) {
            $status = $request->status ?? 'pending';

            return [
                'title' => ucfirst($status) . ' Borrow Request',
                'description' => ($request->borrower_name ?: 'A user') . ' requested to borrow "' . ($request->book_title ?: 'a book') . '"',
                'timestamp' => $request->request_date,
                'icon' => $status === 'approved'
                    ? 'fa-check-circle'
                    : ($status === 'pending' ? 'fa-clock' : 'fa-times-circle'),
                'color' => $status === 'approved'
                    ? '#2E8B57'
                    : ($status === 'pending' ? '#D97706' : '#C65D5D'),
            ];
        });

        return $userActivities
            ->merge($requestActivities)
            ->sortByDesc(fn (array $activity) => optional($activity['timestamp'])->timestamp ?? 0)
            ->take(8)
            ->values();
    }

    private function growthPercent(array $growth): int
    {
        $values = array_values($growth);
        $lastMonth = array_sum($values);
        $previousMonth = 0;

        if ($previousMonth === 0) {
            return $lastMonth > 0 ? 100 : 0;
        }

        return (int) round((($lastMonth - $previousMonth) / $previousMonth) * 100);
    }

    private function greeting(): string
    {
        $hour = (int) now()->format('H');

        if ($hour < 12) {
            return 'Good Morning';
        }

        if ($hour < 18) {
            return 'Good Afternoon';
        }

        return 'Good Evening';
    }
}
