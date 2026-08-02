<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Book;
use App\Models\BorrowRequest;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CalculateBoipokaWinner extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:calculate-boipoka-winner';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate monthly Boipoka (Winner) points and assign badge';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $start = Carbon::now()->subMonth()->startOfMonth();
        $end = Carbon::now()->subMonth()->endOfMonth();

        $this->info("Calculating Boipoka points for " . $start->format('F Y'));

        // Reset points and badges
        User::query()->update(['boipoka_points' => 0, 'boipoka_badge' => false]);

        $users = User::all();
        $allBooks = Book::whereNotNull('reviews')->get();

        foreach ($users as $user) {
            $points = 0;

            // 1. Add Book = 15 points
            $addedBooksCount = Book::where('owner_id', $user->id)
                ->whereBetween('created_at', [$start, $end])
                ->count();
            $points += $addedBooksCount * 15;

            // 2. Borrow Book = 25 points
            // Find requests where user is borrower, created last month, and got approved/returned
            $borrowedRequests = BorrowRequest::where('borrower_id', $user->id)
                ->whereBetween('request_date', [$start, $end])
                ->whereIn('status', ['approved', 'returned'])
                ->get();
            $points += $borrowedRequests->count() * 25;

            // 3. Lending Book = 50 points
            // Find requests where user is owner, created last month, and got approved/returned
            $lentRequests = BorrowRequest::where('owner_id', $user->id)
                ->whereBetween('request_date', [$start, $end])
                ->whereIn('status', ['approved', 'returned'])
                ->get();
            $points += $lentRequests->count() * 50;

            // 4. Lending book on time (Borrower returned on time = 15 points)
            // 5. Lending book overdue (Borrower returned late = -2 per day)
            // Apply this to the borrower
            foreach ($borrowedRequests as $req) {
                if ($req->actual_return_date && $req->expected_return_date) {
                    $actual = Carbon::parse($req->actual_return_date);
                    $expected = Carbon::parse($req->expected_return_date);
                    
                    if ($actual->lessThanOrEqualTo($expected)) {
                        $points += 15;
                    } else {
                        $daysOverdue = $expected->diffInDays($actual);
                        $points -= ($daysOverdue * 2);
                    }
                }
            }

            // 6. Review and rating = 2 points per review
            $reviewCount = 0;
            foreach ($allBooks as $book) {
                $reviews = is_string($book->reviews) ? json_decode($book->reviews, true) : $book->reviews;
                if (is_array($reviews)) {
                    foreach ($reviews as $review) {
                        if (($review['user_id'] ?? null) === $user->id) {
                            if (isset($review['created_at'])) {
                                $reviewDate = Carbon::parse($review['created_at']);
                                if ($reviewDate->between($start, $end)) {
                                    $reviewCount++;
                                }
                            }
                        }
                    }
                }
            }
            $points += $reviewCount * 2;

            if ($points > 0) {
                $user->update(['boipoka_points' => $points]);
            }
        }

        // Assign Badge to the top users
        $topUsers = User::where('boipoka_points', '>', 0)
            ->orderBy('boipoka_points', 'desc')
            ->limit(10)
            ->get();

        if ($topUsers->isEmpty()) {
            $this->info("No points awarded this month.");
        } else {
            $rank = 1;
            foreach ($topUsers as $topUser) {
                if ($rank <= 3) {
                    $topUser->update(['boipoka_badge' => $rank]);
                } else {
                    $topUser->update(['boipoka_badge' => 4]); // 4 represents Top 10
                }
                $rank++;
            }
            $this->info("Badges assigned to top {$topUsers->count()} users.");
        }
    }
}
