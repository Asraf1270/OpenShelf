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

    public static function sortLeaderboardEntries(array $entries): array
    {
        usort($entries, function (array $left, array $right): int {
            $comparison = ($right['boipoka_points'] ?? 0) <=> ($left['boipoka_points'] ?? 0);
            if ($comparison !== 0) {
                return $comparison;
            }

            $comparison = ($right['added_books'] ?? 0) <=> ($left['added_books'] ?? 0);
            if ($comparison !== 0) {
                return $comparison;
            }

            $comparison = ($right['lent_books'] ?? 0) <=> ($left['lent_books'] ?? 0);
            if ($comparison !== 0) {
                return $comparison;
            }

            $comparison = ($right['on_time_returns'] ?? 0) <=> ($left['on_time_returns'] ?? 0);
            if ($comparison !== 0) {
                return $comparison;
            }

            $comparison = ($right['review_count'] ?? 0) <=> ($left['review_count'] ?? 0);
            if ($comparison !== 0) {
                return $comparison;
            }

            $sessionComparison = strcasecmp((string) ($left['session'] ?? ''), (string) ($right['session'] ?? ''));
            if ($sessionComparison !== 0) {
                return $sessionComparison;
            }

            return strcasecmp((string) ($left['name'] ?? ''), (string) ($right['name'] ?? ''));
        });

        return $entries;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $start = Carbon::now()->subMonth()->startOfMonth();
        $end = Carbon::now()->subMonth()->endOfMonth();

        $this->info("Calculating Boipoka points for " . $start->format('F Y'));

        User::query()->update([
            'boipoka_points' => 0,
            'boipoka_badge' => false,
            'boipoka_position' => null,
        ]);

        $users = User::all();
        $allBooks = Book::whereNotNull('reviews')->get();
        $leaderboardEntries = [];

        foreach ($users as $user) {
            $points = 0;

            // 1. Add Book = 15 points
            $addedBooksCount = Book::where('owner_id', $user->id)
                ->whereBetween('created_at', [$start, $end])
                ->count();
            $points += $addedBooksCount * 15;

            // 2. Borrow Book = 25 points
            $borrowedRequests = BorrowRequest::where('borrower_id', $user->id)
                ->whereBetween('request_date', [$start, $end])
                ->whereIn('status', ['approved', 'returned'])
                ->get();
            $points += $borrowedRequests->count() * 25;

            // 3. Lending Book = 50 points
            $lentRequests = BorrowRequest::where('owner_id', $user->id)
                ->whereBetween('request_date', [$start, $end])
                ->whereIn('status', ['approved', 'returned'])
                ->get();
            $points += $lentRequests->count() * 50;

            $onTimeReturnCount = 0;
            foreach ($borrowedRequests as $req) {
                if ($req->actual_return_date && $req->expected_return_date) {
                    $actual = Carbon::parse($req->actual_return_date);
                    $expected = Carbon::parse($req->expected_return_date);

                    if ($actual->lessThanOrEqualTo($expected)) {
                        $points += 15;
                        $onTimeReturnCount++;
                    } else {
                        $daysOverdue = $expected->diffInDays($actual);
                        $points -= ($daysOverdue * 2);
                    }
                }
            }

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
                $leaderboardEntries[] = [
                    'user' => $user,
                    'boipoka_points' => $points,
                    'added_books' => $addedBooksCount,
                    'lent_books' => $lentRequests->count(),
                    'on_time_returns' => $onTimeReturnCount,
                    'review_count' => $reviewCount,
                    'session' => (string) ($user->session ?? ''),
                    'name' => (string) $user->name,
                ];
            }
        }

        $leaderboardEntries = self::sortLeaderboardEntries($leaderboardEntries);

        foreach ($leaderboardEntries as $index => $entry) {
            $entry['user']->update([
                'boipoka_points' => $entry['boipoka_points'],
                'boipoka_position' => $index + 1,
                'boipoka_badge' => $index < 3 ? $index + 1 : 4,
            ]);
        }

        $this->info('Leaderboard updated for ' . count($leaderboardEntries) . ' users.');
    }
}
