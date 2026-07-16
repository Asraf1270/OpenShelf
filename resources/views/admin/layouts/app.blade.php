<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'OpenShelf Admin')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('assets/images/logo-icon.svg') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary: #2C3E50;
            --primary-dark: #1a252f;
            --secondary: #4C9F8A;
            --sidebar-bg: #0f172a;
            --sidebar-hover: rgba(255, 255, 255, 0.05);
            --header-bg: rgba(255, 255, 255, 0.8);
            --text-main: #0f172a;
            --text-muted: #64748b;
            --bg-body: #f8fafc;
            --surface: #ffffff;
            --border: #e2e8f0;
            --radius-lg: 20px;
            --radius-md: 14px;
            --transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        :root[data-theme="dark"] {
            --primary: #4C9F8A;
            --primary-dark: #2C3E50;
            --header-bg: rgba(15, 23, 42, 0.8);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --bg-body: #0f172a;
            --surface: #1e293b;
            --border: #334155;
        }

        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: 'Outfit', system-ui, sans-serif;
            background: var(--bg-body);
            color: var(--text-main);
        }
        a { color: inherit; }
        .admin-wrapper { display: flex; min-height: 100vh; }
        .admin-sidebar {
            width: 290px;
            background: var(--sidebar-bg);
            color: #f1f5f9;
            position: fixed;
            inset: 0 auto 0 0;
            overflow-y: auto;
            z-index: 1000;
            padding: 1.5rem 1rem;
            transition: var(--transition);
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
        }
        .sidebar-logo img { width: 40px; height: 40px; border-radius: 12px; }
        .sidebar-badge {
            background: rgba(44, 62, 80, 0.15);
            color: #4C9F8A;
            padding: 0.35rem 1rem;
            border-radius: 999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-top: 1rem;
            display: inline-block;
        }
        .menu-section { margin-bottom: 2rem; }
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
            border-radius: 14px;
            font-weight: 500;
            transition: var(--transition);
        }
        .menu-item:hover,
        .menu-item.active {
            background: var(--primary);
            color: white;
        }
        .admin-main {
            flex: 1;
            margin-left: 290px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .admin-topbar {
            background: var(--header-bg);
            backdrop-filter: blur(12px);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border);
            position: sticky;
            top: 0;
            z-index: 900;
        }
        .menu-toggle,
        .topbar-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            border: 1px solid var(--border);
            background: var(--surface);
            color: var(--text-muted);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }
        .menu-toggle { display: none; }
        .page-title { font-size: 1.25rem; font-weight: 700; }
        .topbar-right { display: flex; align-items: center; gap: 1rem; }
        .admin-user {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.5rem 0.75rem;
            border-radius: 16px;
            border: 1px solid transparent;
            background: transparent;
        }
        .admin-user:hover { background: var(--surface); border-color: var(--border); }
        .user-avatar {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
        }
        .user-name { font-weight: 700; }
        .user-role { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; }
        .admin-content { padding: 2rem; flex: 1; }
        .status-badge {
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            padding: 0.4rem 0.85rem;
            font-size: 0.78rem;
            font-weight: 700;
        }
        .bulk-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
            padding: 1rem 1.25rem;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 16px;
        }
        .hidden { display: none !important; }
        .btn-admin {
            padding: 0.75rem 1.5rem;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            border: none;
            font-size: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        .btn-admin-primary { background: var(--primary); color: white; }
        .btn-outline {
            background: transparent;
            color: var(--text-main);
            border: 1px solid var(--border);
        }
        .form-control-admin {
            width: 100%;
            background: var(--surface);
            color: var(--text-main);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            font: inherit;
        }
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.65);
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            z-index: 1500;
        }
        .modal.active { display: flex; }
        .modal-content {
            width: 100%;
            max-width: 540px;
            background: var(--surface);
            color: var(--text-main);
            border-radius: 24px;
            overflow: hidden;
            border: 1px solid var(--border);
        }
        .modal-header,
        .modal-footer {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid var(--border);
        }
        .modal-footer {
            border-bottom: none;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: flex-end;
            gap: 0.75rem;
        }
        .modal-body { padding: 1.5rem; }
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-success { background: rgba(16,185,129,0.1); color: #10b981; }
        .alert-error { background: rgba(239,68,68,0.1); color: #ef4444; }
        @media (max-width: 992px) {
            .admin-sidebar { transform: translateX(-100%); }
            .admin-sidebar.show { transform: translateX(0); }
            .admin-main { margin-left: 0; }
            .menu-toggle { display: inline-flex; }
            .admin-topbar { padding: 1rem; }
        }
        @media (max-width: 640px) {
            .admin-content { padding: 1rem; }
            .user-info { display: none; }
            .bulk-bar { flex-direction: column; align-items: stretch; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="admin-wrapper">
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
                    <img src="{{ asset('assets/images/logo-icon.svg') }}" alt="OpenShelf">
                    <span>OpenShelf</span>
                </a>
                <div class="sidebar-badge">
                    <i class="fas fa-shield-alt"></i> Admin Panel
                </div>
            </div>

            <div class="menu-section">
                <div class="menu-title">Main</div>
                <a href="{{ route('admin.dashboard') }}" class="menu-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="{{ route('admin.users.index') }}" class="menu-item {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <i class="fas fa-users"></i> User Management
                </a>
                <a href="{{ route('admin.books.index') }}" class="menu-item {{ request()->routeIs('admin.books.*') ? 'active' : '' }}">
                    <i class="fas fa-book"></i> Book Management
                </a>
                <a href="{{ route('admin.requests.index') }}" class="menu-item {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
                    <i class="fas fa-exchange-alt"></i> Request Management
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-title">Content</div>
                <a href="{{ route('admin.announcements.index') }}" class="menu-item {{ request()->routeIs('admin.announcements.*') ? 'active' : '' }}">
                    <i class="fas fa-bullhorn"></i> Announcements
                </a>
                <a href="{{ route('admin.categories.index') }}" class="menu-item {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="fas fa-tags"></i> Categories
                </a>
                <a href="{{ route('admin.contact-messages.index') }}" class="menu-item {{ request()->routeIs('admin.contact-messages.*') ? 'active' : '' }}">
                    <i class="fas fa-envelope-open-text"></i> Contact Messages
                </a>
                <a href="{{ route('admin.reports-management.index') }}" class="menu-item {{ request()->routeIs('admin.reports-management.*') ? 'active' : '' }}">
                    <i class="fas fa-flag"></i> Reports Management
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-title">Finance</div>
                <a href="{{ route('admin.support-us.index') }}" class="menu-item {{ request()->routeIs('admin.support-us.*') ? 'active' : '' }}">
                    <i class="fas fa-hand-holding-dollar"></i> Support Requests
                </a>
                <a href="{{ route('admin.transactions.index') }}" class="menu-item {{ request()->routeIs('admin.transactions.*') ? 'active' : '' }}">
                    <i class="fas fa-receipt"></i> Transactions
                </a>
            </div>

            <div class="menu-section">
                <div class="menu-title">System</div>
                <a href="{{ route('admin.reports.index') }}" class="menu-item {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}">
                    <i class="fas fa-chart-bar"></i> Reports & Analytics
                </a>
                <a href="{{ route('admin.backup.index') }}" class="menu-item {{ request()->routeIs('admin.backup.*') ? 'active' : '' }}">
                    <i class="fas fa-database"></i> Backup
                </a>
                <a href="{{ route('admin.logs.index') }}" class="menu-item {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                    <i class="fas fa-clipboard-list"></i> System Logs
                </a>
                <a href="{{ route('admin.profile') }}" class="menu-item {{ request()->routeIs('admin.profile') ? 'active' : '' }}">
                    <i class="fas fa-user-circle"></i> My Profile
                </a>
            </div>
        </aside>

        <main class="admin-main">
            <div class="admin-topbar">
                <div style="display:flex;align-items:center;gap:1rem;">
                    <button class="menu-toggle" id="menuToggle" type="button">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div class="page-title">@yield('page_title', 'Admin')</div>
                </div>
                <div class="topbar-right">
                    <button class="topbar-icon" id="adminThemeToggle" type="button" title="Toggle Theme">
                        <i class="fas fa-moon"></i>
                    </button>
                    <div class="admin-user">
                        <div class="user-avatar">{{ strtoupper(substr($admin->name ?? 'A', 0, 1)) }}</div>
                        <div class="user-info">
                            <div class="user-name">{{ $admin->name ?? 'Admin' }}</div>
                            <div class="user-role">{{ $admin->role ?? 'admin' }}</div>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button class="topbar-icon" type="submit" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </form>
                </div>
            </div>

            <div class="admin-content">
                @if (session('success'))
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    <script>
        const savedTheme = localStorage.getItem('theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);

        const sidebar = document.getElementById('adminSidebar');
        document.getElementById('menuToggle')?.addEventListener('click', () => {
            sidebar?.classList.toggle('show');
        });

        document.getElementById('adminThemeToggle')?.addEventListener('click', () => {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const nextTheme = currentTheme === 'dark' ? 'light' : 'dark';
            document.documentElement.setAttribute('data-theme', nextTheme);
            localStorage.setItem('theme', nextTheme);
            document.querySelector('#adminThemeToggle i')?.classList.toggle('fa-sun', nextTheme === 'dark');
            document.querySelector('#adminThemeToggle i')?.classList.toggle('fa-moon', nextTheme !== 'dark');
        });

        if (savedTheme === 'dark') {
            document.querySelector('#adminThemeToggle i')?.classList.replace('fa-moon', 'fa-sun');
        }

        window.addEventListener('click', (event) => {
            if (window.innerWidth > 992) return;
            if (!sidebar?.classList.contains('show')) return;
            if (event.target.closest('#adminSidebar') || event.target.closest('#menuToggle')) return;
            sidebar.classList.remove('show');
        });

        function closeModal(modalId) {
            document.getElementById(modalId)?.classList.remove('active');
        }

        window.closeModal = closeModal;
    </script>
    @stack('scripts')
</body>
</html>
