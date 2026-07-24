<?php

namespace App\Console\Commands;

use App\Models\Book;
use App\Models\Wishlist;
use App\Services\MailerService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class NotifyWishlistAvailability extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:notify-wishlist-availability';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Finds books that have become available and sends a notification email to the FIRST user who added the book to their wishlist.';

    /**
     * Execute the console command.
     */
    public function handle(MailerService $mailer)
    {
        $sentCount = 0;
        $skippedCount = 0;
        $errorCount = 0;

        // Find distinct available books that have at least one un-notified wishlist entry
        $availableBookIds = Wishlist::where('notified', false)
            ->whereHas('book', function ($query) {
                $query->where('status', 'available');
            })
            ->distinct()
            ->pluck('book_id');

        foreach ($availableBookIds as $bookId) {
            $book = Book::find($bookId);
            if (!$book || $book->status !== 'available') {
                $skippedCount++;
                continue;
            }

            // Fetch the first un-notified wishlist entry (FIFO)
            $wish = Wishlist::with('user')
                ->where('book_id', $bookId)
                ->where('notified', false)
                ->orderBy('created_at', 'asc')
                ->first();

            if (!$wish || !$wish->user) {
                $skippedCount++;
                continue;
            }

            if (empty($wish->user->email)) {
                $this->warn("⚠️ No email for user {$wish->user_id} — skipping.");
                $wish->update(['notified' => true]);
                $skippedCount++;
                continue;
            }

            // Determine queue position
            $position = Wishlist::where('book_id', $bookId)
                ->where('notified', false)
                ->where('created_at', '<', $wish->created_at)
                ->count() + 1;

            $emailData = [
                'subject' => "📗 \"{$book->title}\" is now available on OpenShelf!",
                'type' => 'success',
                'user_name' => $wish->user->name,
                'book_title' => $book->title,
                'book_author' => $book->author,
                'book_id' => $book->id,
                'queue_position' => $position,
                'base_url' => config('app.url'),
            ];

            $success = $mailer->sendTemplate(
                $wish->user->email,
                $wish->user->name,
                'wishlist_available',
                $emailData
            );

            if ($success) {
                $wish->update(['notified' => true]);
                $this->info("Sent wishlist notification to {$wish->user->email} for book \"{$book->title}\" (book_id={$bookId})");
                $sentCount++;
            } else {
                $this->error("Failed to send to {$wish->user->email} for book_id={$bookId}");
                $errorCount++;
            }
        }

        $this->info("Done. Sent: {$sentCount}, Skipped: {$skippedCount}, Errors: {$errorCount}");
    }
}
