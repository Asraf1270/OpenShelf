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

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';

/**
 * Load all users from master JSON
 */
function loadAllUsers() {
    $usersFile = DATA_PATH . 'users.json';
    if (!file_exists($usersFile)) {
        return [];
    }
    return json_decode(file_get_contents($usersFile), true) ?? [];
}

/**
 * Load all books from master JSON
 */
function loadAllBooks() {
    $booksFile = DATA_PATH . 'books.json';
    if (!file_exists($booksFile)) {
        return [];
    }
    return json_decode(file_get_contents($booksFile), true) ?? [];
}

/**
 * Load all borrow requests
 */
function loadAllRequests() {
    $requestsFile = DATA_PATH . 'borrow_requests.json';
    if (!file_exists($requestsFile)) {
        return [];
    }
    return json_decode(file_get_contents($requestsFile), true) ?? [];
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

// Load data
$users = loadAllUsers();
$books = loadAllBooks();
$requests = loadAllRequests();

// Statistics
$totalUsers = count($users);
$totalBooks = count($books);
$totalRequests = count($requests);
$availableBooks = count(array_filter($books, fn($b) => ($b['status'] ?? '') === 'available'));
$borrowedBooks = count(array_filter($books, fn($b) => ($b['status'] ?? '') === 'borrowed'));
$pendingUsers = count(array_filter($users, fn($u) => !($u['verified'] ?? false)));
$pendingRequests = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'pending'));
$approvedRequests = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'approved'));
$rejectedRequests = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'rejected'));
$returnedRequests = count(array_filter($requests, fn($r) => ($r['status'] ?? '') === 'returned'));

// Growth data
$userGrowth = getUserGrowth($users, 30);
$bookGrowth = getBookGrowth($books, 30);
$topCategories = getTopCategories($books);
$recentActivities = getRecentActivities($users, $books, $requests, 8);

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
        .dashboard-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--accent));
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.8rem;
        }

        .stat-info {
            flex: 1;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            line-height: 1.2;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
            margin-top: 0.25rem;
        }

        .stat-change {
            font-size: 0.75rem;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .trend-up {
            color: #10b981;
        }

        .trend-down {
            color: #ef4444;
        }

        /* Charts Row */
        .charts-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .chart-card {
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .chart-title {
            font-weight: 600;
            font-size: 1rem;
        }

        .chart-container {
            height: 280px;
            position: relative;
        }

        /* Quick Actions */
        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: white;
            border-radius: 1rem;
            padding: 1rem;
            text-align: center;
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            text-decoration: none;
            color: inherit;
        }

        .action-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            border-color: #6366f1;
        }

        .action-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 0.75rem;
            color: white;
            font-size: 1.2rem;
        }

        .action-title {
            font-weight: 600;
            font-size: 0.9rem;
        }

        .action-desc {
            font-size: 0.7rem;
            color: #64748b;
            margin-top: 0.25rem;
        }

        /* Categories */
        .categories-list {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .category-tag {
            background: #f1f5f9;
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .category-count {
            background: #6366f1;
            color: white;
            padding: 0.15rem 0.5rem;
            border-radius: 1rem;
            font-size: 0.7rem;
        }

        /* Activity Feed */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 1rem;
            transition: all 0.2s ease;
        }

        .activity-item:hover {
            background: #f1f5f9;
            transform: translateX(4px);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .activity-content {
            flex: 1;
        }

        .activity-title {
            font-weight: 600;
            font-size: 0.85rem;
        }

        .activity-desc {
            font-size: 0.75rem;
            color: #64748b;
            margin-top: 0.2rem;
        }

        .activity-time {
            font-size: 0.7rem;
            color: #94a3b8;
            white-space: nowrap;
        }

        /* Welcome Banner */
        .welcome-banner {
            background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
            border-radius: 1.5rem;
            padding: 2rem;
            margin-bottom: 2rem;
            color: white;
            position: relative;
            overflow: hidden;
        }

        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .welcome-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            position: relative;
        }

        .welcome-text {
            opacity: 0.9;
            position: relative;
        }

        .date-badge {
            position: absolute;
            top: 2rem;
            right: 2rem;
            background: rgba(255,255,255,0.2);
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-size: 0.8rem;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .charts-row {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .dashboard-stats {
                grid-template-columns: repeat(2, 1fr);
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1rem;
            }
            
            .stat-value {
                font-size: 1.5rem;
            }
            
            .welcome-banner {
                padding: 1.5rem;
            }
            
            .date-badge {
                position: static;
                margin-top: 1rem;
                display: inline-block;
            }
        }

        @media (max-width: 480px) {
            .dashboard-stats {
                grid-template-columns: 1fr;
            }
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
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo number_format($availableBooks); ?></div>
                    <div class="stat-label">Available Books</div>
                    <div class="stat-change text-success">
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
                        <span class="text-success"><?php echo $approvedRequests; ?> approved</span>
                        <span class="text-danger"> • <?php echo $rejectedRequests; ?> rejected</span>
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
                    <h3 class="chart-title">📈 User Growth (Last 30 Days)</h3>
                    <i class="fas fa-chart-line" style="color: #6366f1;"></i>
                </div>
                <div class="chart-container">
                    <canvas id="userGrowthChart"></canvas>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">📚 Book Growth (Last 30 Days)</h3>
                    <i class="fas fa-chart-bar" style="color: #10b981;"></i>
                </div>
                <div class="chart-container">
                    <canvas id="bookGrowthChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions">
            <a href="/admin/users/?status=pending" class="action-card">
                <div class="action-icon"><i class="fas fa-user-check"></i></div>
                <div class="action-title">Approve Users</div>
                <div class="action-desc"><?php echo $pendingUsers; ?> pending approvals</div>
            </a>
            <a href="/admin/books/" class="action-card">
                <div class="action-icon"><i class="fas fa-book"></i></div>
                <div class="action-title">Manage Books</div>
                <div class="action-desc"><?php echo $totalBooks; ?> books in library</div>
            </a>
            <a href="/admin/requests/?status=pending" class="action-card">
                <div class="action-icon"><i class="fas fa-exchange-alt"></i></div>
                <div class="action-title">Review Requests</div>
                <div class="action-desc"><?php echo $pendingRequests; ?> pending requests</div>
            </a>
            <a href="/admin/announcements/" class="action-card">
                <div class="action-icon"><i class="fas fa-bullhorn"></i></div>
                <div class="action-title">Post Announcement</div>
                <div class="action-desc">Send update to all users</div>
            </a>
            <a href="/admin/reports/" class="action-card">
                <div class="action-icon"><i class="fas fa-chart-pie"></i></div>
                <div class="action-title">View Reports</div>
                <div class="action-desc">Analytics & insights</div>
            </a>
            <a href="/admin/backup/" class="action-card">
                <div class="action-icon"><i class="fas fa-database"></i></div>
                <div class="action-title">Backup Data</div>
                <div class="action-desc">Secure your data</div>
            </a>
        </div>

        <!-- Bottom Section -->
        <div class="charts-row">
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">🏷️ Top Categories</h3>
                    <i class="fas fa-tags" style="color: #f59e0b;"></i>
                </div>
                <div class="categories-list">
                    <?php if (empty($topCategories)): ?>
                        <p style="color: #64748b;">No categories data available yet.</p>
                    <?php else: ?>
                        <?php foreach ($topCategories as $category => $count): ?>
                            <div class="category-tag">
                                <?php echo htmlspecialchars($category); ?>
                                <span class="category-count"><?php echo $count; ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="chart-card">
                <div class="chart-header">
                    <h3 class="chart-title">🕐 Recent Activity</h3>
                    <a href="/admin/logs/" style="font-size: 0.7rem; color: #6366f1;">View all</a>
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
                                elseif ($diff < 3600) echo floor($diff / 60) . ' min ago';
                                elseif ($diff < 86400) echo floor($diff / 3600) . ' hours ago';
                                else echo date('M j', $time);
                                ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // User Growth Chart Data
        const userGrowthLabels = <?php echo json_encode(array_keys($userGrowth)); ?>;
        const userGrowthData = <?php echo json_encode(array_values($userGrowth)); ?>;
        
        // Book Growth Chart Data
        const bookGrowthData = <?php echo json_encode(array_values($bookGrowth)); ?>;
        
        // User Growth Chart
        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: {
                labels: userGrowthLabels.map(d => {
                    const date = new Date(d);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'New Users',
                    data: userGrowthData,
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 2,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#ffffff',
                    pointBorderWidth: 2,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
        
        // Book Growth Chart
        new Chart(document.getElementById('bookGrowthChart'), {
            type: 'bar',
            data: {
                labels: userGrowthLabels.map(d => {
                    const date = new Date(d);
                    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
                }),
                datasets: [{
                    label: 'New Books',
                    data: bookGrowthData,
                    backgroundColor: 'rgba(16, 185, 129, 0.7)',
                    borderRadius: 8,
                    barPercentage: 0.6,
                    categoryPercentage: 0.8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { mode: 'index', intersect: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#e2e8f0' } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>

    <?php include dirname(__DIR__, 2) . '/includes/admin-footer.php'; ?>
</body>
</html>