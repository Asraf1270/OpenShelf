<?php
/**
 * OpenShelf Notifications Page
 * Modern UI - View all user notifications
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('BASE_URL', 'https://openshelf.free.nf');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/notifications/';
    header('Location: /login/');
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'User';

/**
 * Load user's notifications from their file
 */
function loadUserNotifications($userId) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) {
        return [];
    }
    $userData = json_decode(file_get_contents($userFile), true);
    return $userData['notifications'] ?? [];
}

/**
 * Get user's notifications
 */
function getUserNotifications($userId, $includeRead = true) {
    $userNotifications = loadUserNotifications($userId);
    
    if (!$includeRead) {
        $userNotifications = array_filter($userNotifications, fn($n) => empty($n['is_read']));
    }
    
    // Already sorted in migration, but ensure
    usort($userNotifications, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));
    return $userNotifications;
}

/**
 * Mark notification as read
 */
function markAsRead($notificationId, $userId) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) return false;
    
    $userData = json_decode(file_get_contents($userFile), true);
    $notifications = $userData['notifications'] ?? [];
    $updated = false;
    
    foreach ($notifications as &$n) {
        if ($n['id'] === $notificationId) {
            $n['is_read'] = true;
            $n['read_at'] = date('Y-m-d H:i:s');
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        $userData['notifications'] = $notifications;
        return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    return false;
}

/**
 * Mark all notifications as read
 */
function markAllAsRead($userId) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) return false;
    
    $userData = json_decode(file_get_contents($userFile), true);
    $notifications = $userData['notifications'] ?? [];
    $updated = false;
    
    foreach ($notifications as &$n) {
        if (empty($n['is_read'])) {
            $n['is_read'] = true;
            $n['read_at'] = date('Y-m-d H:i:s');
            $updated = true;
        }
    }
    
    if ($updated) {
        $userData['notifications'] = $notifications;
        return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    return false;
}

/**
 * Delete notification
 */
function deleteNotification($notificationId, $userId) {
    $notificationsFile = DATA_PATH . 'notifications.json';
    if (!file_exists($notificationsFile)) return false;
    
    $notifications = json_decode(file_get_contents($notificationsFile), true);
    $filtered = array_filter($notifications, function($n) use ($notificationId, $userId) {
        return !($n['id'] === $notificationId && $n['user_id'] === $userId);
    });
    
    if (count($filtered) !== count($notifications)) {
        return file_put_contents($notificationsFile, json_encode(array_values($filtered), JSON_PRETTY_PRINT));
    }
    return false;
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
        'borrow_request' => '#6366f1',
        'request_approved' => '#10b981',
        'request_rejected' => '#ef4444',
        'return_reminder' => '#f59e0b',
        'book_due_soon' => '#f59e0b',
        'book_overdue' => '#ef4444',
        'book_returned' => '#10b981',
        'new_review' => '#f59e0b',
        'new_comment' => '#6366f1',
        'account_approved' => '#10b981',
        'account_rejected' => '#ef4444',
        'announcement' => '#6366f1'
    ];
    return $colors[$type] ?? '#64748b';
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
            --primary: #6366f1;
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --radius-lg: 16px;
            --radius-xl: 20px;
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
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
            font-size: 2rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--gray-900), var(--primary));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            color: var(--gray-600);
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 1rem 1.5rem;
            border-radius: var(--radius-lg);
            margin-bottom: 1.5rem;
            border: 1px solid var(--gray-200);
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
            background: var(--gray-100);
        }

        /* Notification List */
        .notification-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .notification-item {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1rem 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            border: 1px solid var(--gray-200);
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
            background: linear-gradient(135deg, rgba(99, 102, 241, 0.03), rgba(99, 102, 241, 0.01));
            border-left: 3px solid var(--primary);
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
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 0.25rem;
        }

        .notification-message {
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
            line-height: 1.4;
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
            background: var(--gray-100);
            color: var(--danger);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: var(--radius-xl);
            border: 1px solid var(--gray-200);
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
            min-width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 0.5rem;
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.85rem;
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