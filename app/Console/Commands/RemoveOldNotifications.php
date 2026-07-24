<?php

namespace App\Console\Commands;

use App\Models\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RemoveOldNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:remove-old-notifications';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Enforces a limit of 25 notifications per user by deleting older entries.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Find users with more than 25 notifications
        $users = Notification::select('user_id', DB::raw('COUNT(*) as count'))
            ->groupBy('user_id')
            ->havingRaw('count > 25')
            ->get();

        $deletedCount = 0;

        foreach ($users as $user) {
            $userId = $user->user_id;

            // Get IDs to keep (the 25 most recent)
            $keepIds = Notification::where('user_id', $userId)
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->take(25)
                ->pluck('id');

            if ($keepIds->isNotEmpty()) {
                $deleted = Notification::where('user_id', $userId)
                    ->whereNotIn('id', $keepIds)
                    ->delete();
                $deletedCount += $deleted;
            }
        }

        $this->info("Purged {$deletedCount} old notifications.");
    }
}
