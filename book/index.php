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
define('BASE_URL', 'https://openshelf.free.nf');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

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
    $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    $book = $stmt->fetch();
    
    if ($book) {
        $book['tags'] = json_decode($book['tags'] ?? '[]', true);
        $book['reviews'] = json_decode($book['reviews'] ?? '[]', true);
        $book['comments'] = json_decode($book['comments'] ?? '[]', true);
    }
    
    return $book ?: null;
}

/**
 * Load user data by ID
 */
function loadUserData($userId) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) return null;
    return json_decode(file_get_contents($userFile), true);
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
    $userFile = dirname(__DIR__) . '/users/' . $userId . '.json';
    if (!file_exists($userFile)) return false;
    
    $userData = json_decode(file_get_contents($userFile), true);
    $notifications = $userData['notifications'] ?? [];
    
    $notifications[] = [
        'id' => 'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
        'user_id' => $userId,
        'type' => $type,
        'title' => $title,
        'message' => $message,
        'link' => $link,
        'is_read' => false,
        'created_at' => date('Y-m-d H:i:s'),
        'expires_at' => date('Y-m-d H:i:s', strtotime('+30 days'))
    ];
    
    // Sort and limit
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });
    $notifications = array_slice($notifications, 0, 25);
    
    $userData['notifications'] = $notifications;
    return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Load related books from same category
 */
function loadRelatedBooks($category, $excludeId, $limit = 4) {
    if (empty($category)) return [];
    $db = getDB();
    $stmt = $db->prepare("
        SELECT b.*, u.name as owner_name 
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
if (!$book) {
    header('Location: /books/');
    exit;
}

// Load owner data
$owner = loadUserData($book['owner_id']);
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

// Calculate average rating
$avgRating = 0;
if (!empty($reviews)) {
    $totalRating = array_sum(array_column($reviews, 'rating'));
    $avgRating = round($totalRating / count($reviews), 1);
}

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
            ':borrower_email' => $_SESSION['user_email'] ?? null,
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
                $borrower = loadUserData($currentUserId);
                $mailer->sendTemplate(
                    $owner['personal_info']['email'],
                    $owner['personal_info']['name'] ?? 'Owner',
                    'borrow_request',
                    [
                        'owner_name' => $owner['personal_info']['name'] ?? 'Owner',
                        'borrower_name' => $currentUserName,
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
    
    $db = getDB();
    $stmt = $db->prepare("UPDATE books SET reviews = ?, updated_at = ? WHERE id = ?");
    if ($stmt->execute([json_encode($book['reviews']), date('Y-m-d H:i:s'), $bookId])) {
        echo json_encode(['success' => true, 'review' => $newReview]);
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title><?php echo htmlspecialchars($book['title']); ?> - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');

        :root {
            --primary-h: 263;
            --primary-s: 70%;
            --primary-l: 50%;
            --primary: hsl(var(--primary-h), var(--primary-s), var(--primary-l));
            --primary-light: hsl(var(--primary-h), var(--primary-s), 95%);
            --primary-dark: hsl(var(--primary-h), var(--primary-s), 40%);
            --accent: hsl(199, 89%, 48%);
            --bg: hsl(210, 40%, 98%);
            --surface: hsla(0, 0%, 100%, 0.7);
            --surface-solid: #ffffff;
            --text-main: hsl(222, 47%, 11%);
            --text-muted: hsl(215, 16%, 47%);
            --border: hsla(214, 32%, 91%, 0.8);
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
        }

        .book-detail { 
            max-width: 1200px; 
            margin: 0 auto; 
            padding: 2rem 1.5rem; 
            animation: fadeIn 0.8s cubic-bezier(0.2, 0.8, 0.2, 1);
        }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 2rem;
            padding: 0.5rem 0;
        }
        .breadcrumb a { 
            color: var(--text-muted); 
            text-decoration: none; 
            transition: color 0.2s;
        }
        .breadcrumb a:hover { color: var(--primary); }

        /* Book Layout */
        .book-layout { 
            display: grid; 
            grid-template-columns: 1fr; 
            gap: 3rem; 
            align-items: start;
        }
        @media (min-width: 992px) { 
            .book-layout { grid-template-columns: 350px 1fr; } 
        }

        /* Cover Section */
        .book-cover-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        .cover-wrapper {
            position: relative;
            aspect-ratio: 3 / 4.5;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: 0 30px 60px -12px rgba(0, 0, 0, 0.25);
            background: #fff;
            transition: transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .cover-wrapper:hover { transform: scale(1.02); }

        .book-cover-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .status-badge {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            padding: 0.6rem 1.2rem;
            border-radius: 99px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            backdrop-filter: blur(12px);
            z-index: 2;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .status-badge.available { background: rgba(16, 185, 129, 0.9); color: white; }
        .status-badge.reserved, .status-badge.borrowed { background: rgba(245, 158, 11, 0.9); color: white; }

        /* Info Section */
        .book-info-section {
            padding: 1rem 0;
        }

        .book-header { margin-bottom: 2.5rem; }
        .book-title {
            font-size: clamp(2rem, 5vw, 3.2rem);
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 0.5rem;
            letter-spacing: -1px;
            color: #1a1a1a;
        }
        .book-author {
            font-size: 1.35rem;
            color: var(--primary);
            font-weight: 500;
            opacity: 0.9;
        }

        /* Meta Grid */
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
            gap: 1rem;
            margin-bottom: 2.5rem;
        }
        .meta-item {
            background: var(--surface-solid);
            padding: 1.25rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        .meta-item:hover { transform: translateY(-3px); border-color: var(--primary); box-shadow: var(--shadow-premium); }
        .meta-icon {
            width: 36px;
            height: 36px;
            background: var(--primary-light);
            color: var(--primary);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
        }
        .meta-label { font-size: 0.75rem; color: var(--text-muted); text-transform: uppercase; letter-spacing: 1px; }
        .meta-value { font-weight: 600; font-size: 0.95rem; }

        /* Owner Card */
        .owner-card {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            background: var(--surface-solid);
            padding: 1.25rem;
            border-radius: var(--radius-md);
            border: 1px solid var(--border);
            text-decoration: none;
            color: inherit;
            margin-bottom: 2.5rem;
            transition: all 0.3s ease;
        }
        .owner-card:hover { 
            border-color: var(--primary); 
            box-shadow: var(--shadow-premium);
            transform: scale(1.01);
        }
        .owner-avatar-container { position: relative; }
        .owner-avatar-large {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            object-fit: cover;
            border: 2px solid var(--primary-light);
        }
        .owner-name { font-weight: 700; font-size: 1.1rem; margin-bottom: 0.25rem; }
        .owner-details { display: flex; gap: 1rem; font-size: 0.85rem; color: var(--text-muted); }
        .owner-details i { color: var(--primary); opacity: 0.7; }

        /* Action Buttons */
        .action-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .btn {
            padding: 1rem 2rem;
            border-radius: var(--radius-md);
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            border: none;
            cursor: pointer;
            flex: 1;
            min-width: 200px;
        }
        .btn-primary { 
            background: var(--primary); 
            color: white; 
            box-shadow: 0 10px 20px -5px rgba(124, 58, 237, 0.3);
        }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 15px 30px -10px rgba(124, 58, 237, 0.4); }
        .btn-whatsapp { background: #25d366; color: white; }
        .btn-whatsapp:hover { background: #1eb956; transform: translateY(-2px); }
        .btn-outline { background: white; border: 2px solid var(--border); color: var(--text-main); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }

        /* Tabs Section */
        .tabs-container {
            margin-top: 4rem;
            background: var(--surface-solid);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            overflow: hidden;
            box-shadow: var(--shadow-premium);
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid var(--border);
            padding: 0 1rem;
            background: #fafafa;
        }
        .tab {
            padding: 1.5rem 2rem;
            font-weight: 600;
            color: var(--text-muted);
            border: none;
            background: none;
            cursor: pointer;
            position: relative;
            transition: all 0.3s;
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

        .tab-content { padding: 3rem; display: none; animation: fadeIn 0.4s ease; }
        .tab-content.active { display: block; }

        /* Specific Content Styling */
        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        .detail-item {
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
        }
        .detail-item label { 
            display: block; 
            font-size: 0.75rem; 
            text-transform: uppercase; 
            letter-spacing: 1px; 
            color: var(--text-muted); 
            margin-bottom: 0.4rem;
        }
        .detail-item span { font-weight: 600; font-size: 1rem; }

        /* Entries (Reviews/Comments) */
        .entry-card {
            display: flex;
            gap: 1.5rem;
            padding: 2rem 0;
            border-bottom: 1px solid var(--border);
        }
        .entry-avatar { width: 48px; height: 48px; border-radius: 14px; object-fit: cover; }
        .entry-content { flex: 1; }
        .entry-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 0.75rem; }
        .entry-name { font-weight: 700; font-size: 1.05rem; }
        .entry-date { font-size: 0.8rem; color: var(--text-muted); }
        .entry-text { color: hsl(215, 16%, 30%); line-height: 1.7; }

        .rating-display { color: #facc15; font-size: 0.9rem; margin-top: 0.25rem; }

        .form-dark {
            background: #f8fafc;
            padding: 2rem;
            border-radius: var(--radius-md);
            margin-bottom: 3rem;
            border: 1px solid var(--border);
        }
        .form-control {
            width: 100%;
            padding: 1.25rem;
            border-radius: 12px;
            border: 2px solid var(--border);
            font-family: inherit;
            font-size: 1rem;
            transition: all 0.3s;
            background: white;
        }
        .form-control:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 4px var(--primary-light); }

        /* Modal */
        .modal {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.4);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 1rem;
        }
        .modal.active { display: flex; animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
        @keyframes modalIn { from { opacity: 0; transform: scale(0.95); } to { opacity: 1; transform: scale(1); } }
        .modal-card {
            background: white;
            width: 100%;
            max-width: 500px;
            border-radius: var(--radius-lg);
            padding: 2.5rem;
            box-shadow: 0 40px 100px -20px rgba(0,0,0,0.2);
        }

        @media (max-width: 768px) {
            .book-detail { padding: 1.5rem 1rem; }
            .action-group { flex-direction: column; }
            .btn { width: 100%; }
            .tabs { flex-wrap: nowrap; overflow-x: auto; -webkit-overflow-scrolling: touch; font-size: 0.9rem; }
            .tab { padding: 1.25rem 1rem; white-space: nowrap; }
            .tab-content { padding: 2rem 1rem; }
            .book-title { font-size: 2.2rem; }
            .meta-grid { grid-template-columns: repeat(2, 1fr); }
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
        [data-theme="dark"] .book-title { color: #f8fafc; }
        [data-theme="dark"] .cover-wrapper { background: #0f172a; }
        [data-theme="dark"] .meta-icon { background: #0f172a; }
        [data-theme="dark"] .btn-outline { background: #1e293b; border-color: #334155; color: #f8fafc; }
        [data-theme="dark"] .tabs { background: #0f172a; border-color: #334155; }
        [data-theme="dark"] .form-dark { background: #1e293b; }
        [data-theme="dark"] .form-control { background: #0f172a; border-color: #334155; color: #f8fafc; }
        [data-theme="dark"] .modal-card { background: #1e293b; }
        [data-theme="dark"] .entry-text { color: #cbd5e1; }

        .duration-select {
            appearance: none;
            background-image: url('data:image/svg+xml;charset=US-ASCII,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%2224%22 height=%2224%22 viewBox=%220 0 24 24%22 fill=%22none%22 stroke=%22%2364748b%22 stroke-width=%222%22 stroke-linecap=%22round%22 stroke-linejoin=%22round%22%3E%3Cpath d=%22M6 9l6 6 6-6%22%3E%3C/path%3E%3C/svg%3E');
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1em;
        }

        /* Related Books Section */
        .related-section {
            margin-top: 3rem;
            margin-bottom: 2.5rem;
        }
        .related-title {
            font-size: 1.6rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        .related-title i { color: var(--primary); }
        .related-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1rem;
        }

        @media (min-width: 900px) {
            .related-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 1.5rem;
            }
        }
        .related-card {
            background: white;
            border-radius: var(--radius-md);
            overflow: hidden;
            border: 1px solid var(--border);
            transition: all 0.4s cubic-bezier(0.165, 0.84, 0.44, 1);
            text-decoration: none;
            color: inherit;
        }
        .related-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-color: var(--primary);
        }
        .related-cover {
            aspect-ratio: 3/4.2;
            overflow: hidden;
        }
        .related-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.6s;
        }
        .related-card:hover .related-cover img { transform: scale(1.1); }
        .related-body { padding: 1.25rem; }
        .related-book-title {
            font-weight: 700;
            font-size: 1rem;
            margin-bottom: 0.4rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            line-height: 1.3;
            color: var(--text-main);
        }
        .related-book-author { font-size: 0.85rem; color: var(--text-muted); }

        [data-theme="dark"] .related-card { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .related-book-title { color: #f8fafc; }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    
    <main>
        <div class="book-detail">
            <!-- Breadcrumb -->
            <div class="breadcrumb">
                <a href="/">Home</a> <i class="fas fa-chevron-right" style="font-size:0.7rem;opacity:0.5"></i> 
                <a href="/books/">Books</a> <i class="fas fa-chevron-right" style="font-size:0.7rem;opacity:0.5"></i> 
                <span style="color:var(--text-primary);font-weight:500"><?php echo htmlspecialchars($book['title']); ?></span>
            </div>
            
            <?php if ($borrowMessage): ?>
                <div class="alert alert-success"><?php echo $borrowMessage; ?></div>
            <?php endif; ?>
            <?php if ($borrowError): ?>
                <div class="alert alert-danger"><?php echo $borrowError; ?></div>
            <?php endif; ?>
            
            <div class="book-layout">
                <!-- Cover Section - MAIN IMAGE -->
                <div class="book-cover-section">
                    <div class="cover-wrapper">
                        <img src="<?php echo $coverImage; ?>" 
                             alt="<?php echo htmlspecialchars($book['title']); ?>" 
                             class="book-cover-image"
                             onerror="this.src='/assets/images/default-book-cover.jpg'">
                    </div>
                    <div class="status-badge <?php echo $book['status']; ?>">
                        <i class="fas fa-circle" style="font-size:10px"></i>
                        <?php echo ucfirst($book['status']); ?>
                    </div>
                </div>
                
                <!-- Info Section -->
                <div class="book-info-section">
                    <h1 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h1>
                    <div class="book-author">by <?php echo htmlspecialchars($book['author']); ?></div>
                    
                    <div class="meta-grid">
                        <div class="meta-item">
                            <div class="meta-icon"><i class="fas fa-tag"></i></div>
                            <span class="meta-label">Category</span>
                            <span class="meta-value"><?php echo htmlspecialchars($book['category'] ?? 'General'); ?></span>
                        </div>
                        <div class="meta-item">
                            <div class="meta-icon"><i class="fas fa-star"></i></div>
                            <span class="meta-label">Rating</span>
                            <span class="meta-value"><?php echo $avgRating; ?> <span style="font-weight:400;opacity:0.6;font-size:0.8rem">(<?php echo count($reviews); ?>)</span></span>
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
                    
                    <!-- Owner Card -->
                    <a href="/profile/?id=<?php echo $book['owner_id']; ?>" class="owner-card">
                        <div class="owner-avatar-container">
                            <img src="/uploads/profile/<?php echo htmlspecialchars($owner['personal_info']['profile_pic'] ?? 'default-avatar.jpg'); ?>" 
                                 class="owner-avatar-large" 
                                 alt="<?php echo htmlspecialchars($owner['personal_info']['name'] ?? 'Owner'); ?>"
                                 onerror="this.src='/assets/images/avatars/default.jpg'">
                        </div>
                        <div style="flex:1">
                            <div class="owner-name"><?php echo htmlspecialchars($owner['personal_info']['name'] ?? 'Unknown Owner'); ?></div>
                            <div class="owner-details">
                                <span><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($owner['personal_info']['room_number'] ?? 'N/A'); ?></span>
                                <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($owner['personal_info']['department'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                        <div style="color: var(--primary); opacity: 0.5;">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                    
                    <div class="action-group">
                        <?php if ($isOwner): ?>
                            <a href="/edit-book/?id=<?php echo $bookId; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Listing
                            </a>
                            <button onclick="shareBook()" class="btn btn-outline">
                                <i class="fas fa-share-alt"></i> Share
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
                            <button class="btn btn-secondary" disabled style="background:#f1f5f9; color:#94a3b8; border:1px solid #e2e8f0;">
                                <i class="fas fa-clock"></i> Request Pending
                            </button>
                            <a href="/requests/" class="btn btn-outline">Manage Requests</a>
                        <?php elseif (!$isLoggedIn): ?>
                            <a href="/login/?redirect=/book/?id=<?php echo $bookId; ?>" class="btn btn-primary">
                                Join to Borrow
                            </a>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-lock"></i> Currently Unavailable
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Tabs -->
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
                    <p style="font-size:1.05rem;line-height:1.7;color:var(--text-secondary);white-space:pre-line">
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
            <div class="related-grid">
                <?php foreach ($relatedBooks as $rBook): 
                    $rCover = getCoverImagePath($rBook['cover_image'] ?? '');
                ?>
                    <a href="/book/?id=<?php echo $rBook['id']; ?>" class="related-card">
                        <div class="related-cover">
                            <img src="<?php echo $rCover; ?>" alt="<?php echo htmlspecialchars($rBook['title']); ?>" loading="lazy">
                        </div>
                        <div class="related-body">
                            <h3 class="related-book-title"><?php echo htmlspecialchars($rBook['title']); ?></h3>
                            <p class="related-book-author">By <?php echo htmlspecialchars($rBook['author']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
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
</body>
</html>