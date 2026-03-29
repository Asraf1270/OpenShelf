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

// Get book ID from URL
$bookId = $_GET['id'] ?? '';
if (empty($bookId)) {
    header('Location: /books/');
    exit;
}

/**
 * Load detailed book data from /data/book/[book_id].json
 */
function loadDetailedBook($bookId) {
    $bookFile = BOOKS_DATA_PATH . $bookId . '.json';
    if (!file_exists($bookFile)) {
        return null;
    }
    return json_decode(file_get_contents($bookFile), true);
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
 * Load all borrow requests for this book
 */
function loadBorrowRequests($bookId) {
    $requestsFile = DATA_PATH . 'borrow_requests.json';
    if (!file_exists($requestsFile)) return [];
    
    $allRequests = json_decode(file_get_contents($requestsFile), true) ?? [];
    return array_filter($allRequests, fn($r) => ($r['book_id'] ?? '') === $bookId);
}

/**
 * Check if user has already requested this book
 */
function hasUserRequested($bookId, $userId) {
    $requestsFile = DATA_PATH . 'borrow_requests.json';
    if (!file_exists($requestsFile)) return false;
    
    $requests = json_decode(file_get_contents($requestsFile), true) ?? [];
    foreach ($requests as $r) {
        if (($r['book_id'] ?? '') === $bookId && 
            ($r['borrower_id'] ?? '') === $userId && 
            ($r['status'] ?? '') === 'pending') {
            return true;
        }
    }
    return false;
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
    $notificationsFile = DATA_PATH . 'notifications.json';
    $notifications = file_exists($notificationsFile) ? json_decode(file_get_contents($notificationsFile), true) : [];
    
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
    
    return file_put_contents($notificationsFile, json_encode($notifications, JSON_PRETTY_PRINT));
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
        $requestsFile = DATA_PATH . 'borrow_requests.json';
        $requests = file_exists($requestsFile) ? json_decode(file_get_contents($requestsFile), true) : [];
        
        $requestId = 'REQ' . time() . bin2hex(random_bytes(4));
        $duration = intval($_POST['duration'] ?? 14);
        $message = trim($_POST['message'] ?? '');
        
        $newRequest = [
            'id' => $requestId,
            'book_id' => $bookId,
            'book_title' => $book['title'],
            'book_author' => $book['author'],
            'book_cover' => $book['cover_image'] ?? null,
            'owner_id' => $book['owner_id'],
            'owner_name' => $owner['personal_info']['name'] ?? 'Unknown',
            'owner_email' => $owner['personal_info']['email'] ?? null,
            'borrower_id' => $currentUserId,
            'borrower_name' => $currentUserName,
            'borrower_email' => $_SESSION['user_email'] ?? null,
            'status' => 'pending',
            'request_date' => date('Y-m-d H:i:s'),
            'expected_return_date' => date('Y-m-d H:i:s', strtotime("+{$duration} days")),
            'duration_days' => $duration,
            'message' => $message,
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $requests[] = $newRequest;
        
        if (file_put_contents($requestsFile, json_encode($requests, JSON_PRETTY_PRINT))) {
            // Update book status
            $book['status'] = 'reserved';
            file_put_contents(BOOKS_DATA_PATH . $bookId . '.json', json_encode($book, JSON_PRETTY_PRINT));
            
            // Update master books.json
            $masterBooksFile = DATA_PATH . 'books.json';
            if (file_exists($masterBooksFile)) {
                $masterBooks = json_decode(file_get_contents($masterBooksFile), true);
                foreach ($masterBooks as &$b) {
                    if ($b['id'] === $bookId) {
                        $b['status'] = 'reserved';
                        break;
                    }
                }
                file_put_contents($masterBooksFile, json_encode($masterBooks, JSON_PRETTY_PRINT));
            }
            
            // Create notification for owner
            createNotification(
                $book['owner_id'],
                'borrow_request',
                'New Borrow Request',
                $currentUserName . ' wants to borrow "' . $book['title'] . '"',
                '/requests/?id=' . $requestId
            );
            
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
    
    if (file_put_contents(BOOKS_DATA_PATH . $bookId . '.json', json_encode($book, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'review' => $newReview]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save review']);
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
    
    if (file_put_contents(BOOKS_DATA_PATH . $bookId . '.json', json_encode($book, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'comment' => $newComment]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save comment']);
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
    
    if ($commentFound && file_put_contents(BOOKS_DATA_PATH . $bookId . '.json', json_encode($book, JSON_PRETTY_PRINT))) {
        echo json_encode(['success' => true, 'likes' => $likeCount, 'liked' => $liked]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to like comment']);
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
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --surface: #ffffff;
            --surface-hover: #f8fafc;
            --text-primary: #0f172a;
            --text-secondary: #64748b;
            --text-tertiary: #94a3b8;
            --border: #e2e8f0;
            --radius-md: 12px;
            --radius-lg: 16px;
            --radius-xl: 20px;
            --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.08);
            --shadow-md: 0 10px 15px -3px rgba(15, 23, 42, 0.1);
            --space-1: 0.25rem;
            --space-2: 0.5rem;
            --space-3: 0.75rem;
            --space-4: 1rem;
            --space-5: 1.5rem;
            --space-6: 2rem;
        }

        * { box-sizing: border-box; }
        body { font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.5; background: #f8fafc; }

        .book-detail { max-width: 1200px; margin: 0 auto; padding: var(--space-4); }

        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: var(--space-5);
            flex-wrap: wrap;
        }
        .breadcrumb a { color: var(--primary); text-decoration: none; }
        .breadcrumb a:hover { text-decoration: underline; }

        /* Alerts */
        .alert {
            padding: var(--space-3) var(--space-4);
            border-radius: var(--radius-md);
            margin-bottom: var(--space-5);
            font-size: 0.95rem;
        }
        .alert-success { background: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; }
        .alert-danger { background: #fef2f2; color: #ef4444; border: 1px solid #fecaca; }

        /* Book Layout - MOBILE FIRST */
        .book-layout { display: flex; flex-direction: column; gap: var(--space-6); }
        @media (min-width: 768px) { .book-layout { flex-direction: row; gap: var(--space-8); } }

        /* Cover Section */
        .book-cover-section {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            box-shadow: var(--shadow-md);
            text-align: center;
            flex-shrink: 0;
        }
        .cover-wrapper {
            aspect-ratio: 3 / 4;
            width: 100%;
            max-width: 280px;
            margin: 0 auto;
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            background: #f1f5f9;
        }
        .book-cover-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--space-1);
            padding: 6px 16px;
            border-radius: 9999px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-top: var(--space-4);
        }
        .status-badge.available { background: #10b981; color: white; }
        .status-badge.reserved, .status-badge.borrowed { background: #f59e0b; color: white; }

        /* Info Section */
        .book-info-section {
            background: var(--surface);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            box-shadow: var(--shadow-md);
            flex: 1;
        }
        @media (max-width: 640px) { .book-info-section { padding: var(--space-4); } }

        .book-title {
            font-size: 1.75rem;
            font-weight: 700;
            line-height: 1.2;
            color: var(--text-primary);
            margin-bottom: var(--space-2);
        }
        @media (min-width: 768px) { .book-title { font-size: 2.25rem; } }

        .book-author {
            font-size: 1.25rem;
            color: var(--primary);
            margin-bottom: var(--space-5);
        }

        /* Meta */
        .meta-grid {
            display: flex;
            flex-wrap: wrap;
            gap: var(--space-3);
            margin-bottom: var(--space-6);
        }
        .meta-item {
            display: flex;
            align-items: center;
            background: var(--surface-hover);
            padding: 8px 16px;
            border-radius: 9999px;
            font-size: 0.875rem;
            color: var(--text-secondary);
            gap: var(--space-2);
        }
        .meta-icon {
            width: 28px;
            height: 28px;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Owner Card */
        .owner-card {
            display: flex;
            align-items: center;
            gap: var(--space-4);
            background: var(--surface-hover);
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-6);
        }
        .owner-avatar-large {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid var(--primary);
            flex-shrink: 0;
        }
        .owner-name { font-weight: 600; font-size: 1.1rem; }
        .owner-details { font-size: 0.85rem; color: var(--text-tertiary); display: flex; gap: var(--space-3); }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: var(--space-3);
        }
        @media (min-width: 768px) { .action-buttons { flex-direction: row; flex-wrap: wrap; } }

        .btn {
            padding: 14px 24px;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            text-decoration: none;
            cursor: pointer;
            border: none;
            min-height: 48px;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgb(37 99 235); }
        .btn-secondary { background: #e2e8f0; color: var(--text-primary); }
        .btn-outline { background: transparent; border: 2px solid var(--border); color: var(--text-primary); }
        .btn-outline:hover { border-color: var(--primary); color: var(--primary); }
        .btn-whatsapp { background: #25d366; color: white; }
        .btn-whatsapp:hover { background: #128c7e; transform: translateY(-2px); }
        .btn:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* Tabs */
        .tabs-container {
            background: var(--surface);
            border-radius: var(--radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            margin-top: var(--space-6);
        }
        .tabs {
            display: flex;
            overflow-x: auto;
            scrollbar-width: none;
            border-bottom: 1px solid var(--border);
            padding-left: var(--space-2);
        }
        .tabs::-webkit-scrollbar { display: none; }
        .tab {
            padding: var(--space-4) var(--space-5);
            background: none;
            border: none;
            font-weight: 600;
            color: var(--text-secondary);
            font-size: 1rem;
            white-space: nowrap;
            position: relative;
            flex: 0 0 auto;
            cursor: pointer;
        }
        .tab.active { color: var(--text-primary); }
        .tab.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 40%;
            height: 3px;
            background: var(--primary);
            border-radius: 6px;
        }
        .tab-content { display: none; padding: var(--space-5); }
        .tab-content.active { display: block; }
        @media (max-width: 640px) { .tab-content { padding: var(--space-4); } }

        /* Review & Comment */
        .review-card, .comment-card {
            padding: var(--space-5);
            border-bottom: 1px solid var(--border);
            display: flex;
            gap: var(--space-4);
        }
        .review-avatar, .comment-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            flex-shrink: 0;
            object-fit: cover;
        }
        .review-rating { color: #facc15; display: flex; gap: 2px; }
        .like-btn {
            background: none;
            border: none;
            color: var(--text-tertiary);
            display: flex;
            align-items: center;
            gap: var(--space-1);
            font-size: 0.875rem;
            cursor: pointer;
            padding: 4px 8px;
            border-radius: var(--radius-md);
        }
        .like-btn.active { color: #ef4444; }
        .review-form, .comment-form {
            background: var(--surface-hover);
            border-radius: var(--radius-lg);
            padding: var(--space-5);
            margin-bottom: var(--space-6);
        }
        .rating-stars {
            display: flex;
            gap: 4px;
            font-size: 1.75rem;
            margin-bottom: var(--space-4);
        }
        .rating-stars i { cursor: pointer; color: #cbd5e1; transition: color 0.15s; }
        .rating-stars i.active { color: #facc15; }
        .empty-state {
            text-align: center;
            padding: var(--space-8) var(--space-4);
            color: var(--text-tertiary);
        }
        .empty-state i { font-size: 3rem; opacity: 0.3; margin-bottom: var(--space-4); }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.7);
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        .modal.active { display: flex; }
        .modal-content {
            background: var(--surface);
            border-radius: var(--radius-xl);
            width: 92%;
            max-width: 480px;
            max-height: 92vh;
            overflow: hidden;
            box-shadow: var(--shadow-md);
        }
        .form-control, .form-select {
            width: 100%;
            padding: 14px 16px;
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            font-size: 1rem;
            transition: border 0.2s;
        }
        .form-control:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
        }
        @media (max-width: 480px) {
            .book-detail { padding: var(--space-3); }
            .book-title { font-size: 1.6rem; }
            .cover-wrapper { max-width: 200px; }
        }
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
                            <div>
                                <div style="font-size:0.75rem;opacity:0.7">Category</div>
                                <?php echo htmlspecialchars($book['category'] ?? 'General'); ?>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-icon"><i class="fas fa-star"></i></div>
                            <div>
                                <div style="font-size:0.75rem;opacity:0.7">Rating</div>
                                <?php echo $avgRating; ?> <span style="font-size:0.8rem">•</span> <?php echo count($reviews); ?> reviews
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-icon"><i class="fas fa-calendar"></i></div>
                            <div>
                                <div style="font-size:0.75rem;opacity:0.7">Added</div>
                                <?php echo date('M j, Y', strtotime($book['created_at'])); ?>
                            </div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-icon"><i class="fas fa-eye"></i></div>
                            <div>
                                <div style="font-size:0.75rem;opacity:0.7">Views</div>
                                <?php echo number_format($book['views'] ?? 0); ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Owner Card -->
                    <div class="owner-card">
                        <img src="/uploads/profile/<?php echo htmlspecialchars($owner['personal_info']['profile_pic'] ?? 'default-avatar.jpg'); ?>" 
                             class="owner-avatar-large" 
                             alt="<?php echo htmlspecialchars($owner['personal_info']['name'] ?? 'Owner'); ?>"
                             onerror="this.src='/assets/images/avatars/default.jpg'">
                        <div>
                            <div class="owner-name"><?php echo htmlspecialchars($owner['personal_info']['name'] ?? 'Unknown Owner'); ?></div>
                            <div class="owner-details">
                                <span><i class="fas fa-door-open"></i> <?php echo htmlspecialchars($owner['personal_info']['room_number'] ?? 'N/A'); ?></span>
                                <span><i class="fas fa-building"></i> <?php echo htmlspecialchars($owner['personal_info']['department'] ?? 'N/A'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <?php if ($isOwner): ?>
                            <a href="/edit-book/?id=<?php echo $bookId; ?>" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Book
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
                                    <i class="fab fa-whatsapp"></i> Chat on WhatsApp
                                </a>
                            <?php endif; ?>
                        <?php elseif ($hasRequested): ?>
                            <button class="btn btn-secondary" disabled>
                                <i class="fas fa-clock"></i> Request Pending
                            </button>
                            <a href="/requests/" class="btn btn-outline">View My Requests</a>
                        <?php elseif (!$isLoggedIn): ?>
                            <a href="/login/?redirect=/book/?id=<?php echo $bookId; ?>" class="btn btn-primary">
                                Login to Borrow
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
                    <button class="tab active" onclick="switchTab('description')">Description</button>
                    <button class="tab" onclick="switchTab('details')">Details</button>
                    <button class="tab" onclick="switchTab('reviews')">Reviews <span style="font-size:0.85rem">(<?php echo count($reviews); ?>)</span></button>
                    <button class="tab" onclick="switchTab('comments')">Comments <span style="font-size:0.85rem">(<?php echo count($comments); ?>)</span></button>
                    <button class="tab" onclick="switchTab('history')">History</button>
                </div>
                
                <!-- Description -->
                <div id="description-tab" class="tab-content active">
                    <p style="font-size:1.05rem;line-height:1.7;color:var(--text-secondary);white-space:pre-line">
                        <?php echo nl2br(htmlspecialchars($book['description'] ?? 'No description available.')); ?>
                    </p>
                </div>
                
                <!-- Details -->
                <div id="details-tab" class="tab-content">
                    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:var(--space-4)">
                        <div><strong>ISBN</strong><br><?php echo htmlspecialchars($book['isbn'] ?? 'N/A'); ?></div>
                        <div><strong>Publisher</strong><br><?php echo htmlspecialchars($book['publisher'] ?? 'N/A'); ?></div>
                        <div><strong>Year</strong><br><?php echo htmlspecialchars($book['publication_year'] ?? 'N/A'); ?></div>
                        <div><strong>Pages</strong><br><?php echo htmlspecialchars($book['pages'] ?? 'N/A'); ?></div>
                        <div><strong>Language</strong><br><?php echo htmlspecialchars($book['language'] ?? 'English'); ?></div>
                        <div><strong>Condition</strong><br><?php echo htmlspecialchars($book['condition'] ?? 'Good'); ?></div>
                    </div>
                </div>
                
                <!-- Reviews -->
                <div id="reviews-tab" class="tab-content">
                    <?php if ($isLoggedIn && !$isOwner): ?>
                        <div class="review-form">
                            <h4 style="margin-bottom:var(--space-3)">Write a Review</h4>
                            <div class="rating-stars" id="ratingStarsInput">
                                <i class="far fa-star" data-rating="1"></i>
                                <i class="far fa-star" data-rating="2"></i>
                                <i class="far fa-star" data-rating="3"></i>
                                <i class="far fa-star" data-rating="4"></i>
                                <i class="far fa-star" data-rating="5"></i>
                            </div>
                            <textarea id="reviewText" class="form-control" rows="3" placeholder="Share your honest thoughts..."></textarea>
                            <button onclick="submitReview()" class="btn btn-primary" style="margin-top:var(--space-4);width:100%;max-width:220px">Submit Review</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($reviews)): ?>
                        <div class="empty-state"><i class="far fa-star"></i><p>No reviews yet. Be the first to review this book!</p></div>
                    <?php else: foreach ($reviews as $review): $reviewer = loadUserData($review['user_id']); ?>
                        <div class="review-card">
                            <img src="/uploads/profile/<?php echo htmlspecialchars($reviewer['personal_info']['profile_pic'] ?? 'default-avatar.jpg'); ?>" class="review-avatar">
                            <div style="flex:1">
                                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:var(--space-2)">
                                    <div>
                                        <div style="font-weight:600"><?php echo htmlspecialchars($review['user_name']); ?></div>
                                        <div style="font-size:0.8rem;color:var(--text-tertiary)"><?php echo formatDate($review['created_at']); ?></div>
                                    </div>
                                    <div class="review-rating">
                                        <?php for($i=1; $i<=5; $i++): ?>
                                            <i class="<?php echo ($i <= $review['rating']) ? 'fas fa-star' : 'far fa-star'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                                <p style="margin:0;color:var(--text-secondary)"><?php echo nl2br(htmlspecialchars($review['review_text'])); ?></p>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
                
                <!-- Comments -->
                <div id="comments-tab" class="tab-content">
                    <?php if ($isLoggedIn): ?>
                        <div class="comment-form">
                            <textarea id="commentText" class="form-control" rows="2" placeholder="Add a comment..."></textarea>
                            <button onclick="submitComment()" class="btn btn-primary" style="margin-top:var(--space-3);width:100%;max-width:180px">Post Comment</button>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($comments)): ?>
                        <div class="empty-state"><i class="far fa-comments"></i><p>No comments yet.</p></div>
                    <?php else: foreach ($comments as $comment): 
                        $commenter = loadUserData($comment['user_id']); 
                        $userLiked = $isLoggedIn && in_array($currentUserId, $comment['likes'] ?? []);
                    ?>
                        <div class="comment-card">
                            <img src="/uploads/profile/<?php echo htmlspecialchars($commenter['personal_info']['profile_pic'] ?? 'default-avatar.jpg'); ?>" class="comment-avatar">
                            <div style="flex:1">
                                <div style="display:flex;justify-content:space-between;margin-bottom:var(--space-2)">
                                    <span style="font-weight:600"><?php echo htmlspecialchars($comment['user_name']); ?></span>
                                    <span style="font-size:0.8rem;color:var(--text-tertiary)"><?php echo formatDate($comment['created_at']); ?></span>
                                </div>
                                <p style="margin:0 0 var(--space-3);color:var(--text-secondary)"><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                                <button onclick="likeComment('<?php echo $comment['id']; ?>', this)" class="like-btn <?php echo $userLiked ? 'active' : ''; ?>">
                                    <i class="fas fa-heart"></i> <span class="like-count"><?php echo count($comment['likes'] ?? []); ?></span>
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
                        <div style="display:flex;gap:var(--space-3);align-items:flex-start;padding:var(--space-4);border-bottom:1px solid var(--border)">
                            <div class="rounded-full mt-1" style="width:12px;height:12px;background:<?php echo $request['status'] === 'approved' ? '#10b981' : ($request['status'] === 'pending' ? '#f59e0b' : '#ef4444'); ?>"></div>
                            <div style="flex:1">
                                <div style="font-weight:600"><?php echo htmlspecialchars($request['borrower_name']); ?></div>
                                <div class="status-badge" style="font-size:0.75rem;padding:3px 10px;margin-top:4px;display:inline-flex"><?php echo ucfirst($request['status']); ?></div>
                                <div style="font-size:0.8rem;color:var(--text-tertiary);margin-top:4px">Requested: <?php echo date('M j, Y', strtotime($request['request_date'])); ?></div>
                            </div>
                        </div>
                    <?php endforeach; endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Borrow Modal -->
    <div id="borrowModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" style="padding:var(--space-5);border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
                <h3 style="margin:0;font-size:1.3rem">Request to Borrow</h3>
                <button onclick="closeModal('borrowModal')" style="background:none;border:none;font-size:1.8rem;line-height:1;cursor:pointer">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body" style="padding:var(--space-5)">
                    <input type="hidden" name="action" value="borrow">
                    <div style="margin-bottom:var(--space-4)">
                        <label style="display:block;margin-bottom:6px;font-weight:500">Borrow duration</label>
                        <select name="duration" class="form-select">
                            <option value="7">7 days</option>
                            <option value="14" selected>14 days</option>
                            <option value="21">21 days</option>
                            <option value="30">30 days</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block;margin-bottom:6px;font-weight:500">Message to owner <span style="font-weight:400;color:var(--text-tertiary)">(optional)</span></label>
                        <textarea name="message" class="form-control" rows="4" placeholder="Tell the owner why you'd like to borrow this book..."></textarea>
                    </div>
                </div>
                <div class="modal-footer" style="padding:var(--space-5);border-top:1px solid var(--border);display:flex;gap:var(--space-3)">
                    <button type="button" onclick="closeModal('borrowModal')" class="btn btn-outline" style="flex:1">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex:1">Send Borrow Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Tab switching
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            const tabBtns = document.querySelectorAll('.tab');
            for (let btn of tabBtns) {
                if (btn.textContent.toLowerCase().includes(tab.toLowerCase())) {
                    btn.classList.add('active');
                    break;
                }
            }
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