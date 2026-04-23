<?php
/**
 * OpenShelf Borrow Request System
 * Complete with Working Email Notifications
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_DATA_PATH', dirname(__DIR__) . '/data/book/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('BASE_URL', 'https://openshelf.free.nf');

// Check login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /login/');
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'Unknown';
$currentUserEmail = $_SESSION['user_email'] ?? '';

// Include database connection and helpers
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Initialize mailer
$mailer = null;
try {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    require_once dirname(__DIR__) . '/lib/Mailer.php';
    $mailer = new Mailer();
} catch (Exception $e) {
    error_log("❌ Mailer init failed: " . $e->getMessage());
}

/**
 * Generate request ID
 */
function generateRequestId() {
    return 'REQ' . time() . bin2hex(random_bytes(4));
}

/**
 * Load book data using helper
 */
function loadBookData($bookId) {
    return getBookById($bookId);
}

/**
 * Load user data using helper
 */
function loadUserData($userId) {
    return getUserById($userId);
}

/**
 * Check for existing pending request in DB
 */
function hasPendingRequest($bookId, $userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM borrow_requests WHERE book_id = ? AND borrower_id = ? AND status = 'pending'");
    $stmt->execute([$bookId, $userId]);
    return $stmt->fetch() !== false;
}

/**
 * Save borrow request to DB
 */
function saveBorrowRequest($requestData) {
    $db = getDB();
    $sql = "INSERT INTO borrow_requests (
                id, book_id, book_title, book_author, book_cover, 
                owner_id, owner_name, owner_email, borrower_id, 
                borrower_name, borrower_email, status, request_date, 
                expected_return_date, duration_days, message, updated_at
            ) VALUES (
                :id, :book_id, :book_title, :book_author, :book_cover, 
                :owner_id, :owner_name, :owner_email, :borrower_id, 
                :borrower_name, :borrower_email, :status, :request_date, 
                :expected_return_date, :duration_days, :message, :updated_at
            )";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($requestData);
}

/**
 * Update book status in DB
 */
function updateBookStatus($bookId, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE books SET status = ?, updated_at = ? WHERE id = ?");
    return $stmt->execute([$status, date('Y-m-d H:i:s'), $bookId]);
}

/**
 * Create in-app notification for owner
 */
function createOwnerNotification($ownerId, $borrowerName, $bookTitle, $requestId) {
    $userFile = dirname(__DIR__) . '/users/' . $ownerId . '.json';
    if (!file_exists($userFile)) return false;
    
    $userData = json_decode(file_get_contents($userFile), true);
    $notifications = $userData['notifications'] ?? [];
    
    $notifications[] = [
        'id' => 'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
        'user_id' => $ownerId,
        'type' => 'borrow_request',
        'title' => 'New Borrow Request',
        'message' => "$borrowerName wants to borrow '$bookTitle'",
        'link' => '/requests/?id=' . $requestId,
        'is_read' => false,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Sort by created_at desc
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });
    
    // Limit to 25
    $notifications = array_slice($notifications, 0, 25);
    
    $userData['notifications'] = $notifications;
    return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Send email to owner
 */
function sendOwnerEmail($ownerEmail, $ownerName, $borrowerName, $bookTitle, $bookAuthor, $duration, $message, $requestId, $borrowerData) {
    global $mailer;
    
    if (!$mailer) {
        error_log("❌ Mailer not available for owner email");
        return false;
    }
    
    try {
        error_log("📧 Sending borrow request email to: $ownerEmail");
        
        $result = $mailer->sendTemplate(
            $ownerEmail,
            $ownerName,
            'borrow_request',
            [
                'subject'            => "New Borrow Request: {$bookTitle}",
                'owner_name'         => $ownerName,
                'borrower_name'      => $borrowerName,
                'book_title'         => $bookTitle,
                'book_author'        => $bookAuthor,
                'duration_days'      => $duration,
                'message'            => $message,
                'request_id'         => $requestId,
                'borrower_department'=> $borrowerData['personal_info']['department'] ?? 'N/A',
                'borrower_session'   => $borrowerData['personal_info']['session'] ?? 'N/A',
                'borrower_room'      => $borrowerData['personal_info']['room_number'] ?? 'N/A',
                'borrower_phone'     => $borrowerData['personal_info']['phone'] ?? 'N/A',
                'base_url'           => BASE_URL
            ]
        );
        
        if ($result) {
            error_log("✅ Borrow request email sent to: $ownerEmail");
        } else {
            error_log("❌ Failed to send email to: $ownerEmail");
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("❌ Exception sending email: " . $e->getMessage());
        return false;
    }
}

// Get book ID
$bookId = $_GET['book_id'] ?? '';
$book = loadBookData($bookId);

if (!$book) {
    header('Location: /books/');
    exit;
}

if ($book['owner_id'] === $currentUserId) {
    $_SESSION['error'] = 'You cannot borrow your own book';
    header('Location: /book/?id=' . $bookId);
    exit;
}

if ($book['status'] !== 'available') {
    $_SESSION['error'] = 'Book is not available';
    header('Location: /book/?id=' . $bookId);
    exit;
}

if (hasPendingRequest($bookId, $currentUserId)) {
    $_SESSION['error'] = 'You already have a pending request';
    header('Location: /book/?id=' . $bookId);
    exit;
}

$owner = loadUserData($book['owner_id']);
$borrower = loadUserData($currentUserId);
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $duration = intval($_POST['duration'] ?? 14);
    $message = trim($_POST['message'] ?? '');
    
    $requestId = generateRequestId();
    $requestDate = date('Y-m-d H:i:s');
    $expectedReturnDate = date('Y-m-d H:i:s', strtotime("+{$duration} days"));
    
    $requestData = [
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
        'borrower_email' => $borrower['personal_info']['email'] ?? null,
        'status' => 'pending',
        'request_date' => $requestDate,
        'expected_return_date' => $expectedReturnDate,
        'duration_days' => $duration,
        'message' => $message,
        'updated_at' => $requestDate
    ];
    
    if (saveBorrowRequest($requestData)) {
        updateBookStatus($bookId, 'reserved');
        
        // Create in-app notification
        createOwnerNotification($book['owner_id'], $currentUserName, $book['title'], $requestId);
        
        // Send email to owner
        if (!empty($owner['personal_info']['email'])) {
            $emailSent = sendOwnerEmail(
                $owner['personal_info']['email'],
                $owner['personal_info']['name'] ?? 'Owner',
                $currentUserName,
                $book['title'],
                $book['author'],
                $duration,
                $message,
                $requestId,
                $borrower
            );
            
            if ($emailSent) {
                $_SESSION['success'] = 'Request sent successfully! The owner has been notified.';
            } else {
                $_SESSION['success'] = 'Request sent successfully! (Email notification pending)';
            }
        } else {
            $_SESSION['success'] = 'Request sent successfully!';
        }
        
        header('Location: /requests/');
        exit;
    } else {
        $error = 'Failed to create borrow request';
    }
}

$coverImage = !empty($book['cover_image']) ? '/uploads/book_cover/thumb_' . $book['cover_image'] : '/assets/images/default-book-cover.jpg';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Request - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .borrow-container { max-width: 600px; margin: 2rem auto; padding: 0 1rem; }
        .borrow-card { background: white; border-radius: 1rem; padding: 2rem; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
        .book-summary { display: flex; gap: 1rem; background: #f8fafc; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1.5rem; }
        .book-summary img { width: 80px; height: 100px; object-fit: cover; border-radius: 0.5rem; }
        .form-group { margin-bottom: 1rem; }
        .form-label { display: block; margin-bottom: 0.5rem; font-weight: 500; }
        .form-control, .form-select { width: 100%; padding: 0.75rem; border: 1px solid #e2e8f0; border-radius: 0.5rem; font-size: 0.9rem; }
        .btn-primary { width: 100%; padding: 0.75rem; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; border: none; border-radius: 0.5rem; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .btn-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(99,102,241,0.3); }
        .btn-secondary { display: block; text-align: center; margin-top: 1rem; color: #64748b; text-decoration: none; }
        .info-box { background: #e3f2fd; border-radius: 0.5rem; padding: 0.75rem; margin-bottom: 1rem; font-size: 0.85rem; display: flex; align-items: center; gap: 0.5rem; }
        @media (max-width: 640px) { .borrow-card { padding: 1.5rem; } .book-summary { flex-direction: column; align-items: center; text-align: center; } }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    
    <div class="borrow-container">
        <div class="borrow-card">
            <h1 style="margin-bottom: 1rem;">📖 Request to Borrow</h1>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" style="background:rgba(239,68,68,0.1); color:#ef4444; padding:0.75rem; border-radius:0.5rem; margin-bottom:1rem;">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <i class="fas fa-envelope" style="color: #6366f1;"></i>
                <span>The owner will be notified via email about your request.</span>
            </div>
            
            <div class="book-summary">
                <img src="<?php echo $coverImage; ?>" alt="<?php echo htmlspecialchars($book['title']); ?>">
                <div>
                    <h3><?php echo htmlspecialchars($book['title']); ?></h3>
                    <p>by <?php echo htmlspecialchars($book['author']); ?></p>
                    <p style="color: #64748b; font-size: 0.85rem;">Owner: <?php echo htmlspecialchars($owner['personal_info']['name'] ?? 'Unknown'); ?></p>
                </div>
            </div>
            
            <form method="POST">
                <div class="form-group">
                    <label class="form-label">📅 Borrow Duration</label>
                    <select name="duration" class="form-select">
                        <option value="7">7 days</option>
                        <option value="14" selected>14 days</option>
                        <option value="21">21 days</option>
                        <option value="30">30 days</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">💬 Message to Owner (Optional)</label>
                    <textarea name="message" class="form-control" rows="4" 
                              placeholder="Introduce yourself and explain why you'd like to borrow this book..."></textarea>
                </div>
                
                <button type="submit" class="btn-primary">
                    <i class="fas fa-paper-plane"></i> Send Request
                </button>
                
                <a href="/book/?id=<?php echo $bookId; ?>" class="btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>