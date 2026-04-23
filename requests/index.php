<?php
/**
 * OpenShelf Request Management Page
 * Complete with Approval, Rejection, and Return Confirmation Emails
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_DATA_PATH', dirname(__DIR__) . '/data/book/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('BASE_URL', 'https://openshelf.free.nf');

// Include database connection and helpers
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/requests/';
    header('Location: /login/');
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'Unknown';

// Initialize mailer
$mailer = null;
try {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
    require_once dirname(__DIR__) . '/lib/Mailer.php';
    $mailer = new Mailer();
    error_log("✅ Mailer initialized for requests");
} catch (Exception $e) {
    error_log("❌ Mailer init failed: " . $e->getMessage());
}

/**
 * Load all borrow requests from DB
 */
function loadAllRequests() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM borrow_requests");
    return $stmt->fetchAll();
}

/**
 * Load user data using helper
 */
function loadUserData($userId) {
    return getUserById($userId);
}

/**
 * Load book data using helper
 */
function loadBookData($bookId) {
    return getBookById($bookId);
}

/**
 * Update request status in DB
 */
function updateRequestStatus($requestId, $status, $additionalData = []) {
    $db = getDB();
    
    $sql = "UPDATE borrow_requests SET status = :status, updated_at = :updated_at";
    $params = [
        ':status' => $status,
        ':updated_at' => date('Y-m-d H:i:s'),
        ':id' => $requestId
    ];
    
    if ($status === 'approved') {
        $sql .= ", approved_at = :approved_at";
        $params[':approved_at'] = date('Y-m-d H:i:s');
    } elseif ($status === 'rejected') {
        $sql .= ", rejected_at = :rejected_at, rejection_reason = :rejection_reason";
        $params[':rejected_at'] = date('Y-m-d H:i:s');
        $params[':rejection_reason'] = $additionalData['reason'] ?? '';
    } elseif ($status === 'returned') {
        $sql .= ", returned_at = :returned_at, return_condition = :return_condition, notes = :notes";
        $params[':returned_at'] = date('Y-m-d H:i:s');
        $params[':return_condition'] = $additionalData['condition'] ?? 'same';
        $params[':notes'] = $additionalData['notes'] ?? '';
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    if ($stmt->execute($params)) {
        $stmt = $db->prepare("SELECT * FROM borrow_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        return $stmt->fetch();
    }
    
    return null;
}

/**
 * Update book status in DB
 */
function updateBookStatus($bookId, $status, $borrowerId = null) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE books SET status = ?, updated_at = ? WHERE id = ?");
    return $stmt->execute([$status, date('Y-m-d H:i:s'), $bookId]);
}

/**
 * Update user lists
 */
function updateUserLists($userId, $bookId, $action) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) return;
    
    $user = json_decode(file_get_contents($userFile), true);
    
    if ($action === 'add_borrowed') {
        if (!isset($user['currently_borrowed'])) $user['currently_borrowed'] = [];
        if (!in_array($bookId, $user['currently_borrowed'])) {
            $user['currently_borrowed'][] = $bookId;
        }
        $user['stats']['books_borrowed'] = count($user['currently_borrowed']);
    } elseif ($action === 'add_lent') {
        if (!isset($user['currently_lent'])) $user['currently_lent'] = [];
        if (!in_array($bookId, $user['currently_lent'])) {
            $user['currently_lent'][] = $bookId;
        }
        $user['stats']['books_lent'] = count($user['currently_lent']);
    } elseif ($action === 'remove_borrowed') {
        if (isset($user['currently_borrowed'])) {
            $user['currently_borrowed'] = array_values(array_filter($user['currently_borrowed'], fn($id) => $id !== $bookId));
            $user['stats']['books_borrowed'] = count($user['currently_borrowed']);
        }
    } elseif ($action === 'remove_lent') {
        if (isset($user['currently_lent'])) {
            $user['currently_lent'] = array_values(array_filter($user['currently_lent'], fn($id) => $id !== $bookId));
            $user['stats']['books_lent'] = count($user['currently_lent']);
        }
    }
    
    file_put_contents($userFile, json_encode($user, JSON_PRETTY_PRINT));
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
 * Send email notification
 */
function sendEmail($to, $name, $template, $data) {
    global $mailer;
    
    if (!$mailer) {
        error_log("❌ Mailer not available for $template email");
        return false;
    }
    
    try {
        error_log("📧 Sending $template email to: $to");
        
        $result = $mailer->sendTemplate($to, $name, $template, $data);
        
        if ($result) {
            error_log("✅ $template email sent to: $to");
        } else {
            error_log("❌ sendTemplate returned false for $template to: $to");
        }
        
        return $result;
    } catch (Exception $e) {
        error_log("❌ Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Send approval email to borrower
 */
function sendApprovalEmail($borrowerEmail, $borrowerName, $ownerName, $bookTitle, $bookAuthor, $dueDate, $ownerRoom, $ownerPhone, $requestId) {
    return sendEmail(
        $borrowerEmail,
        $borrowerName,
        'request_approved',
        [
            'subject'      => "Your Borrow Request for \"$bookTitle\" Has Been Approved!",
            'borrower_name'=> $borrowerName,
            'owner_name'   => $ownerName,
            'book_title'   => $bookTitle,
            'book_author'  => $bookAuthor,
            'due_date'     => $dueDate,
            'owner_room'   => $ownerRoom,
            'owner_phone'  => $ownerPhone,
            'request_id'   => $requestId,
            'base_url'     => BASE_URL
        ]
    );
}

/**
 * Send rejection email to borrower
 */
function sendRejectionEmail($borrowerEmail, $borrowerName, $bookTitle, $reason, $requestId) {
    return sendEmail(
        $borrowerEmail,
        $borrowerName,
        'request_rejected',
        [
            'subject'          => "Update on Your Borrow Request for \"$bookTitle\"",
            'borrower_name'    => $borrowerName,
            'book_title'       => $bookTitle,
            'rejection_reason' => $reason,
            'request_id'       => $requestId,
            'base_url'         => BASE_URL
        ]
    );
}

/**
 * Send return confirmation email to borrower
 */
function sendReturnEmailToBorrower($borrowerEmail, $borrowerName, $bookTitle, $returnDate, $requestId) {
    return sendEmail(
        $borrowerEmail,
        $borrowerName,
        'book_returned',
        [
            'subject'       => "Book Return Confirmed: \"$bookTitle\"",
            'borrower_name' => $borrowerName,
            'book_title'    => $bookTitle,
            'return_date'   => $returnDate,
            'request_id'    => $requestId,
            'base_url'      => BASE_URL
        ]
    );
}

/**
 * Send return confirmation email to owner
 */
function sendReturnEmailToOwner($ownerEmail, $ownerName, $bookTitle, $returnDate, $borrowerName, $requestId, $bookId = '') {
    return sendEmail(
        $ownerEmail,
        $ownerName,
        'book_returned_owner',
        [
            'subject'       => "\"$bookTitle\" Has Been Returned",
            'owner_name'    => $ownerName,
            'book_title'    => $bookTitle,
            'return_date'   => $returnDate,
            'borrower_name' => $borrowerName,
            'request_id'    => $requestId,
            'book_id'       => $bookId,
            'base_url'      => BASE_URL
        ]
    );
}

/**
 * Format date
 */
function formatDate($date) {
    if (empty($date)) return 'N/A';
    return date('M j, Y', strtotime($date));
}

// Load all requests
$allRequests = loadAllRequests();

// Separate requests
$receivedRequests = array_filter($allRequests, fn($r) => ($r['owner_id'] ?? '') === $currentUserId);
$sentRequests = array_filter($allRequests, fn($r) => ($r['borrower_id'] ?? '') === $currentUserId);

// Sort by date
usort($receivedRequests, fn($a, $b) => strtotime($b['request_date']) - strtotime($a['request_date']));
usort($sentRequests, fn($a, $b) => strtotime($b['request_date']) - strtotime($a['request_date']));

// Statistics
$pendingCount = count(array_filter($receivedRequests, fn($r) => $r['status'] === 'pending'));
$approvedCount = count(array_filter($receivedRequests, fn($r) => $r['status'] === 'approved'));
$rejectedCount = count(array_filter($receivedRequests, fn($r) => $r['status'] === 'rejected'));
$returnedCount = count(array_filter($receivedRequests, fn($r) => $r['status'] === 'returned'));

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = $_POST['request_id'] ?? '';
    
    $request = null;
    foreach ($allRequests as $r) {
        if ($r['id'] === $requestId) $request = $r;
    }
    
    if (!$request) {
        $error = 'Request not found';
    } elseif ($request['owner_id'] !== $currentUserId && $action !== 'return_book') {
        $error = 'You do not have permission to modify this request';
    } else {
        
        if ($action === 'approve') {
            $updated = updateRequestStatus($requestId, 'approved');
            
            if ($updated) {
                // Update book status to borrowed
                updateBookStatus($request['book_id'], 'borrowed', $request['borrower_id']);
                
                // Update user lists
                updateUserLists($request['borrower_id'], $request['book_id'], 'add_borrowed');
                updateUserLists($currentUserId, $request['book_id'], 'add_lent');
                
                // Get user data
                $borrower = loadUserData($request['borrower_id']);
                $owner = loadUserData($currentUserId);
                
                // Create in-app notification
                createNotification(
                    $request['borrower_id'],
                    'request_approved',
                    'Borrow Request Approved',
                    "Your request for '{$request['book_title']}' has been approved",
                    "/requests/?id={$requestId}"
                );
                
                // Send approval email to borrower
                if (!empty($borrower['personal_info']['email'])) {
                    $emailSent = sendApprovalEmail(
                        $borrower['personal_info']['email'],
                        $borrower['personal_info']['name'] ?? $request['borrower_name'],
                        $currentUserName,
                        $request['book_title'],
                        $request['book_author'],
                        $request['expected_return_date'],
                        $owner['personal_info']['room_number'] ?? 'N/A',
                        $owner['personal_info']['phone'] ?? 'N/A',
                        $requestId
                    );
                    
                    if ($emailSent) {
                        error_log("✅ Approval email sent to: " . $borrower['personal_info']['email']);
                    } else {
                        error_log("❌ Failed to send approval email to: " . $borrower['personal_info']['email']);
                    }
                } else {
                    error_log("⚠️ Borrower has no email: " . $request['borrower_id']);
                }
                
                $message = 'Request approved successfully';
            } else {
                $error = 'Failed to approve request';
            }
            
        } elseif ($action === 'reject') {
            $reason = trim($_POST['rejection_reason'] ?? 'No reason provided');
            $updated = updateRequestStatus($requestId, 'rejected', ['reason' => $reason]);
            
            if ($updated) {
                // Update book status to available
                updateBookStatus($request['book_id'], 'available');
                
                // Create in-app notification
                createNotification(
                    $request['borrower_id'],
                    'request_rejected',
                    'Borrow Request Rejected',
                    "Your request for '{$request['book_title']}' has been rejected" . ($reason ? ": {$reason}" : ''),
                    "/requests/?id={$requestId}"
                );
                
                // Send rejection email to borrower
                $borrower = loadUserData($request['borrower_id']);
                if (!empty($borrower['personal_info']['email'])) {
                    $emailSent = sendRejectionEmail(
                        $borrower['personal_info']['email'],
                        $borrower['personal_info']['name'] ?? $request['borrower_name'],
                        $request['book_title'],
                        $reason,
                        $requestId
                    );
                    
                    if ($emailSent) {
                        error_log("✅ Rejection email sent to: " . $borrower['personal_info']['email']);
                    } else {
                        error_log("❌ Failed to send rejection email to: " . $borrower['personal_info']['email']);
                    }
                } else {
                    error_log("⚠️ Borrower has no email: " . $request['borrower_id']);
                }
                
                $message = 'Request rejected successfully';
            } else {
                $error = 'Failed to reject request';
            }
            
        } elseif ($action === 'return_book') {
            $notes = trim($_POST['notes'] ?? '');
            $condition = trim($_POST['condition'] ?? 'same');
            
            $updated = updateRequestStatus($requestId, 'returned', [
                'notes' => $notes,
                'condition' => $condition,
                'returned_by' => $currentUserId
            ]);
            
            if ($updated) {
                // Update book status to available
                updateBookStatus($request['book_id'], 'available');
                
                // Update user lists (remove from borrowed/lent)
                updateUserLists($request['borrower_id'], $request['book_id'], 'remove_borrowed');
                updateUserLists($request['owner_id'], $request['book_id'], 'remove_lent');
                
                // Get user data
                $borrower = loadUserData($request['borrower_id']);
                $owner = loadUserData($request['owner_id']);
                $returnDate = date('Y-m-d');
                
                // Create in-app notifications
                createNotification(
                    $request['owner_id'],
                    'book_returned',
                    'Book Returned',
                    $currentUserName . ' has returned "' . $request['book_title'] . '"',
                    "/requests/?id={$requestId}"
                );
                
                createNotification(
                    $request['borrower_id'],
                    'return_confirmed',
                    'Return Confirmed',
                    'Your return of "' . $request['book_title'] . '" has been confirmed',
                    "/requests/?id={$requestId}"
                );
                
                // Send return confirmation email to borrower
                if (!empty($borrower['personal_info']['email'])) {
                    $emailSent = sendReturnEmailToBorrower(
                        $borrower['personal_info']['email'],
                        $borrower['personal_info']['name'] ?? $request['borrower_name'],
                        $request['book_title'],
                        $returnDate,
                        $requestId
                    );
                    
                    if ($emailSent) {
                        error_log("✅ Return email sent to borrower: " . $borrower['personal_info']['email']);
                    } else {
                        error_log("❌ Failed to send return email to borrower: " . $borrower['personal_info']['email']);
                    }
                }
                
                // Send return confirmation email to owner
                if (!empty($owner['personal_info']['email'])) {
                    $emailSent = sendReturnEmailToOwner(
                        $owner['personal_info']['email'],
                        $owner['personal_info']['name'] ?? $request['owner_name'],
                        $request['book_title'],
                        $returnDate,
                        $currentUserName,
                        $requestId,
                        $request['book_id']
                    );
                    
                    if ($emailSent) {
                        error_log("✅ Return email sent to owner: " . $owner['personal_info']['email']);
                    } else {
                        error_log("❌ Failed to send return email to owner: " . $owner['personal_info']['email']);
                    }
                }
                
                $message = 'Book returned successfully';
            } else {
                $error = 'Failed to return book';
            }
        }
        
        // Reload data
        $allRequests = loadAllRequests();
        $receivedRequests = array_filter($allRequests, fn($r) => ($r['owner_id'] ?? '') === $currentUserId);
        $sentRequests = array_filter($allRequests, fn($r) => ($r['borrower_id'] ?? '') === $currentUserId);
        usort($receivedRequests, fn($a, $b) => strtotime($b['request_date']) - strtotime($a['request_date']));
        usort($sentRequests, fn($a, $b) => strtotime($b['request_date']) - strtotime($a['request_date']));
        
        $pendingCount = count(array_filter($receivedRequests, fn($r) => $r['status'] === 'pending'));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>My Requests - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .requests-page { max-width: 1000px; margin: 0 auto; padding: 2rem 1rem; }
        .page-header { margin-bottom: 2rem; text-align: center; }
        .page-header h1 { font-size: 2rem; font-weight: 700; background: linear-gradient(135deg, #0f172a, #6366f1); -webkit-background-clip: text; -webkit-text-fill-color: transparent; margin-bottom: 0.5rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.25rem; border-radius: 1rem; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; transition: all 0.2s; }
        .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .stat-value { font-size: 1.75rem; font-weight: 700; margin-bottom: 0.25rem; }
        .stat-label { font-size: 0.75rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; border-bottom: 2px solid #e2e8f0; padding-bottom: 0.5rem; }
        .tab-btn { padding: 0.75rem 1.5rem; background: none; border: none; font-size: 0.95rem; font-weight: 600; color: #64748b; cursor: pointer; border-radius: 1rem; transition: all 0.2s; }
        .tab-btn:hover { color: #6366f1; background: #f1f5f9; }
        .tab-btn.active { color: #6366f1; background: rgba(99,102,241,0.1); }
        .tab-content { display: none; }
        .tab-content.active { display: block; animation: fadeIn 0.3s ease; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .request-card { background: white; border-radius: 1rem; padding: 1.5rem; margin-bottom: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.1); border: 1px solid #e2e8f0; transition: all 0.2s; }
        .request-card:hover { box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); transform: translateY(-2px); }
        .request-card.pending { border-left: 4px solid #f59e0b; }
        .request-card.approved { border-left: 4px solid #10b981; }
        .request-card.rejected { border-left: 4px solid #ef4444; }
        .request-card.returned { border-left: 4px solid #6366f1; }
        .request-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; flex-wrap: wrap; gap: 0.75rem; }
        .book-title { font-size: 1.1rem; font-weight: 600; }
        .book-title a { color: inherit; text-decoration: none; }
        .book-title a:hover { color: #6366f1; }
        .status-badge { padding: 0.25rem 0.75rem; border-radius: 2rem; font-size: 0.7rem; font-weight: 600; }
        .status-pending { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .status-approved { background: rgba(16,185,129,0.1); color: #10b981; }
        .status-rejected { background: rgba(239,68,68,0.1); color: #ef4444; }
        .status-returned { background: rgba(99,102,241,0.1); color: #6366f1; }
        .request-meta { display: flex; flex-wrap: wrap; gap: 1rem; margin-bottom: 1rem; padding: 0.75rem; background: #f8fafc; border-radius: 0.75rem; }
        .meta-item { display: flex; align-items: center; gap: 0.5rem; font-size: 0.8rem; color: #475569; }
        .meta-item i { width: 18px; color: #6366f1; }
        .request-message { background: #fef3c7; border-left: 4px solid #f59e0b; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; font-style: italic; font-size: 0.85rem; }
        .request-actions { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-top: 1rem; padding-top: 1rem; border-top: 1px solid #e2e8f0; }
        .btn { padding: 0.5rem 1.25rem; border-radius: 2rem; font-weight: 500; font-size: 0.85rem; border: none; cursor: pointer; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; transform: translateY(-1px); }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; transform: translateY(-1px); }
        .btn-outline { background: transparent; border: 1px solid #e2e8f0; color: #334155; }
        .btn-outline:hover { border-color: #6366f1; color: #6366f1; }
        .empty-state { text-align: center; padding: 3rem 2rem; background: white; border-radius: 1rem; border: 1px solid #e2e8f0; }
        .empty-state i { font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem; }
        .modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 1000; }
        .modal.active { display: flex; }
        .modal-content { background: white; border-radius: 1rem; max-width: 450px; width: 90%; max-height: 90vh; overflow-y: auto; }
        .modal-header { padding: 1.25rem; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; }
        .modal-body { padding: 1.25rem; }
        .modal-footer { padding: 1.25rem; border-top: 1px solid #e2e8f0; display: flex; gap: 0.75rem; }
        @media (max-width: 768px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } .tabs { overflow-x: auto; white-space: nowrap; } .tab-btn { padding: 0.5rem 1rem; } .request-header { flex-direction: column; } }

        /* Dark Mode Overrides */
        [data-theme="dark"] .page-header h1 { background: linear-gradient(135deg, #f8fafc, #818cf8); -webkit-background-clip: text; background-clip: text; -webkit-text-fill-color: transparent; }
        [data-theme="dark"] .page-header p { color: #cbd5e1; }
        [data-theme="dark"] .stat-card { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .stat-label { color: #94a3b8; }
        [data-theme="dark"] .tabs { border-color: #334155; }
        [data-theme="dark"] .tab-btn { color: #94a3b8; }
        [data-theme="dark"] .tab-btn:hover { background: #1e293b; color: #818cf8; }
        [data-theme="dark"] .tab-btn.active { background: rgba(99, 102, 241, 0.2); color: #818cf8; }
        [data-theme="dark"] .request-card { background: #1e293b; border-color: #334155; color: #cbd5e1; }
        [data-theme="dark"] .book-title { color: #f8fafc; }
        [data-theme="dark"] .book-title a { color: #f8fafc; }
        [data-theme="dark"] .request-meta { background: #0f172a; }
        [data-theme="dark"] .meta-item { color: #cbd5e1; }
        [data-theme="dark"] .request-message { background: rgba(245, 158, 11, 0.1); border-left-color: #f59e0b; color: #cbd5e1; }
        [data-theme="dark"] .request-actions { border-color: #334155; }
        [data-theme="dark"] .btn-outline { border-color: #475569; color: #cbd5e1; }
        [data-theme="dark"] .btn-outline:hover { border-color: #818cf8; color: #f8fafc; }
        [data-theme="dark"] .empty-state { background: #1e293b; border-color: #334155; color: #cbd5e1; }
        [data-theme="dark"] .modal-content { background: #1e293b; color: #cbd5e1; }
        [data-theme="dark"] .modal-header,
        [data-theme="dark"] .modal-footer { border-color: #334155; }
        [data-theme="dark"] .form-control { background: #0f172a; border-color: #334155; color: #f8fafc; }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    
    <main>
        <div class="requests-page">
            <div class="page-header">
                <h1><i class="fas fa-exchange-alt" style="color: #6366f1;"></i> My Requests</h1>
                <p>Manage your book borrowing requests</p>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success" style="background: rgba(16,185,129,0.1); color: #10b981; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger" style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="stats-grid">
                <div class="stat-card"><div class="stat-value" style="color: #f59e0b;"><?php echo $pendingCount; ?></div><div class="stat-label">Pending</div></div>
                <div class="stat-card"><div class="stat-value" style="color: #10b981;"><?php echo $approvedCount; ?></div><div class="stat-label">Approved</div></div>
                <div class="stat-card"><div class="stat-value" style="color: #ef4444;"><?php echo $rejectedCount; ?></div><div class="stat-label">Rejected</div></div>
                <div class="stat-card"><div class="stat-value" style="color: #6366f1;"><?php echo $returnedCount; ?></div><div class="stat-label">Returned</div></div>
            </div>
            
            <div class="tabs">
                <button class="tab-btn active" onclick="switchTab('received')"><i class="fas fa-inbox"></i> Received (<?php echo count($receivedRequests); ?>)</button>
                <button class="tab-btn" onclick="switchTab('sent')"><i class="fas fa-paper-plane"></i> Sent (<?php echo count($sentRequests); ?>)</button>
            </div>
            
            <!-- Received Requests Tab -->
            <div id="received-tab" class="tab-content active">
                <?php if (empty($receivedRequests)): ?>
                    <div class="empty-state"><i class="fas fa-inbox"></i><h3>No Received Requests</h3><p>When someone requests to borrow your books, they'll appear here.</p><a href="/books/" class="btn btn-outline" style="margin-top: 1rem;">Browse Books</a></div>
                <?php else: foreach ($receivedRequests as $request): $borrower = loadUserData($request['borrower_id']); $coverImage = !empty($request['book_cover']) ? '/uploads/book_cover/thumb_' . $request['book_cover'] : '/assets/images/default-book-cover.jpg'; ?>
                    <div class="request-card <?php echo $request['status']; ?>">
                        <div class="request-header"><div><div class="book-title"><a href="/book/?id=<?php echo $request['book_id']; ?>"><?php echo htmlspecialchars($request['book_title']); ?></a></div><div style="font-size:0.8rem;color:#64748b;">by <?php echo htmlspecialchars($request['book_author'] ?? 'Unknown'); ?></div></div><div><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></div></div>
                        <div class="request-meta"><div class="meta-item"><i class="fas fa-user"></i><span><strong><?php echo htmlspecialchars($request['borrower_name']); ?></strong></span></div><div class="meta-item"><i class="far fa-calendar-alt"></i><span>Requested: <?php echo formatDate($request['request_date']); ?></span></div><div class="meta-item"><i class="fas fa-clock"></i><span>Duration: <?php echo $request['duration_days'] ?? 14; ?> days</span></div><?php if (!empty($request['expected_return_date'])): ?><div class="meta-item"><i class="far fa-calendar-check"></i><span>Due: <?php echo formatDate($request['expected_return_date']); ?></span></div><?php endif; ?><?php if (!empty($borrower['personal_info']['phone'])): ?><div class="meta-item"><i class="fas fa-phone"></i><span><?php echo htmlspecialchars($borrower['personal_info']['phone']); ?></span></div><?php endif; ?></div>
                        <?php if (!empty($request['message'])): ?><div class="request-message"><i class="fas fa-quote-left" style="margin-right:0.5rem;color:#f59e0b;"></i> <?php echo nl2br(htmlspecialchars($request['message'])); ?></div><?php endif; ?>
                        <div class="request-actions"><?php if ($request['status'] === 'pending'): ?><button class="btn btn-success" onclick="approveRequest('<?php echo $request['id']; ?>')"><i class="fas fa-check"></i> Approve</button><button class="btn btn-danger" onclick="showRejectModal('<?php echo $request['id']; ?>')"><i class="fas fa-times"></i> Reject</button><?php elseif ($request['status'] === 'approved'): ?><a href="/book/?id=<?php echo $request['book_id']; ?>" class="btn btn-outline"><i class="fas fa-book"></i> View Book</a><?php if (!empty($borrower['personal_info']['phone'])): ?><a href="https://wa.me/88<?php echo preg_replace('/[^0-9]/', '', $borrower['personal_info']['phone']); ?>" target="_blank" class="btn btn-outline" style="border-color:#25D366;color:#25D366;"><i class="fab fa-whatsapp"></i> Contact</a><?php endif; ?><?php else: ?><a href="/book/?id=<?php echo $request['book_id']; ?>" class="btn btn-outline"><i class="fas fa-book"></i> View Book</a><?php endif; ?></div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
            
            <!-- Sent Requests Tab -->
            <div id="sent-tab" class="tab-content">
                <?php if (empty($sentRequests)): ?>
                    <div class="empty-state"><i class="fas fa-paper-plane"></i><h3>No Sent Requests</h3><p>When you request books from others, they'll appear here.</p><a href="/books/" class="btn btn-outline" style="margin-top: 1rem;">Browse Books</a></div>
                <?php else: foreach ($sentRequests as $request): $owner = loadUserData($request['owner_id']); ?>
                    <div class="request-card <?php echo $request['status']; ?>">
                        <div class="request-header"><div><div class="book-title"><a href="/book/?id=<?php echo $request['book_id']; ?>"><?php echo htmlspecialchars($request['book_title']); ?></a></div><div style="font-size:0.8rem;color:#64748b;">by <?php echo htmlspecialchars($request['book_author'] ?? 'Unknown'); ?></div></div><div><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></div></div>
                        <div class="request-meta"><div class="meta-item"><i class="fas fa-user"></i><span><strong><?php echo htmlspecialchars($request['owner_name']); ?></strong></span></div><div class="meta-item"><i class="far fa-calendar-alt"></i><span>Requested: <?php echo formatDate($request['request_date']); ?></span></div><div class="meta-item"><i class="fas fa-clock"></i><span>Duration: <?php echo $request['duration_days'] ?? 14; ?> days</span></div><?php if (!empty($request['expected_return_date'])): ?><div class="meta-item"><i class="far fa-calendar-check"></i><span>Due: <?php echo formatDate($request['expected_return_date']); ?></span></div><?php endif; ?></div>
                        <?php if ($request['status'] === 'rejected' && !empty($request['rejection_reason'])): ?><div class="request-message" style="border-left-color:#ef4444;"><i class="fas fa-times-circle" style="color:#ef4444;margin-right:0.5rem;"></i><strong>Reason:</strong> <?php echo htmlspecialchars($request['rejection_reason']); ?></div><?php endif; ?>
                        <div class="request-actions">
                            <?php if ($request['status'] === 'approved'): ?>
                                <form method="POST" id="returnForm_<?php echo $request['id']; ?>" style="display: inline;">
                                    <input type="hidden" name="action" value="return_book">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    <input type="hidden" name="condition" value="same">
                                    <button type="submit" class="btn btn-success" onclick="return confirm('Confirm return of this book?')">
                                        <i class="fas fa-undo-alt"></i> Return Book
                                    </button>
                                </form>
                                <?php if (!empty($owner['personal_info']['phone'])): ?>
                                    <a href="https://wa.me/88<?php echo preg_replace('/[^0-9]/', '', $owner['personal_info']['phone']); ?>" target="_blank" class="btn btn-outline" style="border-color:#25D366;color:#25D366;">
                                        <i class="fab fa-whatsapp"></i> Contact Owner
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="/book/?id=<?php echo $request['book_id']; ?>" class="btn btn-outline"><i class="fas fa-book"></i> View Book</a>
                        </div>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </main>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-times-circle" style="color:#f59e0b;"></i> Reject Request</h3><button onclick="closeModal('rejectModal')">&times;</button></div>
            <form method="POST">
                <div class="modal-body"><input type="hidden" name="action" value="reject"><input type="hidden" name="request_id" id="rejectRequestId"><div class="form-group"><label style="display:block;margin-bottom:0.5rem;font-weight:500;">Reason for Rejection</label><textarea name="rejection_reason" class="form-control" rows="4" required placeholder="Please provide a reason..."></textarea></div></div>
                <div class="modal-footer"><button type="submit" class="btn btn-danger">Reject Request</button><button type="button" class="btn btn-outline" onclick="closeModal('rejectModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            document.getElementById(tab + '-tab').classList.add('active');
        }
        
        function approveRequest(requestId) {
            if (confirm('Approve this borrow request?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="approve"><input type="hidden" name="request_id" value="${requestId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function showRejectModal(requestId) {
            document.getElementById('rejectRequestId').value = requestId;
            document.getElementById('rejectModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) e.target.classList.remove('active');
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') document.querySelectorAll('.modal.active').forEach(m => m.classList.remove('active'));
        });
    </script>
    
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>