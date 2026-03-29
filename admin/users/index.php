<?php
/**
 * OpenShelf Admin User Management
 * Modern UI with enhanced features and filtering
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__, 2) . '/data/');
define('USERS_PATH', dirname(__DIR__, 2) . '/users/');

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/**
 * Load all users
 */
function loadAllUsers() {
    $usersFile = DATA_PATH . 'users.json';
    if (!file_exists($usersFile)) return [];
    return json_decode(file_get_contents($usersFile), true) ?? [];
}

/**
 * Update user verified status
 */
function updateUserVerifiedStatus($userId, $verified, $rejectionReason = '') {
    global $adminId, $adminName;
    
    $users = loadAllUsers();
    $updated = false;
    $userData = null;
    
    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user['verified'] = $verified;
            $user['status'] = $verified ? 'active' : 'rejected';
            $user['updated_at'] = date('Y-m-d H:i:s');
            $user['rejection_reason'] = $rejectionReason;
            $user['verified_by'] = $adminId;
            $user['verified_at'] = date('Y-m-d H:i:s');
            $userData = $user;
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        file_put_contents(DATA_PATH . 'users.json', json_encode($users, JSON_PRETTY_PRINT));
        
        $profileFile = USERS_PATH . $userId . '.json';
        if (file_exists($profileFile)) {
            $profile = json_decode(file_get_contents($profileFile), true);
            $profile['account_info']['verified'] = $verified;
            $profile['account_info']['status'] = $verified ? 'active' : 'rejected';
            $profile['account_info']['verified_at'] = date('Y-m-d H:i:s');
            $profile['account_info']['verified_by'] = $adminId;
            if (!$verified) $profile['account_info']['rejection_reason'] = $rejectionReason;
            file_put_contents($profileFile, json_encode($profile, JSON_PRETTY_PRINT));
        }
        
        return true;
    }
    return false;
}

/**
 * Delete user
 */
function deleteUser($userId) {
    $users = loadAllUsers();
    $userIndex = -1;
    $userData = null;
    
    foreach ($users as $index => $user) {
        if ($user['id'] === $userId) {
            $userIndex = $index;
            $userData = $user;
            break;
        }
    }
    
    if ($userIndex >= 0) {
        array_splice($users, $userIndex, 1);
        $masterSaved = file_put_contents(DATA_PATH . 'users.json', json_encode($users, JSON_PRETTY_PRINT));
        
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

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? '';
    
    if ($action === 'approve') {
        if (updateUserVerifiedStatus($userId, true)) {
            $message = 'User approved successfully';
        } else {
            $error = 'Failed to approve user';
        }
    } elseif ($action === 'reject') {
        $reason = trim($_POST['rejection_reason'] ?? 'No reason provided');
        if (updateUserVerifiedStatus($userId, false, $reason)) {
            $message = 'User rejected successfully';
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
            if (updateUserVerifiedStatus($uid, true)) $count++;
        }
        $message = "Approved {$count} users successfully";
    } elseif ($action === 'bulk_reject') {
        $userIds = $_POST['user_ids'] ?? [];
        $reason = trim($_POST['bulk_rejection_reason'] ?? 'Account rejected by admin');
        $count = 0;
        foreach ($userIds as $uid) {
            if (updateUserVerifiedStatus($uid, false, $reason)) $count++;
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
    
    $users = loadAllUsers();
}

// Load users
$users = loadAllUsers();

// Filter by status
$status = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

$filteredUsers = $users;
if ($status === 'pending') {
    $filteredUsers = array_filter($filteredUsers, fn($u) => !($u['verified'] ?? false) && ($u['status'] ?? '') !== 'rejected');
} elseif ($status === 'approved') {
    $filteredUsers = array_filter($filteredUsers, fn($u) => ($u['verified'] ?? false) && ($u['status'] ?? '') === 'active');
} elseif ($status === 'rejected') {
    $filteredUsers = array_filter($filteredUsers, fn($u) => ($u['status'] ?? '') === 'rejected');
}

if (!empty($search)) {
    $searchLower = strtolower($search);
    $filteredUsers = array_filter($filteredUsers, fn($u) => 
        strpos(strtolower($u['name'] ?? ''), $searchLower) !== false ||
        strpos(strtolower($u['email'] ?? ''), $searchLower) !== false ||
        strpos(strtolower($u['phone'] ?? ''), $searchLower) !== false
    );
}

$pendingUsers = array_filter($users, fn($u) => !($u['verified'] ?? false) && ($u['status'] ?? '') !== 'rejected');
$approvedUsers = array_filter($users, fn($u) => ($u['verified'] ?? false) && ($u['status'] ?? '') === 'active');
$rejectedUsers = array_filter($users, fn($u) => ($u['status'] ?? '') === 'rejected');

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$total = count($filteredUsers);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedUsers = array_slice($filteredUsers, $offset, $perPage);
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
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.25rem;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
        }
        
        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-tabs {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        
        .filter-tab {
            padding: 0.5rem 1.25rem;
            border-radius: 2rem;
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
            background: white;
            border: 1px solid #e2e8f0;
            color: #0f172a;
        }
        
        .filter-tab:hover {
            border-color: #6366f1;
            color: #6366f1;
        }
        
        .filter-tab.active {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 2rem;
            width: 250px;
            font-size: 0.85rem;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .users-table-container {
            background: white;
            border-radius: 1rem;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .users-table th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.85rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .users-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .users-table tr:hover td {
            background: #f8fafc;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .status-active {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .status-pending {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            color: white;
        }
        
        .action-btn.approve { background: #10b981; }
        .action-btn.reject { background: #f59e0b; }
        .action-btn.delete { background: #ef4444; }
        .action-btn.view { background: #6366f1; }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .bulk-bar {
            background: #f1f5f9;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .bulk-bar.hidden {
            display: none;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-link {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            text-decoration: none;
            color: #0f172a;
            font-size: 0.85rem;
        }
        
        .page-link.active {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            max-width: 450px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 1.25rem;
        }
        
        .modal-footer {
            padding: 1.25rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .users-table th, .users-table td {
                padding: 0.75rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/includes/admin-header.php'; ?>

    <div class="admin-content">
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 700;">User Management</h1>
                <p style="color: #64748b;">Manage and moderate user accounts</p>
            </div>
            <div>
                <a href="/admin/users/export.php" class="btn-admin btn-admin-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
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
        
        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number" style="color: #6366f1;"><?php echo count($users); ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #10b981;"><?php echo count($approvedUsers); ?></div>
                <div class="stat-label">Active</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #f59e0b;"><?php echo count($pendingUsers); ?></div>
                <div class="stat-label">Pending</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ef4444;"><?php echo count($rejectedUsers); ?></div>
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
                    Pending (<?php echo count($pendingUsers); ?>)
                </a>
                <a href="?status=approved&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $status === 'approved' ? 'active' : ''; ?>">
                    Approved (<?php echo count($approvedUsers); ?>)
                </a>
                <a href="?status=rejected&search=<?php echo urlencode($search); ?>" class="filter-tab <?php echo $status === 'rejected' ? 'active' : ''; ?>">
                    Rejected (<?php echo count($rejectedUsers); ?>)
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
        <div class="users-table-container">
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
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <div class="user-avatar">
                                            <?php echo strtoupper(substr($user['name'] ?? 'U', 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($user['name'] ?? 'Unknown'); ?></div>
                                            <div style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($user['email'] ?? ''); ?></div>
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