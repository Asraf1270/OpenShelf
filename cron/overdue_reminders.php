<?php
/**
 * OpenShelf Overdue Book Reminders Cron Job
 * 
 * Checks for overdue borrow requests and sends email notifications to borrowers.
 * Sends notifications at day 1, 3, 7, 14, 21, and 30 overdue.
 * Can be run via CLI cron job: php cron/overdue_reminders.php
 */

// Only allow execution via CLI or authorized request
if (php_sapi_name() !== 'cli' && (!isset($_GET['secret']) || $_GET['secret'] !== 'openshelf_cron_secret')) {
    http_response_code(403);
    die('Unauthorized');
}

require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/lib/Mailer.php';

try {
    $db = getDB();
    $mailer = new Mailer();
    
    // Query to find borrow requests that are overdue by exactly 1, 3, 7, 14, 21, or 30 days
    $sql = "
        SELECT 
            br.id as request_id,
            br.book_title,
            br.expected_return_date as due_date,
            br.borrower_email,
            br.borrower_name,
            br.borrower_id,
            br.owner_name,
            br.history,
            u_owner.phone as owner_phone,
            DATEDIFF(CURRENT_DATE(), br.expected_return_date) as overdue_days
        FROM borrow_requests br
        LEFT JOIN users u_owner ON br.owner_id = u_owner.id
        WHERE br.status IN ('approved', 'borrowed')
          AND br.returned_at IS NULL
          AND br.expected_return_date IS NOT NULL
          AND DATEDIFF(CURRENT_DATE(), br.expected_return_date) IN (1, 3, 7, 14, 21, 30)
    ";
    
    $stmt = $db->query($sql);
    $overdueRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $sentCount = 0;
    $skippedCount = 0;
    
    foreach ($overdueRequests as $row) {
        $requestId = $row['request_id'];
        $overdueDays = (int)$row['overdue_days'];
        $borrowerEmail = $row['borrower_email'];
        $borrowerName = $row['borrower_name'] ?? 'Reader';
        
        // Decode history
        $history = json_decode($row['history'] ?? '[]', true);
        if (!is_array($history)) {
            $history = [];
        }
        
        // Check if an email was already sent for this specific milestone
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
        
        // If email is empty, we log it and skip
        if (empty($borrowerEmail)) {
            error_log("⚠️ Borrower email is empty for request: {$requestId}");
            continue;
        }
        
        // Send email
        $emailData = [
            'subject' => "Urgent: Book \"{$row['book_title']}\" is Overdue",
            'type' => 'danger',
            'borrower_name' => $borrowerName,
            'book_title' => $row['book_title'],
            'due_date' => $row['due_date'],
            'overdue_days' => $overdueDays,
            'owner_name' => $row['owner_name'] ?? 'Owner',
            'owner_phone' => $row['owner_phone'] ?? '',
            'base_url' => 'https://duopenshelf.top'
        ];
        
        $success = $mailer->sendTemplate(
            $borrowerEmail,
            $borrowerName,
            'overdue',
            $emailData
        );
        
        if ($success) {
            // Append event to history
            $history[] = [
                'action' => 'overdue_email_sent',
                'timestamp' => date('Y-m-d H:i:s'),
                'days' => $overdueDays
            ];
            
            // Update borrow request history in DB
            $updateStmt = $db->prepare("
                UPDATE borrow_requests 
                SET history = ?, updated_at = ? 
                WHERE id = ?
            ");
            $updateStmt->execute([
                json_encode($history),
                date('Y-m-d H:i:s'),
                $requestId
            ]);
            
            // Log success
            $logMsg = "Sent overdue email (Day {$overdueDays}) to {$borrowerEmail} for request {$requestId}";
            error_log("ℹ️ [Cron Overdue] " . $logMsg);
            if (php_sapi_name() === 'cli') {
                echo "[" . date('Y-m-d H:i:s') . "] {$logMsg}\n";
            }
            
            $sentCount++;
        } else {
            error_log("❌ Failed to send overdue email to {$borrowerEmail} for request {$requestId}");
        }
    }
    
    if (php_sapi_name() === 'cli') {
        echo "[" . date('Y-m-d H:i:s') . "] Finished overdue checks. Sent: {$sentCount}, Skipped: {$skippedCount}.\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'sent' => $sentCount, 'skipped' => $skippedCount]);
    }
    
} catch (Exception $e) {
    error_log("❌ Cron Job Error (Overdue): " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
