<?php
/**
 * OpenShelf - Modern Header with Hamburger Menu
 * Works for ALL pages - includes proper CSS and JS
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/search_helper.php';
require_once __DIR__ . '/helpers.php';

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];

// Get user data if logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userName = $_SESSION['user_name'] ?? 'Guest';
$userEmail = $_SESSION['user_email'] ?? '';
$userAvatar = 'default-avatar.jpg';
$notificationCount = 0;

if ($isLoggedIn && isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Primary source: Load from MySQL users table
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $u = $stmt->fetch();
    
    if ($u) {
        $userName = $u['name'] ?? $userName;
        $userEmail = $u['email'] ?? $userEmail;
        $userAvatar = $u['profile_pic'] ?? 'default-avatar.jpg';
        
        // Sync hall to session if missing
        if (!isset($_SESSION['user_hall']) && isset($u['hall'])) {
            $_SESSION['user_hall'] = $u['hall'];
        }
    }

    // Fallback/Detailed: Load from detailed profile
    $userFile = dirname(__DIR__) . '/users/' . $userId . '.json';
    if (file_exists($userFile)) {
        $userData = json_decode(file_get_contents($userFile), true);
        // Supplement name/email if not in master or if explicitly set in profile
        $userName = $userData['personal_info']['name'] ?? $userName;
        $userEmail = $userData['personal_info']['email'] ?? $userEmail;
        // Only override avatar if it's explicitly set in personal_info and not already set from master
        if ($userAvatar === 'default-avatar.jpg' && isset($userData['personal_info']['profile_pic'])) {
            $userAvatar = $userData['personal_info']['profile_pic'];
        }
    }
    
    // Also check session for override (e.g. immediately after update)
    if (isset($_SESSION['user_name'])) {
        $userName = $_SESSION['user_name'];
    }
    if (isset($_SESSION['user_avatar'])) {
        $userAvatar = $_SESSION['user_avatar'];
    }
    
    // Get notification count
    $userFile = dirname(__DIR__) . '/users/' . $userId . '.json';
    if (file_exists($userFile)) {
        $userData = json_decode(file_get_contents($userFile), true);
        $notifications = $userData['notifications'] ?? [];
        foreach ($notifications as $n) {
            if (empty($n['is_read'])) {
                $notificationCount++;
            }
        }
    }
}

// Check if profile picture file exists
$avatarPath = '/uploads/profile/' . $userAvatar;
$defaultAvatar = '/assets/images/avatars/default.jpg';

// Verify file exists on server
$fullAvatarPath = dirname(__DIR__) . '/uploads/profile/' . $userAvatar;
if (!file_exists($fullAvatarPath) || $userAvatar === 'default-avatar.jpg') {
    $avatarPath = $defaultAvatar;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>OpenShelf - Share Books, Share Knowledge</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/svg+xml" href="/assets/images/logo-icon.svg">
    <link rel="apple-touch-icon" href="/assets/images/pwa/icon-192x192.png">
    
    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="apple-mobile-web-app-title" content="OpenShelf">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" id="themeColorMeta" content="#ffffff">
    <meta name="msapplication-TileColor" content="#2C3E50">
    <meta name="msapplication-TileImage" content="/assets/images/pwa/icon-144x144.png">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Theme Init -->
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    
    <style>
        /* ========================================
           UNIVERSAL HEADER STYLES
           Works on ALL pages
        ======================================== */
        
        :root {
            --header-bg: #ffffff;
            --header-blur: none;
            --header-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            --header-border: #E2E8F0;
            --nav-link-color: #5A6C7D;
            --nav-link-hover: #2C3E50;
            --nav-link-active: #2C3E50;
            --text-primary: #0F172A;
            --text-secondary: #5A6C7D;
            --text-tertiary: #94a3b8;
            --border: #E2E8F0;
            --surface-hover: #F1F5F9;
            --primary: #2C3E50;
            --primary-soft: rgba(44, 62, 80, 0.08);
        }

        :root[data-theme="dark"] {
            --header-bg: #0F172A;
            --header-shadow: 0 4px 20px rgba(0, 0, 0, 0.3);
            --header-border: #1e293b;
            --nav-link-color: #cbd5e1;
            --nav-link-hover: #4C9F8A;
            --nav-link-active: #4C9F8A;
            --text-primary: #F8F9FA;
            --text-secondary: #cbd5e1;
            --text-tertiary: #94a3b8;
            --border: #334155;
            --surface-hover: #1e293b;
            --primary: #4C9F8A;
            --primary-soft: rgba(76, 159, 138, 0.1);
        }

        [data-theme="dark"] body {
            background: #0f172a;
            color: #cbd5e1;
        }

        [data-theme="dark"] .mobile-menu,
        [data-theme="dark"] .mobile-header,
        [data-theme="dark"] .mobile-overlay {
            background: #0f172a;
        }
        
        [data-theme="dark"] .mobile-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
        }

        [data-theme="dark"] .user-dropdown {
            background: #1e293b;
        }
        
        [data-theme="dark"] .dropdown-header {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            border-bottom-color: #334155;
        }

        /* Reset any conflicting styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, 'Inter', 'Segoe UI', Roboto, sans-serif;
            background: #f8fafc;
            padding-top: 100px;
        }

        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1100;
            background: var(--header-bg);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--header-border);
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            width: 100%;
            height: 72px;
            display: flex;
            align-items: center;
        }

        .site-header.nav-up {
            transform: translateY(-100%);
        }

        .header-container {
            width: 100%;
            padding: 0 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Logo Wrapper Styling */
        .logo-wrapper {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.3s ease;
        }

        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #2C3E50 0%, #4C9F8A 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.3rem;
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.2);
            transition: all 0.3s ease;
        }

        .logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .logo-name {
            font-size: 1.35rem;
            font-weight: 850;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #2C3E50 0%, #4C9F8A 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .logo-tagline {
            font-size: 0.6rem;
            font-weight: 600;
            color: var(--text-tertiary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .logo:hover .logo-wrapper {
            transform: scale(1.02);
        }

        .logo:hover .logo-icon {
            transform: rotate(-5deg) scale(1.1);
            box-shadow: 0 6px 16px rgba(76, 159, 138, 0.3);
        }

        /* Desktop Navigation */
        .nav-desktop {
            display: none;
            align-items: center;
            gap: 0.5rem;
        }

        .nav-link {
            padding: 0.5rem 0.85rem;
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--nav-link-color);
            text-decoration: none;
            border-radius: 2rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
        }

        .nav-link i {
            margin-right: 0.4rem;
            font-size: 0.85rem;
            opacity: 0.8;
        }

        .nav-link:hover {
            color: var(--nav-link-hover);
            background: var(--primary-soft);
            transform: translateY(-1px);
        }

        .nav-link.active {
            color: var(--nav-link-active);
            background: var(--primary-soft);
            box-shadow: inset 0 0 0 1px var(--header-border);
        }

        /* Notification Bell */
        .notification-wrapper {
            position: relative;
        }

        .notification-btn {
            background: none;
            border: none;
            padding: 0.5rem;
            cursor: pointer;
            border-radius: 50%;
            transition: all 0.2s ease;
            color: var(--nav-link-color);
            position: relative;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
        }

        .notification-btn:hover {
            background: var(--primary-soft);
            color: var(--nav-link-hover);
        }

        .notification-badge {
            position: absolute;
            top: 0;
            right: 0;
            min-width: 18px;
            height: 18px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            font-size: 0.65rem;
            font-weight: 600;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0 4px;
            border: 2px solid white;
            transform: translate(30%, -30%);
        }

        /* User Menu */
        .user-menu {
            position: relative;
        }

        .user-btn {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.25rem 0.75rem 0.25rem 0.25rem;
            background: none;
            border: none;
            border-radius: 2rem;
            cursor: pointer;
            transition: all 0.2s ease;
            background: var(--primary-soft);
        }

        .user-btn:hover {
            background: rgba(44, 62, 80, 0.12);
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--primary);
        }

        .user-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: var(--text-primary);
            display: none;
        }

        .user-dropdown {
            position: absolute;
            top: calc(100% + 0.5rem);
            right: 0;
            min-width: 240px;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 20px 35px -10px rgba(0, 0, 0, 0.15);
            border: 1px solid var(--border);
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s ease;
            z-index: 1000;
        }

        .user-dropdown.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .dropdown-header {
            padding: 1rem;
            background: linear-gradient(135deg, #f8fafc, #f1f5f9);
            border-bottom: 1px solid var(--border);
        }

        .dropdown-user-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .dropdown-user-email {
            font-size: 0.75rem;
            color: var(--text-tertiary);
        }

        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .dropdown-item i {
            width: 20px;
            color: var(--primary);
            font-size: 1rem;
        }

        .dropdown-item:hover {
            background: var(--surface-hover);
            color: var(--primary);
        }

        .dropdown-divider {
            height: 1px;
            background: var(--border);
            margin: 0.5rem 0;
        }

        /* Auth Buttons */
        .auth-buttons {
            display: flex;
            gap: 0.75rem;
        }

        .btn-login {
            padding: 0.5rem 1.25rem;
            background: transparent;
            border: 1.5px solid var(--border);
            border-radius: 2rem;
            color: var(--text-secondary);
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .btn-login:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .btn-register {
            padding: 0.5rem 1.25rem;
            background: linear-gradient(135deg, #2C3E50, #4C9F8A);
            border: none;
            border-radius: 2rem;
            color: white;
            font-weight: 500;
            text-decoration: none;
            transition: all 0.2s ease;
            box-shadow: 0 2px 8px rgba(44, 62, 80, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(44, 62, 80, 0.4);
        }

        /* Mobile Menu Toggle - THE THREE LINES */
        .menu-toggle {
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            width: 28px;
            height: 22px;
            background: none;
            border: none;
            cursor: pointer;
            padding: 0;
            z-index: 1001;
        }

        .menu-toggle span {
            width: 100%;
            height: 2px;
            background: var(--text-primary);
            border-radius: 10px;
            transition: all 0.3s ease;
        }

        .menu-toggle.active span:nth-child(1) {
            transform: translateY(10px) rotate(45deg);
        }

        .menu-toggle.active span:nth-child(2) {
            opacity: 0;
        }

        .menu-toggle.active span:nth-child(3) {
            transform: translateY(-10px) rotate(-45deg);
        }

        /* Mobile Menu */
        .mobile-menu {
            position: fixed;
            top: 0;
            left: -100%;
            width: 85%;
            max-width: 320px;
            height: 100vh;
            background: var(--header-bg);
            box-shadow: 20px 0 40px rgba(0, 0, 0, 0.1);
            transition: left 0.3s ease;
            z-index: 1200; /* Higher than header */
            display: flex;
            flex-direction: column;
            overflow-y: auto;
            -webkit-overflow-scrolling: touch;
        }

        .mobile-menu.active {
            left: 0;
        }

        .mobile-header {
            padding: 1.5rem;
            background: linear-gradient(135deg, #2C3E50, #4C9F8A);
            color: white;
        }

        .mobile-user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .mobile-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid white;
        }

        .mobile-user-name {
            font-weight: 600;
            font-size: 1rem;
        }

        .mobile-user-email {
            font-size: 0.7rem;
            opacity: 0.8;
        }

        .mobile-nav {
            flex: 1;
            padding: 1rem 0;
        }

        .mobile-nav-item {
            list-style: none;
        }

        .mobile-nav-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 1.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all 0.2s ease;
        }

        .mobile-nav-link i {
            width: 24px;
            color: var(--primary);
        }

        .mobile-nav-link:hover {
            background: var(--surface-hover);
            color: var(--primary);
        }

        .mobile-nav-link.active {
            background: var(--primary-soft);
            color: var(--primary);
            font-weight: 500;
            border-left: 3px solid var(--primary);
        }

        .mobile-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            font-size: 0.65rem;
            padding: 0.15rem 0.45rem;
            border-radius: 1rem;
        }

        .mobile-divider {
            height: 1px;
            background: var(--border);
            margin: 0.75rem 1.5rem;
        }

        .mobile-nav-section-label {
            list-style: none;
            padding: 0.5rem 1.5rem 0.25rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--text-tertiary);
        }

        .mobile-support-link {
            color: #f59e0b !important;
            font-weight: 600 !important;
        }

        .mobile-support-link i {
            color: #f59e0b !important;
        }

        .mobile-logout-link {
            color: #ef4444 !important;
        }

        .mobile-logout-link i {
            color: #ef4444 !important;
        }

        /* Overlay */
        .mobile-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
            z-index: 999;
        }

        .mobile-overlay.active {
            opacity: 1;
            visibility: visible;
        }

        /* Desktop Styles */
        @media (min-width: 768px) {
            .nav-desktop {
                display: flex;
            }
            
            .user-name {
                display: inline;
            }
            
            .header-container {
                padding: 0 1.5rem;
            }

            .site-header {
                height: 70px;
            }
        }

        @media (max-width: 768px) {
            .auth-buttons {
                display: none;
            }
            
            .notification-wrapper {
                display: none;
            }
            
            .nav-desktop {
                display: none;
            }
        }

        /* Hamburger menu always visible */
        .menu-toggle {
            display: flex !important;
            margin-left: 0.5rem;
        }

        /* Animation */
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

        .user-dropdown.show {
            animation: slideDown 0.2s ease;
        }
    </style>
</head>
<body>

<header class="site-header">
    <div class="header-container">
        <!-- Logo -->
        <a href="/" class="logo">
            <img src="/assets/images/logo-full.svg" alt="OpenShelf" height="45">
        </a>

        <!-- Desktop Navigation -->
        <nav class="nav-desktop">
            <a href="/" class="nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                <i class="fas fa-home"></i> Home
            </a>
            <a href="/books/" class="nav-link <?php echo strpos($currentPath, '/books/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-book"></i> Books
            </a>
            <a href="/feed/" class="nav-link <?php echo strpos($currentPath, '/feed/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-rss"></i> Feed
            </a>
            <a href="/announcements/" class="nav-link <?php echo strpos($currentPath, '/announcements/') !== false ? 'active' : ''; ?>">
                <i class="fas fa-bullhorn"></i> Announcements
            </a>
            <a href="/support_us/" class="nav-link <?php echo strpos($currentPath, '/support_us/') !== false ? 'active' : ''; ?>" style="color: #f59e0b; font-weight: 600;">
                <i class="fas fa-heart"></i> Support Us
            </a>
            <?php if ($isLoggedIn): ?>
                <a href="/notifications/" class="nav-link <?php echo strpos($currentPath, '/notifications/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge" style="position: relative; top: -2px; right: -4px; display: inline-flex; width: 18px; height: 18px; font-size: 0.6rem;"><?php echo $notificationCount > 9 ? '9+' : $notificationCount; ?></span>
                    <?php endif; ?>
                </a>
            <?php endif; ?>
        </nav>

        <!-- Right Section -->
        <div style="display: flex; align-items: center; gap: 0.5rem;">
            <!-- Theme Toggle -->
            <button id="themeToggleBtn" class="notification-btn" title="Toggle Theme" style="display: inline-flex; align-items: center; justify-content: center;">
                <i class="fas fa-moon"></i>
            </button>
            <!-- Notification Bell (Desktop) -->
            <?php if ($isLoggedIn): ?>
            <div class="notification-wrapper">
                <a href="/notifications/" class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <?php if ($notificationCount > 0): ?>
                        <span class="notification-badge"><?php echo $notificationCount > 9 ? '9+' : $notificationCount; ?></span>
                    <?php endif; ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- User Menu / Auth -->
            <?php if ($isLoggedIn): ?>
            <div class="user-menu">
                <button class="user-btn" id="userMenuBtn">
                    <img src="<?php echo $avatarPath; ?>" 
                         alt="<?php echo htmlspecialchars($userName); ?>" 
                         class="user-avatar" 
                         onerror="this.src='/assets/images/avatars/default.jpg'">
                    <span class="user-name"><?php echo htmlspecialchars($userName); ?></span>
                    <i class="fas fa-chevron-down" style="font-size: 0.75rem; color: var(--text-tertiary);"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <div class="dropdown-header">
                        <div class="dropdown-user-name"><?php echo htmlspecialchars($userName); ?></div>
                        <div class="dropdown-user-email"><?php echo htmlspecialchars($userEmail); ?></div>
                    </div>
                    <a href="/profile/" class="dropdown-item">
                        <i class="fas fa-user"></i> My Profile
                    </a>
                    <a href="/add-book/" class="dropdown-item">
                        <i class="fas fa-plus-circle"></i> Add Book
                    </a>
                    <a href="/requests/" class="dropdown-item">
                        <i class="fas fa-exchange-alt"></i> My Requests
                    </a>
                    <a href="/my-borrowed/" class="dropdown-item">
                        <i class="fas fa-book-reader"></i> My Borrowed
                    </a>
                    <a href="/edit-profile/" class="dropdown-item">
                        <i class="fas fa-cog"></i> Settings
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="/logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
            <?php else: ?>
            <div class="auth-buttons">
                <a href="/login/" class="btn-login">Login</a>
                <a href="/register/" class="btn-register">Register</a>
            </div>
            <?php endif; ?>

            <!-- Mobile Menu Toggle (THREE LINES) - Visible on mobile only -->
            <button class="menu-toggle" id="menuToggle">
                <span></span>
                <span></span>
                <span></span>
            </button>
        </div>
    </div>
</header>

<!-- Mobile Menu -->
<div class="mobile-menu" id="mobileMenu">
    <div class="mobile-header">
        <?php if ($isLoggedIn): ?>
        <div class="mobile-user-info">
            <img src="<?php echo $avatarPath; ?>" 
                 alt="<?php echo htmlspecialchars($userName); ?>" 
                 class="mobile-avatar" 
                 onerror="this.src='/assets/images/avatars/default.jpg'">
            <div>
                <div class="mobile-user-name"><?php echo htmlspecialchars($userName); ?></div>
                <div class="mobile-user-email"><?php echo htmlspecialchars($userEmail); ?></div>
            </div>
        </div>
        <?php else: ?>
        <div style="text-align: center;">
            <div class="mobile-user-name">Welcome to OpenShelf</div>
            <div style="margin-top: 1rem; display: flex; gap: 0.75rem; justify-content: center;">
                <a href="/login/" style="background: rgba(255,255,255,0.2); padding: 0.5rem 1rem; border-radius: 2rem; color: white; text-decoration: none;">Login</a>
                <a href="/register/" style="background: white; padding: 0.5rem 1rem; border-radius: 2rem; color: #6366f1; text-decoration: none;">Register</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <nav class="mobile-nav">
        <ul style="list-style: none;">
            <!-- Explore Section -->
            <li class="mobile-nav-section-label">Explore</li>
            <li class="mobile-nav-item">
                <a href="/" class="mobile-nav-link <?php echo $currentPage === 'index.php' ? 'active' : ''; ?>">
                    <i class="fas fa-home"></i> Home
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/books/" class="mobile-nav-link <?php echo strpos($currentPath, '/books/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-book"></i> Books
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/feed/" class="mobile-nav-link <?php echo strpos($currentPath, '/feed/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-rss"></i> Feed
                </a>
            </li>
            <!-- Support Us - Always Visible -->
            <li class="mobile-nav-item">
                <a href="/support_us/" class="mobile-nav-link mobile-support-link <?php echo strpos($currentPath, '/support_us/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-heart"></i> Support Us
                </a>
            </li>
            <?php if ($isLoggedIn): ?>
            <!-- Account Section -->
            <div class="mobile-divider"></div>
            <li class="mobile-nav-section-label">My Account</li>
            <li class="mobile-nav-item">
                <a href="/notifications/" class="mobile-nav-link <?php echo strpos($currentPath, '/notifications/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-bell"></i> Notifications
                    <?php if ($notificationCount > 0): ?>
                        <span class="mobile-badge"><?php echo $notificationCount; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/profile/" class="mobile-nav-link <?php echo strpos($currentPath, '/profile/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-user"></i> My Profile
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/my-borrowed/" class="mobile-nav-link <?php echo strpos($currentPath, '/my-borrowed/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-book-reader"></i> My Borrowed
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/announcements/" class="mobile-nav-link <?php echo strpos($currentPath, '/announcements/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/add-book/" class="mobile-nav-link <?php echo strpos($currentPath, '/add-book/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-plus-circle"></i> Add Book
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/requests/" class="mobile-nav-link <?php echo strpos($currentPath, '/requests/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-exchange-alt"></i> My Requests
                </a>
            </li>
            <li class="mobile-nav-item">
                <a href="/edit-profile/" class="mobile-nav-link <?php echo strpos($currentPath, '/edit-profile/') !== false ? 'active' : ''; ?>">
                    <i class="fas fa-cog"></i> Settings
                </a>
            </li>
            <div class="mobile-divider"></div>
            <li class="mobile-nav-item">
                <a href="/logout.php" class="mobile-nav-link mobile-logout-link">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </li>
            <?php endif; ?>
            <div class="mobile-divider"></div>
            <li class="mobile-nav-item" id="pwaInstallItem" style="display: none;">
                <a href="#" class="mobile-nav-link" id="pwaInstallBtn" style="color: var(--accent-brand); font-weight: 700;">
                    <i class="fas fa-download"></i> Install App
                </a>
            </li>

        </ul>
    </nav>
</div>

<!-- Overlay -->
<div class="mobile-overlay" id="mobileOverlay"></div>

<script>
    // Mobile Menu Toggle
    const menuToggle = document.getElementById('menuToggle');
    const mobileMenu = document.getElementById('mobileMenu');
    const mobileOverlay = document.getElementById('mobileOverlay');

    function closeMobileMenu() {
        if (menuToggle) menuToggle.classList.remove('active');
        if (mobileMenu) mobileMenu.classList.remove('active');
        if (mobileOverlay) mobileOverlay.classList.remove('active');
        document.body.style.overflow = '';
    }

    if (menuToggle && mobileMenu && mobileOverlay) {
        menuToggle.addEventListener('click', function(e) {
            e.stopPropagation();
            this.classList.toggle('active');
            mobileMenu.classList.toggle('active');
            mobileOverlay.classList.toggle('active');
            document.body.style.overflow = mobileMenu.classList.contains('active') ? 'hidden' : '';
        });

        mobileOverlay.addEventListener('click', closeMobileMenu);
        
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && mobileMenu.classList.contains('active')) {
                closeMobileMenu();
            }
        });
    }

    // User Dropdown
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userDropdown = document.getElementById('userDropdown');

    if (userMenuBtn && userDropdown) {
        userMenuBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            userDropdown.classList.toggle('show');
        });

        document.addEventListener('click', function(e) {
            if (!userMenuBtn.contains(e.target) && !userDropdown.contains(e.target)) {
                userDropdown.classList.remove('show');
            }
        });
    }

    // Close dropdown on escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && userDropdown && userDropdown.classList.contains('show')) {
            userDropdown.classList.remove('show');
        }
    });

    // Smart Scroll-to-Reveal Header
    let lastScrollTop = 0;
    const header = document.querySelector('.site-header');
    const scrollDelta = 5;
    const headerHeight = header.offsetHeight;

    window.addEventListener('scroll', () => {
        let st = window.pageYOffset || document.documentElement.scrollTop;
        
        // Scroll effect (shrink)
        if (st > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }

        // Hide/Show on scroll
        if (Math.abs(lastScrollTop - st) <= scrollDelta) return;

        if (st > lastScrollTop && st > headerHeight) {
            // Scroll Down - Hide Header
            header.classList.add('nav-up');
            document.body.classList.add('header-hidden');
        } else {
            // Scroll Up - Show Header
            if (st + window.innerHeight < document.documentElement.scrollHeight) {
                header.classList.remove('nav-up');
                document.body.classList.remove('header-hidden');
            }
        }
        
        lastScrollTop = st;
    });

    // Theme Toggle
    const themeToggleBtn = document.getElementById('themeToggleBtn');
    const themeColorMeta = document.getElementById('themeColorMeta');
    
    function updateThemeMeta(theme) {
        if (themeColorMeta) {
            themeColorMeta.setAttribute('content', theme === 'dark' ? '#0f172a' : '#ffffff');
        }
    }

    if (themeToggleBtn) {
        const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
        updateThemeMeta(currentTheme);
        
        if (currentTheme === 'dark') {
            themeToggleBtn.innerHTML = '<i class="fas fa-sun"></i>';
        }
        
        themeToggleBtn.addEventListener('click', () => {
            const theme = document.documentElement.getAttribute('data-theme');
            const newTheme = theme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            themeToggleBtn.innerHTML = newTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
            updateThemeMeta(newTheme);
        });
    }
</script>

<main class="main-content">