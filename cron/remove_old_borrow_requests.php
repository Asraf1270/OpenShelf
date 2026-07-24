<?php
/**
 * OpenShelf Borrow Request Cleanup Cron Job
 * 
 * Enforces a limit of 15 borrow requests per user by deleting older entries.
 * Can be run via CLI cron job: php cron/remove_old_borrow_requests.php
 */

// Only allow execution via CLI or authorized request
if (php_sapi_name() !== 'cli' && (!isset($_GET['secret']) || $_GET['secret'] !== 'openshelf_cron_secret')) {
    http_response_code(403);
    die('Unauthorized');
}

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $db = getDB();
    
    // Find users (borrowers) with more than 15 borrow requests
    $stmt = $db->query("
        SELECT borrower_id, COUNT(*) as count 
        FROM borrow_requests 
        GROUP BY borrower_id 
        HAVING count > 15
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $deletedCount = 0;
    
    foreach ($users as $user) {
        $borrowerId = $user['borrower_id'];
        
        // Find the 15th request's request_date (0-indexed offset 14)
        $cutoffStmt = $db->prepare("
            SELECT request_date 
            FROM borrow_requests 
            WHERE borrower_id = ? 
            ORDER BY request_date DESC, id DESC 
            LIMIT 1 OFFSET 14
        ");
        $cutoffStmt->execute([$borrowerId]);
        $cutoffDate = $cutoffStmt->fetchColumn();
        
        if ($cutoffDate) {
            // Delete borrow requests older than the 15th request
            // We use nested subquery to bypass MySQL's "cannot update/delete target table in FROM clause"
            $deleteStmt = $db->prepare("
                DELETE FROM borrow_requests 
                WHERE borrower_id = ? 
                AND (
                    request_date < ? 
                    OR (request_date = ? AND id NOT IN (
                        SELECT id FROM (
                            SELECT id FROM borrow_requests 
                            WHERE borrower_id = ? 
                            ORDER BY request_date DESC, id DESC 
                            LIMIT 15
                        ) tmp
                    ))
                )
            ");
            $deleteStmt->execute([$borrowerId, $cutoffDate, $cutoffDate, $borrowerId]);
            $deletedCount += $deleteStmt->rowCount();
        }
    }
    
    if (php_sapi_name() === 'cli') {
        echo "[" . date('Y-m-d H:i:s') . "] Purged $deletedCount old borrow requests.\n";
    } else {
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'purged' => $deletedCount]);
    }
    
} catch (Exception $e) {
    error_log("❌ Cron Job Error: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
