<?php
/**
 * OpenShelf Admin User Management
 * Modern UI with enhanced features and filtering
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__, 2) . '/data/');
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
 * Load paginated users from DB with filters
 */
function loadUsers($status = 'all', $search = '', $offset = 0, $perPage = 15) {
    $db = getDB();
    $where = ["1=1"];
    $params = [];

    if ($status === 'pending') {
        $where[] = "verified = 0 AND status != 'rejected'";
    } elseif ($status === 'approved') {
        $where[] = "verified = 1 AND status = 'active'";
    } elseif ($status === 'rejected') {
        $where[] = "status = 'rejected'";
    }

    applySearchFilter($search, ['name', 'email', 'phone'], $where, $params, '');

    $sql = "SELECT * FROM users WHERE " . implode(' AND ', $where);

    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
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
 * Get total count of filtered users
 */
function getUsersCount($status = 'all', $search = '') {
    $db = getDB();
    $where = ["1=1"];
    $params = [];

    if ($status === 'pending') {
        $where[] = "verified = 0 AND status != 'rejected'";
    } elseif ($status === 'approved') {
        $where[] = "verified = 1 AND status = 'active'";
    } elseif ($status === 'rejected') {
        $where[] = "status = 'rejected'";
    }

    applySearchFilter($search, ['name', 'email', 'phone'], $where, $params, '');

    $sql = "SELECT COUNT(*) FROM users WHERE " . implode(' AND ', $where);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * Get user by ID
 */
function getUserById($userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Update user verified status in DB and profile file
 */
function updateUserVerifiedStatus($userId, $verified, $rejectionReason = '') {
    global $adminId;
    $db = getDB();
    
    $status = $verified ? 'active' : 'rejected';
    $verifiedAt = date('Y-m-d H:i:s');
    
    $sql = "UPDATE users SET 
                verified = :verified, 
                status = :status, 
                updated_at = :updated_at, 
                rejection_reason = :rejection_reason, 
                verified_by = :verified_by, 
                verified_at = :verified_at 
            WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    $updated = $stmt->execute([
        ':verified' => $verified ? 1 : 0,
        ':status' => $status,
        ':updated_at' => date('Y-m-d H:i:s'),
        ':rejection_reason' => $rejectionReason,
        ':verified_by' => $adminId,
        ':verified_at' => $verifiedAt,
        ':id' => $userId
    ]);
    
    if ($updated) {
        $profileFile = USERS_PATH . $userId . '.json';
        if (file_exists($profileFile)) {
            $profile = json_decode(file_get_contents($profileFile), true);
            $profile['account_info']['verified'] = $verified;
            $profile['account_info']['status'] = $status;
            $profile['account_info']['verified_at'] = $verifiedAt;
            $profile['account_info']['verified_by'] = $adminId;
            if (!$verified) $profile['account_info']['rejection_reason'] = $rejectionReason;
            file_put_contents($profileFile, json_encode($profile, JSON_PRETTY_PRINT));
        }
        return true;
    }
    return false;
}

/**
 * Delete user from DB
 */
function deleteUser($userId) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $masterSaved = $stmt->execute([$userId]);
    
    if ($masterSaved) {
        $profileFile = USERS_PATH . $userId . '.json';
        if (file_exists($profileFile)) {
            $archiveDir = DATA_PATH . 'archive/users/';
            if (!file_exists($archiveDir)) mkdir($archiveDir, 0755, true);
            rename($profileFile, $archiveDir . $userId . '_' . time() . '.json');
        }
        return true;
    }
    return false;
}

// Filters
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    
    if ($action === 'approve') {
        if (updateUserVerifiedStatus($userId, true)) {
            $message = 'User approved successfully';
            
            // Send approval email
            $user = getUserById($userId);
            if ($user && !empty($user['email'])) {
                try {
                    $mailer->sendTemplate(
                        $user['email'],
                        $user['name'],
                        'account_approved',
                        [
                            'subject'   => 'Your OpenShelf Account Has Been Approved!',
                            'user_name' => $user['name'],
                            'login_url' => BASE_URL . '/login/',
                            'base_url'  => BASE_URL
                        ]
                    );
                } catch (Exception $e) {
                    error_log("Failed to send approval email: " . $e->getMessage());
                }
            }
        } else {
            $error = 'Failed to approve user';
        }
    } elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? 'No reason provided');
        if (updateUserVerifiedStatus($userId, false, $reason)) {
            $message = 'User rejected successfully';
            
            // Send rejection email
            $user = getUserById($userId);
            if ($user && !empty($user['email'])) {
                try {
                    $mailer->sendTemplate(
                        $user['email'],
                        $user['name'],
                        'account_rejected',
                        [
                            'subject'           => 'Account Status Update - OpenShelf',
                            'user_name'         => $user['name'],
                            'rejection_reason'  => $reason,
                            'support_email'     => 'support@openshelf.org',
                            'base_url'          => BASE_URL
                        ]
                    );
                } catch (Exception $e) {
                    error_log("Failed to send rejection email: " . $e->getMessage());
                }
            }
        } else {
            $error = 'Failed to reject user';
        }
    } elseif ($action === 'delete') {
        if (deleteUser($userId)) {
            $message = 'User deleted successfully';
        } else {
            $error = 'Failed to delete user';
        }
    } elseif ($action === 'bulk_approve') {
        $userIds = $_POST['user_ids'] ?? [];
        $count = 0;
        foreach ($userIds as $uid) {
            if (updateUserVerifiedStatus($uid, true)) {
                $count++;
                // Send approval email
                $user = getUserById($uid);
                if ($user && !empty($user['email'])) {
                    try {
                        $mailer->sendTemplate(
                            $user['email'],
                            $user['name'],
                            'account_approved',
                            [
                                'subject'   => 'Your OpenShelf Account Has Been Approved!',
                                'user_name' => $user['name'],
                                'login_url' => BASE_URL . '/login/',
                                'base_url'  => BASE_URL
                            ]
                        );
                    } catch (Exception $e) { /* Logged in Mailer */ }
                }
            }
        }
        $message = "Approved {$count} users successfully";
    } elseif ($action === 'bulk_reject') {
        $userIds = $_POST['user_ids'] ?? [];
        $reason = trim($_POST['bulk_rejection_reason'] ?? 'Account rejected by admin');
        $count = 0;
        foreach ($userIds as $uid) {
            if (updateUserVerifiedStatus($uid, false, $reason)) {
                $count++;
                // Send rejection email
                $user = getUserById($uid);
                if ($user && !empty($user['email'])) {
                    try {
                        $mailer->sendTemplate(
                            $user['email'],
                            $user['name'],
                            'account_rejected',
                            [
                                'subject'           => 'Account Status Update - OpenShelf',
                                'user_name'         => $user['name'],
                                'rejection_reason'  => $reason,
                                'support_email'     => 'support@openshelf.org',
                                'base_url'          => BASE_URL
                            ]
                        );
                    } catch (Exception $e) { /* Logged in Mailer */ }
                }
            }
        }
        $message = "Rejected {$count} users successfully";
    } elseif ($action === 'bulk_delete') {
        $userIds = $_POST['user_ids'] ?? [];
        $count = 0;
        foreach ($userIds as $uid) {
            if (deleteUser($uid)) $count++;
        }
        $message = "Deleted {$count} users successfully";
    }
}

// Load data from DB efficiently
$paginatedUsers = loadUsers($status, $search, $offset, $perPage);
$total = getUsersCount($status, $search);
$totalPages = ceil($total / $perPage);

// Stats for the cards
$db = getDB();
$totalUsersCount = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();
$pendingUsersCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE verified = 0 AND status != 'rejected'")->fetchColumn();
$approvedUsersCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE verified = 1 AND status = 'active'")->fetchColumn();
$rejectedUsersCount = (int)$db->query("SELECT COUNT(*) FROM users WHERE status = 'rejected'")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - OpenShelf Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* User Management Styles */
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
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
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
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            letter-spacing: -1.5px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.85rem;
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

        .filter-tabs {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .filter-tab {
            padding: 0.6rem 1.25rem;
            border-radius: 12px;
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 600;
            transition: var(--transition);
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
        }

        .filter-tab:hover {
            background: #f1f5f9;
            border-color: var(--primary);
            color: var(--primary);
        }

        .filter-tab.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.2);
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

        .users-table {
            width: 100%;
            border-collapse: collapse;
        }

        .users-table th {
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

        .users-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: var(--text-main);
            font-size: 0.95rem;
        }

        .users-table tr:last-child td {
            border-bottom: none;
        }

        .users-table tr:hover td {
            background: #fcfdfe;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 44px; height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary), var(--primary-dark));
            display: flex; align-items: center; justify-content: center;
            color: white; font-weight: 700;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .user-name { font-weight: 700; margin-bottom: 2px; }
        .user-email { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; }

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

        .status-active { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-pending { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-rejected { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .action-buttons {
            display: flex;
            gap: 0.6rem;
        }

        .action-btn {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            color: white;
            font-size: 1rem;
        }

        .action-btn.approve { background: #10b981; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }
        .action-btn.reject { background: #f59e0b; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2); }
        .action-btn.delete { background: #ef4444; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2); }
        .action-btn.view { background: var(--primary); box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2); }

        .action-btn:hover {
            transform: translateY(-3px);
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

        .page-link:hover:not(.active) {
            border-color: var(--primary);
            color: var(--primary);
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

        @keyframes modalIn {
            from { transform: scale(0.95); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
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
            .action-buttons { flex-wrap: wrap; }
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/includes/admin-header.php'; ?>

    <div class="admin-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-title">
                <h1>User Management</h1>
                <p>Manage and moderate user accounts with precision</p>
            </div>
            <div>
                <a href="/admin/users/export.php" class="btn-admin btn-admin-primary">
                    <i class="fas fa-download"></i> Export Users
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
        
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value" style="color: var(--primary);"><?php echo $totalUsersCount; ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #10b981;"><?php echo $approvedUsersCount; ?></div>
                <div class="stat-label">Active Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #f59e0b;"><?php echo $pendingUsersCount; ?></div>
                <div class="stat-label">Pending Review</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ef4444;"><?php echo $rejectedUsersCount; ?></div>
                <div class="stat-label">Rejected</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-bar">
            <div class="filter-tabs">
                <a href="?status=all&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $status === 'all' ? 'active' : ''; ?>">
                    All Users
                </a>
                <a href="?status=pending&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $status === 'pending' ? 'active' : ''; ?>">
                    Pending (<?php echo $pendingUsersCount; ?>)
                </a>
                <a href="?status=approved&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $status === 'approved' ? 'active' : ''; ?>">
                    Approved (<?php echo $approvedUsersCount; ?>)
                </a>
                <a href="?status=rejected&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $status === 'rejected' ? 'active' : ''; ?>">
                    Rejected (<?php echo $rejectedUsersCount; ?>)
                </a>
            </div>
            <form method="GET" class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by name, email or phone..." value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="status" value="<?php echo $status; ?>">
            </form>
        </div>
        
        <!-- Bulk Actions Bar -->
        <div id="bulkBar" class="bulk-bar hidden">
            <span id="selectedCount">0 selected</span>
            <div style="display: flex; gap: 0.5rem;">
                <button class="btn-admin btn-admin-primary" onclick="bulkApprove()">Approve Selected</button>
                <button class="btn-admin" style="background: #f59e0b; color: white;" onclick="showBulkRejectModal()">Reject Selected</button>
                <button class="btn-admin" style="background: #ef4444; color: white;" onclick="bulkDelete()">Delete Selected</button>
            </div>
        </div>
        
        <!-- Users Table -->
        <div class="table-container">
            <table class="users-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll" onclick="toggleAll()"></th>
                        <th>User</th>
                        <th>Contact</th>
                        <th>Department</th>
                        <th>Registered</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginatedUsers)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-users" style="font-size: 3rem; color: #cbd5e1;"></i>
                                <p style="margin-top: 1rem;">No users found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paginatedUsers as $user): 
                            $isPending = !($user['verified'] ?? false) && ($user['status'] ?? '') !== 'rejected';
                            $isApproved = ($user['verified'] ?? false) && ($user['status'] ?? '') === 'active';
                        ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>" onchange="updateSelectedCount()">
                                </td>
                                 <td>
                                    <div class="user-info">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="user-name"><?php echo htmlspecialchars($user['name'] ?? 'Unknown'); ?></div>
                                            <div class="user-email"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem;">
                                        <div><i class="fas fa-phone" style="width: 20px; color: #6366f1;"></i> <?php echo htmlspecialchars($user['phone'] ?? 'N/A'); ?></div>
                                        <div style="margin-top: 0.25rem;"><i class="fas fa-door-open" style="width: 20px; color: #6366f1;"></i> <?php echo htmlspecialchars($user['room_number'] ?? 'N/A'); ?></div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem;">
                                        <?php echo htmlspecialchars($user['department'] ?? 'N/A'); ?>
                                        <div style="font-size: 0.7rem; color: #64748b;">Session: <?php echo htmlspecialchars($user['session'] ?? 'N/A'); ?></div>
                                    </div>
                                </td>
                                <td style="font-size: 0.85rem;">
                                    <?php echo date('M j, Y', strtotime($user['created_at'] ?? 'now')); ?>
                                </td>
                                <td>
                                    <span class="status-badge <?php echo $isApproved ? 'status-active' : ($isPending ? 'status-pending' : 'status-rejected'); ?>">
                                        <?php echo $isApproved ? 'Active' : ($isPending ? 'Pending' : 'Rejected'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($isPending): ?>
                                            <button class="action-btn approve" onclick="approveUser('<?php echo $user['id']; ?>')" title="Approve">
                                                <i class="fas fa-check"></i>
                                            </button>
                                            <button class="action-btn reject" onclick="showRejectModal('<?php echo $user['id']; ?>')" title="Reject">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        <?php endif; ?>
                                        <button class="action-btn delete" onclick="deleteUser('<?php echo $user['id']; ?>', '<?php echo htmlspecialchars($user['name']); ?>')" title="Delete">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button class="action-btn view" onclick="viewUser('<?php echo $user['id']; ?>')" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
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
                <a href="?page=<?php echo max(1, $page - 1); ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                
                <?php for ($i = 1; $i <= min(5, $totalPages); $i++): ?>
                    <?php if ($i >= $page - 2 && $i <= $page + 2): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($totalPages > 5 && $page < $totalPages - 2): ?>
                    <span class="page-link disabled">...</span>
                    <a href="?page=<?php echo $totalPages; ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" class="page-link"><?php echo $totalPages; ?></a>
                <?php endif; ?>
                
                <a href="?page=<?php echo min($totalPages, $page + 1); ?>&status=<?php echo $status; ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Reject Modal -->
    <div id="rejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle" style="color: #f59e0b;"></i> Reject User</h3>
                <button onclick="closeModal('rejectModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="user_id" id="rejectUserId">
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reason for Rejection</label>
                        <textarea name="rejection_reason" class="form-control-admin" rows="4" required placeholder="Please provide a reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-admin" style="background: #f59e0b; color: white;">Reject User</button>
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('rejectModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Reject Modal -->
    <div id="bulkRejectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-times-circle" style="color: #f59e0b;"></i> Bulk Reject Users</h3>
                <button onclick="closeModal('bulkRejectModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="bulk_reject">
                    <div id="bulkUserIds"></div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Rejection Reason</label>
                        <textarea name="bulk_rejection_reason" class="form-control-admin" rows="4" required placeholder="Please provide a reason..."></textarea>
                    </div>
                    <p style="margin-top: 1rem; color: #64748b;">This will reject <span id="bulkCount"></span> selected user(s).</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-admin" style="background: #f59e0b; color: white;">Reject Selected</button>
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('bulkRejectModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let selectedUsers = new Set();
        
        function approveUser(userId) {
            if (confirm('Approve this user?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="approve"><input type="hidden" name="user_id" value="${userId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function showRejectModal(userId) {
            document.getElementById('rejectUserId').value = userId;
            document.getElementById('rejectModal').classList.add('active');
        }
        
        function deleteUser(userId, userName) {
            if (confirm(`Delete user "${userName}"? This action cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `<input type="hidden" name="action" value="delete"><input type="hidden" name="user_id" value="${userId}">`;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function viewUser(userId) {
            window.open(`/profile/?id=${userId}`, '_blank');
        }
        
        function toggleAll() {
            const checkboxes = document.querySelectorAll('.user-checkbox');
            const selectAll = document.getElementById('selectAll');
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
                if (selectAll.checked) selectedUsers.add(cb.value);
                else selectedUsers.delete(cb.value);
            });
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            document.querySelectorAll('.user-checkbox').forEach(cb => {
                if (cb.checked) selectedUsers.add(cb.value);
                else selectedUsers.delete(cb.value);
            });
            const count = selectedUsers.size;
            const bulkBar = document.getElementById('bulkBar');
            if (count > 0) {
                document.getElementById('selectedCount').textContent = count + ' selected';
                bulkBar.classList.remove('hidden');
            } else {
                bulkBar.classList.add('hidden');
            }
        }
        
        function bulkApprove() {
            if (selectedUsers.size === 0) return;
            if (confirm(`Approve ${selectedUsers.size} selected user(s)?`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                let html = '<input type="hidden" name="action" value="bulk_approve">';
                selectedUsers.forEach(id => html += `<input type="hidden" name="user_ids[]" value="${id}">`);
                form.innerHTML = html;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function showBulkRejectModal() {
            if (selectedUsers.size === 0) return;
            document.getElementById('bulkCount').textContent = selectedUsers.size;
            let html = '';
            selectedUsers.forEach(id => html += `<input type="hidden" name="user_ids[]" value="${id}">`);
            document.getElementById('bulkUserIds').innerHTML = html;
            document.getElementById('bulkRejectModal').classList.add('active');
        }
        
        function bulkDelete() {
            if (selectedUsers.size === 0) return;
            if (confirm(`Delete ${selectedUsers.size} selected user(s)? This cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                let html = '<input type="hidden" name="action" value="bulk_delete">';
                selectedUsers.forEach(id => html += `<input type="hidden" name="user_ids[]" value="${id}">`);
                form.innerHTML = html;
                document.body.appendChild(form);
                form.submit();
            }
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
            searchTimeout = setTimeout(() => this.form.submit(), 500);
        });
    </script>
    
    <?php include dirname(__DIR__, 2) . '/includes/admin-footer.php'; ?>
</body>
</html>