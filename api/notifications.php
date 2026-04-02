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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$currentUserId = $_SESSION['user_id'];

/**
 * Load notifications for current user
 */
function loadUserNotifications($userId, $limit = 50, $includeRead = false) {
    $userFile = USERS_PATH . $userId . '.json';
    
    if (!file_exists($userFile)) {
        return [];
    }
    
    $userData = json_decode(file_get_contents($userFile), true);
    $userNotifications = $userData['notifications'] ?? [];
    
    $filteredNotifications = [];
    foreach ($userNotifications as $notification) {
        // Filter out read notifications if not including them
        if (!$includeRead && $notification['is_read']) {
            continue;
        }
        
        // Check if notification has expired
        if (isset($notification['expires_at']) && strtotime($notification['expires_at']) < time()) {
            continue;
        }
        
        $filteredNotifications[] = $notification;
    }
    
    // Already sorted, but ensure
    usort($filteredNotifications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // Apply limit
    return array_slice($filteredNotifications, 0, $limit);
}

/**
 * Mark notification as read
 */
function markAsRead($notificationId, $userId) {
    $userFile = USERS_PATH . $userId . '.json';
    
    if (!file_exists($userFile)) {
        return false;
    }
    
    $userData = json_decode(file_get_contents($userFile), true);
    $notifications = $userData['notifications'] ?? [];
    $updated = false;
    
    foreach ($notifications as &$notification) {
        if ($notification['id'] === $notificationId) {
            $notification['is_read'] = true;
            $notification['read_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        $userData['notifications'] = $notifications;
        return file_put_contents(
            $userFile,
            json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
    
    return false;
}

/**
 * Mark all notifications as read for user
 */
function markAllAsRead($userId) {
    $userFile = USERS_PATH . $userId . '.json';
    
    if (!file_exists($userFile)) {
        return false;
    }
    
    $userData = json_decode(file_get_contents($userFile), true);
    $notifications = $userData['notifications'] ?? [];
    $updated = false;
    
    foreach ($notifications as &$notification) {
        if (!$notification['is_read']) {
            $notification['is_read'] = true;
            $notification['read_at'] = date('Y-m-d H:i:s');
            $updated = true;
        }
    }
    
    if ($updated) {
        $userData['notifications'] = $notifications;
        return file_put_contents(
            $userFile,
            json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
    
    return false;
}

/**
 * Delete notification
 */
function deleteNotification($notificationId, $userId) {
    $userFile = USERS_PATH . $userId . '.json';
    
    if (!file_exists($userFile)) {
        return false;
    }
    
    $userData = json_decode(file_get_contents($userFile), true);
    $notifications = $userData['notifications'] ?? [];
    $filtered = array_filter($notifications, function($notification) use ($notificationId) {
        return $notification['id'] !== $notificationId;
    });
    
    if (count($filtered) !== count($notifications)) {
        $userData['notifications'] = array_values($filtered);
        return file_put_contents(
            $userFile,
            json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)
        );
    }
    
    return false;
}

/**
 * Get unread count
 */
function getUnreadCount($userId) {
    $notifications = loadUserNotifications($userId, 100, false);
    return count($notifications);
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