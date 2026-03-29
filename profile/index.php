<?php
/**
 * OpenShelf Profile Page
 * Mobile-first design
 */

session_start();
include dirname(__DIR__) . '/includes/header.php';

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('BOOKS_PATH', dirname(__DIR__) . '/books/');

$viewUserId = $_GET['id'] ?? ($_SESSION['user_id'] ?? null);
if (!$viewUserId) {
    header('Location: /books/');
    exit;
}

/**
 * Load user data
 */
function loadUserData($userId) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) return null;
    return json_decode(file_get_contents($userFile), true);
}

/**
 * Load user's books
 */
function loadUserBooks($userId) {
    $booksFile = DATA_PATH . 'books.json';
    if (!file_exists($booksFile)) return [];
    
    $allBooks = json_decode(file_get_contents($booksFile), true) ?? [];
    return array_filter($allBooks, fn($book) => ($book['owner_id'] ?? '') === $userId);
}

/**
 * Get user stats
 */
function getUserStats($userId, $books) {
    $requestsFile = DATA_PATH . 'borrow_requests.json';
    $requests = file_exists($requestsFile) ? json_decode(file_get_contents($requestsFile), true) ?? [] : [];
    
    $borrowed = 0;
    $lent = 0;
    
    foreach ($requests as $r) {
        if ($r['borrower_id'] === $userId && $r['status'] === 'approved') $borrowed++;
        if ($r['owner_id'] === $userId && $r['status'] === 'approved') $lent++;
    }
    
    return [
        'owned' => count($books),
        'borrowed' => $borrowed,
        'lent' => $lent
    ];
}

$user = loadUserData($viewUserId);
if (!$user) {
    header('Location: /books/');
    exit;
}

$books = loadUserBooks($viewUserId);
$stats = getUserStats($viewUserId, $books);
$isOwnProfile = isset($_SESSION['user_id']) && $_SESSION['user_id'] === $viewUserId;

$profileImage = $user['personal_info']['profile_pic'] ?? 'default-avatar.jpg';
$memberSince = date('M Y', strtotime($user['account_info']['created_at'] ?? 'now'));
?>

<div class="container">
    <!-- Profile Header -->
    <div class="card" style="overflow: hidden; padding: 0;">
        <!-- Cover -->
        <div style="height: 150px; background: linear-gradient(135deg, var(--primary), var(--secondary));"></div>
        
        <!-- Profile Info -->
        <div style="padding: 0 var(--space-lg) var(--space-lg); margin-top: -75px;">
            <div style="display: flex; flex-direction: column; align-items: center; text-align: center;">
                <!-- Avatar -->
                <img src="/uploads/profile/<?php echo $profileImage; ?>" 
                     alt="<?php echo htmlspecialchars($user['personal_info']['name']); ?>"
                     style="width: 120px; height: 120px; border-radius: 50%; border: 4px solid white; box-shadow: var(--shadow-md); margin-bottom: var(--space-md);">
                
                <h1 style="margin-bottom: var(--space-xs);"><?php echo htmlspecialchars($user['personal_info']['name']); ?></h1>
                
                <?php if (!empty($user['personal_info']['bio'])): ?>
                    <p style="color: var(--gray-600); max-width: 500px; margin-bottom: var(--space-md);">
                        <?php echo nl2br(htmlspecialchars($user['personal_info']['bio'])); ?>
                    </p>
                <?php endif; ?>
                
                <!-- Details Grid -->
                <div class="grid grid-2" style="max-width: 500px; margin-bottom: var(--space-lg);">
                    <div>
                        <div style="color: var(--gray-600); font-size: var(--font-size-xs);">Department</div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($user['personal_info']['department'] ?? 'N/A'); ?></div>
                    </div>
                    <div>
                        <div style="color: var(--gray-600); font-size: var(--font-size-xs);">Session</div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($user['personal_info']['session'] ?? 'N/A'); ?></div>
                    </div>
                    <div>
                        <div style="color: var(--gray-600); font-size: var(--font-size-xs);">Room</div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($user['personal_info']['room_number'] ?? 'N/A'); ?></div>
                    </div>
                    <div>
                        <div style="color: var(--gray-600); font-size: var(--font-size-xs);">Member Since</div>
                        <div style="font-weight: 600;"><?php echo $memberSince; ?></div>
                    </div>
                </div>
                
                <!-- Stats -->
                <div class="grid grid-3" style="max-width: 400px; margin-bottom: var(--space-lg);">
                    <div class="card" style="padding: var(--space-sm); text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--primary);"><?php echo $stats['owned']; ?></div>
                        <div style="font-size: var(--font-size-xs); color: var(--gray-600);">Owned</div>
                    </div>
                    <div class="card" style="padding: var(--space-sm); text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--success);"><?php echo $stats['borrowed']; ?></div>
                        <div style="font-size: var(--font-size-xs); color: var(--gray-600);">Borrowed</div>
                    </div>
                    <div class="card" style="padding: var(--space-sm); text-align: center;">
                        <div style="font-size: 1.5rem; font-weight: 700; color: var(--warning);"><?php echo $stats['lent']; ?></div>
                        <div style="font-size: var(--font-size-xs); color: var(--gray-600);">Lent</div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <?php if ($isOwnProfile): ?>
                    <div style="display: flex; gap: var(--space-md);">
                        <a href="/edit-profile/" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Edit Profile
                        </a>
                        <a href="/add-book/" class="btn btn-outline">
                            <i class="fas fa-plus-circle"></i> Add Book
                        </a>
                    </div>
                <?php elseif (isset($_SESSION['user_id'])): ?>
                    <a href="https://wa.me/88<?php echo preg_replace('/[^0-9]/', '', $user['personal_info']['phone'] ?? ''); ?>" 
                       target="_blank" class="btn btn-success">
                        <i class="fab fa-whatsapp"></i> Contact via WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Books Section -->
    <div class="card" style="margin-top: var(--space-xl);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: var(--space-lg);">
            <h2 style="margin: 0;">
                <i class="fas fa-book" style="color: var(--primary);"></i>
                Books Owned
            </h2>
            <?php if (count($books) > 6): ?>
                <a href="/books/?owner=<?php echo $viewUserId; ?>" class="btn btn-outline btn-sm">View All</a>
            <?php endif; ?>
        </div>
        
        <?php if (empty($books)): ?>
            <div style="text-align: center; padding: var(--space-xl);">
                <i class="fas fa-book-open" style="font-size: 3rem; color: var(--gray-400); margin-bottom: var(--space-md);"></i>
                <p>No books to display.</p>
                <?php if ($isOwnProfile): ?>
                    <a href="/add-book/" class="btn btn-primary">Add Your First Book</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="book-grid">
                <?php foreach (array_slice($books, 0, 6) as $book): ?>
                    <div class="book-card">
                        <div class="book-cover">
                            <img src="/uploads/book_cover/thumb_<?php echo $book['cover_image'] ?? 'default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <span class="book-status status-<?php echo $book['status']; ?>">
                                <?php echo ucfirst($book['status']); ?>
                            </span>
                        </div>
                        <div class="book-info">
                            <h3 class="book-title">
                                <a href="/book/?id=<?php echo $book['id']; ?>">
                                    <?php echo htmlspecialchars($book['title']); ?>
                                </a>
                            </h3>
                            <p class="book-author"><?php echo htmlspecialchars($book['author']); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>