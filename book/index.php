<?php
/**
 * OpenShelf Book Detail Page
 * Shows MAIN cover image (not thumbnail)
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_DATA_PATH', dirname(__DIR__) . '/data/book/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('BASE_URL', 'https://duopenshelf.top');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/BookCardGrid.php';
require_once dirname(__DIR__) . '/includes/BookCardList.php';

// Initialize mailer
$mailer = null;
try {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    require_once dirname(__DIR__) . '/lib/Mailer.php';
    $mailer = new Mailer();
} catch (Exception $e) {
    error_log("❌ Mailer initialization failed in book/index.php: " . $e->getMessage());
}


// Get book ID from URL
$bookId = $_GET['id'] ?? '';
if (empty($bookId)) {
    header('Location: /books/');
    exit;
}

/**
 * Load detailed book data from DB
 */
function loadDetailedBook($bookId) {
    if (empty($bookId)) return null;
    $db = getDB();
    $stmt = $db->prepare("
        SELECT b.*, 
               u.name as owner_name, 
               u.profile_pic as owner_profile_pic,
               u.room_number as owner_room,
               u.department as owner_dept,
               u.phone as owner_phone,
               u.email as owner_email,
               u.session as owner_session
        FROM books b
        LEFT JOIN users u ON b.owner_id = u.id
        WHERE b.id = ?
    ");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();
    
    if ($book) {
        $book['tags'] = json_decode($book['tags'] ?? '[]', true);
        $book['reviews'] = json_decode($book['reviews'] ?? '[]', true);
        $book['comments'] = json_decode($book['comments'] ?? '[]', true);
        
        // Construct the owner array in the format expected by the template
        $book['owner_data'] = [
            'personal_info' => [
                'name' => $book['owner_name'] ?? 'Unknown Owner',
                'profile_pic' => $book['owner_profile_pic'] ?? 'default-avatar.jpg',
                'room_number' => $book['owner_room'] ?? 'N/A',
                'department' => $book['owner_dept'] ?? 'N/A',
                'phone' => $book['owner_phone'] ?? '',
                'email' => $book['owner_email'] ?? '',
                'session' => $book['owner_session'] ?? ''
            ]
        ];
    }
    
    return $book ?: null;
}

/**
 * Load user data by ID
 */
function loadUserData($userId) {
    if (empty($userId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if ($user) {
        // Map DB columns to the old JSON structure to maintain compatibility
        return [
            'personal_info' => [
                'name' => $user['name'],
                'email' => $user['email'],
                'department' => $user['department'],
                'phone' => $user['phone'],
                'room_number' => $user['room_number'],
                'profile_pic' => $user['profile_pic'],
                'session' => $user['session'] ?? ''
            ],
            'id' => $user['id'],
            'role' => $user['role'],
            'status' => $user['status']
        ];
    }
    return null;
}

/**
 * Load all borrow requests for this book from DB
 */
function loadBorrowRequests($bookId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM borrow_requests WHERE book_id = ?");
    $stmt->execute([$bookId]);
    return $stmt->fetchAll();
}

/**
 * Check if user has already requested this book from DB
 */
function hasUserRequested($bookId, $userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM borrow_requests WHERE book_id = ? AND borrower_id = ? AND status = 'pending'");
    $stmt->execute([$bookId, $userId]);
    return $stmt->fetch() !== false;
}

/**
 * Format date for display
 */
function formatDate($date) {
    if (empty($date)) return 'N/A';
    $timestamp = strtotime($date);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    if ($diff < 604800) return floor($diff / 86400) . ' days ago';
    return date('M j, Y', $timestamp);
}

/**
 * Get cover image path - SHOW MAIN IMAGE FIRST
 */
function getCoverImagePath($coverImage) {
    if (empty($coverImage)) {
        return '/assets/images/default-book-cover.jpg';
    }
    
    // Check main image first (full size)
    $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/book_cover/' . $coverImage;
    $thumbPath = $_SERVER['DOCUMENT_ROOT'] . '/uploads/book_cover/thumb_' . $coverImage;
    
    // Prioritize main image over thumbnail
    if (file_exists($fullPath)) {
        return '/uploads/book_cover/' . $coverImage;
    } elseif (file_exists($thumbPath)) {
        return '/uploads/book_cover/thumb_' . $coverImage;
    }
    
    return '/assets/images/default-book-cover.jpg';
}

/**
 * Format phone for WhatsApp
 */
function formatPhoneForWhatsApp($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 11) {
        $phone = '88' . $phone;
    }
    return $phone;
}

/**
 * Create notification
 */
function createNotification($userId, $type, $title, $message, $link) {
    try {
        $db = getDB();
        $stmt = $db->prepare("
            INSERT INTO `notifications` 
            (id, user_id, type, title, message, link, is_read, created_at, expires_at) 
            VALUES (?, ?, ?, ?, ?, ?, 0, ?, ?)
        ");
        return $stmt->execute([
            'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
            $userId,
            $type,
            $title,
            $message,
            $link,
            date('Y-m-d H:i:s'),
            date('Y-m-d H:i:s', strtotime('+30 days'))
        ]);
    } catch (Exception $e) {
        error_log("Error creating notification: " . $e->getMessage());
        return false;
    }
}

/**
 * Check if user has wishlisted this book
 */
function isBookWishlisted($bookId, $userId) {
    if (empty($userId)) return false;
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM wishlist WHERE book_id = ? AND user_id = ?");
    $stmt->execute([$bookId, $userId]);
    return $stmt->fetch() !== false;
}

/**
 * Toggle wishlist entry (add or remove)
 * Returns ['added' => bool, 'count' => int]
 */
function toggleWishlist($bookId, $userId, $bookTitle) {
    $db = getDB();
    $check = $db->prepare("SELECT id FROM wishlist WHERE book_id = ? AND user_id = ?");
    $check->execute([$bookId, $userId]);
    if ($check->fetch()) {
        // Remove
        $db->prepare("DELETE FROM wishlist WHERE book_id = ? AND user_id = ?")->execute([$bookId, $userId]);
        $added = false;
    } else {
        // Add
        $db->prepare("
            INSERT INTO wishlist (user_id, book_id, book_title, notified, created_at)
            VALUES (?, ?, ?, 0, ?)
        ")->execute([$userId, $bookId, $bookTitle, date('Y-m-d H:i:s')]);
        $added = true;
    }
    $countStmt = $db->prepare("SELECT COUNT(*) FROM wishlist WHERE book_id = ?");
    $countStmt->execute([$bookId]);
    return ['added' => $added, 'count' => (int)$countStmt->fetchColumn()];
}

/**
 * Get wishlist count for a book
 */
function getWishlistCount($bookId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM wishlist WHERE book_id = ?");
    $stmt->execute([$bookId]);
    return (int)$stmt->fetchColumn();
}

/**
 * Load related books from same category
 */
function loadRelatedBooks($category, $excludeId, $limit = 4) {
    if (empty($category)) return [];
    $db = getDB();
    $stmt = $db->prepare("
        SELECT b.*, u.name as owner_name, u.profile_pic as owner_avatar, u.hall as owner_hall
        FROM books b
        LEFT JOIN users u ON b.owner_id = u.id
        WHERE b.category = ? AND b.id != ? AND b.status = 'available'
        ORDER BY RAND() 
        LIMIT ?
    ");
    // PDO::PARAM_INT for limit
    $stmt->bindValue(1, $category, PDO::PARAM_STR);
    $stmt->bindValue(2, $excludeId, PDO::PARAM_STR);
    $stmt->bindValue(3, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}


// Load detailed book data
$book = loadDetailedBook($bookId);
$pageTitle = $book['title'] ?? 'Book Detail';
if (!$book) {
    header('Location: /books/');
    exit;
}

// Count view when user visits the book page (GET request only)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $db = getDB();
        $stmt = $db->prepare("UPDATE books SET views = views + 1 WHERE id = ?");
        $stmt->execute([$bookId]);
        // Sync the local variable to show updated views immediately
        $book['views'] = ($book['views'] ?? 0) + 1;
    } catch (Exception $e) {
        error_log("❌ Failed to increment views for book ID $bookId: " . $e->getMessage());
    }
}


// Load owner data
$owner = $book['owner_data'] ?? null;
$reviews = $book['reviews'] ?? [];
$comments = $book['comments'] ?? [];
$borrowRequests = loadBorrowRequests($bookId);
$relatedBooks = loadRelatedBooks($book['category'] ?? '', $bookId);

// Check login status
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['user_name'] ?? 'Unknown';

// Check permissions
$isOwner = $isLoggedIn && $currentUserId === $book['owner_id'];
$hasRequested = $isLoggedIn && hasUserRequested($bookId, $currentUserId);
$canBorrow = $book['status'] === 'available' && $isLoggedIn && !$isOwner && !$hasRequested;

// Wishlist state
$isUnavailable = $book['status'] !== 'available';
$isWishlisted  = $isLoggedIn && !$isOwner && $isUnavailable && isBookWishlisted($bookId, $currentUserId);
$wishlistCount = getWishlistCount($bookId);

// Get rating from DB columns
$avgRating = number_format($book['rating'] ?? 0, 1);
$ratingCount = $book['rating_count'] ?? 0;

// Get cover image path - MAIN IMAGE
$coverImage = getCoverImagePath($book['cover_image'] ?? '');

// Generate WhatsApp link
$whatsappLink = '';
if ($isLoggedIn && !$isOwner && $owner && !empty($owner['personal_info']['phone'])) {
    $phone = formatPhoneForWhatsApp($owner['personal_info']['phone']);
    $message = "Hello " . ($owner['personal_info']['name'] ?? 'Owner') . "%0A%0A";
    $message .= "I am " . $currentUserName . "%0A";
    $message .= "I am interested in borrowing your book:%0A";
    $message .= "*" . $book['title'] . "* by " . $book['author'] . "%0A%0A";
    $message .= "Is it still available?%0A%0A";
    $message .= "Thanks!";
    $whatsappLink = "https://wa.me/{$phone}?text={$message}";
}

// Handle borrow request
$borrowMessage = '';
$borrowError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'borrow' && $canBorrow) {
        $borrower = loadUserData($currentUserId);
        $requestId = 'REQ' . time() . bin2hex(random_bytes(4));
        $duration = intval($_POST['duration'] ?? 14);
        $message = trim($_POST['message'] ?? '');
        
        $newRequest = [
            ':id' => $requestId,
            ':book_id' => $bookId,
            ':book_title' => $book['title'],
            ':book_author' => $book['author'],
            ':book_cover' => $book['cover_image'] ?? null,
            ':owner_id' => $book['owner_id'],
            ':owner_name' => $owner['personal_info']['name'] ?? 'Unknown',
            ':owner_email' => $owner['personal_info']['email'] ?? null,
            ':borrower_id' => $currentUserId,
            ':borrower_name' => $currentUserName,
            ':borrower_email' => $borrower['personal_info']['email'] ?? $_SESSION['user_email'] ?? null,
            ':status' => 'pending',
            ':request_date' => date('Y-m-d H:i:s'),
            ':expected_return_date' => date('Y-m-d H:i:s', strtotime("+{$duration} days")),
            ':duration_days' => $duration,
            ':message' => $message,
            ':updated_at' => date('Y-m-d H:i:s')
        ];
        
        $db = getDB();
        $sql = "INSERT INTO borrow_requests (
                    id, book_id, book_title, book_author, book_cover, owner_id, 
                    owner_name, owner_email, borrower_id, borrower_name, 
                    borrower_email, status, request_date, expected_return_date, 
                    duration_days, message, updated_at
                ) VALUES (
                    :id, :book_id, :book_title, :book_author, :book_cover, :owner_id, 
                    :owner_name, :owner_email, :borrower_id, :borrower_name, 
                    :borrower_email, :status, :request_date, :expected_return_date, 
                    :duration_days, :message, :updated_at
                )";
        
        $stmt = $db->prepare($sql);
        
        if ($stmt->execute($newRequest)) {
            // Update book status in DB
            $stmt = $db->prepare("UPDATE books SET status = 'reserved', updated_at = ? WHERE id = ?");
            $stmt->execute([date('Y-m-d H:i:s'), $bookId]);
            
            // Create notification for owner
            createNotification(
                $book['owner_id'],
                'borrow_request',
                'New Borrow Request',
                $currentUserName . ' wants to borrow "' . $book['title'] . '"',
                '/requests/?id=' . $requestId
            );
            
            // Send email notification to owner
            if ($mailer && !empty($owner['personal_info']['email'])) {
                $mailer->sendTemplate(
                    $owner['personal_info']['email'],
                    $owner['personal_info']['name'] ?? 'Owner',
                    'borrow_request',
                    [
                        'owner_name' => $owner['personal_info']['name'] ?? 'Owner',
                        'borrower_name' => $currentUserName,
                        'borrower_email' => $borrower['personal_info']['email'] ?? 'N/A',
                        'book_title' => $book['title'],
                        'book_author' => $book['author'],
                        'duration_days' => $duration,
                        'message' => $message,
                        'request_id' => $requestId,
                        'borrower_department' => $borrower['personal_info']['department'] ?? 'N/A',
                        'borrower_session' => $borrower['personal_info']['session'] ?? 'N/A',
                        'borrower_room' => $borrower['personal_info']['room_number'] ?? 'N/A',
                        'borrower_phone' => $borrower['personal_info']['phone'] ?? 'N/A',
                        'base_url' => BASE_URL,
                        'subject' => 'New Borrow Request: ' . $book['title']
                    ]
                );
            }

            $borrowMessage = 'Request sent successfully!';
            $hasRequested = true;
            
            // Refresh book data
            $book = loadDetailedBook($bookId);
$pageTitle = $book['title'] ?? 'Book Detail';
        } else {
            $borrowError = 'Failed to send request';
        }
    }
}

// Handle review submission (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'add_review') {
    header('Content-Type: application/json');
    
    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'Please login to review']);
        exit;
    }
    
    $rating = intval($_POST['rating'] ?? 0);
    $reviewText = trim($_POST['review_text'] ?? '');
    
    if ($rating < 1 || $rating > 5) {
        echo json_encode(['success' => false, 'message' => 'Invalid rating']);
        exit;
    }
    
    if (strlen($reviewText) < 10) {
        echo json_encode(['success' => false, 'message' => 'Review must be at least 10 characters']);
        exit;
    }
    
    // Check if already reviewed
    foreach ($reviews as $review) {
        if ($review['user_id'] === $currentUserId) {
            echo json_encode(['success' => false, 'message' => 'You have already reviewed this book']);
            exit;
        }
    }
    
    $newReview = [
        'id' => 'rev_' . uniqid() . '_' . bin2hex(random_bytes(4)),
        'user_id' => $currentUserId,
        'user_name' => $currentUserName,
        'rating' => $rating,
        'review_text' => $reviewText,
        'likes' => [],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $book['reviews'][] = $newReview;
    
    // Calculate new average rating and count
    $reviewCount = count($book['reviews']);
    $totalRating = 0;
    foreach ($book['reviews'] as $r) {
        $totalRating += $r['rating'];
    }
    $newAvgRating = round($totalRating / $reviewCount, 2);
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE books SET reviews = ?, rating = ?, rating_count = ?, updated_at = ? WHERE id = ?");
    if ($stmt->execute([json_encode($book['reviews']), $newAvgRating, $reviewCount, date('Y-m-d H:i:s'), $bookId])) {
        echo json_encode(['success' => true, 'review' => $newReview, 'new_rating' => $newAvgRating, 'new_count' => $reviewCount]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save review to database']);
    }
    exit;
}

// Handle comment submission (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'add_comment') {
    header('Content-Type: application/json');
    
    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'Please login to comment']);
        exit;
    }
    
    $commentText = trim($_POST['comment_text'] ?? '');
    
    if (strlen($commentText) < 2) {
        echo json_encode(['success' => false, 'message' => 'Comment must be at least 2 characters']);
        exit;
    }
    
    $newComment = [
        'id' => 'com_' . uniqid() . '_' . bin2hex(random_bytes(4)),
        'user_id' => $currentUserId,
        'user_name' => $currentUserName,
        'comment_text' => $commentText,
        'likes' => [],
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $book['comments'][] = $newComment;
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE books SET comments = ?, updated_at = ? WHERE id = ?");
    if ($stmt->execute([json_encode($book['comments']), date('Y-m-d H:i:s'), $bookId])) {
        echo json_encode(['success' => true, 'comment' => $newComment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save comment to database']);
    }
    exit;
}

// Handle wishlist toggle (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'toggle_wishlist') {
    header('Content-Type: application/json');

    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'Please login to use wishlist']);
        exit;
    }

    // Only allow wishlisting unavailable books
    if ($book['status'] === 'available') {
        echo json_encode(['success' => false, 'message' => 'Book is already available — request to borrow instead!']);
        exit;
    }

    // Owner cannot wishlist their own book
    if ($isOwner) {
        echo json_encode(['success' => false, 'message' => 'You cannot wishlist your own book']);
        exit;
    }

    $result = toggleWishlist($bookId, $currentUserId, $book['title']);
    echo json_encode(['success' => true, 'added' => $result['added'], 'count' => $result['count']]);
    exit;
}

// Handle like comment (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_action']) && $_POST['ajax_action'] === 'like_comment') {
    header('Content-Type: application/json');
    
    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'Please login to like']);
        exit;
    }
    
    $commentId = $_POST['comment_id'] ?? '';
    $commentFound = false;
    
    foreach ($book['comments'] as &$comment) {
        if ($comment['id'] === $commentId) {
            if (!isset($comment['likes'])) {
                $comment['likes'] = [];
            }
            
            if (in_array($currentUserId, $comment['likes'])) {
                $comment['likes'] = array_diff($comment['likes'], [$currentUserId]);
                $liked = false;
            } else {
                $comment['likes'][] = $currentUserId;
                $liked = true;
            }
            $commentFound = true;
            $likeCount = count($comment['likes']);
            break;
        }
    }
    
    if ($commentFound) {
        $db = getDB();
        $stmt = $db->prepare("UPDATE books SET comments = ? WHERE id = ?");
        if ($stmt->execute([json_encode($book['comments']), $bookId])) {
            echo json_encode(['success' => true, 'likes' => $likeCount, 'liked' => $liked]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to like comment in database']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Comment not found']);
    }
    exit;
}
?>

<?php include dirname(__DIR__) . '/includes/header.php'; ?>

<style>
    @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

    :root {
        --primary: #2C3E50;
        --primary-light: rgba(44, 62, 80, 0.1);
        --primary-dark: #1a252f;
        --primary-rgb: 44, 62, 80;
        --accent: #4C9F8A;
        --bg: #F8F9FA;
        --surface: hsla(0, 0%, 100%, 0.7);
        --surface-solid: #ffffff;
        --text-main: #0F172A;
        --text-muted: #5A6C7D;
        --border: #E2E8F0;
        --glass-border: hsla(0, 0%, 100%, 0.4);
        --shadow-premium: 0 20px 40px -15px rgba(0, 0, 0, 0.05);
        --radius-lg: 24px;
        --radius-md: 16px;
    }

    * { margin: 0; padding: 0; box-sizing: border-box; -webkit-tap-highlight-color: transparent; }
    body { 
        font-family: 'Outfit', 'Inter', system-ui, -apple-system, sans-serif; 
        background: var(--bg); 
        color: var(--text-main);
        line-height: 1.6;
        overflow-x: hidden;
        transition: background 0.3s, color 0.3s;
    }

    .book-detail { 
        max-width: 1200px; 
        margin: 0 auto; 
        padding: 0.5rem 1.5rem 2rem 1.5rem; 
        animation: fadeIn 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
    }

    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

    /* Centered Flat Book Cover Container & Wrapper */
    .detail-cover-container {
        display: flex;
        justify-content: center;
        margin-bottom: 0.5rem; /* Reduced from 2rem to 0.5rem to pull sections tighter */
    }
    .detail-cover-wrapper {
        position: relative;
        width: 200px;
        height: 280px;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.08), 0 3px 10px rgba(0, 0, 0, 0.04);
        border: 1px solid var(--border);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .detail-cover-wrapper:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12), 0 5px 15px rgba(0, 0, 0, 0.06);
    }
    .detail-cover-flat {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Minimal Availability Badge staying on top of the book cover */
    .status-badge-minimal {
        position: absolute;
        top: 10px;
        right: 10px;
        padding: 4px 10px;
        border-radius: 99px;
        font-size: 0.65rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        backdrop-filter: blur(8px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border: 1px solid rgba(255, 255, 255, 0.2);
        z-index: 5;
    }
    .status-badge-minimal.available { background: rgba(16, 185, 129, 0.9); color: white; }
    .status-badge-minimal.reserved, .status-badge-minimal.borrowed { background: rgba(245, 158, 11, 0.9); color: white; }
    .status-badge-minimal i { font-size: 6px; }

    /* Centered Single Column Layout */
    .book-content-wrapper {
        max-width: 850px;
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 1.5rem; /* Reduced from 2.5rem to 1.5rem for tighter vertical flow */
        margin-top: 1rem;
    }

    /* Title / Author */
    .detail-book-title {
        font-size: clamp(2.4rem, 5vw, 3.6rem);
        font-weight: 800;
        line-height: 1.15;
        margin-bottom: 0.25rem; /* Reduced from 0.5rem to 0.25rem */
        letter-spacing: -0.8px;
        color: var(--text-main);
        transition: color 0.3s;
    }
    .detail-book-author {
        font-size: 1.25rem;
        color: var(--primary);
        font-weight: 500;
        opacity: 0.9;
        margin-bottom: 0.5rem; /* Reduced from 1rem to 0.5rem */
    }

    /* Meta Grid */
    .meta-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 0.75rem; /* Reduced gap slightly */
        margin-bottom: 0.25rem; /* Reduced margin */
    }
    .meta-item {
        background: var(--surface-solid);
        padding: 1rem 0.5rem; /* Reduced padding from 1.25rem 0.75rem */
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        gap: 0.4rem; /* Reduced gap slightly */
        transition: all 0.3s;
    }
    .meta-item:hover { transform: translateY(-3px); border-color: var(--primary); box-shadow: var(--shadow-premium); }
    .meta-icon {
        width: 32px; /* Slightly more compact */
        height: 32px;
        background: rgba(76, 159, 138, 0.1);
        color: var(--accent);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.9rem;
        transition: all 0.3s;
    }
    .meta-label { font-size: 0.65rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.5px; }
    .meta-value { font-weight: 700; font-size: 0.85rem; width: 100%; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }

    /* Owner Card */
    .detail-owner-card {
        display: flex;
        align-items: center;
        gap: 1.25rem;
        background: var(--surface-solid);
        padding: 1rem 1.25rem; /* Reduced padding slightly */
        border-radius: var(--radius-md);
        border: 1px solid var(--border);
        text-decoration: none;
        color: inherit;
        transition: all 0.3s ease;
    }
    .detail-owner-card:hover { 
        border-color: var(--primary); 
        box-shadow: var(--shadow-premium);
        transform: scale(1.01);
    }
    .detail-owner-avatar-container { position: relative; }
    .detail-owner-avatar-large {
        width: 48px; /* Slightly more compact */
        height: 48px;
        border-radius: 14px;
        object-fit: cover;
        border: 2px solid var(--primary-light);
    }
    .detail-owner-name { font-weight: 700; font-size: 1rem; margin-bottom: 0.2rem; }
    .detail-owner-details { display: flex; gap: 0.75rem; font-size: 0.75rem; color: var(--text-muted); }
    .detail-owner-details i { color: var(--primary); opacity: 0.7; }

    /* Action Buttons */
    .action-group {
        display: flex;
        gap: 0.75rem; /* Reduced from 1rem to 0.75rem */
        flex-wrap: wrap;
    }
    .btn {
        padding: 0.85rem 1.5rem; /* Reduced padding from 0.95rem 1.75rem */
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        text-decoration: none;
        border: none;
        cursor: pointer;
        flex: 1;
        min-width: 170px;
    }
    .btn-primary { 
        background: var(--primary); 
        color: white; 
        box-shadow: 0 6px 15px -5px rgba(44, 62, 80, 0.3);
    }
    .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 10px 20px -7px rgba(44, 62, 80, 0.45); }
    .btn-whatsapp { background: #25d366; color: white; }
    .btn-whatsapp:hover { background: #1eb956; transform: translateY(-2px); }
    .btn-outline { background: white; border: 2px solid var(--border); color: var(--text-main); transition: all 0.3s; }
    .btn-outline:hover { border-color: var(--primary); color: var(--primary); background: rgba(44, 62, 80, 0.02); }

    /* Tabs Section */
    .tabs-container {
        background: var(--surface-solid);
        border-radius: var(--radius-lg);
        border: 1px solid var(--border);
        overflow: hidden;
        box-shadow: var(--shadow-premium);
        transition: background 0.3s, border-color 0.3s;
    }
    .tabs {
        display: flex;
        border-bottom: 1px solid var(--border);
        padding: 0 0.5rem; /* Reduced padding */
        background: #fafafa;
        overflow-x: auto;
        scrollbar-width: none;
        transition: background 0.3s, border-color 0.3s;
    }
    .tabs::-webkit-scrollbar {
        display: none;
    }
    
    .tab {
        padding: 1rem 1.25rem; /* Reduced from 1.25rem 1.5rem */
        font-weight: 600;
        color: var(--text-muted);
        border: none;
        background: none;
        cursor: pointer;
        position: relative;
        transition: all 0.3s;
        white-space: nowrap;
    }
    .tab.active { color: var(--primary); }
    .tab.active::after {
        content: '';
        position: absolute;
        bottom: -1px;
        left: 20%;
        right: 20%;
        height: 3px;
        background: var(--primary);
        border-radius: 10px 10px 0 0;
    }

    .tab-content { padding: 1.5rem 1.25rem; display: none; animation: fadeIn 0.4s ease; } /* Reduced padding from 2.5rem 2rem */
    .tab-content.active { display: block; }

    /* Specific Content Styling */
    .detail-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 1.25rem; /* Reduced gap slightly */
    }
    .detail-item {
        padding-bottom: 0.6rem; /* Reduced from 0.75rem */
        border-bottom: 1px solid var(--border);
        transition: border-color 0.3s;
    }
    .detail-item label { 
        display: block; 
        font-size: 0.75rem; 
        text-transform: uppercase; 
        letter-spacing: 1px; 
        color: var(--text-muted); 
        margin-bottom: 0.3rem; /* Reduced from 0.4rem */
    }
    .detail-item span { font-weight: 600; font-size: 0.9rem; }

    /* Entries (Reviews/Comments) */
    .entry-card {
        display: flex;
        gap: 1rem; /* Reduced from 1.25rem */
        padding: 1.25rem 0; /* Reduced from 1.75rem */
        border-bottom: 1px solid var(--border);
        transition: border-color 0.3s;
    }
    .entry-avatar { width: 40px; height: 40px; border-radius: 10px; object-fit: cover; } /* Slightly more compact */
    .entry-content { flex: 1; }
    .entry-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.4rem; }
    .entry-name { font-weight: 700; font-size: 0.95rem; }
    .entry-date { font-size: 0.75rem; color: var(--text-muted); }
    .entry-text { color: var(--text-main); opacity: 0.85; line-height: 1.6; }

    .rating-display { color: #facc15; font-size: 0.8rem; margin-top: 0.2/rem; }

    .form-dark {
        background: #f8fafc;
        padding: 1.25rem; /* Reduced padding from 1.75rem */
        border-radius: var(--radius-md);
        margin-bottom: 2rem; /* Reduced margin from 2.5rem */
        border: 1px solid var(--border);
        transition: background 0.3s, border-color 0.3s;
    }
    .form-control {
        width: 100%;
        padding: 0.85rem 1rem; /* Reduced from 1rem 1.25rem */
        border-radius: 10px;
        border: 2px solid var(--border);
        font-family: inherit;
        font-size: 0.9rem;
        background: white;
        transition: all 0.3s;
    }
    .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(44, 62, 80, 0.1); }
    .btn-submit {
        background: var(--primary);
        color: white;
        padding: 0.85rem 1.5rem;
        border-radius: 10px;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: all 0.3s;
    }
    .btn-submit:hover { background: var(--primary-dark); transform: translateY(-2px); }

    /* Related Books Section */
    .related-section {
        margin-top: 2rem; /* Reduced from 3rem */
        margin-bottom: 1.5rem; /* Reduced from 2.5rem */
    }
    .related-title {
        font-size: 1.35rem; /* Slightly more compact */
        font-weight: 800;
        margin-bottom: 1rem; /* Reduced from 1.5rem */
        letter-spacing: -0.3px;
        color: var(--text-main);
    }
    
    /* Owner Details Section container */
    .owner-section {
        margin-top: 0.5rem;
    }

    @media (max-width: 768px) {
        .book-detail { padding: 0.5rem 0.75rem; } /* Reduced top padding */
        .detail-book-title {
            font-size: 1.8rem;
            text-align: center;
        }
        .detail-book-author {
            font-size: 1.1rem;
            text-align: center;
            margin-bottom: 1rem;
        }
        
        /* Meta Grid 1 Row Minimal for Mobile */
        .meta-grid {
            grid-template-columns: repeat(4, 1fr);
            gap: 0.35rem; /* Tighter gap */
            margin-bottom: 1.25rem; /* Reduced */
        }
        .meta-item {
            padding: 0.5rem 0.2rem; /* Tighter padding */
            border-radius: 10px;
            gap: 0.2rem;
            background: var(--surface-solid);
            border: 1px solid var(--border);
        }
        .meta-icon {
            width: 24px;
            height: 24px;
            font-size: 0.7rem;
            border-radius: 6px;
        }
        .meta-label {
            font-size: 0.5rem;
            letter-spacing: 0.2px;
        }
        .meta-value {
            font-size: 0.65rem;
        }
        
        .action-group {
            flex-direction: column;
            gap: 0.6rem; /* Tighter gap */
            width: 100%;
        }
        .btn {
            width: 100%;
            padding: 0.8rem 1.25rem;
            font-size: 0.9rem;
        }
        .detail-owner-card {
            padding: 0.85rem; /* Tighter padding */
        }
        .tab {
            padding: 0.85rem 0.6rem; /* Tighter tab padding */
            font-size: 0.85rem;
        }
        .tab-content {
            padding: 1.25rem 0.6rem; /* Tighter content padding */
        }
    }

    /* ── Wishlist Button ──────────────────────────────── */
    .btn-wishlist {
        background: #fff;
        border: 2px solid var(--border);
        color: var(--text-muted);
        gap: 0.5rem;
        flex: 0 0 auto;
        min-width: unset;
        padding: 0.85rem 1.25rem;
        border-radius: var(--radius-md);
        font-weight: 600;
        font-size: 0.9rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
    }
    .btn-wishlist i {
        font-size: 1rem;
        transition: transform 0.3s ease, color 0.3s ease;
    }
    .btn-wishlist:hover {
        border-color: #e11d48;
        color: #e11d48;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(225, 29, 72, 0.15);
    }
    .btn-wishlist.wishlisted {
        background: linear-gradient(135deg, #fff1f2, #ffe4e6);
        border-color: #e11d48;
        color: #e11d48;
    }
    .btn-wishlist.wishlisted i {
        animation: heartBeat 0.4s ease;
    }
    @keyframes heartBeat {
        0%   { transform: scale(1); }
        40%  { transform: scale(1.35); }
        70%  { transform: scale(0.9); }
        100% { transform: scale(1); }
    }
    .btn-wishlist .wishlist-count {
        font-size: 0.75rem;
        font-weight: 700;
        background: rgba(225, 29, 72, 0.12);
        color: #e11d48;
        border-radius: 99px;
        padding: 1px 6px;
        display: inline-block;
        line-height: 1.4;
    }
    .wishlist-tooltip {
        position: absolute;
        bottom: calc(100% + 8px);
        left: 50%;
        transform: translateX(-50%);
        background: #1e293b;
        color: #fff;
        font-size: 0.7rem;
        font-weight: 600;
        padding: 5px 10px;
        border-radius: 8px;
        white-space: nowrap;
        pointer-events: none;
        opacity: 0;
        transition: opacity 0.2s ease;
        z-index: 20;
    }
    .btn-wishlist:hover .wishlist-tooltip { opacity: 1; }

    .modal-card {
        background: var(--surface-solid, #ffffff);
        border-radius: var(--radius-xl, 24px);
        max-width: 500px;
        width: 100%;
        max-height: 90vh;
        overflow-y: auto;
        padding: 2rem;
        border: 1px solid var(--border, #e2e8f0);
        box-shadow: 0 20px 50px rgba(0,0,0,0.15);
        animation: modalSlideUp 0.3s ease;
    }

    /* Dark Mode Overrides */
    :root[data-theme="dark"] {
        --bg: #0f172a;
        --surface: hsla(215, 28%, 17%, 0.7);
        --surface-solid: #1e293b;
        --text-main: #f8fafc;
        --text-muted: #94a3b8;
        --border: #334155;
        --glass-border: hsla(215, 28%, 17%, 0.4);
    }
    [data-theme="dark"] .detail-book-title { color: #f8fafc; }
    [data-theme="dark"] .cover-wrapper { background: #0f172a; }
    [data-theme="dark"] .meta-icon { background: #0f172a; }
    [data-theme="dark"] .btn-outline { background: #1e293b; border-color: #334155; color: #f8fafc; }
    [data-theme="dark"] .btn-outline:hover { background: #334155; }
    [data-theme="dark"] .tabs { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .form-dark { background: #1e293b; }
    [data-theme="dark"] .form-control { background: #0f172a; border-color: #334155; color: #f8fafc; }
    [data-theme="dark"] .modal-card { background: #1e293b; }
    [data-theme="dark"] .entry-text { color: #cbd5e1; }
    .borrow-card {
        background: var(--surface-solid, #ffffff);
        border: 1px solid var(--border, #e2e8f0);
        border-radius: var(--radius-md, 12px);
        padding: 1.25rem 1.5rem;
        box-shadow: var(--shadow-premium, 0 10px 30px -10px rgba(0,0,0,0.05));
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }
    .borrow-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border, #e2e8f0);
        padding-bottom: 0.75rem;
    }
    .card-status-label {
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-muted, #64748b);
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .card-status-label.available {
        color: var(--primary, #4c9f8a);
    }
    .card-status-label.unavailable {
        color: #ef4444;
    }
</style>

<div class="book-detail">

            
            <?php if ($borrowMessage): ?>
                <div class="alert alert-success" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius-md);"><?php echo $borrowMessage; ?></div>
            <?php endif; ?>
            <?php if ($borrowError): ?>
                <div class="alert alert-danger" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius-md);"><?php echo $borrowError; ?></div>
            <?php endif; ?>
            
            <!-- Immersive Layout Centered Content Wrapper -->
            <div class="book-content-wrapper">
                
                <!-- Centered Flat Book Cover with Minimal Status Badge -->
                <div class="detail-cover-container">
                    <div class="detail-cover-wrapper">
                        <img src="<?php echo $coverImage; ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>"
                             class="detail-cover-flat"
                             onerror="this.src='/assets/images/default-book-cover.jpg'">
                        
                        <!-- Minimal Availability Badge staying on top of the book cover -->
                        <span class="status-badge-minimal <?php echo $book['status']; ?>">
                            <i class="fas fa-circle"></i>
                            <?php echo ucfirst($book['status']); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Title & Author -->
                <div class="book-header-section">
                    <h1 class="detail-book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <div class="detail-book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
                </div>
                
                <!-- Meta Grid -->
                <div class="meta-grid">
                    <div class="meta-item">
                        <div class="meta-icon"><i class="fas fa-tag"></i></div>
                        <span class="meta-label">Category</span>
                        <span class="meta-value"><?php echo htmlspecialchars($book['category'] ?? 'General'); ?></span>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="fas fa-star"></i></div>
                        <span class="meta-label">Rating</span>
                        <span class="meta-value"><?php echo $avgRating; ?> <span style="font-weight:400;opacity:0.6;font-size:0.8rem">(<?php echo $ratingCount; ?>)</span></span>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="fas fa-calendar"></i></div>
                        <span class="meta-label">Added</span>
                        <span class="meta-value"><?php echo date('M j, Y', strtotime($book['created_at'])); ?></span>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="fas fa-eye"></i></div>
                        <span class="meta-label">Views</span>
                        <span class="meta-value"><?php echo number_format($book['views'] ?? 0); ?></span>
                    </div>
                </div>

                <!-- 1. Owner Profile Section -->
                <div class="owner-section">
                    <a href="/profile/?id=<?php echo $book['owner_id']; ?>" class="detail-owner-card">
                        <div class="detail-owner-avatar-container">
                            <img src="/uploads/profile/<?php echo htmlspecialchars($owner['personal_info']['profile_pic'] ?? 'default-avatar.jpg'); ?>" 
                                 class="detail-owner-avatar-large" 
                                 alt="<?php echo htmlspecialchars($owner['personal_info']['name'] ?? 'Owner'); ?>"
                                 onerror="this.src='/assets/images/avatars/default.jpg'">
                        </div>
                        <div style="flex:1">
                            <div class="detail-owner-name"><?php echo htmlspecialchars($owner['personal_info']['name'] ?? 'Unknown Owner'); ?></div>
                            <div class="detail-owner-details">
                                <span><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($owner['personal_info']['room_number'] ?? 'N/A'); ?></span>
                                <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($owner['personal_info']['department'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div style="color: var(--primary); opacity: 0.5;">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                </div>

                <!-- 2. Action Card -->
                <div class="action-section borrow-card">
                    <div class="borrow-card-header">
                        <?php if ($isOwner): ?>
                            <span class="card-status-label"><i class="fas fa-user-cog"></i> Listing Owner</span>
                        <?php elseif ($book['status'] === 'available'): ?>
                            <span class="card-status-label available"><i class="fas fa-check-circle"></i> Available to Borrow</span>
                        <?php else: ?>
                            <span class="card-status-label unavailable"><i class="fas fa-info-circle"></i> Currently Unavailable</span>
                        <?php endif; ?>
                    </div>
                    <div class="action-group">
                        <?php if ($isOwner): ?>
                            <a href="/edit-book/?id=<?php echo $bookId; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Listing
                            </a>
                            <button onclick="shareBook()" class="btn btn-outline">
                                <i class="fas fa-share-alt"></i> Share Listing
                            </button>
                        <?php elseif ($canBorrow): ?>
                            <button onclick="showBorrowModal()" class="btn btn-primary">
                                <i class="fas fa-handshake"></i> Request to Borrow
                            </button>
                            <?php if ($whatsappLink): ?>
                                <a href="<?php echo $whatsappLink; ?>" target="_blank" class="btn btn-whatsapp">
                                    <i class="fab fa-whatsapp"></i> Chat with Owner
                                </a>
                            <?php endif; ?>
                        <?php elseif ($hasRequested): ?>
                            <button class="btn btn-secondary" disabled style="background:#f1f5f9; color:#94a3b8; border:1px solid #e2e8f0; cursor: not-allowed; flex:1;">
                                <i class="fas fa-clock"></i> Request Pending
                            </button>
                            <a href="/requests/" class="btn btn-outline">Manage Requests</a>
                        <?php elseif (!$isLoggedIn): ?>
                            <a href="/login/?redirect=/book/?id=<?php echo $bookId; ?>" class="btn btn-primary">
                                Join to Borrow
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled style="cursor: not-allowed; flex:1;">
                                <i class="fas fa-lock"></i> Currently Unavailable
                            </button>
                        <?php endif; ?>

                        <?php if ($isLoggedIn && !$isOwner && $isUnavailable): ?>
                            <!-- Wishlist toggle button (only shown when book is unavailable) -->
                            <button
                                id="wishlistBtn"
                                class="btn-wishlist <?php echo $isWishlisted ? 'wishlisted' : ''; ?>"
                                onclick="toggleWishlist()"
                                title="<?php echo $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist'; ?>"
                                aria-label="Wishlist"
                            >
                                <span class="wishlist-tooltip">
                                    <?php echo $isWishlisted ? 'Remove from wishlist' : 'Notify me when available'; ?>
                                </span>
                                <i class="<?php echo $isWishlisted ? 'fas' : 'far'; ?> fa-heart"></i>
                                <?php if ($wishlistCount > 0): ?>
                                    <span class="wishlist-count" id="wishlistCount"><?php echo $wishlistCount; ?></span>
                                <?php else: ?>
                                    <span class="wishlist-count" id="wishlistCount" style="display:none">0</span>
                                <?php endif; ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 3. The Tab-Container -->
                <div class="tabs-container">
                    <div class="tabs">
                        <button class="tab active" data-tab="description" onclick="switchTab('description')">Description</button>
                        <button class="tab" data-tab="details" onclick="switchTab('details')">Details</button>
                        <button class="tab" data-tab="reviews" onclick="switchTab('reviews')">Reviews <span style="font-size:0.85rem;opacity:0.6">(<?php echo count($reviews); ?>)</span></button>
                        <button class="tab" data-tab="comments" onclick="switchTab('comments')">Comments <span style="font-size:0.85rem;opacity:0.6">(<?php echo count($comments); ?>)</span></button>
                        <button class="tab" data-tab="history" onclick="switchTab('history')">History</button>
                    </div>
                    
                    <!-- Description -->
                    <div id="description-tab" class="tab-content active">
                        <p style="font-size:1.05rem;line-height:1.75;color:var(--text-main);opacity:0.95;white-space:pre-line">
                            <?php echo nl2br(htmlspecialchars($book['description'] ?? 'No description available.')); ?>
                        </p>
                    </div>
                    
                    <!-- Details -->
                    <div id="details-tab" class="tab-content">
                        <div class="detail-grid">
                            <div class="detail-item"><label>ISBN</label><span><?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></span></div>
                            <div class="detail-item"><label>Publisher</label><span><?php echo htmlspecialchars($book['publisher'] ?? 'N/A'); ?></span></div>
                            <div class="detail-item"><label>Year</label><span><?php echo htmlspecialchars($book['publication_year'] ?? 'N/A'); ?></span></div>
                            <div class="detail-item"><label>Pages</label><span><?php echo htmlspecialchars($book['pages'] ?? 'N/A'); ?></span></div>
                            <div class="detail-item"><label>Language</label><span><?php echo htmlspecialchars($book['language'] ?? 'English'); ?></span></div>
                            <div class="detail-item"><label>Condition</label><span><?php echo htmlspecialchars($book['condition'] ?? 'Good'); ?></span></div>
                        </div>
                    </div>
                    
                    <!-- Reviews -->
                    <div id="reviews-tab" class="tab-content">
                        <?php if ($isLoggedIn && !$isOwner): ?>
                            <div class="form-dark">
                                <h4 style="margin-bottom:1rem;font-weight:700">Write a Review</h4>
                                <div class="rating-stars" id="ratingStarsInput" style="margin-bottom:1.5rem">
                                    <i class="far fa-star" data-rating="1"></i>
                                    <i class="far fa-star" data-rating="2"></i>
                                    <i class="far fa-star" data-rating="3"></i>
                                    <i class="far fa-star" data-rating="4"></i>
                                    <i class="far fa-star" data-rating="5"></i>
                                </div>
                                <textarea id="reviewText" class="form-control" rows="4" placeholder="What did you think of the book?"></textarea>
                                <button onclick="submitReview()" class="btn btn-primary" style="margin-top:1.5rem;max-width:220px">Submit Review</button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($reviews)): ?>
                            <div class="empty-state"><i class="far fa-star"></i><p>No reviews yet. Be the first to share your thoughts!</p></div>
                        <?php else: foreach ($reviews as $review): $reviewer = loadUserData($review['user_id']); ?>
                            <div class="entry-card">
                                <img src="/uploads/profile/<?php echo htmlspecialchars($reviewer['personal_info']['profile_pic'] ?? 'default-avatar.jpg'); ?>" class="entry-avatar">
                                <div class="entry-content">
                                    <div class="entry-header">
                                        <div>
                                            <div class="entry-name"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                            <div class="rating-display">
                                                <?php for($i=1; $i<=5; $i++): ?>
                                                    <i class="<?php echo ($i <= $review['rating']) ? 'fas fa-star' : 'far fa-star'; ?>"></i>
                                                <?php endfor; ?>
                                            </div>
                                        </div>
                                        <div class="entry-date"><?php echo formatDate($review['created_at']); ?></div>
                                    </div>
                                    <p class="entry-text"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    
                    <!-- Comments -->
                    <div id="comments-tab" class="tab-content">
                        <?php if ($isLoggedIn): ?>
                            <div class="form-dark">
                                <h4 style="margin-bottom:1rem;font-weight:700">Add a Comment</h4>
                                <textarea id="commentText" class="form-control" rows="3" placeholder="Ask a question or share a thought..."></textarea>
                                <button onclick="submitComment()" class="btn btn-primary" style="margin-top:1.5rem;max-width:180px">Post Comment</button>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (empty($comments)): ?>
                            <div class="empty-state"><i class="far fa-comments"></i><p>No comments yet. Start the conversation!</p></div>
                        <?php else: foreach ($comments as $comment): 
                            $commenter = loadUserData($comment['user_id']); 
                            $userLiked = $isLoggedIn && in_array($currentUserId, $comment['likes'] ?? []);
                        ?>
                            <div class="entry-card">
                                <img src="/uploads/profile/<?php echo htmlspecialchars($commenter['personal_info']['profile_pic'] ?? 'default-avatar.jpg'); ?>" class="entry-avatar">
                                <div class="entry-content">
                                    <div class="entry-header">
                                        <span class="entry-name"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                        <span class="entry-date"><?php echo formatDate($comment['created_at']); ?></span>
                                    </div>
                                    <p class="entry-text" style="margin-bottom:1rem"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                    <button onclick="likeComment('<?php echo $comment['id']; ?>', this)" class="like-btn <?php echo $userLiked ? 'active' : ''; ?>" style="background:var(--bg);padding:0.5rem 1rem;border-radius:10px;display:inline-flex;align-items:center;gap:0.5rem;border:none;cursor:pointer;transition:all 0.2s">
                                        <i class="fas fa-heart"></i> <span class="like-count" style="font-weight:600"><?php echo count($comment['likes'] ?? []); ?></span>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                    
                    <!-- History -->
                    <div id="history-tab" class="tab-content">
                        <?php if (empty($borrowRequests)): ?>
                            <div class="empty-state"><i class="fas fa-history"></i><p>No borrow history yet.</p></div>
                        <?php else: foreach ($borrowRequests as $request): ?>
                            <div class="entry-card">
                                <div style="width:10px;height:10px;border-radius:50%;margin-top:0.6rem;background:<?php echo $request['status'] === 'approved' ? '#10b981' : ($request['status'] === 'pending' ? '#f59e0b' : '#ef4444'); ?>;box-shadow: 0 0 10px <?php echo $request['status'] === 'approved' ? 'rgba(16,185,129,0.4)' : ($request['status'] === 'pending' ? 'rgba(245,158,11,0.4)' : 'rgba(239,68,68,0.4)'); ?>"></div>
                                <div class="entry-content">
                                    <div class="entry-header">
                                        <span class="entry-name"><?php echo htmlspecialchars($request['borrower_name']); ?></span>
                                        <span class="entry-date"><?php echo date('M j, Y', strtotime($request['request_date'])); ?></span>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:0.75rem">
                                        <span class="status-badge <?php echo $request['status']; ?>" style="position:static;font-size:0.65rem;padding:0.4rem 0.8rem;border-radius:8px">
                                            <?php echo strtoupper($request['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Borrow Modal -->
    <div id="borrowModal" class="modal">
        <div class="modal-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                <h3 style="margin:0;font-size:1.6rem;font-weight:800;letter-spacing:-0.5px">Request to Borrow</h3>
                <button onclick="closeModal('borrowModal')" style="background:var(--bg);border:none;width:36px;height:36px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.2rem">&times;</button>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="borrow">
                <div style="margin-bottom:1.5rem">
                    <label style="display:block;margin-bottom:0.75rem;font-weight:600;font-size:0.9rem;color:var(--text-muted)">BORROW DURATION</label>
                    <select name="duration" class="form-control duration-select">
                        <option value="7">7 days</option>
                        <option value="14" selected>14 days</option>
                        <option value="21">21 days</option>
                        <option value="30">30 days</option>
                    </select>
                </div>
                <div style="margin-bottom:2.5rem">
                    <label style="display:block;margin-bottom:0.75rem;font-weight:600;font-size:0.9rem;color:var(--text-muted)">MESSAGE TO OWNER <span style="font-weight:400;opacity:0.6">(OPTIONAL)</span></label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Hi! I'd love to read this book..."></textarea>
                </div>
                <div style="display:flex;gap:1rem">
                    <button type="button" onclick="closeModal('borrowModal')" class="btn btn-outline" style="flex:1">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex:2">Send Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Related Books Section -->
    <?php if (!empty($relatedBooks)): ?>
    <div class="book-detail">
        <div class="related-section">
            <h2 class="related-title">
                <i class="fas fa-layer-group"></i>
                Related Books
            </h2>
            <div class="hide-on-mobile">
                <?php renderBookCardGrid($relatedBooks, ['gridClass' => 'book-grid']); ?>
            </div>
            <div class="show-on-mobile">
                <?php renderBookCardList($relatedBooks); ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
        // Tab switching
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.tab[data-tab="${tab}"]`)?.classList.add('active');
            
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(tab + '-tab').classList.add('active');
        }
        
        // Modal
        function showBorrowModal() { document.getElementById('borrowModal').classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        
        // Rating stars
        let currentRating = 0;
        document.addEventListener('DOMContentLoaded', () => {
            const stars = document.querySelectorAll('#ratingStarsInput i');
            stars.forEach(star => {
                star.addEventListener('click', function () {
                    currentRating = parseInt(this.dataset.rating);
                    stars.forEach((s, index) => {
                        s.className = (index + 1 <= currentRating) ? 'fas fa-star' : 'far fa-star';
                    });
                });
                star.addEventListener('mouseover', function () {
                    const hoverRating = parseInt(this.dataset.rating);
                    stars.forEach((s, index) => {
                        s.className = (index + 1 <= hoverRating) ? 'fas fa-star' : 'far fa-star';
                    });
                });
                star.addEventListener('mouseleave', function () {
                    stars.forEach((s, index) => {
                        s.className = (index + 1 <= currentRating) ? 'fas fa-star' : 'far fa-star';
                    });
                });
            });
        });
        
        // Submit review
        function submitReview() {
            if (currentRating === 0) { alert('Please select a rating'); return; }
            const reviewText = document.getElementById('reviewText').value.trim();
            if (reviewText.length < 10) { alert('Review must be at least 10 characters'); return; }
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ ajax_action: 'add_review', rating: currentRating, review_text: reviewText })
            }).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else alert(data.message || 'Failed to submit review');
            }).catch(() => alert('Network error'));
        }
        
        // Submit comment
        function submitComment() {
            const commentText = document.getElementById('commentText').value.trim();
            if (commentText.length < 2) { alert('Comment must be at least 2 characters'); return; }
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ ajax_action: 'add_comment', comment_text: commentText })
            }).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else alert(data.message || 'Failed to post comment');
            }).catch(() => alert('Network error'));
        }
        
        // Like comment
        function likeComment(commentId, btn) {
            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ ajax_action: 'like_comment', comment_id: commentId })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    const countEl = btn.querySelector('.like-count');
                    if (countEl) countEl.textContent = data.likes;
                    if (data.liked) btn.classList.add('active');
                    else btn.classList.remove('active');
                }
            }).catch(e => console.error(e));
        }
        
        // ── Wishlist toggle ─────────────────────────────────────────
        function toggleWishlist() {
            const btn   = document.getElementById('wishlistBtn');
            const icon  = btn ? btn.querySelector('i') : null;
            const badge = document.getElementById('wishlistCount');
            if (!btn || !icon) return;

            // Optimistic UI
            const wasWishlisted = btn.classList.contains('wishlisted');
            btn.classList.toggle('wishlisted', !wasWishlisted);
            icon.className = wasWishlisted ? 'far fa-heart' : 'fas fa-heart';

            fetch(window.location.href, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ ajax_action: 'toggle_wishlist' })
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    // Revert optimistic update
                    btn.classList.toggle('wishlisted', wasWishlisted);
                    icon.className = wasWishlisted ? 'fas fa-heart' : 'far fa-heart';
                    alert(data.message || 'Failed to update wishlist');
                    return;
                }
                // Update badge count
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'inline-block' : 'none';
                }
                // Update tooltip text
                const tip = btn.querySelector('.wishlist-tooltip');
                if (tip) tip.textContent = data.added ? 'Remove from wishlist' : 'Notify me when available';
                btn.title = data.added ? 'Remove from wishlist' : 'Add to wishlist';
            })
            .catch(() => {
                // Revert
                btn.classList.toggle('wishlisted', wasWishlisted);
                icon.className = wasWishlisted ? 'fas fa-heart' : 'far fa-heart';
            });
        }

        // Share
        function shareBook() {
            if (navigator.share) {
                navigator.share({ title: '<?php echo addslashes($book['title']); ?>', text: 'Check out this amazing book on OpenShelf!', url: window.location.href });
            } else {
                navigator.clipboard.writeText(window.location.href).then(() => alert('Link copied to clipboard!'));
            }
        }
        
        // Close modal on outside click
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('borrowModal');
            if (e.target === modal) closeModal('borrowModal');
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('borrowModal');
                if (modal && modal.classList.contains('active')) closeModal('borrowModal');
            }
        });
    </script>
    
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>