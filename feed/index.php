<?php
/**
 * OpenShelf Activity Feed Page
 * Mobile-first design with timeline
 */

session_start();
include dirname(__DIR__) . '/includes/header.php';

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_PATH', dirname(__DIR__) . '/books/');
define('USERS_PATH', dirname(__DIR__) . '/users/');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

/**
 * Load all books from DB
 */
function loadAllBooks() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM books ORDER BY created_at DESC LIMIT 50");
    return $stmt->fetchAll();
}

/**
 * Load all requests from DB
 */
function loadAllRequests() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM borrow_requests WHERE status IN ('approved', 'returned') ORDER BY updated_at DESC LIMIT 50");
    return $stmt->fetchAll();
}

/**
 * Generate activity feed
 */
function generateFeed($books, $requests, $limit = 20) {
    $activities = [];
    
    // Book additions
    foreach ($books as $book) {
        $activities[] = [
            'type' => 'book_added',
            'user_id' => $book['owner_id'],
            'user_name' => $book['owner_name'] ?? 'Unknown',
            'book_id' => $book['id'],
            'book_title' => $book['title'],
            'book_author' => $book['author'],
            'book_cover' => $book['cover_image'] ?? null,
            'timestamp' => $book['created_at']
        ];
    }
    
    // Borrow requests
    foreach ($requests as $request) {
        if (in_array($request['status'], ['approved', 'returned'])) {
            $activities[] = [
                'type' => $request['status'] === 'approved' ? 'book_borrowed' : 'book_returned',
                'user_id' => $request['borrower_id'],
                'user_name' => $request['borrower_name'],
                'book_id' => $request['book_id'],
                'book_title' => $request['book_title'],
                'book_author' => $request['book_author'] ?? 'Unknown',
                'book_cover' => $request['book_cover'] ?? null,
                'owner_id' => $request['owner_id'],
                'owner_name' => $request['owner_name'],
                'timestamp' => $request[$request['status'] . '_at'] ?? $request['updated_at']
            ];
        }
    }
    
    // Sort by timestamp
    usort($activities, fn($a, $b) => strtotime($b['timestamp']) - strtotime($a['timestamp']));
    
    return array_slice($activities, 0, $limit);
}

/**
 * Format time ago
 */
function timeAgo($timestamp) {
    $time = strtotime($timestamp);
    $diff = time() - $time;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $time);
}

/**
 * Get activity icon
 */
function getActivityIcon($type) {
    switch ($type) {
        case 'book_added': return 'fa-plus-circle';
        case 'book_borrowed': return 'fa-hand-holding-heart';
        case 'book_returned': return 'fa-undo-alt';
        default: return 'fa-circle';
    }
}

/**
 * Get activity color
 */
function getActivityColor($type) {
    switch ($type) {
        case 'book_added': return 'var(--success)';
        case 'book_borrowed': return 'var(--primary)';
        case 'book_returned': return 'var(--warning)';
        default: return 'var(--gray-600)';
    }
}

$books = loadAllBooks();
$requests = loadAllRequests();
$activities = generateFeed($books, $requests);
?>

<div class="container">
    <!-- Page Header -->
    <div class="page-header" style="background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: var(--space-xl) 0; border-radius: var(--radius-xl); margin-bottom: var(--space-xl); text-align: center;">
        <h1 style="font-size: 2rem; margin-bottom: var(--space-sm);">
            <i class="fas fa-rss"></i> Activity Feed
        </h1>
        <p style="opacity: 0.9;">Stay updated with the latest community activities</p>
    </div>
    
    <!-- Feed Controls -->
    <div class="card" style="margin-bottom: var(--space-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-md);">
            <div style="display: flex; align-items: center; gap: var(--space-xs);">
                <span class="live-dot" style="width: 10px; height: 10px; background: var(--success); border-radius: 50%; animation: pulse 2s infinite;"></span>
                <span style="color: var(--gray-600);">Live Updates</span>
            </div>
            
            <div style="display: flex; gap: var(--space-xs); flex-wrap: wrap;">
                <button class="btn btn-outline btn-sm active" onclick="filterFeed('all')">All</button>
                <button class="btn btn-outline btn-sm" onclick="filterFeed('book_added')">Added</button>
                <button class="btn btn-outline btn-sm" onclick="filterFeed('book_borrowed')">Borrowed</button>
            </div>
        </div>
    </div>
    
    <!-- Feed Timeline -->
    <?php if (empty($activities)): ?>
        <div class="card text-center" style="padding: var(--space-xxl);">
            <i class="fas fa-rss" style="font-size: 3rem; color: var(--gray-400); margin-bottom: var(--space-md);"></i>
            <h3>No Activities Yet</h3>
            <p style="color: var(--gray-600);">Be the first to add a book or write a review!</p>
        </div>
    <?php else: ?>
        <div class="feed-timeline" style="position: relative; padding-left: 30px;">
            <!-- Timeline Line -->
            <div style="position: absolute; left: 15px; top: 0; bottom: 0; width: 2px; background: linear-gradient(to bottom, var(--primary), var(--secondary)); opacity: 0.3;"></div>
            
            <?php foreach ($activities as $activity): 
                $icon = getActivityIcon($activity['type']);
                $color = getActivityColor($activity['type']);
            ?>
                <div class="activity-card" data-type="<?php echo $activity['type']; ?>" style="position: relative; margin-bottom: var(--space-lg); animation: slideIn 0.5s ease;">
                    <!-- Timeline Dot -->
                    <div style="position: absolute; left: -30px; top: 20px; width: 16px; height: 16px; border-radius: 50%; background: <?php echo $color; ?>; border: 3px solid white; box-shadow: var(--shadow-sm);"></div>
                    
                    <div class="card" style="margin-left: 0;">
                        <div style="display: flex; gap: var(--space-md); flex-wrap: wrap;">
                            <!-- Icon -->
                            <div style="width: 50px; height: 50px; border-radius: 50%; background: <?php echo $color; ?>; display: flex; align-items: center; justify-content: center; color: white; font-size: 1.2rem;">
                                <i class="fas <?php echo $icon; ?>"></i>
                            </div>
                            
                            <!-- Content -->
                            <div style="flex: 1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: var(--space-sm); margin-bottom: var(--space-xs);">
                                    <h3 style="margin: 0; font-size: 1rem;">
                                        <strong><?php echo htmlspecialchars($activity['user_name']); ?></strong>
                                        <?php
                                        if ($activity['type'] === 'book_added') echo 'added a new book';
                                        elseif ($activity['type'] === 'book_borrowed') echo 'borrowed a book';
                                        else echo 'returned a book';
                                        ?>
                                    </h3>
                                    <span style="color: var(--gray-600); font-size: var(--font-size-xs);">
                                        <i class="far fa-clock"></i> <?php echo timeAgo($activity['timestamp']); ?>
                                    </span>
                                </div>
                                
                                <!-- Book Preview -->
                                <div style="display: flex; gap: var(--space-sm); margin-top: var(--space-sm); padding: var(--space-sm); background: var(--gray-100); border-radius: var(--radius-md);">
                                    <img src="/uploads/book_cover/thumb_<?php echo $activity['book_cover'] ?? 'default.jpg'; ?>" 
                                         alt="" style="width: 50px; height: 70px; object-fit: cover; border-radius: var(--radius-sm);">
                                    <div>
                                        <strong><?php echo htmlspecialchars($activity['book_title']); ?></strong>
                                        <p style="color: var(--gray-600); font-size: var(--font-size-sm);">by <?php echo htmlspecialchars($activity['book_author']); ?></p>
                                    </div>
                                </div>
                                
                                <!-- Action Button -->
                                <div style="margin-top: var(--space-sm);">
                                    <a href="/book/?id=<?php echo $activity['book_id']; ?>" class="btn btn-outline btn-sm">
                                        <i class="fas fa-book"></i> View Book
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
@keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
    100% { opacity: 1; transform: scale(1); }
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.btn-outline.active {
    background: var(--primary);
    color: white;
    border-color: var(--primary);
}

/* Dark Mode Overrides */
[data-theme="dark"] .page-header { 
    background: linear-gradient(135deg, #1e293b, #0f172a) !important; 
    border: 1px solid #334155;
}
[data-theme="dark"] .activity-card .card,
[data-theme="dark"] .card { 
    background: #1e293b; 
    border-color: #334155; 
    color: #cbd5e1;
}
[data-theme="dark"] .activity-card h3 strong { 
    color: #f8fafc; 
}
[data-theme="dark"] [style*="background: var(--gray-100)"] { 
    background: #0f172a !important; 
}
[data-theme="dark"] .btn-outline { 
    border-color: #475569; 
    color: #cbd5e1; 
}
[data-theme="dark"] .btn-outline:hover { 
    border-color: var(--primary); 
    color: #f8fafc; 
}
[data-theme="dark"] .btn-outline.active { 
    background: var(--primary); 
    color: white; 
    border-color: var(--primary); 
}
</style>

<script>
function filterFeed(type) {
    // Update active button
    document.querySelectorAll('.btn-outline').forEach(btn => {
        btn.classList.remove('active');
    });
    event.target.classList.add('active');
    
    // Filter activities
    document.querySelectorAll('.activity-card').forEach(card => {
        if (type === 'all' || card.dataset.type === type) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

// Auto-refresh every 30 seconds (optional)
setInterval(() => {
    location.reload();
}, 30000);
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>