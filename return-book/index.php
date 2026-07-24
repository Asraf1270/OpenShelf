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
define('BASE_URL', 'https://duopenshelf.top');

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
 * Update request status — supports 'pending_return' and 'returned'
 */
function updateRequestStatus($requestId, $status, $additionalData = []) {
    $request = loadRequest($requestId);
    if (!$request) return false;
    
    $db = getDB();
    
    $history = $request['history'] ?? [];
    $actionLabel = ($status === 'pending_return') ? 'return_filed' : 'returned';
    $history[] = [
        'action'    => $actionLabel,
        'timestamp' => date('Y-m-d H:i:s'),
        'user_id'   => $GLOBALS['currentUserId'],
        'user_name' => $GLOBALS['currentUserName'],
        'notes'     => $additionalData['notes'] ?? '',
        'condition' => $additionalData['return_condition'] ?? 'same',
        'rating'    => $additionalData['rating'] ?? 0
    ];
    
    // Base SQL
    $sql    = "UPDATE borrow_requests SET status = :status, history = :history, updated_at = :updated_at";
    $params = [
        ':status'     => $status,
        ':history'    => json_encode($history),
        ':updated_at' => date('Y-m-d H:i:s'),
        ':id'         => $requestId
    ];
    
    if ($status === 'pending_return') {
        // Borrower has filed the return — awaiting owner confirmation
        $sql .= ", returned_at = :returned_at, actual_return_date = :actual_return_date,
                   notes = :notes, return_condition = :return_condition,
                   returned_by = :returned_by, returned_by_name = :returned_by_name, rating = :rating,
                   return_confirmation_token = :token,
                   return_confirmation_status = 'pending_owner',
                   return_confirmation_sent_at = :sent_at";

        $params[':returned_at']        = date('Y-m-d H:i:s');
        $params[':actual_return_date'] = date('Y-m-d H:i:s');
        $params[':notes']              = $additionalData['notes'] ?? null;
        $params[':return_condition']   = $additionalData['return_condition'] ?? null;
        $params[':returned_by']        = $additionalData['returned_by'] ?? null;
        $params[':returned_by_name']   = $additionalData['returned_by_name'] ?? null;
        $params[':rating']             = $additionalData['rating'] ?? 0;
        $params[':token']              = $additionalData['confirmation_token'];
        $params[':sent_at']            = date('Y-m-d H:i:s');

    } elseif ($status === 'returned') {
        // Owner confirmed physical receipt
        $sql .= ", return_confirmation_status = 'confirmed',
                   return_confirmed_at = :confirmed_at";

        $params[':confirmed_at'] = date('Y-m-d H:i:s');
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
    
    $notes            = trim($_POST['notes'] ?? '');
    $condition        = trim($_POST['condition'] ?? 'same');
    $rating           = intval($_POST['rating'] ?? 0);
    $damageDescription = trim($_POST['damage_description'] ?? '');
    
    // Validate condition
    if ($condition === 'damaged' && empty($damageDescription)) {
        $error = 'Please describe the damage';
    }
    
    if (empty($error)) {
        // Generate a secure confirmation token for the owner
        $confirmationToken = bin2hex(random_bytes(32)); // 64-char hex

        $additionalData = [
            'notes'              => $notes,
            'return_condition'   => $condition,
            'returned_by'        => $currentUserId,
            'returned_by_name'   => $currentUserName,
            'rating'             => $rating,
            'confirmation_token' => $confirmationToken,
        ];
        
        if ($condition === 'damaged') {
            $additionalData['damage_description'] = $damageDescription;
        }
        
        // Set status to 'pending_return' — owner must confirm physical receipt
        if (updateRequestStatus($requestId, 'pending_return', $additionalData)) {

            // Notify owner in-app
            createNotification(
                $request['owner_id'],
                'return_pending',
                'Book Return Awaiting Confirmation',
                $currentUserName . ' has filed a return for "' . $request['book_title'] . '". Please confirm physical receipt.',
                '/confirm-return/?token=' . $confirmationToken
            );

            // Send confirmation email to owner
            $confirmUrl = BASE_URL . '/confirm-return/?token=' . $confirmationToken;
            $rejectUrl  = BASE_URL . '/confirm-return/?token=' . $confirmationToken . '&action=reject';

            if ($mailer && !empty($owner['personal_info']['email'])) {
                try {
                    $mailer->sendTemplate(
                        $owner['personal_info']['email'],
                        $owner['personal_info']['name'] ?? $request['owner_name'],
                        'book_returned_owner',
                        [
                            'subject'        => 'Action Required: Confirm Return of "' . $request['book_title'] . '"',
                            'owner_name'     => $owner['personal_info']['name'] ?? $request['owner_name'],
                            'book_title'     => $request['book_title'],
                            'return_date'    => date('Y-m-d'),
                            'borrower_name'  => $request['borrower_name'] ?? $currentUserName,
                            'book_id'        => $request['book_id'],
                            'confirm_url'    => $confirmUrl,
                            'reject_url'     => $rejectUrl,
                            'return_condition' => $condition,
                            'return_notes'   => $notes,
                            'base_url'       => BASE_URL,
                            'type'           => 'warning',
                        ]
                    );
                    error_log("✅ Return confirmation email sent to owner: " . $owner['personal_info']['email']);
                } catch (Exception $e) {
                    error_log("❌ Failed to send return confirmation email to owner: " . $e->getMessage());
                }
            }

            $_SESSION['success'] = '✅ Return filed! The book owner has been notified and must confirm physical receipt before it is marked as returned.';

            header('Location: /requests/');
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
        /* ═══════════════════════════════════════
           Return Book — Brand Colors & Premium UI
           ═══════════════════════════════════════ */

        /* Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .return-container {
            max-width: 900px;
            margin: 0 auto;
            padding: var(--space-lg);
            animation: fadeIn 0.4s ease-out;
        }

        /* Top gradient accent line */
        .return-page-band {
            height: 4px;
            background: linear-gradient(90deg, var(--primary), var(--secondary));
            border-radius: var(--radius-full);
            margin-bottom: var(--space-md);
        }

        .return-page-header {
            margin-bottom: var(--space-lg);
        }

        .return-page-header h1 {
            font-size: var(--font-size-xxl);
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            margin-bottom: var(--space-xs);
        }

        .return-header-icon {
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border-radius: var(--radius-md);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            flex-shrink: 0;
            box-shadow: 0 4px 10px rgba(76, 159, 138, 0.2);
        }

        .return-page-header p {
            color: var(--text-secondary);
            margin-left: 0;
            font-size: var(--font-size-sm);
        }

        /* Timeline Tracker */
        .return-timeline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--surface-hover);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: var(--space-sm) var(--space-md);
            margin-bottom: var(--space-lg);
        }

        .timeline-step {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            font-size: var(--font-size-xs);
            color: var(--text-tertiary);
            font-weight: 500;
        }

        .timeline-step.completed {
            color: var(--secondary);
        }

        .timeline-step.active {
            color: var(--primary);
            font-weight: 600;
        }

        .timeline-step-num {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--border);
            color: var(--text-secondary);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.75rem;
        }

        .timeline-step.completed .timeline-step-num {
            background: var(--secondary);
            color: white;
        }

        .timeline-step.active .timeline-step-num {
            background: var(--primary);
            color: white;
            box-shadow: 0 0 8px var(--primary-light);
        }

        .timeline-line {
            flex-grow: 1;
            height: 2px;
            background: var(--border);
            margin: 0 var(--space-sm);
        }

        .timeline-line.active {
            background: var(--secondary);
        }

        /* Two-Column Grid */
        .return-content-layout {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-lg);
            align-items: start;
        }

        @media (min-width: 768px) {
            .return-content-layout {
                grid-template-columns: 1.1fr 1.9fr;
            }
        }

        .return-sidebar {
            display: flex;
            flex-direction: column;
            gap: var(--space-md);
        }

        /* Sidebar Cards styling */
        .return-card {
            background: var(--header-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: var(--space-md);
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        .return-card:hover {
            box-shadow: var(--shadow-md);
        }

        /* Book Preview Detail Card */
        .book-preview-card {
            text-align: center;
        }

        .book-cover-container {
            position: relative;
            width: 120px;
            height: 170px;
            margin: 0 auto var(--space-sm);
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: transform 0.3s ease;
        }

        .book-preview-card:hover .book-cover-container {
            transform: translateY(-4px) scale(1.02);
            box-shadow: var(--shadow-lg);
        }

        .book-cover-container img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .book-details h3 {
            font-size: var(--font-size-md);
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            line-height: 1.3;
        }

        .book-author {
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            margin-bottom: var(--space-sm);
        }

        .book-badge-category {
            display: inline-block;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.6rem;
            border-radius: var(--radius-full);
            background: var(--primary-soft);
            color: var(--primary);
            margin-bottom: var(--space-md);
        }

        /* Metadata table/list */
        .meta-list {
            list-style: none;
            padding: 0;
            margin: 0;
            border-top: 1px solid var(--border);
            padding-top: var(--space-sm);
            text-align: left;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: var(--space-sm);
            font-size: var(--font-size-sm);
            color: var(--text-secondary);
            padding: var(--space-xs) 0;
        }

        .meta-item i {
            color: var(--secondary);
            width: 16px;
        }

        .meta-label {
            font-weight: 500;
            color: var(--text-primary);
        }

        /* Overdue styling */
        .meta-item.overdue {
            color: var(--danger);
        }

        .meta-item.overdue i {
            color: var(--danger);
        }

        .badge-overdue {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.15rem 0.4rem;
            border-radius: var(--radius-sm);
            margin-left: auto;
        }

        /* Info Checklist card */
        .guide-title {
            font-size: var(--font-size-sm);
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: var(--space-sm);
            display: flex;
            align-items: center;
            gap: var(--space-xs);
        }

        .guide-title i {
            color: var(--primary);
        }

        .guide-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .guide-item {
            display: flex;
            align-items: flex-start;
            gap: var(--space-xs);
            font-size: 0.8rem;
            color: var(--text-secondary);
            padding: var(--space-xs) 0;
            line-height: 1.4;
        }

        .guide-item i {
            color: var(--secondary);
            margin-top: 3px;
        }

        /* Main Form Area */
        .form-card {
            background: var(--header-bg);
            border: 1px solid var(--border);
            border-radius: var(--radius-lg);
            padding: var(--space-md);
            box-shadow: var(--shadow-sm);
        }

        @media (min-width: 768px) {
            .form-card {
                padding: var(--space-lg);
            }
        }

        .form-section-title {
            font-size: var(--font-size-sm);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-tertiary);
            font-weight: 700;
            margin-bottom: var(--space-md);
            border-bottom: 1px solid var(--border);
            padding-bottom: var(--space-xs);
        }

        /* Interactive Radio Options */
        .condition-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: var(--space-sm);
            margin-bottom: var(--space-md);
        }

        @media (min-width: 480px) {
            .condition-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .condition-card {
            border: 2px solid var(--border);
            background: var(--header-bg);
            border-radius: var(--radius-md);
            padding: var(--space-md);
            cursor: pointer;
            position: relative;
            display: flex;
            flex-direction: column;
            gap: var(--space-xs);
            transition: all 0.2s ease;
            user-select: none;
        }

        .condition-card input[type="radio"] {
            display: none;
        }

        .condition-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-weight: 600;
            font-size: var(--font-size-sm);
            color: var(--text-primary);
        }

        .condition-header i {
            font-size: 1.1rem;
            transition: transform 0.2s ease;
        }

        .condition-card p {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: 0;
            line-height: 1.35;
        }

        /* Active styling - Same / Intact */
        .condition-card.same-active:has(input:checked) {
            border-color: var(--secondary);
            background: rgba(76, 159, 138, 0.08);
            box-shadow: 0 4px 12px rgba(76, 159, 138, 0.05);
        }

        .condition-card.same-active:has(input:checked) .condition-header i {
            color: var(--secondary);
            transform: scale(1.15);
        }

        /* Active styling - Damaged */
        .condition-card.damaged-active:has(input:checked) {
            border-color: var(--danger);
            background: rgba(239, 68, 68, 0.05);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.05);
        }

        .condition-card.damaged-active:has(input:checked) .condition-header i {
            color: var(--danger);
            transform: scale(1.15);
        }

        /* Damage details section */
        .damage-details {
            display: none;
            animation: slideDown 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(239, 68, 68, 0.02);
            border: 1px dashed var(--danger);
            border-radius: var(--radius-md);
            padding: var(--space-md);
            margin-bottom: var(--space-md);
        }

        .damage-details.show {
            display: block;
        }

        .damage-details label {
            color: var(--danger);
            font-weight: 600;
        }

        .damage-details textarea {
            border-color: rgba(239, 68, 68, 0.3);
        }

        .damage-details textarea:focus {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1);
        }

        /* Premium Rating Stars */
        .rating-wrapper {
            display: flex;
            align-items: center;
            gap: var(--space-md);
            background: var(--surface-hover);
            border-radius: var(--radius-md);
            padding: var(--space-sm) var(--space-md);
            margin-bottom: var(--space-md);
            border: 1px solid var(--border);
        }

        .stars-container {
            display: flex;
            gap: var(--space-xs);
            font-size: 1.8rem;
            cursor: pointer;
        }

        .stars-container i {
            color: var(--text-tertiary);
            transition: color 0.15s ease, transform 0.2s cubic-bezier(0.175, 0.885, 0.32, 1.25);
        }

        .stars-container i:hover,
        .stars-container i.active {
            color: #f59e0b;
            transform: scale(1.2);
        }

        .stars-container i.active {
            text-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
        }

        .rating-label-feedback {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-secondary);
            transition: color 0.2s ease;
        }

        /* Additional Notes Counter styling */
        .textarea-container {
            position: relative;
        }

        .char-counter {
            font-size: 0.75rem;
            color: var(--text-tertiary);
            text-align: right;
            margin-top: 4px;
        }

        /* Buttons & Actions */
        .form-actions {
            display: flex;
            gap: var(--space-sm);
            margin-top: var(--space-lg);
        }

        .form-actions button[type="submit"] {
            flex: 2;
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            border: none;
            color: white;
            box-shadow: 0 4px 14px rgba(76, 159, 138, 0.3);
        }

        .form-actions button[type="submit"]:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(76, 159, 138, 0.4);
        }

        .form-actions a.btn-cancel {
            flex: 1;
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-secondary);
        }

        .form-actions a.btn-cancel:hover {
            background: var(--surface-hover);
            color: var(--text-primary);
        }

        /* Dark mode overrides for high premium visual look */
        [data-theme="dark"] .return-timeline {
            background: rgba(30, 41, 59, 0.6);
        }

        [data-theme="dark"] .rating-wrapper {
            background: rgba(30, 41, 59, 0.4);
        }

        [data-theme="dark"] .condition-card {
            background: #1e293b;
        }

        [data-theme="dark"] .condition-card.same-active:has(input:checked) {
            background: rgba(16, 185, 129, 0.08);
            border-color: var(--success);
        }

        [data-theme="dark"] .condition-card.damaged-active:has(input:checked) {
            background: rgba(239, 68, 68, 0.08);
            border-color: var(--danger);
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="return-container">
                <!-- Page Header -->
                <div class="return-page-header">
                    <div style="margin-bottom: var(--space-sm);">
                        <a href="/requests/" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Requests
                        </a>
                    </div>
                    <div class="return-page-band"></div>
                    <h1>
                        <span class="return-header-icon"><i class="fas fa-undo-alt"></i></span>
                        Return Confirmation
                    </h1>
                    <p>Provide final details to complete the lending transaction</p>
                </div>
                
                <!-- Error Alert -->
                <?php if ($error): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <!-- Progress Flow Timeline Tracker -->
                <div class="return-timeline">
                    <div class="timeline-step completed">
                        <span class="timeline-step-num"><i class="fas fa-check"></i></span> Borrowed
                    </div>
                    <div class="timeline-line active"></div>
                    <div class="timeline-step active">
                        <span class="timeline-step-num">2</span> File Return
                    </div>
                    <div class="timeline-line"></div>
                    <div class="timeline-step">
                        <span class="timeline-step-num">3</span> Owner Confirms
                    </div>
                    <div class="timeline-line"></div>
                    <div class="timeline-step">
                        <span class="timeline-step-num">4</span> Complete
                    </div>
                </div>

                <div class="return-content-layout">
                    <!-- Left Sidebar details -->
                    <div class="return-sidebar">
                        
                        <!-- Book Details Card -->
                        <div class="return-card book-preview-card">
                            <div class="book-cover-container">
                                <img src="<?php echo $coverImage; ?>" alt="<?php echo htmlspecialchars($request['book_title']); ?>">
                            </div>
                            <div class="book-details">
                                <h3><?php echo htmlspecialchars($request['book_title']); ?></h3>
                                <p class="book-author">by <?php echo htmlspecialchars($request['book_author']); ?></p>
                                <span class="book-badge-category">
                                    <i class="fas fa-bookmark"></i> <?php echo htmlspecialchars($book['category'] ?? 'Category'); ?>
                                </span>
                            </div>
                            
                            <?php
                            $isPastDue = false;
                            $overdueDays = 0;
                            if (!empty($request['expected_return_date'])) {
                                $dueDate = strtotime($request['expected_return_date']);
                                $today = strtotime(date('Y-m-d'));
                                if ($dueDate < $today) {
                                    $isPastDue = true;
                                    $overdueDays = round(($today - $dueDate) / (60 * 60 * 24));
                                }
                            }
                            ?>
                            
                            <ul class="meta-list">
                                <li class="meta-item">
                                    <i class="fas fa-user-circle"></i>
                                    <span class="meta-label">Borrower:</span> 
                                    <span style="margin-left:auto;"><?php echo htmlspecialchars($request['borrower_name']); ?></span>
                                </li>
                                <li class="meta-item">
                                    <i class="fas fa-user-tie"></i>
                                    <span class="meta-label">Owner:</span> 
                                    <span style="margin-left:auto;"><?php echo htmlspecialchars($request['owner_name']); ?></span>
                                </li>
                                <?php if (!empty($request['expected_return_date'])): ?>
                                    <li class="meta-item <?php echo $isPastDue ? 'overdue' : ''; ?>">
                                        <i class="far fa-calendar-alt"></i>
                                        <span class="meta-label">Due Date:</span>
                                        <span style="margin-left:auto;"><?php echo date('M j, Y', strtotime($request['expected_return_date'])); ?></span>
                                        <?php if ($isPastDue): ?>
                                            <span class="badge-overdue"><?php echo $overdueDays; ?>d Overdue</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </div>
                        
                        <!-- Guidelines Instruction Card -->
                        <div class="return-card">
                            <div class="guide-title">
                                <i class="fas fa-shield-alt"></i>
                                Return Guidelines
                            </div>
                            <ul class="guide-list">
                                <li class="guide-item">
                                    <i class="fas fa-circle-check"></i>
                                    <span>Check all book pages for personal belongings or notes.</span>
                                </li>
                                <li class="guide-item">
                                    <i class="fas fa-circle-check"></i>
                                    <span>Assess condition honestly to support the book sharing community.</span>
                                </li>
                                <li class="guide-item">
                                    <i class="fas fa-circle-check"></i>
                                    <span>Leave a rating of your experience reading this book.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Right Main Form Card -->
                    <div class="form-card">
                        <form method="POST" id="returnForm">
                            
                            <!-- Book Condition Section -->
                            <div class="form-group">
                                <div class="form-section-title">
                                    <i class="fas fa-check-double"></i> Book Condition
                                </div>
                                
                                <div class="condition-grid">
                                    <!-- Same condition card -->
                                    <label class="condition-card same-active">
                                        <input type="radio" name="condition" value="same" checked onchange="toggleConditionState()">
                                        <div class="condition-header">
                                            <span>Good / Intact</span>
                                            <i class="fas fa-circle-check"></i>
                                        </div>
                                        <p>Same condition as borrowed, no new structural damage or marks.</p>
                                    </label>
                                    
                                    <!-- Damaged condition card -->
                                    <label class="condition-card damaged-active">
                                        <input type="radio" name="condition" value="damaged" onchange="toggleConditionState()">
                                        <div class="condition-header">
                                            <span>Has Damage</span>
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <p>New wears, tears, marks, torn pages, or water damage.</p>
                                    </label>
                                </div>

                                <!-- Slide-down Damage Description -->
                                <div id="damageField" class="damage-details">
                                    <label class="form-label" style="font-size: var(--font-size-sm);">
                                        Describe the damage <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="damage_description" class="form-input" rows="3"
                                              placeholder="Please details the location and severity of the damage..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Premium Rating Stars Section -->
                            <div class="form-group">
                                <div class="form-section-title">
                                    <i class="fas fa-star-half-alt"></i> Rate this book
                                </div>
                                <div class="rating-wrapper">
                                    <div class="stars-container" id="ratingStars">
                                        <i class="far fa-star" data-rating="1"></i>
                                        <i class="far fa-star" data-rating="2"></i>
                                        <i class="far fa-star" data-rating="3"></i>
                                        <i class="far fa-star" data-rating="4"></i>
                                        <i class="far fa-star" data-rating="5"></i>
                                    </div>
                                    <div class="rating-label-feedback" id="ratingFeedbackText">
                                        Select rating
                                    </div>
                                </div>
                                <input type="hidden" name="rating" id="ratingValue" value="0">
                            </div>
                            
                            <!-- Additional Notes Section -->
                            <div class="form-group">
                                <div class="form-section-title">
                                    <i class="fas fa-comment-alt"></i> Notes & Feedback
                                </div>
                                <div class="textarea-container">
                                    <textarea name="notes" class="form-input" rows="3" maxlength="300"
                                              placeholder="Write additional comments about the lending experience or return process..."
                                              oninput="updateCharCount(this)"></textarea>
                                    <div class="char-counter" id="notesCounter">0 / 300 characters</div>
                                </div>
                            </div>
                            
                            <!-- Info banner: owner confirmation step -->
                            <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);border-radius:var(--radius-md);padding:var(--space-md);margin-bottom:var(--space-md);display:flex;gap:var(--space-sm);align-items:flex-start;">
                                <i class="fas fa-info-circle" style="color:#f59e0b;margin-top:2px;flex-shrink:0;"></i>
                                <div style="font-size:var(--font-size-sm);color:var(--text-secondary);">
                                    <strong style="color:var(--text-primary);">Two-step return process</strong><br>
                                    Submitting this form will notify the <strong>book owner</strong> by email.
                                    The book will be marked <em>Available</em> only after the owner confirms physical receipt.
                                </div>
                            </div>

                            <!-- Form Buttons -->
                            <div class="form-actions">
                                <button type="submit" class="btn" id="submitReturnBtn">
                                    <i class="fas fa-paper-plane"></i> Submit Return Request
                                </button>
                                <a href="/requests/" class="btn btn-cancel">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        // Toggle damage field visibility and card design updates
        function toggleConditionState() {
            const damagedRadio = document.querySelector('input[name="condition"][value="damaged"]');
            const damageField = document.getElementById('damageField');
            const damageInput = document.querySelector('textarea[name="damage_description"]');
            
            if (damagedRadio && damagedRadio.checked) {
                damageField.classList.add('show');
                if (damageInput) damageInput.required = true;
            } else {
                damageField.classList.remove('show');
                if (damageInput) {
                    damageInput.required = false;
                    damageInput.value = '';
                }
            }
        }
        
        // Character counter for notes input
        function updateCharCount(el) {
            const count = el.value.length;
            const max = el.getAttribute('maxlength') || 300;
            const counter = document.getElementById('notesCounter');
            if (counter) {
                counter.textContent = `${count} / ${max} characters`;
            }
        }
        
        // Rating stars premium click & hover feedback
        (function() {
            const stars = document.querySelectorAll('#ratingStars i');
            const ratingInput = document.getElementById('ratingValue');
            const feedbackText = document.getElementById('ratingFeedbackText');
            
            const ratingLabels = {
                0: 'Select rating',
                1: 'Poor - Did not like it',
                2: 'Fair - Could be better',
                3: 'Good - Enjoyable read',
                4: 'Very Good - Recommended',
                5: 'Excellent - Absolutely loved it!'
            };
            
            function renderStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.className = 'fas fa-star active';
                    } else {
                        star.className = 'far fa-star';
                    }
                });
                if (feedbackText) {
                    feedbackText.textContent = ratingLabels[rating] || ratingLabels[0];
                    if (rating > 0) {
                        feedbackText.style.color = '#f59e0b';
                    } else {
                        feedbackText.style.color = '';
                    }
                }
            }
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    ratingInput.value = rating;
                    renderStars(rating);
                });
                
                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.dataset.rating);
                    renderStars(rating);
                });
                
                star.addEventListener('mouseleave', function() {
                    const currentRating = parseInt(ratingInput.value || 0);
                    renderStars(currentRating);
                });
            });
            
            // Trigger initial state
            renderStars(0);
        })();
        
        // Validation shaker on submit
        document.getElementById('returnForm').addEventListener('submit', function(e) {
            const condition = document.querySelector('input[name="condition"]:checked');
            
            if (!condition) {
                e.preventDefault();
                alert('Please select the book condition status.');
                return false;
            }
            
            if (condition.value === 'damaged') {
                const damageDesc = document.querySelector('textarea[name="damage_description"]').value.trim();
                if (!damageDesc) {
                    e.preventDefault();
                    alert('Please provide a brief description of the damage.');
                    return false;
                }
            }
            return true;
        });
        
        // Initial call
        toggleConditionState();
    </script>
    
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>