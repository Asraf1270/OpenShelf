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
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Admin Panel Styles */
        :root {
            --admin-primary: #6366f1;
            --admin-primary-dark: #4f46e5;
            --admin-sidebar-bg: #0f172a;
            --admin-sidebar-hover: #1e293b;
            --admin-header-bg: #ffffff;
            --admin-text: #f1f5f9;
            --admin-text-muted: #94a3b8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: system-ui, -apple-system, 'Inter', sans-serif;
            background: #f1f5f9;
            overflow-x: hidden;
        }

        /* Admin Wrapper */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }

        /* Sidebar */
        .admin-sidebar {
            width: 280px;
            background: var(--admin-sidebar-bg);
            color: var(--admin-text);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
            transition: transform 0.3s ease;
            z-index: 100;
        }

        .sidebar-header {
            padding: 1.5rem;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .sidebar-logo {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .sidebar-logo i {
            color: var(--admin-primary);
        }

        .sidebar-logo span {
            background: linear-gradient(135deg, #ffffff, #a5b4fc);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .sidebar-badge {
            background: rgba(99, 102, 241, 0.2);
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            margin-top: 0.5rem;
            display: inline-block;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            padding: 1.5rem 0;
        }

        .menu-section {
            margin-bottom: 1.5rem;
        }

        .menu-title {
            padding: 0 1.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--admin-text-muted);
            margin-bottom: 0.75rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1.5rem;
            color: var(--admin-text-muted);
            text-decoration: none;
            transition: all 0.2s ease;
            border-left: 3px solid transparent;
        }

        .menu-item i {
            width: 20px;
            font-size: 1rem;
        }

        .menu-item:hover {
            background: var(--admin-sidebar-hover);
            color: white;
        }

        .menu-item.active {
            background: rgba(99, 102, 241, 0.2);
            color: white;
            border-left-color: var(--admin-primary);
        }

        .menu-badge {
            margin-left: auto;
            background: #ef4444;
            color: white;
            padding: 0.15rem 0.4rem;
            border-radius: 1rem;
            font-size: 0.7rem;
        }

        /* Main Content */
        .admin-main {
            flex: 1;
            margin-left: 280px;
            min-height: 100vh;
        }

        /* Top Header */
        .admin-topbar {
            background: var(--admin-header-bg);
            padding: 0.75rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 99;
        }

        .menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.2rem;
            cursor: pointer;
            color: var(--admin-text-muted);
        }

        .page-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #0f172a;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .admin-notification {
            position: relative;
            cursor: pointer;
        }

        .admin-notification i {
            font-size: 1.2rem;
            color: #64748b;
        }

        .notification-dot {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 8px;
            height: 8px;
            background: #ef4444;
            border-radius: 50%;
        }

        .admin-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            padding: 0.25rem 0.5rem;
            border-radius: 2rem;
            transition: background 0.2s;
        }

        .admin-user:hover {
            background: #f1f5f9;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, var(--admin-primary), var(--admin-primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .user-info {
            display: none;
        }

        .user-name {
            font-weight: 600;
            font-size: 0.9rem;
            color: #0f172a;
        }

        .user-role {
            font-size: 0.7rem;
            color: #64748b;
        }

        /* Responsive */
        @media (min-width: 768px) {
            .user-info {
                display: block;
            }
        }

        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
            }
            .menu-toggle {
                display: block;
            }
        }

        /* Content Area */
        .admin-content {
            padding: 2rem;
        }

        @media (max-width: 768px) {
            .admin-content {
                padding: 1rem;
            }
        }

        /* Cards */
        .admin-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }

        .admin-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .admin-card-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        /* Buttons */
        .btn-admin {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-admin-primary {
            background: var(--admin-primary);
            color: white;
        }

        .btn-admin-primary:hover {
            background: var(--admin-primary-dark);
            transform: translateY(-1px);
        }

        /* Form Controls */
        .form-control-admin {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            font-size: 0.9rem;
        }

        .form-control-admin:focus {
            outline: none;
            border-color: var(--admin-primary);
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="/admin/dashboard/" class="sidebar-logo">
                    <i class="fas fa-book-open"></i>
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
                    <a href="/admin/logs/" class="menu-item <?php echo strpos($currentPath, '/admin/logs/') !== false ? 'active' : ''; ?>">
                        <i class="fas fa-history"></i>
                        System Logs
                    </a>
                </div>

                <div class="menu-section">
                    <div class="menu-title">System</div>
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