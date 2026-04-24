<?php
/**
 * OpenShelf Admin Request Management
 * Modern UI with advanced filtering and bulk actions
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__, 2) . '/data/');
define('BOOKS_PATH', dirname(__DIR__, 2) . '/books/');
define('USERS_PATH', dirname(__DIR__, 2) . '/users/');

// Include database connection
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/search_helper.php';
define('BASE_URL', 'https://openshelf.free.nf');

// Load mailer
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/lib/Mailer.php';
$mailer = new Mailer();

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/**
 * Load paginated borrow requests from DB with filters
 */
function loadRequests($status = 'all', $search = '', $fromDate = '', $toDate = '', $offset = 0, $perPage = 15) {
    $db = getDB();
    $where = ["1=1"];
    $params = [];

    if ($status !== 'all') {
        if ($status === 'overdue') {
            $where[] = "status IN ('approved', 'borrowed') AND expected_return_date < NOW()";
        } else {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
    }

    applySearchFilter($search, ['book_title', 'borrower_name', 'owner_name'], $where, $params, '');

    if (!empty($fromDate)) {
        $where[] = "request_date >= :fromDate";
        $params[':fromDate'] = $fromDate . ' 00:00:00';
    }

    if (!empty($toDate)) {
        $where[] = "request_date <= :toDate";
        $params[':toDate'] = $toDate . ' 23:59:59';
    }

    $sql = "SELECT * FROM borrow_requests WHERE " . implode(' AND ', $where) . " ORDER BY request_date DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get total count of filtered requests
 */
function getRequestsCount($status = 'all', $search = '', $fromDate = '', $toDate = '') {
    $db = getDB();
    $where = ["1=1"];
    $params = [];

    if ($status !== 'all') {
        if ($status === 'overdue') {
            $where[] = "status IN ('approved', 'borrowed') AND expected_return_date < NOW()";
        } else {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }
    }

    applySearchFilter($search, ['book_title', 'borrower_name', 'owner_name'], $where, $params, '');

    if (!empty($fromDate)) {
        $where[] = "request_date >= :fromDate";
        $params[':fromDate'] = $fromDate . ' 00:00:00';
    }

    if (!empty($toDate)) {
        $where[] = "request_date <= :toDate";
        $params[':toDate'] = $toDate . ' 23:59:59';
    }

    $sql = "SELECT COUNT(*) FROM borrow_requests WHERE " . implode(' AND ', $where);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * Load book data from DB
 */
function loadBookData($bookId) {
    if (empty($bookId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    return $stmt->fetch() ?: null;
}

/**
 * Update request status in DB
 */
function updateRequestStatus($requestId, $status, $additionalData = []) {
    global $adminId, $adminName;
    $db = getDB();
    
    // Get current request data
    $stmt = $db->prepare("SELECT * FROM borrow_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $requestData = $stmt->fetch();
    
    if (!$requestData) return false;
    
    $sql = "UPDATE borrow_requests SET 
                status = :status, 
                updated_at = :updated_at";
    
    $params = [
        ':status' => $status,
        ':updated_at' => date('Y-m-d H:i:s'),
        ':id' => $requestId
    ];
    
    if ($status === 'approved') {
        $sql .= ", approved_at = :approved_at, approved_by = :approved_by";
        $params[':approved_at'] = date('Y-m-d H:i:s');
        $params[':approved_by'] = $adminId;
    } elseif ($status === 'rejected') {
        $sql .= ", rejected_at = :rejected_at, rejected_by = :rejected_by, rejection_reason = :rejection_reason";
        $params[':rejected_at'] = date('Y-m-d H:i:s');
        $params[':rejected_by'] = $adminId;
        $params[':rejection_reason'] = $additionalData['reason'] ?? '';
    } elseif ($status === 'closed') {
        $sql .= ", closed_at = :closed_at, closed_by = :closed_by, closed_notes = :closed_notes";
        $params[':closed_at'] = date('Y-m-d H:i:s');
        $params[':closed_by'] = $adminId;
        $params[':closed_notes'] = $additionalData['notes'] ?? '';
    }
    
    // Handle history (stored as JSON)
    $history = json_decode($requestData['history'] ?? '[]', true);
    $history[] = [
        'action' => $status . '_by_admin',
        'timestamp' => date('Y-m-d H:i:s'),
        'admin_id' => $adminId,
        'admin_name' => $adminName,
        'data' => $additionalData
    ];
    
    $sql .= ", history = :history WHERE id = :id";
    $params[':history'] = json_encode($history);
    
    $stmt = $db->prepare($sql);
    $updated = $stmt->execute($params);
    
    if ($updated) {
        // Update book status if approved or rejected
        if ($status === 'approved') {
            updateBookStatus($requestData['book_id'], 'borrowed', $requestData['borrower_id']);
        } elseif ($status === 'rejected' || $status === 'closed') {
            $book = loadBookData($requestData['book_id']);
            if ($book && ($book['status'] ?? '') === 'reserved') {
                updateBookStatus($requestData['book_id'], 'available');
            }
        }
        
        // Create notification for user
        createNotification($requestData, $status);
        
        // Send email notification
        global $mailer;
        if ($mailer && !empty($requestData['borrower_email'])) {
            if ($status === 'approved') {
                $mailer->sendTemplate(
                    $requestData['borrower_email'],
                    $requestData['borrower_name'],
                    'request_approved',
                    [
                        'subject'           => 'Your Borrow Request Has Been Approved!',
                        'user_name'         => $requestData['borrower_name'],
                        'book_title'        => $requestData['book_title'],
                        'owner_name'        => $requestData['owner_name'],
                        'expected_return'   => $requestData['expected_return_date'],
                        'base_url'          => BASE_URL
                    ]
                );
            } elseif ($status === 'rejected') {
                $mailer->sendTemplate(
                    $requestData['borrower_email'],
                    $requestData['borrower_name'],
                    'request_rejected',
                    [
                        'subject'           => 'Update on Your Borrow Request',
                        'user_name'         => $requestData['borrower_name'],
                        'book_title'        => $requestData['book_title'],
                        'rejection_reason'  => $additionalData['reason'] ?? 'No reason provided',
                        'base_url'          => BASE_URL
                    ]
                );
            }
        }
        
        return true;
    }
    return false;
}

/**
 * Update book status in DB
 */
function updateBookStatus($bookId, $status, $borrowerId = null) {
    if (empty($bookId)) return;
    $db = getDB();
    
    $sql = "UPDATE books SET 
                status = :status, 
                updated_at = :updated_at";
    
    $params = [
        ':status' => $status,
        ':updated_at' => date('Y-m-d H:i:s'),
        ':id' => $bookId
    ];
    
    if ($status === 'borrowed' && $borrowerId) {
        $sql .= ", current_borrower_id = :borrower_id, borrowed_since = :borrowed_since";
        $params[':borrower_id'] = $borrowerId;
        $params[':borrowed_since'] = date('Y-m-d H:i:s');
    } elseif ($status === 'available') {
        $sql .= ", current_borrower_id = NULL, borrowed_since = NULL";
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
}

/**
 * Extend return date in DB
 */
function extendReturnDate($requestId, $additionalDays, $reason = '') {
    global $adminId;
    $db = getDB();
    
    // Get current request data
    $stmt = $db->prepare("SELECT * FROM borrow_requests WHERE id = ?");
    $stmt->execute([$requestId]);
    $request = $stmt->fetch();
    
    if (!$request) return false;
    if (!in_array($request['status'], ['approved', 'borrowed'])) return false;
    
    $oldDate = $request['expected_return_date'];
    $newDate = date('Y-m-d H:i:s', strtotime($oldDate . " +{$additionalDays} days"));
    
    $history = json_decode($request['history'] ?? '[]', true);
    $history[] = [
        'action' => 'extended_by_admin',
        'timestamp' => date('Y-m-d H:i:s'),
        'admin_id' => $adminId,
        'additional_days' => $additionalDays,
        'reason' => $reason
    ];
    
    $stmt = $db->prepare("UPDATE borrow_requests SET 
                            expected_return_date = :new_date, 
                            extended_at = :extended_at, 
                            extended_by = :extended_by, 
                            extension_days = extension_days + :additional_days, 
                            extension_reason = :reason, 
                            updated_at = :updated_at, 
                            history = :history 
                          WHERE id = :id");
    
    $updated = $stmt->execute([
        ':new_date' => $newDate,
        ':extended_at' => date('Y-m-d H:i:s'),
        ':extended_by' => $adminId,
        ':additional_days' => $additionalDays,
        ':reason' => $reason,
        ':updated_at' => date('Y-m-d H:i:s'),
        ':history' => json_encode($history),
        ':id' => $requestId
    ]);
    
    if ($updated) {
        $request['expected_return_date'] = $newDate;
        createExtensionNotification($request, $additionalDays);
        
        // Send email notification
        global $mailer;
        if ($mailer && !empty($request['borrower_email'])) {
            try {
                $mailer->sendTemplate(
                    $request['borrower_email'],
                    $request['borrower_name'],
                    'overdue', // Or a custom extension template if available
                    [
                        'subject'           => 'Return Date Extended for ' . $request['book_title'],
                        'user_name'         => $request['borrower_name'],
                        'book_title'        => $request['book_title'],
                        'new_due_date'      => $newDate,
                        'additional_days'   => $additionalDays,
                        'reason'            => $reason,
                        'base_url'          => BASE_URL
                    ]
                );
            } catch (Exception $e) { /* Logged */ }
        }
        return true;
    }
    return false;
}

/**
 * Create notification for user
 */
function createNotification($request, $status) {
    $userId = $request['borrower_id'];
    $userFile = dirname(dirname(__DIR__)) . '/users/' . $userId . '.json';
    if (!file_exists($userFile)) return;
    
    $userData = json_decode(file_get_contents($userFile), true);
    $notifications = $userData['notifications'] ?? [];
    
    if ($status === 'approved') {
        $notification = [
            'id' => 'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
            'user_id' => $userId,
            'type' => 'request_approved',
            'title' => 'Borrow Request Approved',
            'message' => "Your request for '{$request['book_title']}' has been approved by admin",
            'link' => "/requests/?id={$request['id']}",
            'is_read' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $notifications[] = $notification;
    } elseif ($status === 'rejected') {
        $notification = [
            'id' => 'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
            'user_id' => $userId,
            'type' => 'request_rejected',
            'title' => 'Borrow Request Rejected',
            'message' => "Your request for '{$request['book_title']}' has been rejected by admin",
            'link' => "/requests/?id={$request['id']}",
            'is_read' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $notifications[] = $notification;
    } elseif ($status === 'closed') {
        $notification = [
            'id' => 'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
            'user_id' => $userId,
            'type' => 'request_closed',
            'title' => 'Borrow Request Closed',
            'message' => "Your borrow request for '{$request['book_title']}' has been closed by admin",
            'link' => "/requests/?id={$request['id']}",
            'is_read' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $notifications[] = $notification;
    }
    
    // Sort and limit
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });
    $notifications = array_slice($notifications, 0, 25);
    
    $userData['notifications'] = $notifications;
    file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Create extension notification
 */
function createExtensionNotification($request, $additionalDays) {
    $userId = $request['borrower_id'];
    $userFile = dirname(dirname(__DIR__)) . '/users/' . $userId . '.json';
    if (!file_exists($userFile)) return;
    
    $userData = json_decode(file_get_contents($userFile), true);
    $notifications = $userData['notifications'] ?? [];
    
    $notification = [
        'id' => 'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
        'user_id' => $userId,
        'type' => 'return_date_extended',
        'title' => 'Return Date Extended',
        'message' => "Your return date for '{$request['book_title']}' has been extended by {$additionalDays} days",
        'link' => "/requests/?id={$request['id']}",
        'is_read' => false,
        'created_at' => date('Y-m-d H:i:s')
    ];
    $notifications[] = $notification;
    
    // Sort and limit
    usort($notifications, function($a, $b) {
        return strtotime($b['created_at']) <=> strtotime($a['created_at']);
    });
    $notifications = array_slice($notifications, 0, 25);
    
    $userData['notifications'] = $notifications;
    file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
}

/**
 * Calculate overdue status
 */
function calculateOverdueStatus($requests) {
    $now = time();
    foreach ($requests as &$request) {
        if (in_array($request['status'], ['approved', 'borrowed']) && !empty($request['expected_return_date'])) {
            $dueDate = strtotime($request['expected_return_date']);
            if ($dueDate < $now) {
                $request['overdue'] = true;
                $request['overdue_days'] = floor(($now - $dueDate) / 86400);
            } else {
                $request['overdue'] = false;
                $request['days_until_due'] = floor(($dueDate - $now) / 86400);
            }
        }
    }
    return $requests;
}

// Filters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$fromDate = $_GET['from'] ?? '';
$toDate = $_GET['to'] ?? '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Load data from DB efficiently
$paginatedRequests = loadRequests($status, $search, $fromDate, $toDate, $offset, $perPage);
$paginatedRequests = calculateOverdueStatus($paginatedRequests);
$total = getRequestsCount($status, $search, $fromDate, $toDate);
$totalPages = ceil($total / $perPage);

// Stats for the cards
$db = getDB();
$stats = [
    'total' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests")->fetchColumn(),
    'pending' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'pending'")->fetchColumn(),
    'approved' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'approved'")->fetchColumn(),
    'rejected' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'rejected'")->fetchColumn(),
    'returned' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'returned'")->fetchColumn(),
    'overdue' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status IN ('approved', 'borrowed') AND expected_return_date < NOW()")->fetchColumn()
];

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $requestId = $_POST['request_id'] ?? '';
    
    if ($action === 'approve') {
        if (updateRequestStatus($requestId, 'approved')) {
            $message = 'Request approved successfully';
        } else {
            $error = 'Failed to approve request';
        }
    } elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? 'No reason provided');
        if (updateRequestStatus($requestId, 'rejected', ['reason' => $reason])) {
            $message = 'Request rejected successfully';
        } else {
            $error = 'Failed to reject request';
        }
    } elseif ($action === 'close') {
        $notes = trim($_POST['close_notes'] ?? '');
        if (updateRequestStatus($requestId, 'closed', ['notes' => $notes])) {
            $message = 'Request closed successfully';
        } else {
            $error = 'Failed to close request';
        }
    } elseif ($action === 'extend') {
        $days = intval($_POST['extend_days'] ?? 0);
        $reason = trim($_POST['extend_reason'] ?? '');
        if ($days > 0 && extendReturnDate($requestId, $days, $reason)) {
            $message = "Return date extended by {$days} days";
        } else {
            $error = 'Failed to extend return date';
        }
    } elseif ($action === 'bulk_approve') {
        $requestIds = $_POST['request_ids'] ?? [];
        $count = 0;
        foreach ($requestIds as $rid) {
            if (updateRequestStatus($rid, 'approved')) $count++;
        }
        $message = "Approved {$count} requests successfully";
    } elseif ($action === 'bulk_reject') {
        $requestIds = $_POST['request_ids'] ?? [];
        $reason = trim($_POST['bulk_rejection_reason'] ?? '');
        $count = 0;
        foreach ($requestIds as $rid) {
            if (updateRequestStatus($rid, 'rejected', ['reason' => $reason])) $count++;
        }
        $message = "Rejected {$count} requests successfully";
    }
    
    // Refresh data after action
    $paginatedRequests = loadRequests($status, $search, $fromDate, $toDate, $offset, $perPage);
    $paginatedRequests = calculateOverdueStatus($paginatedRequests);
    $total = getRequestsCount($status, $search, $fromDate, $toDate);
    $totalPages = ceil($total / $perPage);
    
    // Refresh stats
    $stats = [
        'total' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests")->fetchColumn(),
        'pending' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'pending'")->fetchColumn(),
        'approved' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'approved'")->fetchColumn(),
        'rejected' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'rejected'")->fetchColumn(),
        'returned' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status = 'returned'")->fetchColumn(),
        'overdue' => (int)$db->query("SELECT COUNT(*) FROM borrow_requests WHERE status IN ('approved', 'borrowed') AND expected_return_date < NOW()")->fetchColumn()
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Management - OpenShelf Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Request Management Styles */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .header-title h1 {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -1px;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .header-title p {
            color: var(--text-muted);
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.25rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid #f1f5f9;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0,0,0,0.05);
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            letter-spacing: -1px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 20px;
            border: 1px solid #f1f5f9;
        }

        .filter-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .filter-select, .date-input {
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-select:hover, .date-input:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            width: 320px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
        }

        .table-container {
            background: white;
            border-radius: 24px;
            overflow-x: auto;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            margin-bottom: 2rem;
            -webkit-overflow-scrolling: touch;
        }

        .requests-table {
            width: 100%;
            border-collapse: collapse;
        }

        .requests-table th {
            text-align: left;
            padding: 1.25rem 1.5rem;
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #f1f5f9;
        }

        .requests-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: var(--text-main);
            font-size: 0.9rem;
        }

        .requests-table tr:hover td {
            background: #fcfdfe;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.85rem;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-approved { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-rejected { background: rgba(239, 68, 68, 0.1); color: #ef4444; }
        .status-returned { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        .status-closed { background: rgba(100, 116, 139, 0.1); color: #475569; }

        .overdue-badge {
            background: #dc2626;
            color: white;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            margin-left: 0.5rem;
            box-shadow: 0 4px 8px rgba(220, 38, 38, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .action-btn {
            padding: 0.45rem 0.85rem;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            font-size: 0.75rem;
            font-weight: 700;
            color: white;
        }

        .action-btn.approve { background: #10b981; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
        .action-btn.reject { background: #f59e0b; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2); }
        .action-btn.close { background: #ef4444; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2); }
        .action-btn.extend { background: var(--primary); box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2); }
        .action-btn.view { background: #64748b; box-shadow: 0 4px 10px rgba(100, 116, 139, 0.2); }

        .action-btn:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }

        .bulk-bar {
            background: #1e293b;
            color: white;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
        }

        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .bulk-bar.hidden { display: none; }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 3rem;
        }

        .page-link {
            padding: 0.75rem 1.1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: #475569;
            font-size: 0.9rem;
            font-weight: 600;
            background: white;
            transition: var(--transition);
        }

        .page-link.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.2);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 1.5rem;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: white;
            border-radius: 24px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 { font-weight: 800; letter-spacing: -0.5px; }

        .modal-body { padding: 2rem; }

        .modal-footer {
            padding: 1.5rem 2rem;
            background: #f8fafc;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        @media (max-width: 768px) {
            .page-header { margin-bottom: 1.5rem; }
            .filters-bar { padding: 1rem; }
            .search-box input { width: 100%; }
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/includes/admin-header.php'; ?>

    <div class="admin-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-title">
                <h1>Request Management</h1>
                <p>Monitor and process borrow requests across the platform</p>
            </div>
            <div>
                <a href="/admin/requests/export.php" class="btn-admin btn-admin-primary">
                    <i class="fas fa-download"></i> Export Requests
                </a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success" style="background: rgba(16,185,129,0.1); color: #10b981; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value" style="color: var(--primary);"><?php echo $stats['total']; ?></div>
                <div class="stat-label">Total Requests</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #f59e0b;"><?php echo $stats['pending']; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #10b981;"><?php echo $stats['approved']; ?></div>
                <div class="stat-label">Approved</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ef4444;"><?php echo $stats['rejected']; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: var(--primary);"><?php echo $stats['returned']; ?></div>
                <div class="stat-label">Returned</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #dc2626;"><?php echo $stats['overdue']; ?></div>
                <div class="stat-label">Overdue</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-bar">
            <div class="filter-group">
                <select class="filter-select" id="statusFilter" onchange="applyFilter()">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                    <option value="approved" <?php echo $status === 'approved' ? 'selected' : ''; ?>>Approved</option>
                    <option value="rejected" <?php echo $status === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                    <option value="returned" <?php echo $status === 'returned' ? 'selected' : ''; ?>>Returned</option>
                    <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                </select>
                <input type="date" class="date-input" id="fromDate" value="<?php echo $fromDate; ?>" placeholder="From" onchange="applyFilter()">
                <input type="date" class="date-input" id="toDate" value="<?php echo $toDate; ?>" placeholder="To" onchange="applyFilter()">
            </div>
            <form method="GET" class="search-box" id="searchForm">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by book, borrower, owner..." value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="status" id="hiddenStatus" value="<?php echo $status; ?>">
                <input type="hidden" name="from" id="hiddenFrom" value="<?php echo $fromDate; ?>">
                <input type="hidden" name="to" id="hiddenTo" value="<?php echo $toDate; ?>">
            </form>
        </div>
        
        <!-- Bulk Actions Bar -->
        <div id="bulkBar" class="bulk-bar hidden">
            <span id="selectedCount">0 selected</span>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn-admin btn-admin-primary" onclick="bulkApprove()">Approve Selected</button>
                <button class="btn-admin" style="background: #f59e0b; color: white;" onclick="showBulkRejectModal()">Reject Selected</button>
            </div>
        </div>
        
        <!-- Requests Table -->
        <div class="table-container">
            <table class="requests-table">
                <thead>
                    <tr><th width="40"><input type="checkbox" id="selectAll" onclick="toggleAll()"></th><th>Book</th><th>Borrower</th><th>Owner</th><th>Request Date</th><th>Due Date</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                    <?php if (empty($paginatedRequests)): ?>
                        <tr><td colspan="8" style="text-align: center; padding: 3rem;"><i class="fas fa-exchange-alt" style="font-size: 3rem; color: #cbd5e1;"></i><p style="margin-top: 1rem;">No requests found</p></td></tr>
                    <?php else: ?>
                        <?php foreach ($paginatedRequests as $request): ?>
                            <tr>
                                <td><input type="checkbox" class="request-checkbox" value="<?php echo $request['id']; ?>" onchange="updateSelectedCount()"></td>
                                <td><div style="font-weight: 500;"><?php echo htmlspecialchars($request['book_title']); ?></div><div style="font-size: 0.7rem; color: #64748b;">by <?php echo htmlspecialchars($request['book_author'] ?? 'Unknown'); ?></div></td>
                                <td><div><?php echo htmlspecialchars($request['borrower_name']); ?></div><div style="font-size: 0.7rem; color: #64748b;">ID: <?php echo htmlspecialchars($request['borrower_id']); ?></div></td>
                                <td><div><?php echo htmlspecialchars($request['owner_name']); ?></div><div style="font-size: 0.7rem; color: #64748b;">ID: <?php echo htmlspecialchars($request['owner_id']); ?></div></td>
                                <td style="font-size: 0.85rem;"><?php echo date('M j, Y', strtotime($request['request_date'])); ?><div style="font-size: 0.7rem; color: #64748b;"><?php echo $request['duration_days'] ?? 14; ?> days</div></td>
                                <td style="font-size: 0.85rem;">
                                    <?php if (!empty($request['expected_return_date'])): ?>
                                        <span><?php echo date('M j, Y', strtotime($request['expected_return_date'])); ?></span>
                                        <?php if (!empty($request['overdue_days'])): ?><div class="overdue-badge"><?php echo $request['overdue_days']; ?> days overdue</div>
                                        <?php elseif (!empty($request['days_until_due'])): ?><div style="font-size: 0.65rem; color: #10b981;"><?php echo $request['days_until_due']; ?> days left</div><?php endif; ?>
                                    <?php else: ?><span class="text-muted">Not set</span><?php endif; ?>
                                </td>
                                <td><span class="status-badge status-<?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span><?php if (!empty($request['overdue'])): ?><span class="overdue-badge">OVERDUE</span><?php endif; ?></td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($request['status'] === 'pending'): ?>
                                            <button class="action-btn approve" onclick="approveRequest('<?php echo $request['id']; ?>')" title="Approve"><i class="fas fa-check"></i> Approve</button>
                                            <button class="action-btn reject" onclick="showRejectModal('<?php echo $request['id']; ?>')" title="Reject"><i class="fas fa-times"></i> Reject</button>
                                        <?php endif; ?>
                                        <?php if (in_array($request['status'], ['approved', 'borrowed'])): ?>
                                            <button class="action-btn extend" onclick="showExtendModal('<?php echo $request['id']; ?>')" title="Extend"><i class="fas fa-calendar-plus"></i> Extend</button>
                                            <button class="action-btn close" onclick="showCloseModal('<?php echo $request['id']; ?>')" title="Force Close"><i class="fas fa-lock"></i> Close</button>
                                        <?php endif; ?>
                                        <button class="action-btn view" onclick="viewRequest('<?php echo $request['id']; ?>')" title="View"><i class="fas fa-eye"></i> View</button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <a href="?page=<?php echo max(1, $page - 1); ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&from=<?php echo $fromDate; ?>&to=<?php echo $toDate; ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>"><i class="fas fa-chevron-left"></i></a>
                <?php for ($i = 1; $i <= min(5, $totalPages); $i++): ?>
                    <?php if ($i >= $page - 2 && $i <= $page + 2): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&from=<?php echo $fromDate; ?>&to=<?php echo $toDate; ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>"><?php echo $i; ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                <?php if ($totalPages > 5 && $page < $totalPages - 2): ?><span class="page-link disabled">...</span><a href="?page=<?php echo $totalPages; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&from=<?php echo $fromDate; ?>&to=<?php echo $toDate; ?>" class="page-link"><?php echo $totalPages; ?></a><?php endif; ?>
                <a href="?page=<?php echo min($totalPages, $page + 1); ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>&from=<?php echo $fromDate; ?>&to=<?php echo $toDate; ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>"><i class="fas fa-chevron-right"></i></a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-times-circle" style="color: #f59e0b;"></i> Reject Request</h3><button onclick="closeModal('rejectModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="request_id" id="rejectUserId">
                    <div class="form-group"><label style="display:block;margin-bottom:0.5rem;font-weight:500;">Reason for Rejection</label><textarea name="rejection_reason" class="form-control-admin" rows="4" required placeholder="Please provide a reason..."></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn-admin" style="background:#f59e0b;color:white;">Reject Request</button><button type="button" class="btn-admin btn-outline" onclick="closeModal('rejectModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    
    <!-- Close Modal -->
    <div id="closeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-lock" style="color:#ef4444;"></i> Force Close Request</h3><button onclick="closeModal('closeModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="close">
                    <input type="hidden" name="request_id" id="closeRequestId">
                    <div class="form-group"><label style="display:block;margin-bottom:0.5rem;font-weight:500;">Closing Notes</label><textarea name="close_notes" class="form-control-admin" rows="4" placeholder="Add notes about this closure..."></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn-admin" style="background:#ef4444;color:white;">Force Close</button><button type="button" class="btn-admin btn-outline" onclick="closeModal('closeModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    
    <!-- Extend Modal -->
    <div id="extendModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-calendar-plus" style="color:#6366f1;"></i> Extend Return Date</h3><button onclick="closeModal('extendModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="extend">
                    <input type="hidden" name="request_id" id="extendRequestId">
                    <div class="form-group"><label style="display:block;margin-bottom:0.5rem;font-weight:500;">Additional Days</label><input type="number" name="extend_days" class="form-control-admin" min="1" max="90" value="7" required></div>
                    <div class="form-group"><label style="display:block;margin-bottom:0.5rem;font-weight:500;">Reason (Optional)</label><textarea name="extend_reason" class="form-control-admin" rows="3" placeholder="Why is the extension needed?"></textarea></div>
                </div>
                <div class="modal-footer"><button type="submit" class="btn-admin" style="background:#6366f1;color:white;">Extend Return Date</button><button type="button" class="btn-admin btn-outline" onclick="closeModal('extendModal')">Cancel</button></div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Reject Modal -->
    <div id="bulkRejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header"><h3><i class="fas fa-times-circle" style="color:#f59e0b;"></i> Bulk Reject Requests</h3><button onclick="closeModal('bulkRejectModal')" style="background:none;border:none;font-size:1.5rem;cursor:pointer;">&times;</button></div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="bulk_reject">
                    <div id="bulkRequestIds"></div>
                    <div class="form-group"><label style="display:block;margin-bottom:0.5rem;font-weight:500;">Rejection Reason</label><textarea name="bulk_rejection_reason" class="form-control-admin" rows="4" required placeholder="Please provide a reason..."></textarea></div>
                    <p style="margin-top:1rem;color:#64748b;">This will reject <span id="bulkCount"></span> selected request(s).</p>
                </div>
                <div class="modal-footer"><button type="submit" class="btn-admin" style="background:#f59e0b;color:white;">Reject Selected</button><button type="button" class="btn-admin btn-outline" onclick="closeModal('bulkRejectModal')">Cancel</button></div>
            </form>
        </div>
    </div>

    <script>
        function applyFilter() {
            const status = document.getElementById('statusFilter').value;
            const from = document.getElementById('fromDate').value;
            const to = document.getElementById('toDate').value;
            const search = document.querySelector('input[name="search"]').value;
            window.location.href = `?status=${status}&search=${encodeURIComponent(search)}&from=${from}&to=${to}`;
        }
        
        function approveRequest(requestId) {
            if (confirm('Approve this request?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="approve"><input type="hidden" name="request_id" value="${requestId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function showRejectModal(requestId) {
            document.getElementById('rejectUserId').value = requestId;
            document.getElementById('rejectModal').classList.add('active');
        }
        
        function showCloseModal(requestId) {
            document.getElementById('closeRequestId').value = requestId;
            document.getElementById('closeModal').classList.add('active');
        }
        
        function showExtendModal(requestId) {
            document.getElementById('extendRequestId').value = requestId;
            document.getElementById('extendModal').classList.add('active');
        }
        
        function viewRequest(requestId) {
            window.open(`/requests/?id=${requestId}`, '_blank');
        }
        
        let selectedRequests = new Set();
        
        function toggleAll() {
            const checkboxes = document.querySelectorAll('.request-checkbox');
            const selectAll = document.getElementById('selectAll');
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
                if (selectAll.checked) selectedRequests.add(cb.value);
                else selectedRequests.delete(cb.value);
            });
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            document.querySelectorAll('.request-checkbox').forEach(cb => {
                if (cb.checked) selectedRequests.add(cb.value);
                else selectedRequests.delete(cb.value);
            });
            const count = selectedRequests.size;
            const bulkBar = document.getElementById('bulkBar');
            if (count > 0) {
                document.getElementById('selectedCount').textContent = count + ' selected';
                bulkBar.classList.remove('hidden');
            } else {
                bulkBar.classList.add('hidden');
            }
        }
        
        function bulkApprove() {
            if (selectedRequests.size === 0) return;
            if (confirm(`Approve ${selectedRequests.size} selected request(s)?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                let html = '<input type="hidden" name="action" value="bulk_approve">';
                selectedRequests.forEach(id => html += `<input type="hidden" name="request_ids[]" value="${id}">`);
                form.innerHTML = html;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function showBulkRejectModal() {
            if (selectedRequests.size === 0) return;
            document.getElementById('bulkCount').textContent = selectedRequests.size;
            let html = '';
            selectedRequests.forEach(id => html += `<input type="hidden" name="request_ids[]" value="${id}">`);
            document.getElementById('bulkRequestIds').innerHTML = html;
            document.getElementById('bulkRejectModal').classList.add('active');
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) e.target.classList.remove('active');
        });
        
        let searchTimeout;
        document.querySelector('.search-box input').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => document.getElementById('searchForm').submit(), 500);
        });
    </script>

    <?php include dirname(__DIR__, 2) . '/includes/admin-footer.php'; ?>
</body>
</html>