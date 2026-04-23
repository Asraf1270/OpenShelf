<?php
/**
 * OpenShelf Return Book System
 * Handles book returns and updates all related data
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_DATA_PATH', dirname(__DIR__) . '/data/book/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('BASE_URL', 'https://openshelf.free.nf');

require_once dirname(__DIR__) . '/includes/db.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /login/');
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'Unknown';

// Load mailer
$mailer = null;
try {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    require_once dirname(__DIR__) . '/lib/Mailer.php';
    $mailer = new Mailer();
} catch (Exception $e) {
    error_log("❌ Mailer init failed in return-book: " . $e->getMessage());
}

/**
 * Load request data
 */
function loadRequest($requestId) {
    if (empty($requestId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM borrow_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    if ($request) {
        $request['history'] = json_decode($request['history'] ?? '[]', true);
    }
    return $request ?: null;
}

/**
 * Load detailed book data using helper
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
 * Update request status to returned
 */
function updateRequestStatus($requestId, $status, $additionalData = []) {
    $request = loadRequest($requestId);
    if (!$request) return false;
    
    $db = getDB();
    
    $history = $request['history'] ?? [];
    $history[] = [
        'action' => 'returned',
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id' => $GLOBALS['currentUserId'],
        'user_name' => $GLOBALS['currentUserName'],
        'notes' => $additionalData['notes'] ?? '',
        'condition' => $additionalData['return_condition'] ?? 'same',
        'rating' => $additionalData['rating'] ?? 0
    ];
    
    // Base SQL
    $sql = "UPDATE borrow_requests SET status = :status, history = :history, updated_at = :updated_at";
    
    $params = [
        ':status' => $status,
        ':history' => json_encode($history),
        ':updated_at' => date('Y-m-d H:i:s'),
        ':id' => $requestId
    ];
    
    if ($status === 'returned') {
        $sql .= ", returned_at = :returned_at, actual_return_date = :actual_return_date, notes = :notes, return_condition = :return_condition, returned_by = :returned_by, returned_by_name = :returned_by_name, rating = :rating";
        
        $params[':returned_at'] = date('Y-m-d H:i:s');
        $params[':actual_return_date'] = date('Y-m-d H:i:s');
        $params[':notes'] = $additionalData['notes'] ?? null;
        $params[':return_condition'] = $additionalData['return_condition'] ?? null;
        $params[':returned_by'] = $additionalData['returned_by'] ?? null;
        $params[':returned_by_name'] = $additionalData['returned_by_name'] ?? null;
        $params[':rating'] = $additionalData['rating'] ?? 0;
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($params);
}

/**
 * Update book status in both master and detailed files
 */
function updateBookStatus($bookId, $status) {
    if (empty($bookId)) return false;
    $db = getDB();
    $stmt = $db->prepare("UPDATE books SET status = :status, updated_at = :updated_at WHERE id = :id");
    return $stmt->execute([
        ':status' => $status,
        ':updated_at' => date('Y-m-d H:i:s'),
        ':id' => $bookId
    ]);
}

/**
 * Update user's borrowed books list (remove from currently borrowed)
 */
function updateUserBorrowedList($userId, $bookId, $action) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) return false;
    
    $userData = json_decode(file_get_contents($userFile), true);
    
    if ($action === 'remove') {
        if (isset($userData['currently_borrowed'])) {
            $userData['currently_borrowed'] = array_values(array_filter(
                $userData['currently_borrowed'],
                function($id) use ($bookId) { return $id !== $bookId; }
            ));
            $userData['stats']['books_borrowed'] = count($userData['currently_borrowed']);
        }
        
        // Add to borrow history
        if (!isset($userData['borrow_history'])) {
            $userData['borrow_history'] = [];
        }
        $userData['borrow_history'][] = [
            'book_id' => $bookId,
            'returned_at' => date('Y-m-d H:i:s'),
            'status' => 'completed'
        ];
    }
    
    return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT));
}

/**
 * Update owner's lent books list (remove from currently lent)
 */
function updateOwnerLentList($userId, $bookId, $action) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) return false;
    
    $userData = json_decode(file_get_contents($userFile), true);
    
    if ($action === 'remove') {
        if (isset($userData['currently_lent'])) {
            $userData['currently_lent'] = array_values(array_filter(
                $userData['currently_lent'],
                function($id) use ($bookId) { return $id !== $bookId; }
            ));
            $userData['stats']['books_lent'] = count($userData['currently_lent']);
        }
        
        // Add to lent history
        if (!isset($userData['lent_history'])) {
            $userData['lent_history'] = [];
        }
        $userData['lent_history'][] = [
            'book_id' => $bookId,
            'returned_at' => date('Y-m-d H:i:s'),
            'returned_by' => $GLOBALS['currentUserId']
        ];
        
        // Sort lent_history by date desc and limit to 25
        usort($userData['lent_history'], function($a, $b) {
            $dateA = $a['returned_at'] ?? $a['date'] ?? '1970-01-01';
            $dateB = $b['returned_at'] ?? $b['date'] ?? '1970-01-01';
            return strtotime($dateB) <=> strtotime($dateA);
        });
        $userData['lent_history'] = array_slice($userData['lent_history'], 0, 25);
    }
    
    return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Create notification for user
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

// Get request ID from URL
$requestId = $_GET['id'] ?? '';
if (empty($requestId)) {
    $_SESSION['error'] = 'No request specified';
    header('Location: /requests/');
    exit;
}

// Load request data
$request = loadRequest($requestId);
if (!$request) {
    $_SESSION['error'] = 'Request not found';
    header('Location: /requests/');
    exit;
}

// Check if user is authorized (borrower or owner)
$isBorrower = $currentUserId === $request['borrower_id'];
$isOwner = $currentUserId === $request['owner_id'];
$isAdmin = isset($_SESSION['admin_id']);

if (!$isBorrower && !$isOwner && !$isAdmin) {
    $_SESSION['error'] = 'You are not authorized to return this book';
    header('Location: /requests/');
    exit;
}

// Check if request is in a returnable state
$returnableStatuses = ['approved', 'borrowed'];
if (!in_array($request['status'], $returnableStatuses)) {
    $_SESSION['error'] = 'This request cannot be returned at this time';
    header('Location: /requests/');
    exit;
}

// Load book data
$book = loadBookData($request['book_id']);
if (!$book) {
    $_SESSION['error'] = 'Book not found';
    header('Location: /requests/');
    exit;
}

// Load user data
$borrower = loadUserData($request['borrower_id']);
$owner = loadUserData($request['owner_id']);

// Process return
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $notes = trim($_POST['notes'] ?? '');
    $condition = trim($_POST['condition'] ?? 'same');
    $rating = intval($_POST['rating'] ?? 0);
    $damageDescription = trim($_POST['damage_description'] ?? '');
    
    // Validate condition
    if ($condition === 'damaged' && empty($damageDescription)) {
        $error = 'Please describe the damage';
    }
    
    if (empty($error)) {
        // Update request status
        $additionalData = [
            'notes' => $notes,
            'return_condition' => $condition,
            'returned_by' => $currentUserId,
            'returned_by_name' => $currentUserName,
            'rating' => $rating
        ];
        
        if ($condition === 'damaged') {
            $additionalData['damage_description'] = $damageDescription;
        }
        
        if (updateRequestStatus($requestId, 'returned', $additionalData)) {
            
            // Update book status to available
            updateBookStatus($request['book_id'], 'available');
            
            // Update borrower's list (remove from currently borrowed)
            updateUserBorrowedList($request['borrower_id'], $request['book_id'], 'remove');
            
            // Update owner's lent list (remove from currently lent)
            updateOwnerLentList($request['owner_id'], $request['book_id'], 'remove');
            
            // Create notification for owner
            createNotification(
                $request['owner_id'],
                'book_returned',
                'Book Returned',
                $currentUserName . ' has returned "' . $request['book_title'] . '"',
                '/requests/?id=' . $requestId
            );
            
            // Create notification for borrower (if not the same as current user)
            if ($currentUserId !== $request['borrower_id']) {
                createNotification(
                    $request['borrower_id'],
                    'return_confirmed',
                    'Return Confirmed',
                    'Your return of "' . $request['book_title'] . '" has been confirmed',
                    '/requests/?id=' . $requestId
                );
            }
            
            $_SESSION['success'] = 'Book returned successfully!';
            
            // Send return confirmation emails
            if ($mailer) {
                $returnDate = date('Y-m-d');
                
                // Email to borrower
                if (!empty($borrower['personal_info']['email'])) {
                    try {
                        $mailer->sendTemplate(
                            $borrower['personal_info']['email'],
                            $borrower['personal_info']['name'] ?? $request['borrower_name'],
                            'book_returned',
                            [
                                'subject'       => "Book Return Confirmed: \"{$request['book_title']}\"",
                                'borrower_name' => $borrower['personal_info']['name'] ?? $request['borrower_name'],
                                'book_title'    => $request['book_title'],
                                'return_date'   => $returnDate,
                                'base_url'      => BASE_URL
                            ]
                        );
                        error_log("✅ Return email sent to borrower: " . $borrower['personal_info']['email']);
                    } catch (Exception $e) {
                        error_log("❌ Failed to send return email to borrower: " . $e->getMessage());
                    }
                }
                
                // Email to owner
                if (!empty($owner['personal_info']['email'])) {
                    try {
                        $mailer->sendTemplate(
                            $owner['personal_info']['email'],
                            $owner['personal_info']['name'] ?? $request['owner_name'],
                            'book_returned_owner',
                            [
                                'subject'       => "\"{$request['book_title']}\" Has Been Returned",
                                'owner_name'    => $owner['personal_info']['name'] ?? $request['owner_name'],
                                'book_title'    => $request['book_title'],
                                'return_date'   => $returnDate,
                                'borrower_name' => $currentUserName,
                                'book_id'       => $request['book_id'],
                                'base_url'      => BASE_URL
                            ]
                        );
                        error_log("✅ Return email sent to owner: " . $owner['personal_info']['email']);
                    } catch (Exception $e) {
                        error_log("❌ Failed to send return email to owner: " . $e->getMessage());
                    }
                }
            }
            
            // Redirect based on who returned
            if ($isBorrower) {
                header('Location: /requests/');
            } else {
                header('Location: /requests/?id=' . $requestId);
            }
            exit;
            
        } else {
            $error = 'Failed to process return. Please try again.';
        }
    }
}

// Load book cover
$coverImage = !empty($book['cover_image']) ? '/uploads/book_cover/thumb_' . $book['cover_image'] : '/assets/images/default-book-cover.jpg';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Return Book - <?php echo htmlspecialchars($request['book_title']); ?> | OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .return-container {
            max-width: 600px;
            margin: 0 auto;
            padding: var(--space-5);
        }
        
        .book-preview {
            display: flex;
            gap: var(--space-4);
            background: var(--surface-hover);
            padding: var(--space-4);
            border-radius: var(--radius-lg);
            margin-bottom: var(--space-5);
        }
        
        .book-preview-cover {
            width: 80px;
            height: 100px;
            border-radius: var(--radius-md);
            overflow: hidden;
            flex-shrink: 0;
        }
        
        .book-preview-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .book-preview-info h3 {
            margin-bottom: var(--space-1);
            font-size: var(--font-size-base);
        }
        
        .book-preview-info p {
            color: var(--text-tertiary);
            font-size: var(--font-size-sm);
            margin-bottom: var(--space-1);
        }
        
        .info-box {
            background: rgba(37, 99, 235, 0.1);
            border: 1px solid rgba(37, 99, 235, 0.2);
            border-radius: var(--radius-lg);
            padding: var(--space-4);
            margin-bottom: var(--space-5);
            display: flex;
            gap: var(--space-3);
        }
        
        .info-box i {
            color: var(--primary);
            font-size: 1.5rem;
        }
        
        .info-box p {
            margin-bottom: 0;
            color: var(--text-secondary);
        }
        
        .radio-group {
            display: flex;
            gap: var(--space-4);
            margin-top: var(--space-2);
        }
        
        .radio-option {
            display: flex;
            align-items: center;
            gap: var(--space-2);
            cursor: pointer;
        }
        
        .radio-option input {
            cursor: pointer;
        }
        
        .damage-field {
            margin-top: var(--space-3);
            margin-left: var(--space-5);
            display: none;
        }
        
        .damage-field.show {
            display: block;
        }
        
        .rating-stars {
            display: flex;
            gap: var(--space-1);
            font-size: 1.5rem;
            color: gold;
            cursor: pointer;
        }
        
        .rating-stars i {
            transition: all var(--transition-fast);
        }
        
        .rating-stars i:hover,
        .rating-stars i.active {
            color: gold;
        }
        
        @media (max-width: 640px) {
            .return-container {
                padding: var(--space-4);
            }
            
            .radio-group {
                flex-direction: column;
                gap: var(--space-2);
            }
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="return-container">
                <!-- Page Header -->
                <div style="margin-bottom: var(--space-6);">
                    <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-2);">
                        <a href="/requests/" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Requests
                        </a>
                    </div>
                    <h1 style="font-size: var(--font-size-xl); margin-bottom: var(--space-2);">
                        <i class="fas fa-undo-alt" style="color: var(--success);"></i>
                        Return Book
                    </h1>
                    <p style="color: var(--text-tertiary);">Confirm the return of this book</p>
                </div>
                
                <!-- Error Message -->
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Book Preview -->
                <div class="book-preview">
                    <div class="book-preview-cover">
                        <img src="<?php echo $coverImage; ?>" alt="<?php echo htmlspecialchars($request['book_title']); ?>">
                    </div>
                    <div class="book-preview-info">
                        <h3><?php echo htmlspecialchars($request['book_title']); ?></h3>
                        <p>by <?php echo htmlspecialchars($request['book_author']); ?></p>
                        <p><i class="fas fa-user"></i> Borrowed by: <?php echo htmlspecialchars($request['borrower_name']); ?></p>
                        <?php if (!empty($request['expected_return_date'])): ?>
                            <p><i class="far fa-calendar"></i> Due date: <?php echo date('M j, Y', strtotime($request['expected_return_date'])); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Info Box -->
                <div class="info-box">
                    <i class="fas fa-info-circle"></i>
                    <div>
                        <p>Please confirm the book condition. If the book is damaged, please describe the damage.</p>
                        <p class="text-muted" style="font-size: var(--font-size-sm); margin-top: var(--space-2);">
                            <i class="fas fa-shield-alt"></i>
                            This helps maintain trust in the community.
                        </p>
                    </div>
                </div>
                
                <!-- Return Form -->
                <form method="POST" id="returnForm">
                    <!-- Book Condition -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-star"></i>
                            Book Condition <span class="text-danger">*</span>
                        </label>
                        <div class="radio-group">
                            <label class="radio-option">
                                <input type="radio" name="condition" value="same" checked onchange="toggleDamageField()">
                                <span>Same as borrowed</span>
                            </label>
                            <label class="radio-option">
                                <input type="radio" name="condition" value="damaged" onchange="toggleDamageField()">
                                <span>Damaged</span>
                            </label>
                        </div>
                        
                        <div id="damageField" class="damage-field">
                            <label class="form-label" style="font-size: var(--font-size-sm);">
                                <i class="fas fa-exclamation-triangle"></i>
                                Describe the damage
                            </label>
                            <textarea name="damage_description" class="form-input" rows="3" 
                                      placeholder="Please describe any damage to the book..."></textarea>
                        </div>
                    </div>
                    
                    <!-- Rating (optional) -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-star"></i>
                            Rate this book (Optional)
                        </label>
                        <div class="rating-stars" id="ratingStars">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                        </div>
                        <input type="hidden" name="rating" id="ratingValue" value="0">
                    </div>
                    
                    <!-- Additional Notes -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-comment"></i>
                            Additional Notes (Optional)
                        </label>
                        <textarea name="notes" class="form-input" rows="3" 
                                  placeholder="Any additional comments about the return..."></textarea>
                    </div>
                    
                    <!-- Form Actions -->
                    <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                        <button type="submit" class="btn btn-success" style="flex: 2;">
                            <i class="fas fa-check-circle"></i>
                            Confirm Return
                        </button>
                        <a href="/requests/" class="btn btn-outline" style="flex: 1;">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script>
        // Toggle damage field visibility
        function toggleDamageField() {
            const damageRadio = document.querySelector('input[name="condition"][value="damaged"]');
            const damageField = document.getElementById('damageField');
            
            if (damageRadio && damageRadio.checked) {
                damageField.classList.add('show');
                document.querySelector('textarea[name="damage_description"]').required = true;
            } else {
                damageField.classList.remove('show');
                document.querySelector('textarea[name="damage_description"]').required = false;
            }
        }
        
        // Rating stars functionality
        const stars = document.querySelectorAll('#ratingStars i');
        const ratingInput = document.getElementById('ratingValue');
        
        stars.forEach(star => {
            star.addEventListener('click', function() {
                const rating = parseInt(this.dataset.rating);
                ratingInput.value = rating;
                
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.className = 'fas fa-star';
                    } else {
                        s.className = 'far fa-star';
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = parseInt(this.dataset.rating);
                stars.forEach((s, index) => {
                    if (index < rating) {
                        s.className = 'fas fa-star';
                    } else {
                        s.className = 'far fa-star';
                    }
                });
            });
            
            star.addEventListener('mouseleave', function() {
                const currentRating = parseInt(ratingInput.value);
                stars.forEach((s, index) => {
                    if (index < currentRating) {
                        s.className = 'fas fa-star';
                    } else {
                        s.className = 'far fa-star';
                    }
                });
            });
        });
        
        // Form validation
        document.getElementById('returnForm').addEventListener('submit', function(e) {
            const condition = document.querySelector('input[name="condition"]:checked');
            
            if (!condition) {
                e.preventDefault();
                alert('Please select the book condition');
                return false;
            }
            
            if (condition.value === 'damaged') {
                const damageDesc = document.querySelector('textarea[name="damage_description"]').value.trim();
                if (!damageDesc) {
                    e.preventDefault();
                    alert('Please describe the damage to the book');
                    return false;
                }
            }
            
            return true;
        });
        
        // Initialize
        toggleDamageField();
    </script>
    
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>