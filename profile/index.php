<?php
/**
 * OpenShelf Profile Page
 * Mobile-first design
 */

session_start();
include dirname(__DIR__) . '/includes/header.php';
include dirname(__DIR__) . '/includes/BookCardGrid.php';
include dirname(__DIR__) . '/includes/BookCardList.php';
echo '<link rel="stylesheet" href="/assets/css/profile.css?v=' . filemtime(dirname(__DIR__) . '/assets/css/profile.css') . '">';

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('BOOKS_PATH', dirname(__DIR__) . '/books/');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

$viewUserId = $_GET['id'] ?? ($_SESSION['user_id'] ?? null);
if (!$viewUserId) {
    header('Location: /login/');
    exit;
}

/**
 * Load user data
 */
function loadUserData($userId) {
    if (empty($userId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        return [
            'personal_info' => [
                'name' => $user['name'] ?? 'Unknown User',
                'bio' => $user['bio'] ?? '',
                'department' => $user['department'] ?? 'N/A',
                'session' => $user['session'] ?? 'N/A',
                'hall' => $user['hall'] ?? '',
                'room_number' => $user['room_number'] ?? 'N/A',
                'phone' => $user['phone'] ?? '',
                'profile_pic' => $user['profile_pic'] ?? 'default-avatar.jpg'
            ],
            'account_info' => [
                'created_at' => $user['created_at'] ?? date('Y-m-d H:i:s')
            ],
            'id' => $user['id'],
            'role' => $user['role'] ?? 'user'
        ];
    }
    return null;
}

/**
 * Load user's books from DB
 */
function loadUserBooks($userId) {
    if (empty($userId)) return [];
    $db = getDB();
    $stmt = $db->prepare("SELECT b.id, b.title, b.author, b.category, b.status, b.created_at, b.cover_image, b.rating, b.rating_count, b.owner_id, u.name as owner_name, u.hall as owner_hall, u.profile_pic as owner_avatar FROM books b LEFT JOIN users u ON b.owner_id = u.id WHERE b.owner_id = ?");
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
                'owner_name' => $r['owner_name'],
                'owner_hall' => 'N/A Hall' // Would need additional join to get this properly
            ];
        }
        if ($r['owner_id'] === $userId) {
            $lentBooks[] = [
                'id' => $r['book_id'],
                'title' => $r['book_title'],
                'author' => $r['book_author'],
                'cover_image' => $r['book_cover'],
                'status' => $r['status'],
                'borrower_name' => $r['borrower_name'],
                'owner_hall' => 'My Hall'
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
        <!-- Profile Avatar wrapper overlapping banner -->
        <div class="profile-avatar-wrapper">
            <img src="<?php echo $profileImagePath; ?>" 
                 alt="<?php echo htmlspecialchars($user['personal_info']['name']); ?>"
                 class="profile-avatar">
        </div>

        <h1 class="profile-name"><?php echo htmlspecialchars($user['personal_info']['name']); ?></h1>
        
        <!-- Subtitle and Meta Info -->
        <div class="profile-subtitle">
            <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($user['personal_info']['department'] ?? 'N/A'); ?>
        </div>
        
        <div class="profile-meta">
            <span class="meta-item"><i class="far fa-calendar-alt"></i> Joined <?php echo $memberSince; ?></span>
        </div>
        
        <!-- Bio Section -->
        <div class="profile-bio">
            <?php if (!empty($user['personal_info']['bio'])): ?>
                <p><?php echo nl2br(htmlspecialchars($user['personal_info']['bio'])); ?></p>
            <?php else: ?>
                <p class="no-bio"><i class="fas fa-info-circle"></i> No bio available yet.</p>
            <?php endif; ?>
        </div>

        <!-- Info Grid: 2 Columns (Department, Session, Hall, Room) -->
        <div class="info-grid">
            <div class="info-card">
                <div class="info-icon dept-icon">
                    <i class="fas fa-university"></i>
                </div>
                <div class="info-content">
                    <span class="info-label">Department</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['personal_info']['department'] ?? 'N/A'); ?></span>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-icon session-icon">
                    <i class="far fa-calendar-check"></i>
                </div>
                <div class="info-content">
                    <span class="info-label">Session</span>
                    <span class="info-value"><?php echo htmlspecialchars($user['personal_info']['session'] ?? 'N/A'); ?></span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon hall-icon">
                    <i class="fas fa-hotel"></i>
                </div>
                <div class="info-content">
                    <span class="info-label">Hall</span>
                    <span class="info-value">
                        <?php 
                        require_once dirname(__DIR__) . '/includes/helpers.php';
                        $hallName = getHallName($user['personal_info']['hall'] ?? '');
                        echo htmlspecialchars($hallName ?: 'N/A');
                        ?>
                    </span>
                </div>
            </div>

            <div class="info-card">
                <div class="info-icon room-icon">
                    <i class="fas fa-door-open"></i>
                </div>
                <div class="info-content">
                    <span class="info-label">Room</span>
                    <span class="info-value">
                        <?php if ($showSensitiveInfo): ?>
                            <?php echo htmlspecialchars($user['personal_info']['room_number'] ?? 'N/A'); ?>
                        <?php else: ?>
                            <span class="locked-text"><i class="fas fa-lock"></i> Private</span>
                        <?php endif; ?>
                    </span>
                </div>
            </div>
        </div>

        <!-- Stats Row: 1 Row for Owned, Borrowed, Lent -->
        <div class="stats-row">
            <div class="stat-card">
                <span class="stat-count"><?php echo $stats['owned']; ?></span>
                <span class="stat-text">Owned</span>
            </div>
            <div class="stat-card">
                <span class="stat-count"><?php echo $stats['borrowed']; ?></span>
                <span class="stat-text">Borrowed</span>
            </div>
            <div class="stat-card">
                <span class="stat-count"><?php echo $stats['lent']; ?></span>
                <span class="stat-text">Lent</span>
            </div>
        </div>

        <!-- Consolidated Actions Wrapper from sketch -->
        <div class="profile-actions-wrapper">
            <?php if ($isOwnProfile): ?>
                <div class="action-buttons-row">
                    <a href="/settings/edit-profile/" class="btn btn-profile-action add-btn" style="justify-content: center;">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </a>
                    <a href="/add-book/" class="btn btn-profile-action edit-btn" style="justify-content: center;">
                        <i class="fas fa-plus-circle"></i> Add Book
                    </a>
                </div>
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
            <div id="desktop-view-wrapper" class="hide-on-mobile">
                <?php renderBookCardGrid($books, ['showOwner' => false]); ?>
            </div>
            <div id="mobile-view-wrapper" class="show-on-mobile">
                <?php renderBookCardList($books, ['showOwner' => false]); ?>
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
            <div id="desktop-view-wrapper-borrowed" class="hide-on-mobile">
                <?php renderBookCardGrid($borrowedBooks, ['showOwner' => true, 'extraInfoKey' => 'owner_name', 'extraInfoLabel' => 'Borrowed from']); ?>
            </div>
            <div id="mobile-view-wrapper-borrowed" class="show-on-mobile">
                <?php renderBookCardList($borrowedBooks, ['showOwner' => true, 'extraInfoKey' => 'owner_name', 'extraInfoLabel' => 'Borrowed from']); ?>
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
            <div id="desktop-view-wrapper-lent" class="hide-on-mobile">
                <?php renderBookCardGrid($lentBooks, ['showOwner' => true, 'extraInfoKey' => 'borrower_name', 'extraInfoLabel' => 'Lent to']); ?>
            </div>
            <div id="mobile-view-wrapper-lent" class="show-on-mobile">
                <?php renderBookCardList($lentBooks, ['showOwner' => true, 'extraInfoKey' => 'borrower_name', 'extraInfoLabel' => 'Lent to']); ?>
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