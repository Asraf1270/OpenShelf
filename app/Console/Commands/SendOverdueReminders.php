<?php

namespace App\Console\Commands;

use App\Models\BorrowRequest;
use App\Models\User;
use App\Services\MailerService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class SendOverdueReminders extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-overdue-reminders';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checks for overdue borrow requests and sends email notifications to borrowers.';

    /**
     * Execute the console command.
     */
    public function handle(MailerService $mailer)
    {
        $overdueDaysTargets = [1, 3, 7, 14, 21, 30];
        $sentCount = 0;
        $skippedCount = 0;

        // Fetch requests that might be overdue
        $requests = BorrowRequest::with('owner')->whereIn('status', ['approved', 'borrowed'])
            ->whereNull('returned_at')
            ->whereNotNull('expected_return_date')
            ->get();

        foreach ($requests as $request) {
            $expectedReturnDate = Carbon::parse($request->expected_return_date)->startOfDay();
            $now = Carbon::now()->startOfDay();
            $overdueDays = $expectedReturnDate->diffInDays($now, false);

            // only process negative diffs which means overdue if you do diffInDays(now, false).
            // Actually diffInDays returns absolute by default, let's just do:
            // diffInDays with false as second param: expectedReturnDate->diffInDays(now, false)
            // If expected is past, expected->diffInDays(now, false) is positive.
            if ($expectedReturnDate->isFuture() || !in_array($overdueDays, $overdueDaysTargets)) {
                continue;
            }

            $history = is_string($request->history) ? json_decode($request->history, true) : ($request->history ?? []);
            if (!is_array($history)) {
                $history = [];
            }

            $alreadySent = false;
            foreach ($history as $event) {
                if (isset($event['action']) && $event['action'] === 'overdue_email_sent' && isset($event['days']) && (int)$event['days'] === $overdueDays) {
                    $alreadySent = true;
                    break;
                }
            }

            if ($alreadySent) {
                $skippedCount++;
                continue;
            }

            if (empty($request->borrower_email)) {
                $this->warn("⚠️ Borrower email is empty for request: {$request->id}");
                continue;
            }

            $emailData = [
                'subject' => "Urgent: Book \"{$request->book_title}\" is Overdue",
                'type' => 'danger',
                'borrower_name' => $request->borrower_name ?? 'Reader',
                'book_title' => $request->book_title,
                'due_date' => $request->expected_return_date,
                'overdue_days' => $overdueDays,
                'owner_name' => $request->owner_name ?? 'Owner',
                'owner_phone' => $request->owner->phone ?? '',
                'base_url' => config('app.url')
            ];

            $success = $mailer->sendTemplate(
                $request->borrower_email,
                $request->borrower_name ?? 'Reader',
                'overdue',
                $emailData
            );

            if ($success) {
                $history[] = [
                    'action' => 'overdue_email_sent',
                    'timestamp' => Carbon::now()->format('Y-m-d H:i:s'),
                    'days' => $overdueDays
                ];

                $request->update([
                    'history' => $history
                ]);

                $this->info("Sent overdue email (Day {$overdueDays}) to {$request->borrower_email} for request {$request->id}");
                $sentCount++;
            } else {
                $this->error("Failed to send overdue email to {$request->borrower_email} for request {$request->id}");
            }
        }

        $this->info("Finished overdue checks. Sent: {$sentCount}, Skipped: {$skippedCount}.");
    }
}
