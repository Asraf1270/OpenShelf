<?php
/**
 * OpenShelf Notifications API
 * Handles AJAX requests for notifications
 */

session_start();
header('Content-Type: application/json');

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('USERS_PATH', dirname(__DIR__) . '/users/');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$currentUserId = $_SESSION['user_id'];

/**
 * Load notifications for current user from database
 */
function loadUserNotifications($userId, $limit = 50, $includeRead = false) {
    try {
        $db = getDB();
        $sql = "SELECT * FROM `notifications` WHERE `user_id` = :user_id";
        
        if (!$includeRead) {
            $sql .= " AND `is_read` = 0";
        }
        
        // Filter out expired notifications
        $sql .= " AND (`expires_at` IS NULL OR `expires_at` > NOW())";
        
        $sql .= " ORDER BY `created_at` DESC LIMIT " . intval($limit);
        
        $stmt = $db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error loading notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Mark notification as read in database
 */
function markAsRead($notificationId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE `notifications` 
            SET `is_read` = 1, `read_at` = :read_at 
            WHERE `id` = :id AND `user_id` = :user_id
        ");
        return $stmt->execute([
            ':read_at' => date('Y-m-d H:i:s'),
            ':id' => $notificationId,
            ':user_id' => $userId
        ]);
    } catch (Exception $e) {
        error_log("Error marking notification as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read for user in database
 */
function markAllAsRead($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE `notifications` 
            SET `is_read` = 1, `read_at` = :read_at 
            WHERE `user_id` = :user_id AND `is_read` = 0
        ");
        return $stmt->execute([
            ':read_at' => date('Y-m-d H:i:s'),
            ':user_id' => $userId
        ]);
    } catch (Exception $e) {
        error_log("Error marking all notifications as read: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete notification from database
 */
function deleteNotification($notificationId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("DELETE FROM `notifications` WHERE `id` = :id AND `user_id` = :user_id");
        return $stmt->execute([
            ':id' => $notificationId,
            ':user_id' => $userId
        ]);
    } catch (Exception $e) {
        error_log("Error deleting notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Get unread count from database
 */
function getUnreadCount($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT COUNT(*) 
            FROM `notifications` 
            WHERE `user_id` = :user_id 
            AND `is_read` = 0 
            AND (`expires_at` IS NULL OR `expires_at` > NOW())
        ");
        $stmt->execute([':user_id' => $userId]);
        return (int)$stmt->fetchColumn();
    } catch (Exception $e) {
        error_log("Error getting unread count: " . $e->getMessage());
        return 0;
    }
}

// Handle request method
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Get notifications
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'list') {
        $limit = intval($_GET['limit'] ?? 20);
        $includeRead = isset($_GET['include_read']) && $_GET['include_read'] === 'true';
        
        $notifications = loadUserNotifications($currentUserId, $limit, $includeRead);
        $unreadCount = getUnreadCount($currentUserId);
        
        // Format notifications for display
        foreach ($notifications as &$notification) {
            $notification['time_ago'] = formatTimeAgo($notification['created_at']);
            $notification['icon'] = getNotificationIcon($notification['type']);
            $notification['color'] = getNotificationColor($notification['type']);
        }
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unreadCount,
            'total' => count($notifications)
        ]);
        
    } elseif ($action === 'count') {
        $unreadCount = getUnreadCount($currentUserId);
        
        echo json_encode([
            'success' => true,
            'unread_count' => $unreadCount
        ]);
    }
    
} elseif ($method === 'POST') {
    // Handle POST actions
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? $_POST['action'] ?? '';
    
    if ($action === 'mark_read') {
        $notificationId = $input['notification_id'] ?? $_POST['notification_id'] ?? '';
        
        if (empty($notificationId)) {
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
            exit;
        }
        
        if (markAsRead($notificationId, $currentUserId)) {
            $unreadCount = getUnreadCount($currentUserId);
            echo json_encode([
                'success' => true,
                'message' => 'Notification marked as read',
                'unread_count' => $unreadCount
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
        }
        
    } elseif ($action === 'mark_all_read') {
        if (markAllAsRead($currentUserId)) {
            echo json_encode([
                'success' => true,
                'message' => 'All notifications marked as read',
                'unread_count' => 0
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to mark notifications as read']);
        }
        
    } elseif ($action === 'delete') {
        $notificationId = $input['notification_id'] ?? $_POST['notification_id'] ?? '';
        
        if (empty($notificationId)) {
            echo json_encode(['success' => false, 'message' => 'Notification ID required']);
            exit;
        }
        
        if (deleteNotification($notificationId, $currentUserId)) {
            $unreadCount = getUnreadCount($currentUserId);
            echo json_encode([
                'success' => true,
                'message' => 'Notification deleted',
                'unread_count' => $unreadCount
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
        }
    }
}

/**
 * Format time ago
 */
function formatTimeAgo($timestamp) {
    $time = strtotime($timestamp);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

/**
 * Get notification icon based on type
 */
function getNotificationIcon($type) {
    switch ($type) {
        case 'borrow_request':
            return 'fa-hand-holding-heart';
        case 'request_approved':
            return 'fa-check-circle';
        case 'request_rejected':
            return 'fa-times-circle';
        case 'return_reminder':
            return 'fa-clock';
        case 'book_due_soon':
            return 'fa-exclamation-triangle';
        case 'book_overdue':
            return 'fa-exclamation-circle';
        case 'book_returned':
            return 'fa-undo-alt';
        case 'new_review':
            return 'fa-star';
        case 'new_comment':
            return 'fa-comment';
        default:
            return 'fa-bell';
    }
}

/**
 * Get notification color based on type
 */
function getNotificationColor($type) {
    switch ($type) {
        case 'borrow_request':
            return '#667eea'; // blue
        case 'request_approved':
            return '#2dce89'; // green
        case 'request_rejected':
            return '#f5365c'; // red
        case 'return_reminder':
            return '#ffc107'; // yellow
        case 'book_due_soon':
            return '#fb6340'; // orange
        case 'book_overdue':
            return '#f5365c'; // red
        case 'book_returned':
            return '#2dce89'; // green
        case 'new_review':
            return '#ffc107'; // yellow
        case 'new_comment':
            return '#667eea'; // blue
        default:
            return '#8898aa'; // gray
    }
}