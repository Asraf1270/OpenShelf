<?php

namespace App\Console\Commands;

use App\Models\BorrowRequest;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveOldBorrowRequests extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-old-borrow-requests';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enforces a limit of 15 borrow requests per user by deleting older entries.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find users with more than 15 borrow requests
        $users = BorrowRequest::select('borrower_id', DB::raw('COUNT(*) as count'))
            ->groupBy('borrower_id')
            ->havingRaw('count > 15')
            ->get();

        $deletedCount = 0;

        foreach ($users as $user) {
            $borrowerId = $user->borrower_id;

            // Get IDs to keep (the 15 most recent)
            $keepIds = BorrowRequest::where('borrower_id', $borrowerId)
                ->orderBy('request_date', 'desc')
                ->orderBy('id', 'desc')
                ->take(15)
                ->pluck('id');

            if ($keepIds->isNotEmpty()) {
                $deleted = BorrowRequest::where('borrower_id', $borrowerId)
                    ->whereNotIn('id', $keepIds)
                    ->delete();
                $deletedCount += $deleted;
            }
        }

        $this->info("Purged {$deletedCount} old borrow requests.");
    }
}
