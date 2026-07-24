@php
    $isLoggedIn = (bool) session('user_id');
    $notifCount = (int) ($notificationCount ?? 0);
@endphp

<header class="app-header">
    @if (request()->routeIs('books'))
    <div class="header-search-overlay" id="headerSearchOverlay">
        <div class="header-container">
            <form action="{{ route('books') }}" method="GET" class="search-form-overlay">
                <i class="fas fa-search search-icon-overlay"></i>
                <input type="text" name="q" placeholder="Search books, authors, categories..." class="search-input-overlay" value="{{ request('q', '') }}" id="headerSearchInput">
                <button type="button" class="close-search-btn" id="closeSearchBtn"><i class="fas fa-times"></i></button>
            </form>
        </div>
    </div>
    @endif
    <div class="header-container" id="mainHeaderContainer">
        <div class="header-logo">
            <a href="{{ route('home') }}" class="logo-link">
                <img src="{{ asset('images/logo-full.svg') }}" alt="OpenShelf" class="logo-image">
            </a>
        </div>

        <nav class="header-nav-desktop" aria-label="Primary">
            <a href="{{ route('home') }}" class="header-nav-link {{ request()->routeIs('home') ? 'active' : '' }}">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="{{ route('books') }}" class="header-nav-link {{ request()->routeIs('books', 'book.show') ? 'active' : '' }}">
                <i class="fas fa-book"></i> Books
            </a>
            <a href="{{ route('announcements.index') }}" class="header-nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
                <i class="fas fa-bullhorn"></i> Announcements
            </a>
            @if ($isLoggedIn)
                <a href="{{ route('requests.index') }}" class="header-nav-link {{ request()->routeIs('requests.*') ? 'active' : '' }}">
                    <i class="fas fa-paper-plane"></i> Requests
                </a>
                <a href="{{ route('my-borrowed') }}" class="header-nav-link {{ request()->routeIs('my-borrowed') ? 'active' : '' }}">
                    <i class="fas fa-book-reader"></i> My Books
                </a>
            @endif
            <a href="{{ route('support-us') }}" class="header-nav-link header-nav-support {{ request()->routeIs('support-us') ? 'active' : '' }}">
                <i class="fas fa-heart"></i> Support Us
            </a>
        </nav>



        <div class="header-right">
            @if (request()->routeIs('books'))
                <button class="search-toggle-btn" id="headerSearchToggleBtn" type="button" aria-label="Search">
                    <i class="fas fa-search"></i>
                </button>
            @endif

            @if ($isLoggedIn)
                <div class="notification-bell">
                    <button class="bell-button" id="notificationBell" type="button" aria-label="Notifications">
                        <i class="fas fa-bell"></i>
                        @if ($notifCount > 0)
                            <span class="notification-badge" id="headerNotificationBadge">{{ min($notifCount, 99) }}</span>
                        @else
                            <span class="notification-badge" id="headerNotificationBadge" style="display: none;"></span>
                        @endif
                    </button>

                    <div class="notification-dropdown" id="notificationDropdown">
                        <div class="notification-header">
                            <h3>Notifications</h3>
                            <button class="close-btn" id="closeNotifications" type="button" aria-label="Close">
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

            @if ($headerUser)
                <div class="user-menu">
                    <button class="user-button" id="userMenuBtn" type="button" aria-label="User menu">
                        <img src="{{ $headerUser->profile_image_url }}" alt="{{ $headerUser->name }}" class="user-avatar">
                    </button>

                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-info">
                            <img src="{{ $headerUser->profile_image_url }}" alt="{{ $headerUser->name }}" class="user-avatar-large">
                            <div>
                                <p class="user-name">{{ $headerUser->name }}</p>
                                <p class="user-email">{{ $headerUser->email }}</p>
                            </div>
                        </div>

                        <div class="dropdown-divider"></div>
                        <a href="{{ route('profile') }}" class="dropdown-link"><i class="fas fa-user"></i> My Profile</a>
                        <a href="{{ route('settings') }}" class="dropdown-link"><i class="fas fa-cog"></i> Settings</a>
                        <a href="{{ route('my-borrowed') }}" class="dropdown-link"><i class="fas fa-book"></i> My Books</a>
                        <a href="{{ route('books.create') }}" class="dropdown-link"><i class="fas fa-plus"></i> Add Book</a>
                        <a href="{{ route('requests.index') }}" class="dropdown-link"><i class="fas fa-paper-plane"></i> Requests</a>

                        <div class="dropdown-divider"></div>
                        <form action="{{ route('logout') }}" method="POST" class="logout-form">
                            @csrf
                            <button type="submit" class="dropdown-link logout-link">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <div class="auth-buttons">
                    <a href="{{ route('login') }}" class="btn btn-ghost">Login</a>
                    <a href="{{ route('register') }}" class="btn btn-primary">Sign Up</a>
                </div>
            @endif

            <button class="theme-toggle" id="themeToggle" type="button" aria-label="Toggle theme">
                <i class="fas fa-moon"></i>
            </button>

            <button class="mobile-menu-btn" id="mobileMenuBtn" type="button" aria-label="Menu" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>

</header>

<div class="mobile-menu-overlay" id="mobileMenuOverlay"></div>

<div class="mobile-nav-panel" id="mobileNavPanel" role="dialog" aria-label="Navigation menu">
    <div class="mobile-nav-header">
        <img src="{{ asset('images/logo-full.svg') }}" alt="OpenShelf" style="height: 30px;">
        <button class="mobile-nav-close" id="mobileNavClose" type="button" aria-label="Close menu">
            <i class="fas fa-times"></i>
        </button>
    </div>

    @if ($headerUser)
        <div class="mobile-nav-user">
            <img src="{{ $headerUser->profile_image_url }}" alt="{{ $headerUser->name }}" class="mobile-nav-avatar">
            <div>
                <div class="mobile-nav-user-name">{{ $headerUser->name }}</div>
                <div class="mobile-nav-user-email">{{ $headerUser->email }}</div>
            </div>
        </div>
    @else
        <div class="mobile-nav-guest">
            <div class="mobile-nav-user-name">Welcome to OpenShelf</div>
            <div class="mobile-nav-guest-actions">
                <a href="{{ route('login') }}" class="mobile-nav-guest-btn">Login</a>
                <a href="{{ route('register') }}" class="mobile-nav-guest-btn primary">Sign Up</a>
            </div>
        </div>
    @endif

    <nav class="mobile-nav-links">
        <div class="mobile-nav-section-label">General</div>
        <a href="{{ route('home') }}" class="mobile-nav-link desktop-only-nav-item {{ request()->routeIs('home') ? 'active' : '' }}">
            <i class="fas fa-home"></i> Home
        </a>
        <a href="{{ route('books') }}" class="mobile-nav-link {{ request()->routeIs('books', 'book.show') ? 'active' : '' }}">
            <i class="fas fa-book"></i> Browse Books
        </a>
        <a href="{{ route('announcements.index') }}" class="mobile-nav-link {{ request()->routeIs('announcements.*') ? 'active' : '' }}">
            <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="{{ route('about') }}" class="mobile-nav-link {{ request()->routeIs('about') ? 'active' : '' }}">
            <i class="fas fa-info-circle"></i> About
        </a>
        <a href="#" class="mobile-nav-link" id="pwaInstallBtn" style="display: none;">
            <i class="fas fa-download"></i> Install App
        </a>

        @if ($isLoggedIn)
            <div class="mobile-nav-divider"></div>
            <div class="mobile-nav-section-label">Management</div>
            <a href="{{ route('books.create') }}" class="mobile-nav-link desktop-only-nav-item {{ request()->routeIs('books.create') ? 'active' : '' }}">
                <i class="fas fa-plus"></i> Add Book
            </a>
            <a href="{{ route('requests.index') }}" class="mobile-nav-link desktop-only-nav-item {{ request()->routeIs('requests.*') ? 'active' : '' }}">
                <i class="fas fa-paper-plane"></i> Requests
            </a>
            <a href="{{ route('my-borrowed') }}" class="mobile-nav-link desktop-only-nav-item {{ request()->routeIs('my-borrowed') ? 'active' : '' }}">
                <i class="fas fa-book-reader"></i> My Borrowed
            </a>
            <a href="{{ route('notifications.index') }}" class="mobile-nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}">
                <i class="fas fa-bell"></i> Notifications
                @if ($notifCount > 0)
                    <span class="mobile-nav-badge">{{ $notifCount > 99 ? '99+' : $notifCount }}</span>
                @endif
            </a>
            <a href="{{ route('profile') }}" class="mobile-nav-link desktop-only-nav-item {{ request()->routeIs('profile') ? 'active' : '' }}">
                <i class="fas fa-user"></i> My Profile
            </a>
            <a href="{{ route('settings') }}" class="mobile-nav-link {{ request()->routeIs('settings*') ? 'active' : '' }}">
                <i class="fas fa-cog"></i> Settings
            </a>
        @endif

        <div class="mobile-nav-divider"></div>
        <div class="mobile-nav-section-label">Support</div>
        <a href="{{ route('support-us') }}" class="mobile-nav-link mobile-nav-support {{ request()->routeIs('support-us') ? 'active' : '' }}">
            <i class="fas fa-heart"></i> Support Us
        </a>
        <a href="{{ route('faq') }}" class="mobile-nav-link {{ request()->routeIs('faq') ? 'active' : '' }}">
            <i class="fas fa-question-circle"></i> FAQ
        </a>
        <a href="{{ route('guidelines') }}" class="mobile-nav-link {{ request()->routeIs('guidelines') ? 'active' : '' }}">
            <i class="fas fa-book-open"></i> Guidelines
        </a>
        <a href="{{ route('contact') }}" class="mobile-nav-link {{ request()->routeIs('contact') ? 'active' : '' }}">
            <i class="fas fa-envelope"></i> Contact
        </a>
        <a href="{{ route('report') }}" class="mobile-nav-link {{ request()->routeIs('report') ? 'active' : '' }}">
            <i class="fas fa-flag"></i> Report Issue
        </a>

        @if ($isLoggedIn)
            <div class="mobile-nav-divider"></div>
            <form action="{{ route('logout') }}" method="POST">
                @csrf
                <button type="submit" class="mobile-nav-link mobile-nav-logout">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        @endif
    </nav>
</div>

<style>
    .app-header {
        position: sticky;
        top: 0;
        z-index: 100;
        background: var(--header-bg, #ffffff);
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
        transition: background 0.3s ease, transform 0.3s ease;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
    }

    .app-header.header-hidden {
        transform: translateY(-100%);
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

    .header-logo { flex-shrink: 0; }
    .logo-link { display: flex; align-items: center; text-decoration: none; }
    .logo-image { height: 36px; width: auto; }

    .header-nav-desktop {
        display: flex;
        align-items: center;
        gap: 0.25rem;
        flex-shrink: 0;
    }

    .header-nav-link {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 0.75rem;
        border-radius: 10px;
        color: #5A6C7D;
        text-decoration: none;
        font-size: 0.875rem;
        font-weight: 600;
        white-space: nowrap;
        transition: color 0.2s ease, background 0.2s ease;
    }

    .header-nav-link i { font-size: 0.8rem; opacity: 0.85; }

    .header-nav-link:hover,
    .header-nav-link.active {
        color: #2C3E50;
        background: rgba(76, 159, 138, 0.08);
    }

    .header-nav-link.active { color: #4C9F8A; }

    .header-nav-support {
        color: #f59e0b !important;
        font-weight: 700;
    }

    .header-nav-support:hover,
    .header-nav-support.active {
        background: rgba(245, 158, 11, 0.1);
        color: #d97706 !important;
    }

    [data-theme="dark"] .header-nav-link { color: #94a3b8; }
    [data-theme="dark"] .header-nav-link:hover,
    [data-theme="dark"] .header-nav-link.active { color: #4C9F8A; background: rgba(76, 159, 138, 0.12); }

    .header-search-desktop {
        flex: 1;
        max-width: 360px;
        margin: 0 0.5rem;
    }

    .search-form { width: 100%; }

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
        min-width: 0;
    }

    [data-theme="dark"] .search-input { color: #f1f5f9; }
    .search-input::placeholder { color: #94a3b8; }

    .header-right {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        flex-shrink: 0;
    }

    .notification-bell { position: relative; }

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

    [data-theme="dark"] .bell-button { color: #f1f5f9; }
    .bell-button:hover { color: #4C9F8A; }

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

    .user-menu { position: relative; }

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

    [data-theme="dark"] .user-avatar { border-color: #334155; }
    .user-button:hover .user-avatar { border-color: #4C9F8A; }

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
    .user-dropdown.active { display: block; }

    .notification-header,
    .user-info {
        padding: 1rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }

    .notification-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .notification-header h3 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
    }

    .close-btn {
        background: none;
        border: none;
        cursor: pointer;
        color: #94a3b8;
        padding: 0.25rem;
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

    .notification-dropdown .notification-item:hover { background: rgba(76, 159, 138, 0.06); }
    .notification-dropdown .notification-item.unread { background: rgba(76, 159, 138, 0.08); }

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

    .notification-dropdown .notification-item-content { flex: 1; min-width: 0; }

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

    [data-theme="dark"] .notification-dropdown .notification-item-title { color: #f1f5f9; }
    [data-theme="dark"] .notification-dropdown .notification-item-message { color: #94a3b8; }
    [data-theme="dark"] .notification-header,
    [data-theme="dark"] .user-info { border-bottom-color: rgba(255, 255, 255, 0.05); }

    .user-info { display: flex; gap: 0.75rem; align-items: center; }
    .user-avatar-large { width: 48px; height: 48px; border-radius: 50%; object-fit: cover; }
    .user-name { font-weight: 600; color: #1e293b; margin: 0; font-size: 0.95rem; }
    [data-theme="dark"] .user-name { color: #f1f5f9; }
    .user-email { color: #94a3b8; font-size: 0.825rem; margin: 0; }

    .dropdown-divider {
        height: 1px;
        background: rgba(0, 0, 0, 0.05);
        margin: 0.5rem 0;
    }

    [data-theme="dark"] .dropdown-divider { background: rgba(255, 255, 255, 0.05); }

    .dropdown-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.75rem 1rem;
        color: #1e293b;
        text-decoration: none;
        transition: all 0.2s ease;
    }

    [data-theme="dark"] .dropdown-link { color: #cbd5e1; }
    .dropdown-link:hover { background: rgba(76, 159, 138, 0.1); color: #4C9F8A; }
    .dropdown-link i { width: 18px; text-align: center; }

    .logout-link {
        border: none;
        cursor: pointer;
        width: 100%;
        text-align: left;
        font-family: inherit;
        background: none;
    }

    .logout-form { width: 100%; }
    .auth-buttons { display: flex; gap: 0.5rem; }

    .theme-toggle,
    .mobile-menu-btn {
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: #1e293b;
        padding: 0.5rem;
        transition: color 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    [data-theme="dark"] .theme-toggle,
    [data-theme="dark"] .mobile-menu-btn { color: #f1f5f9; }

    .theme-toggle:hover,
    .mobile-menu-btn:hover { color: #4C9F8A; }

    .header-search-mobile {
        display: none;
        padding: 0.5rem 1.5rem 1rem;
        border-top: 1px solid rgba(0, 0, 0, 0.05);
    }

    [data-theme="dark"] .header-search-mobile { border-top-color: rgba(255, 255, 255, 0.05); }

    .mobile-menu-overlay {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.5);
        z-index: 200;
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
    }

    .mobile-menu-overlay.active { display: block; }

    .mobile-nav-panel {
        position: fixed;
        top: 0;
        right: 0;
        height: 100%;
        width: min(320px, 88vw);
        background: #ffffff;
        z-index: 300;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        overflow-y: auto;
        box-shadow: -4px 0 24px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
    }

    [data-theme="dark"] .mobile-nav-panel { background: #1e293b; }
    .mobile-nav-panel.active { transform: translateX(0); }

    .mobile-nav-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
        position: sticky;
        top: 0;
        background: inherit;
        z-index: 1;
    }

    [data-theme="dark"] .mobile-nav-header { border-bottom-color: rgba(255, 255, 255, 0.06); }

    .mobile-nav-close {
        background: none;
        border: none;
        font-size: 1.1rem;
        cursor: pointer;
        color: #64748b;
        padding: 0.35rem;
        border-radius: 6px;
        transition: color 0.2s, background 0.2s;
    }

    .mobile-nav-close:hover { color: #ef4444; background: rgba(239, 68, 68, 0.1); }
    [data-theme="dark"] .mobile-nav-close { color: #94a3b8; }

    .mobile-nav-user,
    .mobile-nav-guest {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 1rem 1.25rem;
        border-bottom: 1px solid rgba(0, 0, 0, 0.06);
    }

    .mobile-nav-guest {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.85rem;
    }

    .mobile-nav-guest-actions { display: flex; gap: 0.6rem; width: 100%; }

    .mobile-nav-guest-btn {
        flex: 1;
        text-align: center;
        padding: 0.6rem 0.75rem;
        border-radius: 10px;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.85rem;
        background: rgba(76, 159, 138, 0.1);
        color: #4C9F8A;
    }

    .mobile-nav-guest-btn.primary {
        background: #4C9F8A;
        color: #fff;
    }

    [data-theme="dark"] .mobile-nav-user,
    [data-theme="dark"] .mobile-nav-guest { border-bottom-color: rgba(255, 255, 255, 0.06); }

    .mobile-nav-avatar {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #4C9F8A;
    }

    .mobile-nav-user-name {
        font-size: 0.9rem;
        font-weight: 700;
        color: #1e293b;
    }

    [data-theme="dark"] .mobile-nav-user-name { color: #f1f5f9; }
    .mobile-nav-user-email { font-size: 0.78rem; color: #64748b; }

    .mobile-nav-links { padding: 0.5rem 0 1.5rem; flex: 1; }

    .mobile-nav-section-label {
        padding: 0.75rem 1.25rem 0.35rem;
        font-size: 0.7rem;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #94a3b8;
    }

    .mobile-nav-link {
        display: flex;
        align-items: center;
        gap: 0.85rem;
        padding: 0.8rem 1.25rem;
        color: #374151;
        text-decoration: none;
        font-size: 0.95rem;
        font-weight: 500;
        transition: background 0.2s, color 0.2s;
        border: none;
        background: none;
        width: 100%;
        text-align: left;
        cursor: pointer;
        font-family: inherit;
    }

    [data-theme="dark"] .mobile-nav-link { color: #cbd5e1; }

    .mobile-nav-link:hover,
    .mobile-nav-link.active {
        background: rgba(76, 159, 138, 0.08);
        color: #4C9F8A;
    }

    .mobile-nav-link i {
        width: 20px;
        text-align: center;
        color: #94a3b8;
        font-size: 0.9rem;
    }

    .mobile-nav-link:hover i,
    .mobile-nav-link.active i { color: #4C9F8A; }

    .mobile-nav-badge {
        margin-left: auto;
        background: #ef4444;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 700;
        padding: 0.15rem 0.45rem;
        border-radius: 999px;
        min-width: 1.25rem;
        text-align: center;
    }

    .mobile-nav-support { color: #f59e0b !important; font-weight: 700; }
    .mobile-nav-support i { color: #f59e0b !important; }

    .mobile-nav-logout { color: #ef4444; }
    .mobile-nav-logout:hover { background: rgba(239, 68, 68, 0.08); color: #ef4444; }
    .mobile-nav-logout i { color: #ef4444; }

    .mobile-nav-divider {
        height: 1px;
        background: rgba(0, 0, 0, 0.06);
        margin: 0.5rem 0;
    }

    [data-theme="dark"] .mobile-nav-divider { background: rgba(255, 255, 255, 0.06); }

    @media (max-width: 1100px) {
        .header-nav-desktop .header-nav-link span,
        .header-nav-desktop { gap: 0; }
        .header-nav-link { padding: 0.45rem 0.55rem; font-size: 0.8rem; }
        .header-search-desktop { max-width: 220px; }
    }

    @media (max-width: 900px) {
        .header-nav-desktop { display: none; }
        .auth-buttons { display: none; }
        .header-container { gap: 0.5rem; }
        .desktop-only-nav-item { display: none !important; }
        .user-menu { display: none !important; }
        .theme-toggle { display: none !important; }
    }

    .search-toggle-btn {
        background: none;
        border: none;
        font-size: 1.25rem;
        cursor: pointer;
        color: #1e293b;
        padding: 0.5rem;
        transition: color 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    [data-theme="dark"] .search-toggle-btn { color: #f1f5f9; }
    .search-toggle-btn:hover { color: #4C9F8A; }

    .header-search-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: var(--header-bg, #ffffff);
        z-index: 200;
        display: none;
        align-items: center;
    }
    .header-search-overlay.active {
        display: flex;
    }
    [data-theme="dark"] .header-search-overlay {
        background: #1e293b;
    }
    .search-form-overlay {
        display: flex;
        align-items: center;
        width: 100%;
        background: rgba(0, 0, 0, 0.03);
        border-radius: 20px;
        padding: 0.5rem 1rem;
    }
    [data-theme="dark"] .search-form-overlay {
        background: rgba(255, 255, 255, 0.05);
    }
    .search-icon-overlay {
        color: #94a3b8;
        margin-right: 0.75rem;
    }
    .search-input-overlay {
        flex: 1;
        border: none;
        background: transparent;
        outline: none;
        font-size: 1rem;
        color: #1e293b;
    }
    [data-theme="dark"] .search-input-overlay { color: #f1f5f9; }
    .close-search-btn {
        background: none;
        border: none;
        color: #94a3b8;
        font-size: 1.25rem;
        cursor: pointer;
        padding: 0 0.5rem;
    }
    .close-search-btn:hover { color: #ef4444; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');
    const notificationBell = document.getElementById('notificationBell');
    const notificationDropdown = document.getElementById('notificationDropdown');
    const themeToggle = document.getElementById('themeToggle');
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileNavPanel = document.getElementById('mobileNavPanel');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const mobileNavClose = document.getElementById('mobileNavClose');

    if (userMenuBtn) {
        userMenuBtn.addEventListener('click', () => {
            userDropdown?.classList.toggle('active');
            notificationDropdown?.classList.remove('active');
        });
    }

    if (notificationBell) {
        notificationBell.addEventListener('click', () => {
            const isOpening = !notificationDropdown?.classList.contains('active');
            notificationDropdown?.classList.toggle('active');
            userDropdown?.classList.remove('active');
            if (isOpening) loadHeaderNotifications();
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
                    body: JSON.stringify({ action: 'mark_read', notification_id: id }),
                }).finally(() => { window.location.href = link; });
            });
        });
    }

    function loadHeaderNotifications() {
        const list = document.getElementById('notificationList');
        if (!list) return;
        list.innerHTML = '<div class="notification-loading" style="padding: 1rem; text-align: center; color: #94a3b8; font-size: 0.85rem;">Loading...</div>';
        fetch('/api/notifications?action=list&limit=10&include_read=false', { headers: { 'Accept': 'application/json' } })
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

    document.getElementById('closeNotifications')?.addEventListener('click', () => {
        notificationDropdown?.classList.remove('active');
    });

    document.addEventListener('click', (e) => {
        if (!e.target.closest('.notification-bell') && !e.target.closest('.user-menu')) {
            userDropdown?.classList.remove('active');
            notificationDropdown?.classList.remove('active');
        }
    });

    function openMobileMenu() {
        mobileNavPanel?.classList.add('active');
        mobileMenuOverlay?.classList.add('active');
        mobileMenuBtn?.setAttribute('aria-expanded', 'true');
        document.body.style.overflow = 'hidden';
    }

    function closeMobileMenu() {
        mobileNavPanel?.classList.remove('active');
        mobileMenuOverlay?.classList.remove('active');
        mobileMenuBtn?.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    }

    mobileMenuBtn?.addEventListener('click', openMobileMenu);
    mobileNavClose?.addEventListener('click', closeMobileMenu);
    mobileMenuOverlay?.addEventListener('click', closeMobileMenu);

    if (themeToggle) {
        themeToggle.addEventListener('click', () => {
            const html = document.documentElement;
            const currentTheme = html.getAttribute('data-theme');
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            html.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            updateThemeIcon(newTheme);
        });
        updateThemeIcon(localStorage.getItem('theme') || 'light');
    }

    function updateThemeIcon(theme) {
        const icon = themeToggle?.querySelector('i');
        if (!icon) return;
        icon.classList.toggle('fa-moon', theme !== 'dark');
        icon.classList.toggle('fa-sun', theme === 'dark');
    }

    const searchToggleBtn = document.getElementById('headerSearchToggleBtn');
    const searchOverlay = document.getElementById('headerSearchOverlay');
    const closeSearchBtn = document.getElementById('closeSearchBtn');
    const searchInput = document.getElementById('headerSearchInput');
    const mainHeaderContainer = document.getElementById('mainHeaderContainer');

    if (searchToggleBtn && searchOverlay) {
        searchToggleBtn.addEventListener('click', () => {
            searchOverlay.classList.add('active');
            if(mainHeaderContainer) mainHeaderContainer.style.opacity = '0';
            setTimeout(() => searchInput?.focus(), 100);
        });

        closeSearchBtn?.addEventListener('click', () => {
            searchOverlay.classList.remove('active');
            if(mainHeaderContainer) mainHeaderContainer.style.opacity = '1';
        });
    }

    let headerLastScrollY = window.pageYOffset;
    const header = document.querySelector('.app-header');
    
    window.addEventListener('scroll', () => {
        const currentScrollY = window.pageYOffset;
        if (currentScrollY > 100) {
            if (currentScrollY > headerLastScrollY) {
                // scrolling down
                header.classList.add('header-hidden');
                document.body.classList.add('header-hidden');
            } else {
                // scrolling up
                header.classList.remove('header-hidden');
                document.body.classList.remove('header-hidden');
            }
        } else {
            header.classList.remove('header-hidden');
            document.body.classList.remove('header-hidden');
        }
        headerLastScrollY = currentScrollY;
    }, { passive: true });

    // PWA Install Logic
    let deferredPrompt;
    const pwaInstallBtn = document.getElementById('pwaInstallBtn');

    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent the mini-infobar from appearing on mobile
        e.preventDefault();
        // Stash the event so it can be triggered later
        deferredPrompt = e;
        // Update UI notify the user they can install the PWA
        if (pwaInstallBtn) {
            pwaInstallBtn.style.display = 'flex';
        }
    });

    if (pwaInstallBtn) {
        pwaInstallBtn.addEventListener('click', async (e) => {
            e.preventDefault();
            if (deferredPrompt) {
                // Hide the app provided install promotion
                pwaInstallBtn.style.display = 'none';
                // Show the install prompt
                deferredPrompt.prompt();
                // Wait for the user to respond to the prompt
                const { outcome } = await deferredPrompt.userChoice;
                if (outcome === 'accepted') {
                    console.log('User accepted the install prompt');
                } else {
                    console.log('User dismissed the install prompt');
                    // Show it again if dismissed
                    pwaInstallBtn.style.display = 'flex';
                }
                // We've used the prompt, and can't use it again, throw it away
                deferredPrompt = null;
            }
        });
    }
});
</script>
