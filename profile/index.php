<?php
/**
 * OpenShelf Profile Page
 * Mobile-first design
 */

session_start();
include dirname(__DIR__) . '/includes/header.php';
echo '<link rel="stylesheet" href="/assets/css/profile.css">';

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('BOOKS_PATH', dirname(__DIR__) . '/books/');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

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
 * Load user's books from DB
 */
function loadUserBooks($userId) {
    if (empty($userId)) return [];
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM books WHERE owner_id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Get detailed user stats and book lists from DB
 */
function getUserProfileData($userId, $ownedBooks) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM borrow_requests WHERE (borrower_id = ? OR owner_id = ?) AND (status = 'approved' OR status = 'returned')");
    $stmt->execute([$userId, $userId]);
    $requests = $stmt->fetchAll();
    
    $borrowedBooks = [];
    $lentBooks = [];
    
    foreach ($requests as $r) {
        if ($r['borrower_id'] === $userId) {
            $borrowedBooks[] = [
                'id' => $r['book_id'],
                'title' => $r['book_title'],
                'author' => $r['book_author'],
                'cover_image' => $r['book_cover'],
                'status' => $r['status'],
                'owner_name' => $r['owner_name']
            ];
        }
        if ($r['owner_id'] === $userId) {
            $lentBooks[] = [
                'id' => $r['book_id'],
                'title' => $r['book_title'],
                'author' => $r['book_author'],
                'cover_image' => $r['book_cover'],
                'status' => $r['status'],
                'borrower_name' => $r['borrower_name']
            ];
        }
    }
    
    return [
        'stats' => [
            'owned' => count($ownedBooks),
            'borrowed' => count($borrowedBooks),
            'lent' => count($lentBooks)
        ],
        'borrowed' => $borrowedBooks,
        'lent' => $lentBooks
    ];
}

$user = loadUserData($viewUserId);
if (!$user) {
    header('Location: /books/');
    exit;
}

$books = loadUserBooks($viewUserId);
$profileData = getUserProfileData($viewUserId, $books);
$stats = $profileData['stats'];
$borrowedBooks = $profileData['borrowed'];
$lentBooks = $profileData['lent'];

$isOwnProfile = isset($_SESSION['user_id']) && $_SESSION['user_id'] === $viewUserId;

// --- Profile Image Logic (Robust) ---
$profileImageName = 'default.jpg'; // Initialize

// 1. Primary Source: Load from DB
$db = getDB();
$stmt = $db->prepare("SELECT profile_pic FROM users WHERE id = ?");
$stmt->execute([$viewUserId]);
$profileImageName = $stmt->fetchColumn() ?: 'default.jpg';

// 2. Secondary Source: Supplement from private user JSON if not already found or if it overrides
$userFile = USERS_PATH . $viewUserId . '.json';
if (file_exists($userFile)) {
    $userData = json_decode(file_get_contents($userFile), true);
    if (!empty($userData['personal_info']['profile_pic'])) {
        $profileImageName = $userData['personal_info']['profile_pic'];
    }
}

// 3. Fallback/Standardize
if (empty($profileImageName) || $profileImageName === 'default-avatar.jpg') {
    $profileImageName = 'default.jpg';
}

$profileImagePath = '/uploads/profile/' . $profileImageName;
$serverImagePath = dirname(__DIR__) . '/uploads/profile/' . $profileImageName;
$defaultAvatar = '/assets/images/avatars/default.jpg';

// 4. Final Path Verification on server
if (!file_exists($serverImagePath) || $profileImageName === 'default.jpg') {
    $profileImagePath = $defaultAvatar;
}

$memberSince = date('M Y', strtotime($user['account_info']['created_at'] ?? 'now'));
$showSensitiveInfo = $isOwnProfile; // Only owner can see sensitive info like room/phone/stats
?>

<div class="profile-hero"></div>

<div class="profile-container">
    <div class="glass-card reveal active">
        <!-- Action Buttons (Top Right) -->
        <button class="share-btn" onclick="copyProfileLink()" title="Share Profile">
            <i class="fas fa-share-alt"></i>
        </button>

        <div class="profile-avatar-wrapper">
            <img src="<?php echo $profileImagePath; ?>" 
                 alt="<?php echo htmlspecialchars($user['personal_info']['name']); ?>"
                 class="profile-avatar">
        </div>

        <h1 class="profile-name"><?php echo htmlspecialchars($user['personal_info']['name']); ?></h1>
        
        <div class="profile-bio">
            <?php if (!empty($user['personal_info']['bio'])): ?>
                <p><?php echo nl2br(htmlspecialchars($user['personal_info']['bio'])); ?></p>
            <?php else: ?>
                <p>No bio available.</p>
            <?php endif; ?>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-2" style="max-width: 600px; margin: 0 auto 2rem; border-top: 1px solid var(--gray-200); padding-top: 1.5rem; position: relative;">
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <div style="background: var(--gray-100); padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: var(--profile-primary);">
                    <i class="fas fa-university"></i>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">Department</div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($user['personal_info']['department'] ?? 'N/A'); ?></div>
                </div>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <div style="background: var(--gray-100); padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: var(--profile-secondary);">
                    <i class="fas fa-graduation-cap"></i>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">Session</div>
                    <div style="font-weight: 600;"><?php echo htmlspecialchars($user['personal_info']['session'] ?? 'N/A'); ?></div>
                </div>
            </div>
            
            <?php if (!$showSensitiveInfo): ?>
                <div style="position: absolute; top: -10px; left: 50%; transform: translateX(-50%); background: var(--gray-100); padding: 2px 12px; border-radius: var(--radius-full); font-size: 0.65rem; color: var(--gray-500); display: flex; align-items: center; gap: 4px; border: 1px solid var(--gray-200);">
                    <i class="fas fa-lock" style="font-size: 0.6rem;"></i> Limited Profile
                </div>
            <?php endif; ?>
            <?php if ($showSensitiveInfo): ?>
                <div style="display: flex; gap: 0.75rem; align-items: center;">
                    <div style="background: var(--gray-100); padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: var(--profile-accent);">
                        <i class="fas fa-door-open"></i>
                    </div>
                    <div>
                        <div style="font-size: 0.75rem; color: var(--gray-500);">Room</div>
                        <div style="font-weight: 600;"><?php echo htmlspecialchars($user['personal_info']['room_number'] ?? 'N/A'); ?></div>
                    </div>
                </div>
            <?php endif; ?>

            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <div style="background: var(--gray-100); padding: 0.5rem; border-radius: 50%; width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; color: var(--warning);">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <div>
                    <div style="font-size: 0.75rem; color: var(--gray-500);">Member Since</div>
                    <div style="font-weight: 600;"><?php echo $memberSince; ?></div>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-value"><?php echo $stats['owned']; ?></span>
                <span class="stat-label">Owned</span>
            </div>
            <?php if ($showSensitiveInfo): ?>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $stats['borrowed']; ?></span>
                    <span class="stat-label">Borrowed</span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo $stats['lent']; ?></span>
                    <span class="stat-label">Lent</span>
                </div>
            <?php endif; ?>
        </div>

        <div style="display: flex; justify-content: center; gap: var(--space-md); margin-top: 2rem;">
            <?php if ($isOwnProfile): ?>
                <a href="/edit-profile/" class="btn btn-primary" style="border-radius: var(--radius-full); padding: 0.75rem 2rem;">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                <a href="/add-book/" class="btn btn-outline" style="border-radius: var(--radius-full); padding: 0.75rem 2rem;">
                    <i class="fas fa-plus-circle"></i> Add Book
                </a>
            <?php elseif (isset($_SESSION['user_id'])): ?>
                <a href="https://wa.me/88<?php echo preg_replace('/[^0-9]/', '', $user['personal_info']['phone'] ?? ''); ?>" 
                   target="_blank" class="btn btn-success" style="border-radius: var(--radius-full); padding: 0.75rem 2rem;">
                    <i class="fab fa-whatsapp"></i> Contact Me
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Books Section with Tabs -->
    <div class="profile-tabs" style="<?php echo !$showSensitiveInfo ? 'max-width: 200px; margin: 3rem auto 2rem;' : ''; ?>">
        <button class="tab-btn active" onclick="switchTab(event, 'owned')">Books Owned</button>
        <?php if ($showSensitiveInfo): ?>
            <button class="tab-btn" onclick="switchTab(event, 'borrowed')">Borrowed</button>
            <button class="tab-btn" onclick="switchTab(event, 'lent')">Lent</button>
        <?php endif; ?>
    </div>

    <!-- Tab Contents -->
    <div id="owned" class="tab-content active">
        <?php if (empty($books)): ?>
            <div style="text-align: center; padding: 4rem 2rem; background: rgba(255,255,255,0.5); border-radius: var(--radius-xl);">
                <i class="fas fa-book-open" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
                <p>No owned books to show.</p>
            </div>
        <?php else: ?>
            <div class="book-grid-profile">
                <?php foreach ($books as $book): ?>
                    <div class="book-card">
                        <div class="book-cover">
                            <img src="/uploads/book_cover/thumb_<?php echo $book['cover_image'] ?? 'default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 onerror="this.src='/assets/images/default-book-cover.jpg'">
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

    <div id="borrowed" class="tab-content">
        <?php if (empty($borrowedBooks)): ?>
            <div style="text-align: center; padding: 4rem 2rem; background: rgba(255,255,255,0.5); border-radius: var(--radius-xl);">
                <i class="fas fa-book-reader" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
                <p>No borrowed books to show.</p>
            </div>
        <?php else: ?>
            <div class="book-grid-profile">
                <?php foreach ($borrowedBooks as $book): ?>
                    <div class="book-card">
                        <div class="book-cover">
                            <img src="/uploads/book_cover/thumb_<?php echo $book['cover_image'] ?? 'default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 onerror="this.src='/assets/images/default-book-cover.jpg'">
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
                            <p class="book-author">By <?php echo htmlspecialchars($book['author']); ?></p>
                            <p style="font-size: 0.7rem; color: var(--gray-500); margin-top: 4px;">Borrowed from: <b><?php echo htmlspecialchars($book['owner_name']); ?></b></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div id="lent" class="tab-content">
        <?php if (empty($lentBooks)): ?>
            <div style="text-align: center; padding: 4rem 2rem; background: rgba(255,255,255,0.5); border-radius: var(--radius-xl);">
                <i class="fas fa-hand-holding-heart" style="font-size: 3rem; color: var(--gray-300); margin-bottom: 1rem;"></i>
                <p>No books lent yet.</p>
            </div>
        <?php else: ?>
            <div class="book-grid-profile">
                <?php foreach ($lentBooks as $book): ?>
                    <div class="book-card">
                        <div class="book-cover">
                            <img src="/uploads/book_cover/thumb_<?php echo $book['cover_image'] ?? 'default.jpg'; ?>" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>"
                                 onerror="this.src='/assets/images/default-book-cover.jpg'">
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
                            <p style="font-size: 0.7rem; color: var(--gray-500); margin-top: 4px;">Lent to: <b><?php echo htmlspecialchars($book['borrower_name']); ?></b></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
function switchTab(evt, tabId) {
    const tabcontents = document.getElementsByClassName("tab-content");
    for (let i = 0; i < tabcontents.length; i++) {
        tabcontents[i].classList.remove("active");
    }
    const tablinks = document.getElementsByClassName("tab-btn");
    for (let i = 0; i < tablinks.length; i++) {
        tablinks[i].classList.remove("active");
    }
    document.getElementById(tabId).classList.add("active");
    evt.currentTarget.classList.add("active");
}

function copyProfileLink() {
    const url = window.location.href;
    navigator.clipboard.writeText(url).then(() => {
        alert("Profile link copied to clipboard!");
    }).catch(err => {
        console.error('Could not copy text: ', err);
    });
}

// Reveal animation on load
document.addEventListener('DOMContentLoaded', () => {
    const glassCard = document.querySelector('.glass-card');
    glassCard.classList.add('active');
});
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>