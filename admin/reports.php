<?php
session_start();
/**
 * OpenShelf Admin Reports
 * Analytics and reports dashboard
 */

define('DATA_PATH', dirname(__DIR__) . '/data/');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

/**
 * Get all users from DB
 */
function loadAllUsers() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM users");
    return $stmt->fetchAll();
}

/**
 * Get all books from DB
 */
function loadAllBooks() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM books");
    return $stmt->fetchAll();
}

/**
 * Get all requests from DB
 */
function loadAllRequests() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM borrow_requests");
    return $stmt->fetchAll();
}

/**
 * Get monthly stats from DB
 */
function getMonthlyStatsFromDB($table, $dateField, $months = 6) {
    $db = getDB();
    $stats = [];
    for ($i = $months - 1; $i >= 0; $i--) {
        $date = date('Y-m', strtotime("-$i months"));
        $stats[$date] = 0;
    }
    
    $startDate = date('Y-m-01', strtotime("-".($months-1)." months"));
    $stmt = $db->prepare("SELECT DATE_FORMAT($dateField, '%Y-%m') as month, COUNT(*) as count 
                          FROM $table 
                          WHERE $dateField >= ? 
                          GROUP BY month");
    $stmt->execute([$startDate]);
    $results = $stmt->fetchAll();
    
    foreach ($results as $row) {
        if (isset($stats[$row['month']])) {
            $stats[$row['month']] = (int)$row['count'];
        }
    }
    return $stats;
}

/**
 * Get top books by borrow count from DB
 */
function getTopBooksFromDB($limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("SELECT book_title as title, book_author as author, COUNT(*) as borrow_count 
                          FROM borrow_requests 
                          WHERE status = 'approved' 
                          GROUP BY book_id 
                          ORDER BY borrow_count DESC 
                          LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

/**
 * Get user activity stats from DB
 */
function getUserActivityStatsFromDB() {
    $db = getDB();
    $stats = [];
    
    $stmt = $db->query("SELECT COUNT(*) FROM users");
    $stats['total'] = (int)$stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE last_login > ?");
    $stmt->execute([date('Y-m-d 00:00:00')]);
    $stats['today'] = (int)$stmt->fetchColumn();
    
    $stmt->execute([date('Y-m-d H:i:s', strtotime('-7 days'))]);
    $stats['this_week'] = (int)$stmt->fetchColumn();
    
    $stmt->execute([date('Y-m-d H:i:s', strtotime('-30 days'))]);
    $stats['this_month'] = (int)$stmt->fetchColumn();
    
    return $stats;
}

$db = getDB();
$userGrowth = getMonthlyStatsFromDB('users', 'created_at');
$bookGrowth = getMonthlyStatsFromDB('books', 'created_at');
$topBooks = getTopBooksFromDB();
$userActivity = getUserActivityStatsFromDB();

$totalUsers = $userActivity['total'];
$totalBooks = (int)$db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$totalRequests = (int)$db->query("SELECT COUNT(*) FROM borrow_requests")->fetchColumn();
$pendingUsers = (int)$db->query("SELECT COUNT(*) FROM users WHERE verified = 0")->fetchColumn();
$pendingRequests = (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'pending'")->fetchColumn();

$reportType = $_GET['type'] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - OpenShelf Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    :root {
        --primary: #2C3E50;
        --secondary: #4C9F8A;
        --accent: #3A7B6B;
        --bg: #F8F9FA;
        --surface: #ffffff;
        --border: #E2E8F0;
        --text-main: #0F172A;
        --text-muted: #5A6C7D;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
        --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.05);
        --radius-lg: 24px;
        --radius-md: 16px;
    }

    [data-theme="dark"] {
        --bg: #0F172A;
        --surface: #1E293B;
        --border: #334155;
        --text-main: #F8F9FA;
        --text-muted: #94A3B8;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.2);
        --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.3);
    }

    body {
        background: var(--bg);
        color: var(--text-main);
        transition: background 0.3s ease;
    }

    .reports-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .stat-card {
        background: var(--surface);
        padding: 1.75rem;
        border-radius: var(--radius-md);
        text-align: center;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
        transition: transform 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
    }

    .stat-value {
        font-size: 2.5rem;
        font-weight: 850;
        letter-spacing: -1.5px;
        margin-bottom: 0.25rem;
    }

    .stat-label {
        color: var(--text-muted);
        font-weight: 700;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .chart-container {
        background: var(--surface);
        padding: 2rem;
        border-radius: var(--radius-lg);
        margin-bottom: 2rem;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
    }

    .chart-header {
        margin-bottom: 1.5rem;
        font-weight: 800;
        font-size: 1.25rem;
        letter-spacing: -0.5px;
    }

    .top-books {
        background: var(--surface);
        padding: 2rem;
        border-radius: var(--radius-lg);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--border);
    }

    .book-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1rem 0;
        border-bottom: 1px solid var(--border);
    }

    .book-item:last-child {
        border-bottom: none;
    }

    .book-title {
        font-weight: 700;
        color: var(--text-main);
    }

    .book-count {
        background: var(--primary);
        color: white;
        padding: 0.35rem 0.85rem;
        border-radius: 2rem;
        font-size: 0.75rem;
        font-weight: 700;
    }

    .tabs {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .tab-btn {
        padding: 0.75rem 1.5rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 2rem;
        cursor: pointer;
        text-decoration: none;
        color: var(--text-muted);
        font-weight: 700;
        transition: all 0.3s ease;
    }

    .tab-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 8px 16px rgba(44, 62, 80, 0.2);
    }

    .export-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        background: var(--secondary);
        color: white;
        border-radius: 2rem;
        text-decoration: none;
        font-weight: 700;
        box-shadow: 0 8px 16px rgba(76, 159, 138, 0.2);
        transition: all 0.3s ease;
    }

    .export-btn:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: repeat(2, 1fr);
        }
        .stat-value { font-size: 1.75rem; }
    }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/admin-header.php'; ?>
    
    <main>
        <div class="reports-page">
            <div class="flex justify-between items-center" style="margin-bottom: 1.5rem;">
                <h1 style="font-size: 1.75rem; font-weight: 700;">Reports & Analytics</h1>
                <a href="/admin/reports/export.php" class="export-btn">
                    <i class="fas fa-download"></i> Export Data
                </a>
            </div>
            
            <div class="tabs">
                <a href="?type=overview" class="tab-btn <?php echo $reportType === 'overview' ? 'active' : ''; ?>">Overview</a>
                <a href="?type=users" class="tab-btn <?php echo $reportType === 'users' ? 'active' : ''; ?>">Users</a>
                <a href="?type=books" class="tab-btn <?php echo $reportType === 'books' ? 'active' : ''; ?>">Books</a>
                <a href="?type=requests" class="tab-btn <?php echo $reportType === 'requests' ? 'active' : ''; ?>">Requests</a>
            </div>
            
            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--primary);"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--secondary);"><?php echo $totalBooks; ?></div>
                    <div class="stat-label">Total Books</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #f59e0b;"><?php echo $totalRequests; ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #ef4444;"><?php echo $pendingUsers; ?></div>
                    <div class="stat-label">Pending Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: var(--accent);"><?php echo $pendingRequests; ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value" style="color: #06b6d4;"><?php echo $userActivity['today']; ?></div>
                    <div class="stat-label">Active Today</div>
                </div>
            </div>
            
            <!-- Charts -->
            <div class="chart-container">
                <h3 style="margin-bottom: 1rem;">User Growth (Last 6 Months)</h3>
                <canvas id="userGrowthChart"></canvas>
            </div>
            
            <div class="chart-container">
                <h3 style="margin-bottom: 1rem;">Book Growth (Last 6 Months)</h3>
                <canvas id="bookGrowthChart"></canvas>
            </div>
            
            <div class="top-books">
                <h3 style="margin-bottom: 1rem;">Most Borrowed Books</h3>
                <?php if (empty($topBooks)): ?>
                    <p>No borrowing data yet.</p>
                <?php else: ?>
                    <?php foreach ($topBooks as $book): ?>
                        <div class="book-item">
                            <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                            <div class="book-count"><?php echo $book['borrow_count']; ?> borrows</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <script>
        const userGrowthData = <?php echo json_encode(array_values($userGrowth)); ?>;
        const userGrowthLabels = <?php echo json_encode(array_keys($userGrowth)); ?>;
        const bookGrowthData = <?php echo json_encode(array_values($bookGrowth)); ?>;
        
        new Chart(document.getElementById('userGrowthChart'), {
            type: 'line',
            data: {
                labels: userGrowthLabels,
                datasets: [{
                    label: 'New Users',
                    data: userGrowthData,
                    borderColor: '#2C3E50',
                    backgroundColor: 'rgba(44, 62, 80, 0.05)',
                    fill: true,
                    tension: 0.4,
                    pointRadius: 4,
                    pointBackgroundColor: '#2C3E50'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
        
        new Chart(document.getElementById('bookGrowthChart'), {
            type: 'bar',
            data: {
                labels: userGrowthLabels,
                datasets: [{
                    label: 'New Books',
                    data: bookGrowthData,
                    backgroundColor: '#4C9F8A',
                    borderRadius: 12,
                    hoverBackgroundColor: '#3A7B6B'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
    
    <?php include dirname(__DIR__) . '/includes/admin-footer.php'; ?>
</body>
</html>