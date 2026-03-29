<?php
/**
 * OpenShelf Navigation Bar with Notifications
 */

// Get current user ID if logged in
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['user_name'] ?? 'Guest';

// Get user avatar
$userAvatar = 'default-avatar.jpg';
if ($currentUserId) {
    $userFile = dirname(__DIR__) . '/users/' . $currentUserId . '.json';
    if (file_exists($userFile)) {
        $userData = json_decode(file_get_contents($userFile), true);
        $userAvatar = $userData['personal_info']['profile_pic'] ?? 'default-avatar.jpg';
    }
}
?>

<!-- Navigation -->
<nav class="navbar">
    <div class="container">
        <a href="/" class="navbar-brand">
            <i class="fas fa-book-open"></i> OpenShelf
        </a>
        
        <button class="navbar-toggler" id="navbarToggler">
            <i class="fas fa-bars"></i>
        </button>
        
        <div class="navbar-menu" id="navbarMenu">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a href="/" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>">
                        <i class="fas fa-home"></i> Home
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/books/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/books/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i> Books
                    </a>
                </li>
                <li class="nav-item">
                    <a href="/feed/" class="nav-link <?php echo strpos($_SERVER['PHP_SELF'], '/feed/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-rss"></i> Feed
                    </a>
                </li>
                
                <!-- Notifications Bell (only for logged in users) -->
                <?php if ($currentUserId): ?>
                <li class="nav-item notification-item">
                    <a href="/notifications/" class="nav-link notification-bell" id="notificationBell">
                        <i class="fas fa-bell"></i>
                        <span class="notification-badge" id="notificationBadge" style="display: none;">0</span>
                    </a>
                    
                    <!-- Notification Dropdown -->
                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <button class="mark-all-read" onclick="markAllNotificationsRead()">
                                <i class="fas fa-check-double"></i> Mark all read
                            </button>
                        </div>
                        
                        <div class="notification-list" id="notificationList">
                            <!-- Notifications will be loaded via AJAX -->
                            <div class="notification-loading">
                                <i class="fas fa-spinner fa-spin"></i> Loading...
                            </div>
                        </div>
                        
                        <div class="notification-footer">
                            <a href="/notifications/" class="view-all">View All Notifications</a>
                        </div>
                    </div>
                </li>
                <?php endif; ?>
            </ul>
            
            <!-- Profile Dropdown -->
            <div class="profile-dropdown">
                <div class="profile-trigger" id="profileTrigger">
                    <img src="/uploads/profile/<?php echo $userAvatar; ?>" alt="Profile" class="profile-image">
                    <i class="fas fa-chevron-down"></i>
                </div>
                
                <div class="dropdown-menu" id="dropdownMenu">
                    <?php if ($currentUserId): ?>
                        <a href="/profile/" class="dropdown-item">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="/add-book/" class="dropdown-item">
                            <i class="fas fa-plus-circle"></i> Add Book
                        </a>
                        <a href="/requests/" class="dropdown-item">
                            <i class="fas fa-exchange-alt"></i> My Requests
                        </a>
                        <a href="/notifications/" class="dropdown-item">
                            <i class="fas fa-bell"></i> Notifications
                            <span class="dropdown-badge" id="dropdownNotificationBadge" style="display: none;">0</span>
                        </a>
                        <a href="/edit-profile/" class="dropdown-item">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="/logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    <?php else: ?>
                        <a href="/login/" class="dropdown-item">
                            <i class="fas fa-sign-in-alt"></i> Login
                        </a>
                        <a href="/register/" class="dropdown-item">
                            <i class="fas fa-user-plus"></i> Register
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Notification Styles -->
<style>
.notification-item {
    position: relative;
}

.notification-bell {
    position: relative;
    padding: 0.5rem 1rem !important;
}

.notification-badge {
    position: absolute;
    top: 0;
    right: 0;
    background: #f5365c;
    color: white;
    font-size: 0.7rem;
    font-weight: 600;
    min-width: 18px;
    height: 18px;
    border-radius: 9px;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0 4px;
    border: 2px solid white;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
}

.notification-dropdown {
    position: absolute;
    top: 100%;
    right: -50px;
    width: 380px;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
    margin-top: 10px;
    display: none;
    z-index: 1000;
    overflow: hidden;
}

.notification-dropdown.show {
    display: block;
    animation: slideDown 0.3s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.notification-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem 1.5rem;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.notification-header h3 {
    font-size: 1rem;
    font-weight: 600;
    margin: 0;
}

.mark-all-read {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    font-size: 0.8rem;
    padding: 0.25rem 0.75rem;
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.mark-all-read:hover {
    background: rgba(255, 255, 255, 0.3);
}

.notification-list {
    max-height: 400px;
    overflow-y: auto;
    scrollbar-width: thin;
}

.notification-list::-webkit-scrollbar {
    width: 5px;
}

.notification-list::-webkit-scrollbar-track {
    background: #f1f1f1;
}

.notification-list::-webkit-scrollbar-thumb {
    background: #667eea;
    border-radius: 5px;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 1rem;
    padding: 1rem 1.5rem;
    border-bottom: 1px solid #e9ecef;
    transition: all 0.3s ease;
    cursor: pointer;
}

.notification-item:hover {
    background: #f8f9fa;
}

.notification-item.unread {
    background: rgba(102, 126, 234, 0.05);
}

.notification-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    flex-shrink: 0;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #333;
    margin-bottom: 0.25rem;
    font-size: 0.95rem;
}

.notification-message {
    color: #666;
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
    line-height: 1.4;
}

.notification-time {
    color: #8898aa;
    font-size: 0.8rem;
    display: flex;
    align-items: center;
    gap: 0.25rem;
}

.notification-actions {
    display: flex;
    gap: 0.5rem;
    margin-top: 0.5rem;
}

.notification-action {
    background: none;
    border: none;
    color: #8898aa;
    font-size: 0.8rem;
    cursor: pointer;
    padding: 0.25rem 0.5rem;
    border-radius: 4px;
    transition: all 0.3s ease;
}

.notification-action:hover {
    background: #e9ecef;
    color: #667eea;
}

.notification-footer {
    padding: 1rem 1.5rem;
    text-align: center;
    border-top: 1px solid #e9ecef;
    background: #f8f9fa;
}

.view-all {
    color: #667eea;
    text-decoration: none;
    font-size: 0.9rem;
    font-weight: 600;
}

.view-all:hover {
    text-decoration: underline;
}

.notification-loading {
    text-align: center;
    padding: 2rem;
    color: #8898aa;
}

.notification-loading i {
    margin-right: 0.5rem;
}

.notification-empty {
    text-align: center;
    padding: 3rem 1.5rem;
    color: #8898aa;
}

.notification-empty i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.notification-empty p {
    margin-bottom: 1rem;
}

.notification-badge-mobile {
    display: none;
}

.dropdown-badge {
    background: #f5365c;
    color: white;
    font-size: 0.7rem;
    padding: 0.15rem 0.4rem;
    border-radius: 10px;
    margin-left: 0.5rem;
}

@media (max-width: 768px) {
    .notification-dropdown {
        position: fixed;
        top: 60px;
        left: 10px;
        right: 10px;
        width: auto;
        max-width: none;
    }
    
    .notification-badge-mobile {
        display: inline-block;
    }
}
</style>

<!-- Notification Script -->
<script>
// Notification system
let notificationCheckInterval;
let isNotificationDropdownOpen = false;

// Initialize notification system when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    <?php if ($currentUserId): ?>
    // Load initial notifications
    loadNotifications();
    
    // Start polling for new notifications every 30 seconds
    startNotificationPolling();
    
    // Notification bell click handler
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    
    if (notificationBell) {
        notificationBell.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (notificationDropdown.classList.contains('show')) {
                closeNotificationDropdown();
            } else {
                openNotificationDropdown();
            }
        });
    }
    
    // Close dropdown when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notification-item') && !e.target.closest('.notification-bell')) {
            closeNotificationDropdown();
        }
    });
    
    // Mark notification as read when clicked
    document.addEventListener('click', function(e) {
        const notificationItem = e.target.closest('.notification-item[data-id]');
        if (notificationItem && !e.target.closest('.notification-action')) {
            const notificationId = notificationItem.dataset.id;
            markNotificationAsRead(notificationId, true);
        }
    });
    <?php endif; ?>
});

/**
 * Start polling for new notifications
 */
function startNotificationPolling() {
    notificationCheckInterval = setInterval(checkNewNotifications, 30000); // 30 seconds
}

/**
 * Stop notification polling
 */
function stopNotificationPolling() {
    if (notificationCheckInterval) {
        clearInterval(notificationCheckInterval);
    }
}

/**
 * Check for new notifications
 */
async function checkNewNotifications() {
    try {
        const response = await fetch('/api/notifications.php?action=count');
        const data = await response.json();
        
        if (data.success) {
            updateNotificationBadge(data.unread_count);
        }
    } catch (error) {
        console.error('Error checking notifications:', error);
    }
}

/**
 * Load notifications into dropdown
 */
async function loadNotifications() {
    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;
    
    try {
        const response = await fetch('/api/notifications.php?action=list&limit=10');
        const data = await response.json();
        
        if (data.success) {
            updateNotificationBadge(data.unread_count);
            renderNotifications(data.notifications);
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        notificationList.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-exclamation-circle"></i>
                <p>Failed to load notifications</p>
                <button onclick="loadNotifications()" class="notification-action">
                    <i class="fas fa-redo"></i> Try Again
                </button>
            </div>
        `;
    }
}

/**
 * Render notifications in dropdown
 */
function renderNotifications(notifications) {
    const notificationList = document.getElementById('notificationList');
    if (!notificationList) return;
    
    if (notifications.length === 0) {
        notificationList.innerHTML = `
            <div class="notification-empty">
                <i class="fas fa-bell-slash"></i>
                <p>No notifications yet</p>
            </div>
        `;
        return;
    }
    
    let html = '';
    notifications.forEach(notification => {
        const unreadClass = notification.is_read ? '' : 'unread';
        html += `
            <div class="notification-item ${unreadClass}" data-id="${notification.id}">
                <div class="notification-icon" style="background: ${notification.color}">
                    <i class="fas ${notification.icon}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${escapeHtml(notification.title)}</div>
                    <div class="notification-message">${escapeHtml(notification.message)}</div>
                    <div class="notification-time">
                        <i class="far fa-clock"></i>
                        ${notification.time_ago}
                    </div>
                    <div class="notification-actions">
                        ${!notification.is_read ? `
                            <button class="notification-action" onclick="markNotificationAsRead('${notification.id}', false)">
                                <i class="fas fa-check"></i> Mark read
                            </button>
                        ` : ''}
                        <button class="notification-action" onclick="deleteNotification('${notification.id}')">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    </div>
                </div>
            </div>
        `;
    });
    
    notificationList.innerHTML = html;
}

/**
 * Open notification dropdown
 */
function openNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.add('show');
    isNotificationDropdownOpen = true;
    
    // Refresh notifications when opening
    loadNotifications();
}

/**
 * Close notification dropdown
 */
function closeNotificationDropdown() {
    const dropdown = document.getElementById('notificationDropdown');
    dropdown.classList.remove('show');
    isNotificationDropdownOpen = false;
}

/**
 * Mark notification as read
 */
async function markNotificationAsRead(notificationId, redirect = false) {
    try {
        const response = await fetch('/api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_read',
                notification_id: notificationId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update notification item
            const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (notificationItem) {
                notificationItem.classList.remove('unread');
            }
            
            // Update badge
            updateNotificationBadge(data.unread_count);
            
            // If redirect is true, follow the notification link
            if (redirect) {
                const notification = data.notifications?.find(n => n.id === notificationId);
                if (notification && notification.link) {
                    window.location.href = notification.link;
                }
            }
        }
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
}

/**
 * Mark all notifications as read
 */
async function markAllNotificationsRead() {
    try {
        const response = await fetch('/api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'mark_all_read'
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Update all notification items
            document.querySelectorAll('.notification-item').forEach(item => {
                item.classList.remove('unread');
            });
            
            // Update badge
            updateNotificationBadge(0);
            
            // Show success message
            showNotification('All notifications marked as read', 'success');
        }
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    }
}

/**
 * Delete notification
 */
async function deleteNotification(notificationId) {
    if (!confirm('Delete this notification?')) {
        return;
    }
    
    try {
        const response = await fetch('/api/notifications.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                action: 'delete',
                notification_id: notificationId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Remove notification from DOM
            const notificationItem = document.querySelector(`.notification-item[data-id="${notificationId}"]`);
            if (notificationItem) {
                notificationItem.remove();
            }
            
            // Update badge
            updateNotificationBadge(data.unread_count);
            
            // Check if list is empty
            const notificationList = document.getElementById('notificationList');
            if (notificationList && notificationList.children.length === 0) {
                notificationList.innerHTML = `
                    <div class="notification-empty">
                        <i class="fas fa-bell-slash"></i>
                        <p>No notifications yet</p>
                    </div>
                `;
            }
        }
    } catch (error) {
        console.error('Error deleting notification:', error);
    }
}

/**
 * Update notification badge
 */
function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    const dropdownBadge = document.getElementById('dropdownNotificationBadge');
    
    if (count > 0) {
        if (badge) {
            badge.style.display = 'flex';
            badge.textContent = count > 99 ? '99+' : count;
        }
        if (dropdownBadge) {
            dropdownBadge.style.display = 'inline';
            dropdownBadge.textContent = count > 99 ? '99+' : count;
        }
        
        // Update page title if this is the notifications page
        if (window.location.pathname.includes('/notifications/')) {
            document.title = `(${count}) Notifications - OpenShelf`;
        }
    } else {
        if (badge) badge.style.display = 'none';
        if (dropdownBadge) dropdownBadge.style.display = 'none';
        
        if (window.location.pathname.includes('/notifications/')) {
            document.title = 'Notifications - OpenShelf';
        }
    }
}

/**
 * Escape HTML to prevent XSS
 */
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

/**
 * Show notification message
 */
function showNotification(message, type = 'info') {
    // Check if notification container exists
    let container = document.getElementById('notificationContainer');
    if (!container) {
        container = document.createElement('div');
        container.id = 'notificationContainer';
        container.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
        `;
        document.body.appendChild(container);
    }
    
    const notification = document.createElement('div');
    notification.style.cssText = `
        background: ${type === 'success' ? '#2dce89' : type === 'error' ? '#f5365c' : '#667eea'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        margin-bottom: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        animation: slideIn 0.3s ease;
        cursor: pointer;
    `;
    
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;
    
    container.appendChild(notification);
    
    // Remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
            if (container.children.length === 0) {
                container.remove();
            }
        }, 300);
    }, 3000);
    
    // Click to dismiss
    notification.addEventListener('click', () => {
        notification.remove();
    });
}

// Clean up on page unload
window.addEventListener('beforeunload', function() {
    stopNotificationPolling();
});
</script>