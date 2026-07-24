<?php
/**
 * OpenShelf Notifications Page
 * Modern UI - View all user notifications
 */

session_start();

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/notifications/';
    header('Location: /login/');
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'User';

/**
 * Load user's notifications from database
 */
function loadUserNotifications($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            SELECT * FROM `notifications` 
            WHERE `user_id` = ? 
            AND (`expires_at` IS NULL OR `expires_at` > NOW())
            ORDER BY `created_at` DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        error_log("Error loading notifications: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user's notifications
 */
function getUserNotifications($userId, $includeRead = true) {
    $userNotifications = loadUserNotifications($userId);
    
    if (!$includeRead) {
        $userNotifications = array_filter($userNotifications, fn($n) => empty($n['is_read']));
    }
    
    return $userNotifications;
}

/**
 * Mark notification as read
 */
function markAsRead($notificationId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE `notifications` 
            SET `is_read` = 1, `read_at` = ? 
            WHERE `id` = ? AND `user_id` = ?
        ");
        return $stmt->execute([date('Y-m-d H:i:s'), $notificationId, $userId]);
    } catch (Exception $e) {
        error_log("Error marking notification read: " . $e->getMessage());
        return false;
    }
}

/**
 * Mark all notifications as read
 */
function markAllAsRead($userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            UPDATE `notifications` 
            SET `is_read` = 1, `read_at` = ? 
            WHERE `user_id` = ? AND `is_read` = 0
        ");
        return $stmt->execute([date('Y-m-d H:i:s'), $userId]);
    } catch (Exception $e) {
        error_log("Error marking all notifications read: " . $e->getMessage());
        return false;
    }
}

/**
 * Delete notification
 */
function deleteNotification($notificationId, $userId) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            DELETE FROM `notifications` 
            WHERE `id` = ? AND `user_id` = ?
        ");
        return $stmt->execute([$notificationId, $userId]);
    } catch (Exception $e) {
        error_log("Error deleting notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Format time ago
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $time);
}

/**
 * Get notification icon
 */
function getNotificationIcon($type) {
    $icons = [
        'borrow_request' => 'fa-hand-holding-heart',
        'request_approved' => 'fa-check-circle',
        'request_rejected' => 'fa-times-circle',
        'return_reminder' => 'fa-clock',
        'book_due_soon' => 'fa-exclamation-triangle',
        'book_overdue' => 'fa-exclamation-circle',
        'book_returned' => 'fa-undo-alt',
        'new_review' => 'fa-star',
        'new_comment' => 'fa-comment',
        'account_approved' => 'fa-user-check',
        'account_rejected' => 'fa-user-times',
        'announcement' => 'fa-bullhorn'
    ];
    return $icons[$type] ?? 'fa-bell';
}

/**
 * Get notification color
 */
function getNotificationColor($type) {
    $colors = [
        'borrow_request' => '#2C3E50',
        'request_approved' => '#4C9F8A',
        'request_rejected' => '#ef4444',
        'return_reminder' => '#f59e0b',
        'book_due_soon' => '#f59e0b',
        'book_overdue' => '#ef4444',
        'book_returned' => '#4C9F8A',
        'new_review' => '#f59e0b',
        'new_comment' => '#2C3E50',
        'account_approved' => '#4C9F8A',
        'account_rejected' => '#ef4444',
        'announcement' => '#2C3E50'
    ];
    return $colors[$type] ?? '#5A6C7D';
}

// Load notifications
$notifications = getUserNotifications($currentUserId, true);
$unreadCount = count(array_filter($notifications, fn($n) => empty($n['is_read'])));

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'mark_all_read') {
        if (markAllAsRead($currentUserId)) {
            $message = 'All notifications marked as read';
            $notifications = getUserNotifications($currentUserId, true);
            $unreadCount = 0;
        } else {
            $error = 'Failed to mark notifications as read';
        }
    } elseif ($action === 'delete') {
        $notificationId = $_POST['notification_id'] ?? '';
        if (deleteNotification($notificationId, $currentUserId)) {
            $message = 'Notification deleted';
            $notifications = getUserNotifications($currentUserId, true);
            $unreadCount = count(array_filter($notifications, fn($n) => empty($n['is_read'])));
        } else {
            $error = 'Failed to delete notification';
        }
    } elseif ($action === 'mark_read') {
        $notificationId = $_POST['notification_id'] ?? '';
        if (markAsRead($notificationId, $currentUserId)) {
            $notifications = getUserNotifications($currentUserId, true);
            $unreadCount = count(array_filter($notifications, fn($n) => empty($n['is_read'])));
        }
    }
}

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$total = count($notifications);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedNotifications = array_slice($notifications, $offset, $perPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Notifications - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* ========================================
           MODERN NOTIFICATIONS PAGE
        ======================================== */
        
        :root {
            --primary: #2C3E50;
            --secondary: #4C9F8A;
            --success: #4C9F8A;
            --warning: #f59e0b;
            --danger: #ef4444;
            --bg: #F8F9FA;
            --surface: #ffffff;
            --border: #E2E8F0;
            --text-main: #0F172A;
            --text-muted: #5A6C7D;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.05);
            --radius-lg: 16px;
            --radius-xl: 20px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        :root[data-theme="dark"] {
            --bg: #0F172A;
            --surface: #1E293B;
            --border: #334155;
            --text-main: #F8F9FA;
            --text-muted: #94A3B8;
            --primary: #4C9F8A;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.2);
            --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.3);
        }

        body {
            background: var(--bg);
            color: var(--text-main);
            transition: background 0.3s ease;
        }

        .notifications-page {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        /* Page Header */
        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-header h1 {
            font-size: 2.5rem;
            font-weight: 850;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
        }

        .page-header p {
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--surface);
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-sm);
        }

        .unread-badge {
            background: var(--primary);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .mark-all-btn {
            background: none;
            border: none;
            color: var(--primary);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            transition: var(--transition);
        }

        .mark-all-btn:hover {
            background: var(--primary);
            color: white;
        }

        /* Notification List */
        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .notification-item {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 1.25rem;
            border: 1px solid var(--border);
            transition: var(--transition);
            cursor: pointer;
            position: relative;
        }

        .notification-item:hover {
            transform: translateX(4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-light);
        }

        .notification-item.unread {
            background: linear-gradient(135deg, rgba(76, 159, 138, 0.08), rgba(76, 159, 138, 0.02));
            border-left: 4px solid var(--secondary);
        }

        .notification-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 1.2rem;
        }

        .notification-content {
            flex: 1;
        }

        .notification-title {
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 0.35rem;
            font-size: 1.05rem;
        }

        .notification-message {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
            line-height: 1.5;
        }

        .notification-time {
            font-size: 0.7rem;
            color: var(--gray-400);
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .notification-actions {
            display: flex;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .action-btn {
            background: none;
            border: none;
            font-size: 0.7rem;
            color: var(--gray-500);
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 0.5rem;
            transition: var(--transition);
        }

        .action-btn:hover {
            background: var(--danger);
            color: white;
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--surface);
            border-radius: var(--radius-xl);
            border: 1px solid var(--border);
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--gray-500);
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .page-btn {
            padding: 0.5rem 0.75rem;
            min-width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 0.75rem;
            color: var(--text-muted);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition);
        }

        .page-btn:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .page-btn.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
        }

        .page-btn.disabled {
            opacity: 0.5;
            pointer-events: none;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-xl);
            max-width: 400px;
            width: 90%;
            padding: 1.5rem;
            text-align: center;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }

        /* Responsive */
        @media (max-width: 640px) {
            .stats-bar {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .notification-item {
                padding: 0.875rem;
            }
            
            .notification-icon {
                width: 36px;
                height: 36px;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    
    <main>
        <div class="notifications-page">
            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-bell" style="color: var(--primary);"></i> Notifications</h1>
                <p>Stay updated with your latest activities</p>
            </div>
            
            <!-- Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success" style="background: rgba(16,185,129,0.1); color: var(--success); padding: 1rem; border-radius: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" style="background: rgba(239,68,68,0.1); color: var(--danger); padding: 1rem; border-radius: 1rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <!-- Stats Bar -->
            <div class="stats-bar">
                <div>
                    <span class="unread-badge"><?php echo $unreadCount; ?> unread</span>
                    <span style="margin-left: 0.5rem; color: var(--gray-500);"><?php echo $total; ?> total</span>
                </div>
                <?php if ($unreadCount > 0): ?>
                    <form method="POST" onsubmit="return confirm('Mark all notifications as read?')">
                        <input type="hidden" name="action" value="mark_all_read">
                        <button type="submit" class="mark-all-btn">
                            <i class="fas fa-check-double"></i> Mark all as read
                        </button>
                    </form>
                <?php endif; ?>
            </div>
            
            <!-- Notification List -->
            <?php if (empty($paginatedNotifications)): ?>
                <div class="empty-state">
                    <i class="fas fa-bell-slash"></i>
                    <h3>No Notifications</h3>
                    <p>You're all caught up! New notifications will appear here.</p>
                </div>
            <?php else: ?>
                <div class="notification-list">
                    <?php foreach ($paginatedNotifications as $notification): 
                        $icon = getNotificationIcon($notification['type']);
                        $color = getNotificationColor($notification['type']);
                        $isUnread = empty($notification['is_read']);
                    ?>
                        <div class="notification-item <?php echo $isUnread ? 'unread' : ''; ?>" 
                             data-id="<?php echo $notification['id']; ?>"
                             data-link="<?php echo $notification['link'] ?? '#'; ?>">
                            <div class="notification-icon" style="background: <?php echo $color; ?>20; color: <?php echo $color; ?>;">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            <div class="notification-content">
                                <div class="notification-title"><?php echo htmlspecialchars($notification['title']); ?></div>
                                <div class="notification-message"><?php echo htmlspecialchars($notification['message']); ?></div>
                                <div class="notification-time">
                                    <i class="far fa-clock"></i> <?php echo timeAgo($notification['created_at']); ?>
                                </div>
                                <div class="notification-actions">
                                    <?php if ($isUnread): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="mark_read">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" class="action-btn">
                                                <i class="fas fa-check"></i> Mark as read
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this notification?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                        <button type="submit" class="action-btn">
                                            <i class="fas fa-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <a href="?page=<?php echo max(1, $page - 1); ?>" class="page-btn <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        
                        <?php for ($i = 1; $i <= min(5, $totalPages); $i++): ?>
                            <?php if ($i >= $page - 2 && $i <= $page + 2): ?>
                                <a href="?page=<?php echo $i; ?>" class="page-btn <?php echo $i === $page ? 'active' : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($totalPages > 5 && $page < $totalPages - 2): ?>
                            <span class="page-btn disabled">...</span>
                            <a href="?page=<?php echo $totalPages; ?>" class="page-btn"><?php echo $totalPages; ?></a>
                        <?php endif; ?>
                        
                        <a href="?page=<?php echo min($totalPages, $page + 1); ?>" class="page-btn <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>
    
    <script>
        // Make notification items clickable
        document.querySelectorAll('.notification-item').forEach(item => {
            const link = item.dataset.link;
            if (link && link !== '#') {
                item.addEventListener('click', function(e) {
                    // Don't trigger if clicking on action buttons
                    if (e.target.closest('.action-btn') || e.target.closest('form')) {
                        return;
                    }
                    
                    // Mark as read via AJAX
                    const notificationId = this.dataset.id;
                    fetch(window.location.href, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: new URLSearchParams({
                            action: 'mark_read',
                            notification_id: notificationId
                        })
                    }).then(() => {
                        window.location.href = link;
                    }).catch(() => {
                        window.location.href = link;
                    });
                });
            }
        });
    </script>
    
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>