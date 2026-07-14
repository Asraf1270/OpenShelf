<?php
/**
 * OpenShelf Header Component for Blade
 * Handles authentication, notifications, and navigation
 */
?>
<header class="app-header">
    <div class="header-container">
        <!-- Logo -->
        <div class="header-logo">
            <a href="/" class="logo-link">
                <img src="{{ asset('assets/images/logo.svg') }}" alt="OpenShelf" class="logo-image">
            </a>
        </div>

        <!-- Search Bar (Desktop) -->
        <div class="header-search-desktop">
            <form action="/books" method="GET" class="search-form">
                <div class="search-input-group">
                    <i class="fas fa-search"></i>
                    <input 
                        type="text" 
                        name="q" 
                        placeholder="Search books, authors, categories..."
                        class="search-input"
                        value="{{ request('q', '') }}"
                    >
                </div>
            </form>
        </div>

        <!-- Right Side: Auth & Notifications -->
        <div class="header-right">
            <!-- Notifications -->
            @if(session('user_id'))
            <div class="notification-bell">
                <button class="bell-button" id="notificationBell" aria-label="Notifications">
                    <i class="fas fa-bell"></i>
                    @if(($notificationCount ?? 0) > 0)
                        <span class="notification-badge" id="headerNotificationBadge">{{ min($notificationCount, 99) }}</span>
                    @else
                        <span class="notification-badge" id="headerNotificationBadge" style="display: none;"></span>
                    @endif
                </button>
                
                <!-- Notification Dropdown -->
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-header">
                        <h3>Notifications</h3>
                        <button class="close-btn" id="closeNotifications" aria-label="Close">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="notification-list" id="notificationList">
                        <div class="notification-loading" style="padding: 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem;">
                            Loading...
                        </div>
                    </div>
                    <div class="notification-footer" style="padding: 0.75rem 1rem; border-top: 1px solid rgba(0,0,0,0.05); text-align: center;">
                        <a href="{{ route('notifications.index') }}" style="font-size: 0.85rem; font-weight: 600; color: #4C9F8A; text-decoration: none;">View all notifications</a>
                    </div>
                </div>
            </div>
            @endif

            <!-- Auth Menu -->
            @if($headerUser)
                <div class="user-menu">
                    <button class="user-button" id="userMenuBtn" aria-label="User menu">
                        <img
                            src="{{ $headerUser->profile_image_url }}"
                            alt="{{ $headerUser->name }}"
                            class="user-avatar"
                        >
                    </button>

                    <!-- User Dropdown Menu -->
                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-info">
                            <img
                                src="{{ $headerUser->profile_image_url }}"
                                alt="{{ $headerUser->name }}"
                                class="user-avatar-large"
                            >
                            <div>
                                <p class="user-name">{{ $headerUser->name }}</p>
                                <p class="user-email">{{ $headerUser->email }}</p>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>

                        <a href="/profile" class="dropdown-link">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="/settings" class="dropdown-link">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <a href="/my-borrowed" class="dropdown-link">
                            <i class="fas fa-book"></i> My Books
                        </a>

                        @if($headerUser->role === 'admin')
                            <div class="dropdown-divider"></div>
                            <a href="/admin" class="dropdown-link">
                                <i class="fas fa-shield"></i> Admin Panel
                            </a>
                        @endif

                        <div class="dropdown-divider"></div>

                        <form action="/logout" method="POST" class="logout-form">
                            @csrf
                            <button type="submit" class="dropdown-link logout-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="auth-buttons">
                    <a href="/login" class="btn btn-ghost">Login</a>
                    <a href="/register" class="btn btn-primary">Sign Up</a>
                </div>
            @endif

            <!-- Theme Toggle -->
            <button class="theme-toggle" id="themeToggle" aria-label="Toggle theme">
                <i class="fas fa-moon"></i>
            </button>

            <!-- Mobile Menu Button -->
            <button class="mobile-menu-btn" id="mobileMenuBtn" aria-label="Menu">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

    <!-- Mobile Search Bar -->
    <div class="header-search-mobile">
        <form action="/books" method="GET" class="search-form">
            <div class="search-input-group">
                <i class="fas fa-search"></i>
                <input 
                    type="text" 
                    name="q" 
                    placeholder="Search books..."
                    class="search-input"
                    value="{{ request('q', '') }}"
                >
            </div>
        </form>
    </div>
</header>

<style>
    .app-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: var(--header-bg, #ffffff);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        transition: background 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    [data-theme="dark"] .app-header {
        background: #1e293b;
        border-bottom-color: rgba(255, 255, 255, 0.05);
    }

    .header-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0.75rem 1.5rem;
        display: flex;
        align-items: center;
        gap: 1rem;
        justify-content: space-between;
    }

    .header-logo {
        flex-shrink: 0;
    }

    .logo-link {
        display: flex;
        align-items: center;
        text-decoration: none;
    }

    .logo-image {
        height: 36px;
        width: auto;
    }

    .header-search-desktop {
        flex: 1;
        max-width: 500px;
        margin: 0 1rem;
    }

    .search-form {
        width: 100%;
    }

    .search-input-group {
        position: relative;
        display: flex;
        align-items: center;
        background: rgba(0, 0, 0, 0.03);
        border-radius: 20px;
        padding: 0.5rem 1rem;
        border: 1px solid rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
    }

    [data-theme="dark"] .search-input-group {
        background: rgba(255, 255, 255, 0.05);
        border-color: rgba(255, 255, 255, 0.1);
    }

    .search-input-group:focus-within {
        border-color: #4C9F8A;
        background: rgba(76, 159, 138, 0.05);
    }

    .search-input-group i {
        color: #94a3b8;
        margin-right: 0.5rem;
        font-size: 0.875rem;
    }

    .search-input {
        flex: 1;
        border: none;
        background: transparent;
        outline: none;
        font-size: 0.9rem;
        color: #1e293b;
    }

    [data-theme="dark"] .search-input {
        color: #f1f5f9;
    }

    .search-input::placeholder {
        color: #94a3b8;
    }

    .header-right {
        display: flex;
        align-items: center;
        gap: 1rem;
        flex-shrink: 0;
    }

    /* Notification Bell */
    .notification-bell {
        position: relative;
    }

    .bell-button {
        position: relative;
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: #1e293b;
        transition: color 0.2s ease;
        padding: 0.5rem;
    }

    [data-theme="dark"] .bell-button {
        color: #f1f5f9;
    }

    .bell-button:hover {
        color: #4C9F8A;
    }

    .notification-badge {
        position: absolute;
        top: 0;
        right: 0;
        background: #ef4444;
        color: white;
        font-size: 0.65rem;
        font-weight: 700;
        padding: 0.15rem 0.35rem;
        border-radius: 20px;
        min-width: 18px;
        text-align: center;
    }

    /* User Menu */
    .user-menu {
        position: relative;
    }

    .user-button {
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        display: flex;
        align-items: center;
    }

    .user-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #e2e8f0;
        transition: border-color 0.2s ease;
    }

    [data-theme="dark"] .user-avatar {
        border-color: #334155;
    }

    .user-button:hover .user-avatar {
        border-color: #4C9F8A;
    }

    /* Dropdowns */
    .notification-dropdown,
    .user-dropdown {
        display: none;
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        margin-top: 0.5rem;
        min-width: 280px;
        z-index: 1000;
    }

    [data-theme="dark"] .notification-dropdown,
    [data-theme="dark"] .user-dropdown {
        background: #1e293b;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .notification-dropdown.active,
    .user-dropdown.active {
        display: block;
    }

    .notification-header,
    .user-info {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .notification-dropdown .notification-list {
        max-height: 320px;
        overflow-y: auto;
    }

    .notification-dropdown .notification-item {
        display: flex;
        gap: 0.75rem;
        padding: 0.875rem 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.04);
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        transition: background 0.2s ease;
    }

    .notification-dropdown .notification-item:hover {
        background: rgba(76, 159, 138, 0.06);
    }

    .notification-dropdown .notification-item.unread {
        background: rgba(76, 159, 138, 0.08);
    }

    .notification-dropdown .notification-item-icon {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 0.9rem;
    }

    .notification-dropdown .notification-item-content {
        flex: 1;
        min-width: 0;
    }

    .notification-dropdown .notification-item-title {
        font-size: 0.85rem;
        font-weight: 700;
        margin-bottom: 0.15rem;
        color: #1e293b;
    }

    .notification-dropdown .notification-item-message {
        font-size: 0.78rem;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .notification-dropdown .notification-item-time {
        font-size: 0.7rem;
        color: #94a3b8;
        margin-top: 0.2rem;
    }

    .notification-dropdown .notification-empty {
        padding: 2rem 1rem;
        text-align: center;
        color: #94a3b8;
        font-size: 0.85rem;
    }

    [data-theme="dark"] .notification-dropdown .notification-item-title {
        color: #f1f5f9;
    }

    [data-theme="dark"] .notification-dropdown .notification-item-message {
        color: #94a3b8;
    }

    [data-theme="dark"] .notification-header,
    [data-theme="dark"] .user-info {
        border-bottom-color: rgba(255, 255, 255, 0.05);
    }

    .user-info {
        display: flex;
        gap: 0.75rem;
        align-items: center;
    }

    .user-avatar-large {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        object-fit: cover;
    }

    .user-name {
        font-weight: 600;
        color: #1e293b;
        margin: 0;
        font-size: 0.95rem;
    }

    [data-theme="dark"] .user-name {
        color: #f1f5f9;
    }

    .user-email {
        color: #94a3b8;
        font-size: 0.825rem;
        margin: 0;
    }

    .dropdown-divider {
        height: 1px;
        background: rgba(0, 0, 0, 0.05);
        margin: 0.5rem 0;
    }

    [data-theme="dark"] .dropdown-divider {
        background: rgba(255, 255, 255, 0.05);
    }

    .dropdown-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: #1e293b;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    [data-theme="dark"] .dropdown-link {
        color: #cbd5e1;
    }

    .dropdown-link:hover {
        background: rgba(76, 159, 138, 0.1);
        color: #4C9F8A;
    }

    .dropdown-link i {
        width: 18px;
        text-align: center;
    }

    .logout-link {
        border: none;
        cursor: pointer;
        width: 100%;
        text-align: left;
        font-family: inherit;
    }

    .logout-form {
        width: 100%;
    }

    /* Auth Buttons */
    .auth-buttons {
        display: flex;
        gap: 0.5rem;
    }

    /* Theme Toggle */
    .theme-toggle {
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: #1e293b;
        padding: 0.5rem;
        transition: color 0.2s ease;
    }

    [data-theme="dark"] .theme-toggle {
        color: #f1f5f9;
    }

    .theme-toggle:hover {
        color: #4C9F8A;
    }

    /* Mobile Menu Button */
    .mobile-menu-btn {
        display: none;
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: #1e293b;
        padding: 0.5rem;
    }

    [data-theme="dark"] .mobile-menu-btn {
        color: #f1f5f9;
    }

    /* Mobile Search */
    .header-search-mobile {
        display: none;
        padding: 0.5rem 1.5rem 1rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    [data-theme="dark"] .header-search-mobile {
        border-top-color: rgba(255, 255, 255, 0.05);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .header-search-desktop {
            display: none;
        }

        .header-search-mobile {
            display: block;
        }

        .mobile-menu-btn {
            display: block;
        }

        .header-container {
            gap: 0.5rem;
        }

        .auth-buttons {
            display: none;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // User menu toggle
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const themeToggle = document.getElementById('themeToggle');

    // User menu
    if (userMenuBtn) {
        userMenuBtn.addEventListener('click', () => {
            userDropdown?.classList.toggle('active');
            notificationDropdown?.classList.remove('active');
        });
    }

    // Notification dropdown
    if (notificationBell) {
        notificationBell.addEventListener('click', () => {
            const isOpening = !notificationDropdown?.classList.contains('active');
            notificationDropdown?.classList.toggle('active');
            userDropdown?.classList.remove('active');
            if (isOpening) {
                loadHeaderNotifications();
            }
        });
    }

    function updateHeaderNotificationBadge(count) {
        const badge = document.getElementById('headerNotificationBadge');
        if (!badge) return;

        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'inline-block';
        } else {
            badge.style.display = 'none';
        }
    }

    function renderHeaderNotifications(notifications) {
        const list = document.getElementById('notificationList');
        if (!list) return;

        if (!notifications.length) {
            list.innerHTML = '<div class="notification-empty"><i class="fas fa-bell-slash"></i><p>No notifications</p></div>';
            return;
        }

        list.innerHTML = notifications.map(notification => `
            <a href="${notification.link || '#'}" class="notification-item ${notification.is_read ? '' : 'unread'}" data-id="${notification.id}">
                <div class="notification-item-icon" style="background: ${notification.color}20; color: ${notification.color};">
                    <i class="fas ${notification.icon}"></i>
                </div>
                <div class="notification-item-content">
                    <div class="notification-item-title">${notification.title}</div>
                    <div class="notification-item-message">${notification.message}</div>
                    <div class="notification-item-time">${notification.time_ago}</div>
                </div>
            </a>
        `).join('');

        list.querySelectorAll('.notification-item').forEach(item => {
            item.addEventListener('click', function(e) {
                const id = this.dataset.id;
                const link = this.getAttribute('href');
                if (!id || !link || link === '#') return;

                e.preventDefault();
                const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

                fetch('/api/notifications', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({
                        action: 'mark_read',
                        notification_id: id,
                    }),
                }).finally(() => {
                    window.location.href = link;
                });
            });
        });
    }

    function loadHeaderNotifications() {
        const list = document.getElementById('notificationList');
        if (!list) return;

        list.innerHTML = '<div class="notification-loading" style="padding: 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem;">Loading...</div>';

        fetch('/api/notifications?action=list&limit=10&include_read=false', {
            headers: { 'Accept': 'application/json' },
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    renderHeaderNotifications(data.notifications || []);
                    updateHeaderNotificationBadge(data.unread_count || 0);
                } else {
                    list.innerHTML = '<div class="notification-empty">Unable to load notifications</div>';
                }
            })
            .catch(() => {
                list.innerHTML = '<div class="notification-empty">Unable to load notifications</div>';
            });
    }

    // Close notification dropdown
    document.getElementById('closeNotifications')?.addEventListener('click', () => {
        notificationDropdown?.classList.remove('active');
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.notification-bell') && !e.target.closest('.user-menu')) {
            userDropdown?.classList.remove('active');
            notificationDropdown?.classList.remove('active');
        }
    });

    // Theme toggle
    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });

        // Set initial icon
        const theme = localStorage.getItem('theme') || 'light';
        updateThemeIcon(theme);
    }

    function updateThemeIcon(theme) {
        const icon = themeToggle.querySelector('i');
        if (theme === 'dark') {
            icon.classList.remove('fa-moon');
            icon.classList.add('fa-sun');
        } else {
            icon.classList.remove('fa-sun');
            icon.classList.add('fa-moon');
        }
    }
});
</script>
