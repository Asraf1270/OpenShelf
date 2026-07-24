<?php
/**
 * OpenShelf Notification Cleanup Cron Job
 * 
 * Enforces a limit of 25 notifications per user by deleting older entries.
 * Can be run via CLI cron job: php cron/remove_notifications.php
 */

// Only allow execution via CLI or authorized request
if (php_sapi_name() !== 'cli' && (!isset($_GET['secret']) || $_GET['secret'] !== 'openshelf_cron_secret')) {
    http_response_code(403);
    die('Unauthorized');
}

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $db = getDB();
    
    // Find users with more than 25 notifications
    $stmt = $db->query("
        SELECT user_id, COUNT(*) as count 
        FROM notifications 
        GROUP BY user_id 
        HAVING count > 25
    ");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $deletedCount = 0;
    
    foreach ($users as $user) {
        $userId = $user['user_id'];
        
        // Find the 25th notification's timestamp (0-indexed offset 24)
        $cutoffStmt = $db->prepare("
            SELECT created_at 
            FROM notifications 
            WHERE user_id = ? 
            ORDER BY created_at DESC, id DESC 
            LIMIT 1 OFFSET 24
        ");
        $cutoffStmt->execute([$userId]);
        $cutoffDate = $cutoffStmt->fetchColumn();
        
        if ($cutoffDate) {
            // Delete notifications older than the 25th notification
            // We use nested subquery to bypass MySQL's "cannot update/delete target table in FROM clause"
            $deleteStmt = $db->prepare("
                DELETE FROM notifications 
                WHERE user_id = ? 
                AND (
                    created_at < ? 
                    OR (created_at = ? AND id NOT IN (
                        SELECT id FROM (
                            SELECT id FROM notifications 
                            WHERE user_id = ? 
                            ORDER BY created_at DESC, id DESC 
                            LIMIT 25
                        ) tmp
                    ))
                )
            ");
            $deleteStmt->execute([$userId, $cutoffDate, $cutoffDate, $userId]);
            $deletedCount += $deleteStmt->rowCount();
        }
    }
    
    if (php_sapi_name() === 'cli') {
        echo "[" . date('Y-m-d H:i:s') . "] Purged $deletedCount old notifications.\n";
    } else {
        echo json_encode(['success' => true, 'purged' => $deletedCount]);
    }
    
} catch (Exception $e) {
    error_log("❌ Cron Job Error: " . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        echo "Error: " . $e->getMessage() . "\n";
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}
