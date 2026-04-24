<?php
/**
 * OpenShelf Admin Dashboard
 * Modern dashboard with real-time stats, charts, and quick actions
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__, 2) . '/data/');
define('BOOKS_PATH', dirname(__DIR__, 2) . '/books/');
define('USERS_PATH', dirname(__DIR__, 2) . '/users/');

// Include database connection
require_once dirname(__DIR__, 2) . '/includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';

/**
 * Load recent users for dashboard
 */
function loadRecentUsers($limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Load recent books for dashboard
 */
function loadRecentBooks($limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM books ORDER BY created_at DESC LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Load recent borrow requests for dashboard
 */
function loadRecentRequests($limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM borrow_requests ORDER BY request_date DESC LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get user growth for chart
 */
function getUserGrowth($users, $days = 30) {
    $growth = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $growth[$date] = 0;
    }
    
    foreach ($users as $user) {
        if (!empty($user['created_at'])) {
            $date = date('Y-m-d', strtotime($user['created_at']));
            if (isset($growth[$date])) {
                $growth[$date]++;
            }
        }
    }
    
    return $growth;
}

/**
 * Get book growth for chart
 */
function getBookGrowth($books, $days = 30) {
    $growth = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $growth[$date] = 0;
    }
    
    foreach ($books as $book) {
        if (!empty($book['created_at'])) {
            $date = date('Y-m-d', strtotime($book['created_at']));
            if (isset($growth[$date])) {
                $growth[$date]++;
            }
        }
    }
    
    return $growth;
}

/**
 * Get top categories
 */
function getTopCategories($books, $limit = 5) {
    $categories = [];
    foreach ($books as $book) {
        $cat = $book['category'] ?? 'Uncategorized';
        $categories[$cat] = ($categories[$cat] ?? 0) + 1;
    }
    arsort($categories);
    return array_slice($categories, 0, $limit);
}

/**
 * Get recent activities
 */
function getRecentActivities($users, $books, $requests, $limit = 10) {
    $activities = [];
    
    foreach (array_slice($users, 0, 5) as $user) {
        $activities[] = [
            'type' => 'user_registered',
            'title' => 'New User Registration',
            'description' => $user['name'] . ' (' . $user['email'] . ') joined OpenShelf',
            'user_name' => $user['name'],
            'user_id' => $user['id'],
            'timestamp' => $user['created_at'] ?? date('Y-m-d H:i:s'),
            'icon' => 'fa-user-plus',
            'color' => '#10b981'
        ];
    }
    
    foreach (array_slice($books, 0, 5) as $book) {
        $activities[] = [
            'type' => 'book_added',
            'title' => 'New Book Added',
            'description' => '"' . $book['title'] . '" by ' . $book['author'] . ' added to library',
            'book_title' => $book['title'],
            'book_id' => $book['id'],
            'user_name' => $book['owner_name'] ?? 'Unknown',
            'timestamp' => $book['created_at'] ?? date('Y-m-d H:i:s'),
            'icon' => 'fa-book',
            'color' => '#6366f1'
        ];
    }
    
    foreach (array_slice($requests, 0, 5) as $request) {
        $activities[] = [
            'type' => 'request_' . ($request['status'] ?? 'pending'),
            'title' => ucfirst($request['status'] ?? 'New') . ' Borrow Request',
            'description' => $request['borrower_name'] . ' requested to borrow "' . $request['book_title'] . '"',
            'book_title' => $request['book_title'],
            'user_name' => $request['borrower_name'],
            'timestamp' => $request['request_date'] ?? date('Y-m-d H:i:s'),
            'icon' => $request['status'] === 'approved' ? 'fa-check-circle' : ($request['status'] === 'pending' ? 'fa-clock' : 'fa-times-circle'),
            'color' => $request['status'] === 'approved' ? '#10b981' : ($request['status'] === 'pending' ? '#f59e0b' : '#ef4444')
        ];
    }
    
    usort($activities, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));
    return array_slice($activities, 0, $limit);
}

// Statistics from DB
$db = getDB();

$totalUsers = (int) $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBooks = (int) $db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalRequests = (int) $db->query("SELECT COUNT(*) FROM borrow_requests")->fetchColumn();

$availableBooks = (int) $db->query("SELECT COUNT(*) FROM books WHERE status = 'available'")->fetchColumn();
$borrowedBooks = (int) $db->query("SELECT COUNT(*) FROM books WHERE status = 'borrowed'")->fetchColumn();

$pendingUsers = (int) $db->query("SELECT COUNT(*) FROM users WHERE verified = 0 AND status != 'rejected'")->fetchColumn();
$pendingRequests = (int) $db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'pending'")->fetchColumn();
$approvedRequests = (int) $db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'approved'")->fetchColumn();
$rejectedRequests = (int) $db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'rejected'")->fetchColumn();
$returnedRequests = (int) $db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'returned'")->fetchColumn();

// Growth data from DB (last 30 days only)
function getDailyGrowth($table, $dateColumn, $days = 30) {
    $db = getDB();
    $sql = "SELECT DATE($dateColumn) as date, COUNT(*) as count 
            FROM $table 
            WHERE $dateColumn >= DATE_SUB(CURDATE(), INTERVAL :days DAY)
            GROUP BY DATE($dateColumn)
            ORDER BY date ASC";
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':days', (int)$days, PDO::PARAM_INT);
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    $growth = [];
    for ($i = $days - 1; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $growth[$date] = (int)($results[$date] ?? 0);
    }
    return $growth;
}

$userGrowth = getDailyGrowth('users', 'created_at', 30);
$bookGrowth = getDailyGrowth('books', 'created_at', 30);

// Top categories directly from DB
function getTopCategoriesFromDB($limit = 5) {
    $db = getDB();
    $stmt = $db->prepare("SELECT category, COUNT(*) as count FROM books GROUP BY category ORDER BY count DESC LIMIT :limit");
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
}
$topCategories = getTopCategoriesFromDB();

// Recent activities using limited data
$recentUsers = loadRecentUsers(8);
$recentBooks = loadRecentBooks(8);
$recentRequests = loadRecentRequests(8);
$recentActivities = getRecentActivities($recentUsers, $recentBooks, $recentRequests, 8);

// Calculate growth percentages
$lastMonthUsers = array_sum(array_slice($userGrowth, 0, 30));
$previousMonthUsers = array_sum(array_slice($userGrowth, 30, 30));
$userGrowthPercent = $previousMonthUsers > 0 ? round(($lastMonthUsers - $previousMonthUsers) / $previousMonthUsers * 100) : 0;

$lastMonthBooks = array_sum(array_slice($bookGrowth, 0, 30));
$previousMonthBooks = array_sum(array_slice($bookGrowth, 30, 30));
$bookGrowthPercent = $previousMonthBooks > 0 ? round(($lastMonthBooks - $previousMonthBooks) / $previousMonthBooks * 100) : 0;

// Get greeting based on time
$hour = date('H');
if ($hour < 12) {
    $greeting = 'Good Morning';
} elseif ($hour < 18) {
    $greeting = 'Good Afternoon';
} else {
    $greeting = 'Good Evening';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Dashboard Specific Styles */
        :root {
            --chart-primary: #6366f1;
            --chart-secondary: #a855f7;
            --chart-success: #10b981;
            --chart-warning: #f59e0b;
            --chart-danger: #ef4444;
        }

        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .stat-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: var(--transition);
            border: 1px solid #f1f5f9;
            position: relative;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.05);
            border-color: rgba(99, 102, 241, 0.2);
        }

        .stat-icon {
            width: 70px; height: 70px;
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            font-size: 2rem;
            flex-shrink: 0;
            transition: var(--transition);
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -1px;
            color: var(--text-main);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-change {
            font-size: 0.8rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-weight: 700;
        }

        .trend-up { color: #10b981; }
        .trend-down { color: #ef4444; }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #0f172a 0%, #1e1b4b 50%, #312e81 100%);
            border-radius: 30px;
            padding: 3.5rem;
            margin-bottom: 2.5rem;
            color: white;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px -10px rgba(15, 23, 42, 0.3);
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%; right: -20%;
            width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(99, 102, 241, 0.15) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.75rem;
            letter-spacing: -1.5px;
        }

        .welcome-text {
            font-size: 1.1rem;
            opacity: 0.8;
            max-width: 600px;
            line-height: 1.6;
        }

        .date-badge {
            position: absolute;
            top: 2rem; right: 2rem;
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            padding: 0.6rem 1.25rem;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
            margin-bottom: 2.5rem;
        }

        .chart-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }

        .chart-title {
            font-weight: 800;
            font-size: 1.25rem;
            color: var(--text-main);
            letter-spacing: -0.5px;
        }

        .chart-container {
            height: 320px;
            position: relative;
        }

        /* Quick Actions */
        .quick-actions-section {
            margin-bottom: 3rem;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            color: var(--text-main);
            letter-spacing: -1px;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .action-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            transition: var(--transition);
            border: 1px solid #f1f5f9;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0,0,0,0.05);
            border-color: var(--primary);
            background: var(--bg-body);
        }

        .action-icon {
            width: 56px; height: 56px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 16px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1rem;
            color: white;
            font-size: 1.4rem;
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.3);
        }

        .action-title { font-weight: 700; font-size: 1rem; }
        .action-desc { font-size: 0.8rem; color: var(--text-muted); margin-top: 0.35rem; }

        /* Sidebar for activity */
        .activity-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
        }

        .activity-feed {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid #f1f5f9;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            padding: 1.25rem;
            background: #f8fafc;
            border-radius: 16px;
            transition: var(--transition);
            border: 1px solid transparent;
        }

        .activity-item:hover {
            background: white;
            border-color: #e2e8f0;
            transform: translateX(8px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
        }

        .activity-icon {
            width: 48px; height: 48px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            color: white; font-size: 1.1rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .activity-content { flex: 1; }
        .activity-title { font-weight: 700; font-size: 0.95rem; color: var(--text-main); }
        .activity-desc { font-size: 0.85rem; color: var(--text-muted); margin-top: 0.25rem; }
        .activity-time { font-size: 0.75rem; font-weight: 600; color: #94a3b8; }

        /* Category Card */
        .category-card {
            background: white;
            border-radius: 24px;
            padding: 2rem;
            border: 1px solid #f1f5f9;
        }

        .category-tag {
            background: #f1f5f9;
            padding: 0.6rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
            display: flex; align-items: center; justify-content: space-between;
            margin-bottom: 0.75rem;
            transition: var(--transition);
        }

        .category-tag:hover {
            background: #e2e8f0;
            transform: scale(1.02);
        }

        .category-count {
            background: var(--primary);
            color: white;
            padding: 0.2rem 0.6rem;
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 800;
        }

        @media (max-width: 1200px) {
            .activity-grid { grid-template-columns: 1fr; }
        }

        @media (max-width: 1024px) {
            .charts-row { grid-template-columns: 1fr; }
        }

        @media (max-width: 768px) {
            .welcome-banner { padding: 2rem; }
            .welcome-title { font-size: 1.75rem; }
            .dashboard-stats { grid-template-columns: 1fr; }
            .date-badge { position: static; margin-bottom: 1rem; display: inline-block; }
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/includes/admin-header.php'; ?>

    <div class="admin-content">
        <!-- Welcome Banner -->
        <div class="welcome-banner">
            <div class="date-badge">
                <i class="far fa-calendar-alt"></i> <?php echo date('l, F j, Y'); ?>
            </div>
            <h1 class="welcome-title"><?php echo $greeting; ?>, <?php echo htmlspecialchars($adminName); ?>!</h1>
            <p class="welcome-text">Here's what's happening with OpenShelf today. You have <?php echo $pendingUsers; ?> pending user approvals and <?php echo $pendingRequests; ?> pending requests.</p>
        </div>

        <!-- Stats Grid -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($totalUsers); ?></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-change <?php echo $userGrowthPercent >= 0 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="fas fa-arrow-<?php echo $userGrowthPercent >= 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo abs($userGrowthPercent); ?>% from last month
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <i class="fas fa-book"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($totalBooks); ?></div>
                    <div class="stat-label">Total Books</div>
                    <div class="stat-change <?php echo $bookGrowthPercent >= 0 ? 'trend-up' : 'trend-down'; ?>">
                        <i class="fas fa-arrow-<?php echo $bookGrowthPercent >= 0 ? 'up' : 'down'; ?>"></i>
                        <?php echo abs($bookGrowthPercent); ?>% from last month
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($availableBooks); ?></div>
                    <div class="stat-label">Available Books</div>
                    <div class="stat-change" style="color: #8b5cf6">
                        <i class="fas fa-percent"></i> <?php echo $totalBooks > 0 ? round($availableBooks / $totalBooks * 100) : 0; ?>% of total
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;">
                    <i class="fas fa-hand-holding-heart"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($borrowedBooks); ?></div>
                    <div class="stat-label">Borrowed Books</div>
                    <div class="stat-change text-warning">
                        <i class="fas fa-chart-line"></i> Currently in circulation
                    </div>
                </div>
            </div>
        </div>

        <!-- Second Row Stats -->
        <div class="dashboard-stats">
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($pendingUsers); ?></div>
                    <div class="stat-label">Pending Approvals</div>
                    <div class="stat-change">
                        <a href="/admin/users/?status=pending" style="color: #f59e0b; text-decoration: none;">
                            Review now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                    <i class="fas fa-exchange-alt"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($totalRequests); ?></div>
                    <div class="stat-label">Total Requests</div>
                    <div class="stat-change">
                        <span class="trend-up"><?php echo $approvedRequests; ?> approved</span>
                        <span class="trend-down"> • <?php echo $rejectedRequests; ?> rejected</span>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <i class="fas fa-hourglass-half"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($pendingRequests); ?></div>
                    <div class="stat-label">Pending Requests</div>
                    <div class="stat-change">
                        <a href="/admin/requests/?status=pending" style="color: #f59e0b; text-decoration: none;">
                            Process now <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <i class="fas fa-undo-alt"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($returnedRequests); ?></div>
                    <div class="stat-label">Completed Returns</div>
                    <div class="stat-change text-success">
                        <i class="fas fa-check-double"></i> Successfully completed
                    </div>
                </div>
            </div>
        </div>

        <!-- Charts Row -->
        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📈 User Growth</h3>
                </div>
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📚 Book Activity</h3>
                </div>
                <div class="chart-container">
                    <canvas id="bookGrowthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions-section">
            <h2 class="section-title">⚡ Quick Actions</h2>
            <div class="quick-actions">
                <a href="/admin/users/?status=pending" class="action-card">
                    <div class="action-icon"><i class="fas fa-user-check"></i></div>
                    <div class="action-title">Approve Users</div>
                    <div class="action-desc"><?php echo $pendingUsers; ?> pending approvals</div>
                </a>
                <a href="/admin/books/" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-book"></i></div>
                    <div class="action-title">Manage Books</div>
                    <div class="action-desc"><?php echo $totalBooks; ?> books in library</div>
                </a>
                <a href="/admin/requests/?status=pending" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #f59e0b, #d97706);"><i class="fas fa-exchange-alt"></i></div>
                    <div class="action-title">Review Requests</div>
                    <div class="action-desc"><?php echo $pendingRequests; ?> pending requests</div>
                </a>
                <a href="/admin/announcements/" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #0ea5e9, #0284c7);"><i class="fas fa-bullhorn"></i></div>
                    <div class="action-title">Post Announcement</div>
                    <div class="action-desc">Send update to all users</div>
                </a>
                <a href="/admin/reports/" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #ec4899, #db2777);"><i class="fas fa-chart-pie"></i></div>
                    <div class="action-title">View Reports</div>
                    <div class="action-desc">Analytics & insights</div>
                </a>
                <a href="/admin/backup/" class="action-card">
                    <div class="action-icon" style="background: linear-gradient(135deg, #64748b, #475569);"><i class="fas fa-database"></i></div>
                    <div class="action-title">Backup Data</div>
                    <div class="action-desc">Secure your data</div>
                </a>
            </div>
        </div>

        <div class="activity-grid">
            <div class="activity-feed">
                <div class="chart-header">
                    <h3 class="chart-title">🕐 Recent Activity</h3>
                    <a href="/admin/logs/" style="font-size: 0.85rem; font-weight: 600; color: var(--primary); text-decoration: none;">View all</a>
                </div>
                <div class="activity-list">
                    <?php foreach ($recentActivities as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon" style="background: <?php echo $activity['color']; ?>20; color: <?php echo $activity['color']; ?>;">
                                <i class="fas <?php echo $activity['icon']; ?>"></i>
                            </div>
                            <div class="activity-content">
                                <div class="activity-title"><?php echo $activity['title']; ?></div>
                                <div class="activity-desc"><?php echo htmlspecialchars($activity['description']); ?></div>
                            </div>
                            <div class="activity-time">
                                <?php
                                $time = strtotime($activity['timestamp']);
                                $diff = time() - $time;
                                if ($diff < 60) echo 'Just now';
                                elseif ($diff < 3600) echo floor($diff / 60) . 'm ago';
                                elseif ($diff < 86400) echo floor($diff / 3600) . 'h ago';
                                else echo date('M j', $time);
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="category-card">
                <div class="chart-header">
                    <h3 class="chart-title">🏷️ Top Categories</h3>
                </div>
                <div class="categories-list">
                    <?php if (empty($topCategories)): ?>
                        <p style="color: #64748b;">No categories data available yet.</p>
                    <?php else: ?>
                        <?php foreach ($topCategories as $category => $count): ?>
                            <div class="category-tag">
                                <span><?php echo htmlspecialchars($category); ?></span>
                                <span class="category-count"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Set chart default font to Outfit
        Chart.defaults.font.family = "'Outfit', sans-serif";
        Chart.defaults.color = '#64748b';

        // User Growth Chart
        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_keys($userGrowth)); ?>.map(d => {
                    const date = new Date(d);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'New Users',
                    data: <?php echo json_encode(array_values($userGrowth)); ?>,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#ffffff',
                    pointBorderColor: '#6366f1',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6,
                    pointHoverBackgroundColor: '#6366f1'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { 
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 12,
                        titleFont: { size: 14, weight: 'bold' }
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f5f9' },
                        ticks: { stepSize: 1 }
                    },
                    x: { 
                        grid: { display: false }
                    }
                }
            }
        });
        
        // Book Growth Chart
        new Chart(document.getElementById('bookGrowthChart'), {
            type: 'bar',
            data: {
                labels: <?php echo json_encode(array_keys($userGrowth)); ?>.map(d => {
                    const date = new Date(d);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'New Books',
                    data: <?php echo json_encode(array_values($bookGrowth)); ?>,
                    backgroundColor: '#10b981',
                    borderRadius: 12,
                    barPercentage: 0.5
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { 
                        backgroundColor: '#0f172a',
                        padding: 12,
                        cornerRadius: 12
                    }
                },
                scales: {
                    y: { 
                        beginAtZero: true, 
                        grid: { color: '#f1f5f9' },
                        ticks: { stepSize: 1 }
                    },
                    x: { 
                        grid: { display: false }
                    }
                }
            }
        });
    </script>

    <?php include dirname(__DIR__, 2) . '/includes/admin-footer.php'; ?>
</body>
</html>