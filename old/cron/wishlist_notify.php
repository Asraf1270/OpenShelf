<?php
/**
 * OpenShelf Wishlist Availability Notifier — Cron Job
 *
 * Finds books that have become available (status = 'available') and
 * sends a notification email to the FIRST user who added the book to
 * their wishlist (FIFO, ordered by created_at ASC).
 *
 * After sending, the row is marked `notified = 1` so it won't be
 * re-sent for the same availability window.  When a book is borrowed
 * again its wishlist entries are reset to `notified = 0` automatically
 * (handled in the return-book flow).
 *
 * Run via system cron:
 *   php /path/to/OpenShelf/cron/wishlist_notify.php
 *
 * Or via HTTP (with secret):
 *   https://yourdomain.com/cron/wishlist_notify.php?secret=openshelf_cron_secret
 */

// Only allow CLI or authorised HTTP request
if (php_sapi_name() !== 'cli' && (!isset($_GET['secret']) || $_GET['secret'] !== 'openshelf_cron_secret')) {
    http_response_code(403);
    die('Unauthorized');
}

define('BASE_URL', 'https://duopenshelf.top');

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/lib/Mailer.php';

$sentCount    = 0;
$skippedCount = 0;
$errorCount   = 0;

try {
    $db     = getDB();
    $mailer = new Mailer();

    // ────────────────────────────────────────────────────────────────
    // Find ALL distinct available books that have at least ONE
    // un-notified wishlist entry.
    // ────────────────────────────────────────────────────────────────
    $availableBooks = $db->query("
        SELECT DISTINCT w.book_id
        FROM   wishlist w
        JOIN   books b ON b.id = w.book_id
        WHERE  b.status   = 'available'
          AND  w.notified = 0
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach ($availableBooks as $bookId) {

        // ── Load book details ────────────────────────────────────────
        $bookStmt = $db->prepare("SELECT id, title, author, status FROM books WHERE id = ?");
        $bookStmt->execute([$bookId]);
        $book = $bookStmt->fetch(PDO::FETCH_ASSOC);

        if (!$book || $book['status'] !== 'available') {
            $skippedCount++;
            continue;
        }

        // ── Fetch the FIRST un-notified wishlist user (FIFO) ─────────
        $wishStmt = $db->prepare("
            SELECT w.id AS wishlist_id,
                   w.user_id,
                   w.created_at,
                   u.name  AS user_name,
                   u.email AS user_email
            FROM   wishlist w
            JOIN   users u ON u.id = w.user_id
            WHERE  w.book_id  = ?
              AND  w.notified = 0
            ORDER  BY w.created_at ASC
            LIMIT  1
        ");
        $wishStmt->execute([$bookId]);
        $wish = $wishStmt->fetch(PDO::FETCH_ASSOC);

        if (!$wish) {
            $skippedCount++;
            continue;
        }

        if (empty($wish['user_email'])) {
            error_log("⚠️ [Cron Wishlist] No email for user {$wish['user_id']} — skipping.");
            // Mark as notified anyway so the loop moves on next run
            $db->prepare("UPDATE wishlist SET notified = 1, updated_at = ? WHERE id = ?")
               ->execute([date('Y-m-d H:i:s'), $wish['wishlist_id']]);
            $skippedCount++;
            continue;
        }

        // ── Determine their queue position ───────────────────────────
        $posStmt = $db->prepare("
            SELECT COUNT(*) + 1 AS position
            FROM   wishlist
            WHERE  book_id   = ?
              AND  notified  = 0
              AND  created_at < ?
        ");
        $posStmt->execute([$bookId, $wish['created_at']]);
        $position = (int) ($posStmt->fetchColumn() ?: 1);

        // ── Send email ───────────────────────────────────────────────
        $emailData = [
            'subject'        => "📗 \"{$book['title']}\" is now available on OpenShelf!",
            'type'           => 'success',
            'user_name'      => $wish['user_name'],
            'book_title'     => $book['title'],
            'book_author'    => $book['author'],
            'book_id'        => $book['id'],
            'queue_position' => $position,
            'base_url'       => BASE_URL,
        ];

        $success = $mailer->sendTemplate(
            $wish['user_email'],
            $wish['user_name'],
            'wishlist_available',
            $emailData
        );

        if ($success) {
            // Mark this entry as notified
            $db->prepare("UPDATE wishlist SET notified = 1, updated_at = ? WHERE id = ?")
               ->execute([date('Y-m-d H:i:s'), $wish['wishlist_id']]);

            $msg = "Sent wishlist notification to {$wish['user_email']} for book \"{$book['title']}\" (book_id={$bookId})";
            error_log("ℹ️ [Cron Wishlist] " . $msg);
            if (php_sapi_name() === 'cli') {
                echo "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n";
            }
            $sentCount++;
        } else {
            error_log("❌ [Cron Wishlist] Failed to send to {$wish['user_email']} for book_id={$bookId}");
            $errorCount++;
        }
    }

    // ── Summary output ───────────────────────────────────────────────
    if (php_sapi_name() === 'cli') {
        echo "[" . date('Y-m-d H:i:s') . "] Done. Sent: {$sentCount}, Skipped: {$skippedCount}, Errors: {$errorCount}\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'sent'    => $sentCount,
            'skipped' => $skippedCount,
            'errors'  => $errorCount,
        ]);
    }

} catch (Exception $e) {
    error_log("❌ [Cron Wishlist] Fatal error: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
