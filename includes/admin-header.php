<?php
/**
 * OpenShelf Admin Header
 * Modern admin panel header with sidebar navigation
 */

// Get current page for active states
$currentPage = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];

$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OpenShelf Admin Panel</title>
    <link rel="icon" type="image/svg+xml" href="/assets/images/logo-icon.svg">
    <link rel="apple-touch-icon" href="/assets/images/pwa/icon-192x192.png">
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#6366f1">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="OpenShelf">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="msapplication-TileColor" content="#6366f1">
    <meta name="msapplication-TileImage" content="/assets/images/pwa/icon-144x144.png">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        if (savedTheme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
        }
    </script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        /* Admin Panel Styles */
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --sidebar-bg: #0f172a;
            --sidebar-glass: rgba(15, 23, 42, 0.95);
            --sidebar-hover: rgba(255, 255, 255, 0.05);
            --sidebar-active: rgba(99, 102, 241, 0.2);
            --header-bg: rgba(255, 255, 255, 0.8);
            --header-blur: blur(12px);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-body: #f8fafc;
            --radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        :root[data-theme="dark"] {
            --primary: #818cf8;
            --primary-dark: #6366f1;
            --sidebar-bg: #0b1120;
            --header-bg: rgba(15, 23, 42, 0.8);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --bg-body: #0f172a;
        }

        [data-theme="dark"] .admin-card {
            background: #1e293b;
            border-color: #334155;
            color: #f8fafc;
        }

        [data-theme="dark"] .menu-toggle {
            background: #1e293b;
            border-color: #334155;
        }

        [data-theme="dark"] .admin-notification {
            background: #1e293b;
            border-color: #334155;
        }

        [data-theme="dark"] .admin-notification:hover {
            border-color: #818cf8;
        }
        
        [data-theme="dark"] .admin-user:hover {
            background: #1e293b;
            border-color: #334155;
        }
        
        [data-theme="dark"] .admin-topbar {
            border-bottom-color: #334155;
        }
        
        [data-theme="dark"] .page-title {
            color: #f8fafc;
        }
        
        [data-theme="dark"] .user-name {
            color: #f8fafc;
        }

        * {
            margin: 0; padding: 0; box-sizing: border-box;
        }

        body {
            font-family: 'Outfit', system-ui, -apple-system, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Admin Wrapper */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 290px;
            background: var(--sidebar-bg);
            color: #f1f5f9;
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: var(--transition);
            z-index: 1000;
            backdrop-filter: blur(20px);
            padding: 1.5rem 1rem;
        }

        .sidebar-header {
            padding: 1rem 0.5rem 2rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            margin-bottom: 1rem;
        }

        .sidebar-logo {
            font-size: 1.75rem;
            font-weight: 800;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            letter-spacing: -1px;
        }

        .sidebar-logo-img {
            width: 40px;
            height: 40px;
            border-radius: 12px;
            filter: drop-shadow(0 0 10px rgba(99, 102, 241, 0.5));
        }

        .sidebar-logo span {
            background: linear-gradient(135deg, #ffffff 0%, #a5b4fc 100%);
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-badge {
            background: rgba(99, 102, 241, 0.15);
            color: var(--primary-light);
            padding: 0.35rem 1rem;
            border-radius: 2rem;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 1rem;
            display: inline-block;
            border: 1px solid rgba(99, 102, 241, 0.2);
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .menu-section {
            margin-bottom: 2rem;
        }

        .menu-title {
            padding: 0 1rem;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #475569;
            margin-bottom: 1rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.85rem 1.25rem;
            color: #94a3b8;
            text-decoration: none;
            transition: var(--transition);
            border-radius: 14px;
            font-weight: 500;
            font-size: 0.95rem;
        }

        .menu-item i {
            width: 20px;
            font-size: 1.1rem;
            transition: var(--transition);
        }

        .menu-item:hover {
            color: white;
            background: var(--sidebar-hover);
        }

        .menu-item.active {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 20px -5px rgba(99, 102, 241, 0.4);
        }

        .menu-item.active i {
            color: white;
        }

        .menu-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            padding: 0.2rem 0.5rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 700;
        }

        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 290px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Topbar */
        .admin-topbar {
            background: var(--header-bg);
            backdrop-filter: var(--header-blur);
            padding: 1rem 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(226, 232, 240, 0.8);
            position: sticky;
            top: 0;
            z-index: 900;
            transition: var(--transition);
        }

        .menu-toggle {
            display: none;
            background: white;
            border: 1px solid #e2e8f0;
            width: 40px; height: 40px;
            border-radius: 10px;
            font-size: 1.1rem;
            cursor: pointer;
            color: var(--text-muted);
            transition: var(--transition);
        }

        .menu-toggle:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .page-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 2rem;
        }

        .admin-notification {
            position: relative;
            cursor: pointer;
            width: 42px; height: 42px;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            transition: var(--transition);
        }

        .admin-notification:hover {
            border-color: var(--primary);
            color: var(--primary);
            transform: translateY(-2px);
        }

        .admin-notification i {
            font-size: 1.25rem;
            color: var(--text-muted);
        }

        .notification-dot {
            position: absolute;
            top: 10px; right: 10px;
            width: 10px; height: 10px;
            background: #ef4444;
            border: 2px solid white;
            border-radius: 50%;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 1rem;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 16px;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .admin-user:hover {
            background: white;
            border-color: #e2e8f0;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05);
        }

        .user-avatar {
            width: 44px; height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            border-radius: 14px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }

        .user-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--text-main);
            margin-bottom: 2px;
        }

        .user-role {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Admin Content Area */
        .admin-content {
            padding: 2.5rem;
            flex: 1;
        }

        /* Generic Admin Card */
        .admin-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            transition: var(--transition);
        }

        .admin-card:hover {
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05);
        }

        /* Buttons */
        .btn-admin {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            border: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-admin-primary {
            background: var(--primary);
            color: white;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
        }

        .btn-admin-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
        }

        /* Responsive */
        @media (max-width: 992px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
                box-shadow: 20px 0 50px rgba(0,0,0,0.2);
            }
            .admin-main { margin-left: 0; }
            .menu-toggle { display: block; }
            .user-info { display: none; }
            .admin-topbar { padding: 1rem; }
        }

        @media (max-width: 480px) {
            .page-title { font-size: 1.1rem; }
            .topbar-right { gap: 1rem; }
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="/admin/dashboard/" class="sidebar-logo">
                    <img src="/assets/images/logo-icon.svg" alt="OpenShelf" class="sidebar-logo-img">
                    <span>OpenShelf</span>
                </a>
                <div class="sidebar-badge">
                    <i class="fas fa-shield-alt"></i> Admin Panel
                </div>
            </div>

            <div class="sidebar-menu">
                <div class="menu-section">
                    <div class="menu-title">Main</div>
                    <a href="/admin/dashboard/" class="menu-item <?php echo strpos($currentPath, '/admin/dashboard/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-tachometer-alt"></i>
                        Dashboard
                    </a>
                    <a href="/admin/users/" class="menu-item <?php echo strpos($currentPath, '/admin/users/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        User Management
                    </a>
                    <a href="/admin/books/" class="menu-item <?php echo strpos($currentPath, '/admin/books/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-book"></i>
                        Book Management
                    </a>
                    <a href="/admin/requests/" class="menu-item <?php echo strpos($currentPath, '/admin/requests/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-exchange-alt"></i>
                        Request Management
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-title">Content</div>
                    <a href="/admin/announcements/" class="menu-item <?php echo strpos($currentPath, '/admin/announcements/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-bullhorn"></i>
                        Announcements
                    </a>
                    <a href="/admin/categories.php" class="menu-item <?php echo strpos($currentPath, '/admin/categories/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-tags"></i>
                        Categories
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-title">Analytics</div>
                    <a href="/admin/reports.php" class="menu-item <?php echo strpos($currentPath, '/admin/reports/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-chart-line"></i>
                        Reports & Analytics
                    </a>
                    <a href="/admin/logs.php" class="menu-item <?php echo strpos($currentPath, '/admin/logs.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        System Logs
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-title">System</div>
                    <a href="/admin/profile.php" class="menu-item <?php echo strpos($currentPath, '/admin/profile.php') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-user-circle"></i>
                        My Profile
                    </a>
                    <a href="/admin/backup.php" class="menu-item <?php echo strpos($currentPath, '/admin/backup/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-database"></i>
                        Backup Manager
                    </a>
                    <a href="/logout.php" class="menu-item">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="admin-main">
            <div class="admin-topbar">
                <button class="menu-toggle" id="menuToggle">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="page-title" id="pageTitle"><?php echo $currentPage === 'dashboard.php' ? 'Dashboard' : ucfirst(str_replace('.php', '', $currentPage)); ?></div>
                <div class="topbar-right">
                    <div class="admin-notification" id="adminThemeToggle" title="Toggle Theme" style="display: flex; align-items: center; justify-content: center;">
                        <i class="fas fa-moon"></i>
                    </div>
                    <div class="admin-notification">
                        <i class="far fa-bell"></i>
                        <span class="notification-dot"></span>
                    </div>
                    <div class="admin-user" id="adminUser">
                        <div class="user-avatar">
                            <?php echo strtoupper(substr($adminName, 0, 1)); ?>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo htmlspecialchars($adminName); ?></div>
                            <div class="user-role"><?php echo ucfirst($adminRole); ?></div>
                        </div>
                        <i class="fas fa-chevron-down" style="font-size: 0.7rem; color: #64748b;"></i>
                    </div>
                </div>
            </div>

            <div class="admin-content">
            
<script>
    const adminThemeToggle = document.getElementById('adminThemeToggle');
    if (adminThemeToggle) {
        if (document.documentElement.getAttribute('data-theme') === 'dark') {
            adminThemeToggle.innerHTML = '<i class="fas fa-sun"></i>';
        }
        adminThemeToggle.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const newTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
            adminThemeToggle.innerHTML = newTheme === 'dark' ? '<i class="fas fa-sun"></i>' : '<i class="fas fa-moon"></i>';
        });
    }

    const menuToggleBtn = document.getElementById('menuToggle');
    const adminSidebar = document.getElementById('adminSidebar');
    if (menuToggleBtn && adminSidebar) {
        menuToggleBtn.addEventListener('click', () => {
            adminSidebar.classList.toggle('show');
        });
    }
</script>