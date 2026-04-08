<?php
/**
 * OpenShelf My Borrowed Books Page
 * Focused on mobile-first design, active borrows, and easy returns.
 */

session_start();

// Configuration
require_once dirname(__DIR__) . '/includes/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/my-borrowed/';
    header('Location: /login/');
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'Guest';

/**
 * Fetch active borrowed books for the current user
 * Status 'approved' or 'borrowed' and not yet returned (returned_at IS NULL)
 */
function getActiveBorrows($userId) {
    $db = getDB();
    $sql = "SELECT br.*, b.cover_image, b.author as book_author, b.title as book_title
            FROM borrow_requests br
            JOIN books b ON br.book_id = b.id
            WHERE br.borrower_id = :user_id 
            AND br.status IN ('approved', 'borrowed')
            AND br.returned_at IS NULL
            ORDER BY br.request_date DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}

/**
 * Fetch past borrowed books for the current user
 */
function getPastBorrows($userId) {
    $db = getDB();
    $sql = "SELECT br.*, b.cover_image, b.author as book_author, b.title as book_title
            FROM borrow_requests br
            JOIN books b ON br.book_id = b.id
            WHERE br.borrower_id = :user_id 
            AND br.status = 'returned'
            ORDER BY br.returned_at DESC
            LIMIT 10";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([':user_id' => $userId]);
    return $stmt->fetchAll();
}

$activeBorrows = getActiveBorrows($currentUserId);
$pastBorrows = getPastBorrows($currentUserId);

// Calculate stats
$totalActive = count($activeBorrows);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>My Borrowed Books - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Modern Mobile-First UI for My Borrowed */
        :root {
            --card-grad: linear-gradient(135deg, #ffffff 0%, #f9fafb 100%);
            --accent-glow: 0 8px 32px rgba(99, 102, 241, 0.15);
        }

        [data-theme="dark"] {
            --card-grad: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            --accent-glow: 0 8px 32px rgba(0, 0, 0, 0.4);
        }

        body {
            background: var(--bg);
            transition: background 0.3s ease;
        }

        .page-container {
            max-width: 1000px;
            margin: 0 auto;
            padding: var(--space-xl) var(--space-md);
        }

        /* Header Styling */
        .page-header {
            margin-bottom: var(--space-xl);
            text-align: left;
        }

        .page-header h1 {
            font-size: clamp(1.8rem, 5vw, 2.8rem);
            font-weight: 800;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            -webkit-background-clip: text;
            background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: var(--space-xs);
            letter-spacing: -0.5px;
        }

        .page-header p {
            color: var(--text-muted);
            font-size: var(--font-size-md);
            opacity: 0.8;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex;
            gap: var(--space-md);
            margin-bottom: var(--space-xl);
            overflow-x: auto;
            padding: var(--space-xs) 0 var(--space-md);
            scrollbar-width: none;
        }

        .stats-bar::-webkit-scrollbar { display: none; }

        .stat-item {
            background: var(--card-grad);
            padding: var(--space-md) var(--space-lg);
            border-radius: var(--radius-lg);
            border: 1px solid var(--glass-border);
            min-width: 150px;
            box-shadow: var(--shadow-sm);
            display: flex;
            flex-direction: column;
            gap: 2px;
            flex: 1;
            transition: var(--transition);
        }
        
        .stat-item:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-md);
        }

        .stat-item .value {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--primary);
            line-height: 1;
        }

        .stat-item .label {
            font-size: 0.7rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 600;
        }

        /* Cards Styling */
        .borrow-grid {
            display: grid;
            gap: var(--space-lg);
            grid-template-columns: 1fr;
        }

        @media (min-width: 768px) {
            .borrow-grid {
                grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));
            }
        }

        .borrow-card {
            background: var(--card-grad);
            border-radius: var(--radius-xl);
            border: 1px solid var(--glass-border);
            padding: var(--space-md);
            display: flex;
            gap: var(--space-md);
            position: relative;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
            overflow: hidden;
        }

        .borrow-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--accent-glow);
            border-color: var(--primary-light);
        }

        .book-thumb {
            width: 110px;
            height: 155px;
            border-radius: var(--radius-lg);
            object-fit: cover;
            box-shadow: var(--shadow-md);
            flex-shrink: 0;
            border: 1px solid var(--glass-border);
        }

        .card-content {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .book-info h3 {
            font-size: 1.15rem;
            margin-bottom: 4px;
            line-height: 1.25;
            font-weight: 700;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-info .author {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: var(--space-sm);
            font-weight: 500;
        }

        .meta-tags {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-xs);
            margin-bottom: var(--space-sm);
        }

        .tag {
            font-size: 0.65rem;
            padding: 3px 10px;
            border-radius: var(--radius-full);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .tag-due { background: rgba(245, 54, 92, 0.1); color: var(--danger); border: 1px solid rgba(245, 54, 92, 0.2); }
        .tag-owner { background: rgba(99, 102, 241, 0.1); color: var(--primary); border: 1px solid rgba(99, 102, 241, 0.2); }
        .tag-days { background: rgba(45, 206, 137, 0.1); color: var(--success); border: 1px solid rgba(45, 206, 137, 0.2); }

        .due-progress {
            height: 6px;
            background: var(--gray-200);
            border-radius: 10px;
            margin: var(--space-sm) 0;
            overflow: hidden;
        }

        .progress-bar {
            height: 100%;
            background: linear-gradient(to right, var(--primary), var(--secondary));
            border-radius: 10px;
            transition: width 1s ease-out;
        }

        .actions {
            display: flex;
            gap: var(--space-sm);
            margin-top: var(--space-md);
        }

        .btn-return {
            flex: 1;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: var(--radius-lg);
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(99, 102, 241, 0.2);
        }

        .btn-return:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(99, 102, 241, 0.3);
            filter: brightness(1.1);
        }

        .btn-info {
            width: 42px;
            height: 42px;
            border-radius: var(--radius-lg);
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            transition: var(--transition);
        }

        .btn-info:hover {
            background: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        /* Empty State */
        .empty-state {
            text-align: center;
            padding: 5rem 2rem;
            background: var(--card-grad);
            border-radius: var(--radius-xl);
            border: 2px dashed var(--gray-300);
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .empty-state i {
            font-size: 4rem;
            color: var(--gray-400);
            margin-bottom: var(--space-md);
            opacity: 0.5;
        }

        .empty-state h2 { margin-bottom: var(--space-sm); font-size: 1.5rem; }
        .empty-state p { opacity: 0.7; max-width: 300px; margin-bottom: var(--space-lg); }

        /* Sections */
        .section-title {
            font-size: 1.4rem;
            font-weight: 800;
            margin: var(--space-xl) 0 var(--space-lg);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            color: var(--dark);
        }

        .section-title i { 
            width: 38px;
            height: 38px;
            background: rgba(99, 102, 241, 0.1);
            color: var(--primary);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 12px;
            font-size: 1.1rem;
        }

        /* Recent List */
        .recent-list {
            display: grid;
            gap: var(--space-md);
            grid-template-columns: 1fr;
        }

        @media (min-width: 768px) {
            .recent-list {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        .recent-card {
            background: var(--card-grad);
            border-radius: var(--radius-lg);
            padding: var(--space-sm);
            display: flex;
            align-items: center;
            gap: var(--space-md);
            border: 1px solid var(--glass-border);
            opacity: 0.9;
            transition: var(--transition);
        }
        
        .recent-card:hover {
            opacity: 1;
            transform: translateX(5px);
            border-color: var(--primary-light);
        }

        .recent-thumb {
            width: 50px;
            height: 70px;
            border-radius: var(--radius-md);
            object-fit: cover;
            box-shadow: var(--shadow-sm);
        }

        /* Dark Mode Specific Adjustments */
        [data-theme="dark"] .stat-item { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .borrow-card { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .due-progress { background: #0f172a; }
        [data-theme="dark"] .empty-state { border-color: #334155; background: #0f172a; }
        [data-theme="dark"] .tag-owner { background: rgba(99, 102, 241, 0.2); }
        [data-theme="dark"] .btn-info { background: #334155; border-color: #475569; color: #cbd5e1; }
        [data-theme="dark"] .recent-card { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .section-title i { background: rgba(99, 102, 241, 0.2); }

        /* Animation */
        .fade-in {
            animation: fadeIn 0.6s cubic-bezier(0.16, 1, 0.3, 1) forwards;
            opacity: 0;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(15px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* FAB */
        .fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 20px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.4);
            z-index: 100;
            transition: var(--transition);
            font-size: 1.5rem;
        }

        .fab:hover { 
            transform: scale(1.1) rotate(10deg);
            box-shadow: 0 15px 30px rgba(99, 102, 241, 0.5);
        }

    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>

    <main class="page-container">
        <!-- Page Header -->
        <header class="page-header">
            <h1 class="fade-in">My Borrowed Books</h1>
            <p class="fade-in" style="animation-delay: 0.1s">Manage your active reads and track return deadlines.</p>
        </header>

        <!-- Stats Bar -->
        <div class="stats-bar fade-in" style="animation-delay: 0.2s">
            <div class="stat-item">
                <span class="value"><?php echo $totalActive; ?></span>
                <span class="label">Currently Reading</span>
            </div>
            <div class="stat-item">
                <span class="value"><?php echo count($pastBorrows); ?></span>
                <span class="label">Books Returned</span>
            </div>
            <div class="stat-item">
                <span class="value"><?php echo date('M j'); ?></span>
                <span class="label">Today's Date</span>
            </div>
        </div>

        <!-- Active Borrows Section -->
        <section>
            <h2 class="section-title fade-in" style="animation-delay: 0.3s">
                <i class="fas fa-bookmark"></i> Active Borrows
            </h2>
            
            <?php if (empty($activeBorrows)): ?>
                <div class="empty-state fade-in" style="animation-delay: 0.4s">
                    <i class="fas fa-layer-group"></i>
                    <h2>Your shelf is empty</h2>
                    <p>Start your reading journey by requesting a book from the library.</p>
                    <a href="/books/" class="btn btn-primary" style="padding: 12px 24px; border-radius: 12px;">
                        <i class="fas fa-search"></i> Discover Books
                    </a>
                </div>
            <?php else: ?>
                <div class="borrow-grid">
                    <?php foreach ($activeBorrows as $index => $borrow): 
                        $dueDateStr = $borrow['expected_return_date'];
                        $dueDate = $dueDateStr ? strtotime($dueDateStr) : null;
                        $today = strtotime(date('Y-m-d'));
                        $diff = $dueDate ? ceil(($dueDate - $today) / (60 * 60 * 24)) : 0;
                        $isOverdue = $dueDate && $diff < 0;
                        
                        $coverPath = !empty($borrow['cover_image']) ? '/uploads/book_cover/thumb_' . $borrow['cover_image'] : '/assets/images/default-book-cover.jpg';
                        
                        // Calculate progress
                        $totalDays = $borrow['duration_days'] ?? 14;
                        $remainingPercent = $dueDate ? max(0, min(100, ($diff / $totalDays) * 100)) : 100;
                        $progressWidth = 100 - $remainingPercent;
                    ?>
                        <div class="borrow-card fade-in" style="animation-delay: <?php echo 0.4 + ($index * 0.1); ?>s">
                            <img src="<?php echo $coverPath; ?>" alt="<?php echo htmlspecialchars($borrow['book_title']); ?>" class="book-thumb">
                            <div class="card-content">
                                <div class="book-info">
                                    <div class="meta-tags">
                                        <span class="tag tag-owner"><?php echo htmlspecialchars($borrow['owner_name']); ?></span>
                                        <?php if ($dueDate): ?>
                                            <span class="tag <?php echo $isOverdue ? 'tag-due' : 'tag-days'; ?>">
                                                <?php echo $isOverdue ? 'Overdue' : $diff . ' days left'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <h3><?php echo htmlspecialchars($borrow['book_title']); ?></h3>
                                    <p class="author">by <?php echo htmlspecialchars($borrow['book_author']); ?></p>
                                    
                                    <?php if ($dueDate): ?>
                                        <div class="due-progress">
                                            <div class="progress-bar" style="width: <?php echo $progressWidth; ?>%; <?php echo $isOverdue ? 'background: #f5365c;' : ''; ?>"></div>
                                        </div>
                                        <div style="font-size: 0.65rem; color: var(--text-muted); display: flex; justify-content: space-between; font-weight: 600;">
                                            <span>Starts: <?php echo date('M j', strtotime($borrow['request_date'])); ?></span>
                                            <span style="<?php echo $isOverdue ? 'color: var(--danger);' : ''; ?>">Due: <?php echo date('M j', $dueDate); ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="actions">
                                    <a href="/return-book/?id=<?php echo $borrow['id']; ?>" class="btn-return">
                                        <i class="fas fa-undo"></i> Return Book
                                    </a>
                                    <a href="/book/?id=<?php echo $borrow['book_id']; ?>" class="btn-info" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <!-- Past Borrows Section -->
        <?php if (!empty($pastBorrows)): ?>
            <section style="margin-top: var(--space-xxl); margin-bottom: 4rem;">
                <h2 class="section-title fade-in" style="animation-delay: 0.6s">
                    <i class="fas fa-history"></i> Just Finished
                </h2>
                <div class="recent-list fade-in" style="animation-delay: 0.7s">
                    <?php foreach ($pastBorrows as $index => $past): 
                        $coverPath = !empty($past['cover_image']) ? '/uploads/book_cover/thumb_' . $past['cover_image'] : '/assets/images/default-book-cover.jpg';
                    ?>
                        <div class="recent-card">
                            <img src="<?php echo $coverPath; ?>" alt="<?php echo htmlspecialchars($past['book_title']); ?>" class="recent-thumb">
                            <div class="card-content">
                                <h4 style="font-size: 1rem; margin: 0; font-weight: 700;"><?php echo htmlspecialchars($past['book_title']); ?></h4>
                                <p style="font-size: 0.75rem; color: var(--text-muted); margin: 4px 0 0; font-weight: 500;">
                                    <i class="fas fa-check-circle" style="color: var(--success); font-size: 0.7rem;"></i> 
                                    Returned on <?php echo date('M j, Y', strtotime($past['returned_at'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    </main>

    <!-- FAB for adding more books -->
    <a href="/books/" class="fab fade-in" style="animation-delay: 0.8s" title="Borrow another book">
        <i class="fas fa-search"></i>
    </a>

    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>
