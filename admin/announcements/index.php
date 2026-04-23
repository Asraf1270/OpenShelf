<?php
/**
 * OpenShelf Admin Announcement System
 * Modern UI with clean design, scheduling, and analytics
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__, 2) . '/data/');
define('USERS_PATH', dirname(__DIR__, 2) . '/users/');
define('BASE_URL', 'https://openshelf.free.nf');

// Include database connection
require_once dirname(__DIR__, 2) . '/includes/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';
$adminRole = $_SESSION['admin_role'] ?? 'admin';

// Load mailer for email notifications
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/lib/Mailer.php';
$mailer = new Mailer();

/**
 * Load all announcements from DB
 */
function loadAnnouncements() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM announcements ORDER BY created_at DESC");
    $announcements = $stmt->fetchAll();
    
    // Map DB structure to app structure if needed
    foreach ($announcements as &$ann) {
        $sentVia = json_decode($ann['sent_via'] ?? '{"email":false,"notification":false}', true);
        $ann['sent_via'] = [
            'email' => (bool)($sentVia['email'] ?? false),
            'notification' => (bool)($sentVia['notification'] ?? false)
        ];
        
        $stats = json_decode($ann['stats'] ?? '{"views":0,"read":0}', true);
        $ann['stats'] = [
            'views' => $stats['views'] ?? 0,
            'read' => $stats['read'] ?? 0
        ];
    }
    
    return ['announcements' => $announcements];
}

/**
 * Load all active users from DB
 */
function loadAllUsers() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM users WHERE status = 'active'");
    return $stmt->fetchAll();
}

/**
 * Send email to a user
 */
function sendAnnouncementEmail($userEmail, $userName, $announcement) {
    global $mailer;
    
    try {
        return $mailer->sendTemplate(
            $userEmail,
            $userName,
            'announcement',
            [
                'subject'                => '[OpenShelf] ' . $announcement['title'],
                'user_name'              => $userName,
                'announcement_title'     => $announcement['title'],
                'announcement_content'   => $announcement['content'],
                'announcement_priority'  => $announcement['priority'],
                'announcement_link'      => BASE_URL . '/announcements/?id=' . $announcement['id'],
                'base_url'               => BASE_URL
            ]
        );
    } catch (Exception $e) {
        error_log("Announcement email failed to {$userEmail}: " . $e->getMessage());
        return false;
    }
}

/**
 * Create notification for a user
 */
function createAnnouncementNotification($userId, $announcement) {
    $userFile = dirname(dirname(__DIR__)) . '/users/' . $userId . '.json';
    if (!file_exists($userFile)) return false;
    
    $userData = json_decode(file_get_contents($userFile), true);
    $notifications = $userData['notifications'] ?? [];
    
    $priorityIcon = [
        'info' => 'fa-info-circle',
        'success' => 'fa-check-circle',
        'warning' => 'fa-exclamation-triangle',
        'danger' => 'fa-exclamation-circle'
    ];
    
    $notifications[] = [
        'id' => 'notif_' . uniqid() . '_' . bin2hex(random_bytes(4)),
        'user_id' => $userId,
        'type' => 'announcement',
        'title' => $announcement['title'],
        'message' => substr($announcement['content'], 0, 100) . (strlen($announcement['content']) > 100 ? '...' : ''),
        'link' => '/announcements/?id=' . $announcement['id'],
        'icon' => $priorityIcon[$announcement['priority']] ?? 'fa-bullhorn',
        'is_read' => false,
        'created_at' => date('Y-m-d H:i:s'),
        'expires_at' => $announcement['expires_at'] ?? date('Y-m-d H:i:s', strtotime('+30 days'))
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
 * Generate unique announcement ID
 */
function generateAnnouncementId() {
    return 'ann_' . uniqid() . '_' . bin2hex(random_bytes(4));
}

/**
 * Send announcement to all users
 */
function sendAnnouncement($announcement, $sendEmail, $sendNotification) {
    $users = loadAllUsers();
    $sentCount = ['email' => 0, 'notification' => 0];
    
    foreach ($users as $user) {
        if ($user['status'] === 'active') {
            // Send notification
            if ($sendNotification) {
                createAnnouncementNotification($user['id'], $announcement);
                $sentCount['notification']++;
            }
            // Send email
            if ($sendEmail && !empty($user['email'])) {
                if (sendAnnouncementEmail($user['email'], $user['name'], $announcement)) {
                    $sentCount['email']++;
                }
            }
        }
    }
    
    return $sentCount;
}

// Load data
$announcementsData = loadAnnouncements();
$announcements = $announcementsData['announcements'];

// Handle actions
$message = '';
$error = '';
$editingAnnouncement = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $priority = $_POST['priority'] ?? 'info';
        $target = $_POST['target'] ?? 'all';
        $sendEmail = isset($_POST['send_email']) && $_POST['send_email'] === 'on';
        $sendNotification = isset($_POST['send_notification']) && $_POST['send_notification'] === 'on';
        $scheduleDate = trim($_POST['schedule_date'] ?? '');
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        
        // Validation
        if (empty($title)) {
            $error = 'Announcement title is required';
        } elseif (empty($content)) {
            $error = 'Announcement content is required';
        } else {
            $id = generateAnnouncementId();
            $sql = "INSERT INTO announcements (id, title, content, priority, target, created_by, created_by_name, created_at, scheduled_for, expires_at, sent_via, stats) 
                    VALUES (:id, :title, :content, :priority, :target, :created_by, :created_by_name, :created_at, :scheduled_for, :expires_at, :sent_via, :stats)";
            
            $stmt = $db->prepare($sql);
            $saved = $stmt->execute([
                ':id' => $id,
                ':title' => $title,
                ':content' => $content,
                ':priority' => $priority,
                ':target' => $target,
                ':created_by' => $adminId,
                ':created_by_name' => $adminName,
                ':created_at' => date('Y-m-d H:i:s'),
                ':scheduled_for' => !empty($scheduleDate) ? date('Y-m-d H:i:s', strtotime($scheduleDate)) : null,
                ':expires_at' => !empty($expiryDate) ? date('Y-m-d H:i:s', strtotime($expiryDate)) : date('Y-m-d H:i:s', strtotime('+30 days')),
                ':sent_via' => json_encode(['email' => $sendEmail, 'notification' => $sendNotification]),
                ':stats' => json_encode(['views' => 0, 'read' => 0])
            ]);
            
            if ($saved) {
                $message = 'Announcement created successfully!';
                
                $announcement = [
                    'id' => $id,
                    'title' => $title,
                    'content' => $content,
                    'priority' => $priority,
                    'expires_at' => $expiryDate
                ];
                
                // Send immediately if not scheduled
                if (empty($scheduleDate) || strtotime($scheduleDate) <= time()) {
                    $sentCount = sendAnnouncement($announcement, $sendEmail, $sendNotification);
                    $message .= " Sent to {$sentCount['email']} users via email and {$sentCount['notification']} via notification.";
                } else {
                    $message .= " Scheduled for " . date('M j, Y g:i A', strtotime($scheduleDate));
                }
            } else {
                $error = 'Failed to save announcement';
            }
        }
        
    } elseif ($action === 'update') {
        $announcementId = $_POST['announcement_id'] ?? '';
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $priority = $_POST['priority'] ?? 'info';
        $expiryDate = trim($_POST['expiry_date'] ?? '');
        
        $sql = "UPDATE announcements SET 
                    title = :title, 
                    content = :content, 
                    priority = :priority, 
                    expires_at = :expires_at, 
                    updated_at = :updated_at 
                WHERE id = :id";
        
        $stmt = $db->prepare($sql);
        $updated = $stmt->execute([
            ':title' => $title,
            ':content' => $content,
            ':priority' => $priority,
            ':expires_at' => !empty($expiryDate) ? date('Y-m-d H:i:s', strtotime($expiryDate)) : null,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id' => $announcementId
        ]);
        
        if ($updated) {
            $message = 'Announcement updated successfully!';
        } else {
            $error = 'Failed to update announcement';
        }
        
    } elseif ($action === 'delete') {
        $announcementId = $_POST['announcement_id'] ?? '';
        
        $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
        $deletedAnn = $stmt->execute([$announcementId]);
        
        $stmt = $db->prepare("DELETE FROM announcement_read_status WHERE announcement_id = ?");
        $deletedStatus = $stmt->execute([$announcementId]);
        
        if ($deletedAnn) {
            $message = 'Announcement deleted successfully!';
        } else {
            $error = 'Failed to delete announcement';
        }
    } elseif ($action === 'send_now') {
        $announcementId = $_POST['announcement_id'] ?? '';
        
        $stmt = $db->prepare("SELECT * FROM announcements WHERE id = ?");
        $stmt->execute([$announcementId]);
        $announcement = $stmt->fetch();
        
        if ($announcement) {
            $stmt = $db->prepare("UPDATE announcements SET scheduled_for = NULL WHERE id = ?");
            $stmt->execute([$announcementId]);
            
            $sentVia = json_decode($announcement['sent_via'] ?? '{"email":false,"notification":false}', true);
            $sentCount = sendAnnouncement(
                $announcement,
                (bool)($sentVia['email'] ?? false),
                (bool)($sentVia['notification'] ?? false)
            );
            $message = "Announcement sent! Delivered to {$sentCount['email']} emails and {$sentCount['notification']} notifications.";
        } else {
            $error = 'Failed to send announcement';
        }
    }
    
    // Reload data
    $announcementsData = loadAnnouncements();
    $announcements = $announcementsData['announcements'];
}

// Get announcement for editing
if (isset($_GET['edit'])) {
    foreach ($announcements as $ann) {
        if ($ann['id'] === $_GET['edit']) {
            $editingAnnouncement = $ann;
            break;
        }
    }
}

// Sort announcements by date (newest first)
usort($announcements, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

// Get stats
$totalAnnouncements = count($announcements);
$activeAnnouncements = count(array_filter($announcements, fn($a) => empty($a['expires_at']) || strtotime($a['expires_at']) > time()));
$scheduledAnnouncements = count(array_filter($announcements, fn($a) => !empty($a['scheduled_for']) && strtotime($a['scheduled_for']) > time()));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements - OpenShelf Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .announcements-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
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

        .form-card {
            background: white;
            border-radius: 1.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }

        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #e2e8f0;
        }

        .form-header h2 {
            font-size: 1.25rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #0f172a;
        }

        .form-label i {
            color: #6366f1;
            margin-right: 0.5rem;
        }

        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            font-size: 0.9rem;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
        }

        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }

        .priority-select {
            width: 100%;
            padding: 0.75rem;
            border-radius: 0.75rem;
            border: 1px solid #e2e8f0;
            background: white;
            cursor: pointer;
        }

        .checkbox-group {
            display: flex;
            gap: 1rem;
            align-items: center;
            flex-wrap: wrap;
        }

        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            cursor: pointer;
        }

        .btn {
            padding: 0.6rem 1.25rem;
            border-radius: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(99,102,241,0.3);
        }

        .btn-outline {
            background: transparent;
            border: 1px solid #e2e8f0;
            color: #0f172a;
        }

        .btn-outline:hover {
            border-color: #6366f1;
            color: #6366f1;
        }

        .announcement-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .announcement-card {
            background: white;
            border-radius: 1rem;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            position: relative;
        }

        .announcement-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }

        .announcement-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .announcement-title {
            font-size: 1.1rem;
            font-weight: 600;
        }

        .priority-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .priority-info { background: rgba(59,130,246,0.1); color: #3b82f6; }
        .priority-success { background: rgba(16,185,129,0.1); color: #10b981; }
        .priority-warning { background: rgba(245,158,11,0.1); color: #f59e0b; }
        .priority-danger { background: rgba(239,68,68,0.1); color: #ef4444; }

        .announcement-content {
            color: #334155;
            margin-bottom: 1rem;
            line-height: 1.5;
            white-space: pre-wrap;
        }

        .announcement-meta {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
            font-size: 0.7rem;
            color: #64748b;
            margin-bottom: 0.75rem;
        }

        .announcement-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-end;
            border-top: 1px solid #e2e8f0;
            padding-top: 0.75rem;
            margin-top: 0.5rem;
        }

        .action-btn {
            padding: 0.35rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
            background: none;
            border: none;
            color: #64748b;
        }

        .action-btn:hover {
            background: #f1f5f9;
            color: #6366f1;
        }

        .action-btn.delete:hover {
            color: #ef4444;
        }

        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
        }

        @media (max-width: 768px) {
            .form-row {
                grid-template-columns: 1fr;
            }
            .stats-cards {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/includes/admin-header.php'; ?>

    <div class="announcements-page">
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 700;">📢 Announcements</h1>
                <p style="color: #64748b;">Create and manage announcements sent to all users</p>
            </div>
            <a href="/admin/announcements/export.php" class="btn btn-outline">
                <i class="fas fa-download"></i> Export Stats
            </a>
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
            <div class="stat-card"><div class="stat-number" style="color: #6366f1;"><?php echo $totalAnnouncements; ?></div><div class="stat-label">Total Announcements</div></div>
            <div class="stat-card"><div class="stat-number" style="color: #10b981;"><?php echo $activeAnnouncements; ?></div><div class="stat-label">Active</div></div>
            <div class="stat-card"><div class="stat-number" style="color: #f59e0b;"><?php echo $scheduledAnnouncements; ?></div><div class="stat-label">Scheduled</div></div>
            <div class="stat-card"><div class="stat-number" style="color: #ef4444;"><?php echo count(array_filter($announcements, fn($a) => strtotime($a['expires_at']) < time())); ?></div><div class="stat-label">Expired</div></div>
        </div>

        <!-- Create/Edit Form -->
        <div class="form-card">
            <div class="form-header">
                <h2><i class="fas fa-<?php echo $editingAnnouncement ? 'edit' : 'plus-circle'; ?>"></i> <?php echo $editingAnnouncement ? 'Edit Announcement' : 'Create New Announcement'; ?></h2>
                <?php if ($editingAnnouncement): ?>
                    <a href="/admin/announcements/" class="btn btn-outline btn-sm">Cancel Edit</a>
                <?php endif; ?>
            </div>

            <form method="POST" id="announcementForm">
                <input type="hidden" name="action" value="<?php echo $editingAnnouncement ? 'update' : 'create'; ?>">
                <?php if ($editingAnnouncement): ?>
                    <input type="hidden" name="announcement_id" value="<?php echo $editingAnnouncement['id']; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-heading"></i> Title</label>
                    <input type="text" name="title" class="form-control" required maxlength="200" value="<?php echo htmlspecialchars($editingAnnouncement['title'] ?? ''); ?>" placeholder="Enter announcement title...">
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-align-left"></i> Content</label>
                    <textarea name="content" class="form-control" rows="8" required placeholder="Write your announcement content here..."><?php echo htmlspecialchars($editingAnnouncement['content'] ?? ''); ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-flag"></i> Priority</label>
                        <select name="priority" class="form-control">
                            <option value="info" <?php echo ($editingAnnouncement['priority'] ?? '') === 'info' ? 'selected' : ''; ?>>ℹ️ Info - General Information</option>
                            <option value="success" <?php echo ($editingAnnouncement['priority'] ?? '') === 'success' ? 'selected' : ''; ?>>✅ Success - Positive Update</option>
                            <option value="warning" <?php echo ($editingAnnouncement['priority'] ?? '') === 'warning' ? 'selected' : ''; ?>>⚠️ Warning - Important Notice</option>
                            <option value="danger" <?php echo ($editingAnnouncement['priority'] ?? '') === 'danger' ? 'selected' : ''; ?>>🔴 Urgent - Critical Update</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-users"></i> Target Audience</label>
                        <select name="target" class="form-control">
                            <option value="all" <?php echo ($editingAnnouncement['target'] ?? '') === 'all' ? 'selected' : ''; ?>>All Users</option>
                            <option value="active">Active Users Only</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar"></i> Schedule (Optional)</label>
                        <input type="datetime-local" name="schedule_date" class="form-control" value="<?php echo $editingAnnouncement ? (date('Y-m-d\TH:i', strtotime($editingAnnouncement['scheduled_for'] ?? ''))) : ''; ?>">
                        <small style="color: #64748b;">Leave empty to send immediately</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-calendar-times"></i> Expiry Date</label>
                        <input type="datetime-local" name="expiry_date" class="form-control" value="<?php echo $editingAnnouncement ? (date('Y-m-d\TH:i', strtotime($editingAnnouncement['expires_at'] ?? ''))) : date('Y-m-d\TH:i', strtotime('+30 days')); ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-bell"></i> Delivery Methods</label>
                    <div class="checkbox-group">
                        <label class="checkbox-label">
                            <input type="checkbox" name="send_email" <?php echo ($editingAnnouncement['sent_via']['email'] ?? true) ? 'checked' : ''; ?>>
                            <i class="fas fa-envelope"></i> Send via Email
                        </label>
                        <label class="checkbox-label">
                            <input type="checkbox" name="send_notification" <?php echo ($editingAnnouncement['sent_via']['notification'] ?? true) ? 'checked' : ''; ?>>
                            <i class="fas fa-bell"></i> Send via In-App Notification
                        </label>
                    </div>
                </div>

                <div style="display: flex; gap: 1rem; margin-top: 1rem;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-<?php echo $editingAnnouncement ? 'save' : 'paper-plane'; ?>"></i> 
                        <?php echo $editingAnnouncement ? 'Update Announcement' : 'Create & Send'; ?>
                    </button>
                    <?php if ($editingAnnouncement): ?>
                        <button type="button" class="btn btn-outline" onclick="window.location.href='/admin/announcements/'">Cancel</button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Announcements List -->
        <h2 style="margin: 1.5rem 0 1rem;">📋 All Announcements</h2>

        <?php if (empty($announcements)): ?>
            <div class="empty-state">
                <i class="fas fa-bullhorn" style="font-size: 3rem; color: #cbd5e1; margin-bottom: 1rem;"></i>
                <h3>No Announcements Yet</h3>
                <p style="color: #64748b;">Create your first announcement to reach all users</p>
            </div>
        <?php else: ?>
            <div class="announcement-list">
                <?php foreach ($announcements as $announcement): 
                    $priorityClass = 'priority-' . ($announcement['priority'] ?? 'info');
                    $isExpired = !empty($announcement['expires_at']) && strtotime($announcement['expires_at']) < time();
                    $isScheduled = !empty($announcement['scheduled_for']) && strtotime($announcement['scheduled_for']) > time();
                ?>
                    <div class="announcement-card">
                        <div class="announcement-header">
                            <div>
                                <span class="priority-badge <?php echo $priorityClass; ?>">
                                    <i class="fas fa-<?php echo $announcement['priority'] === 'danger' ? 'exclamation-circle' : ($announcement['priority'] === 'warning' ? 'exclamation-triangle' : ($announcement['priority'] === 'success' ? 'check-circle' : 'info-circle')); ?>"></i>
                                    <?php echo strtoupper($announcement['priority'] ?? 'INFO'); ?>
                                </span>
                                <span class="announcement-title"><?php echo htmlspecialchars($announcement['title']); ?></span>
                            </div>
                            <div class="announcement-meta">
                                <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($announcement['created_by_name']); ?></span>
                                <span><i class="far fa-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($announcement['created_at'])); ?></span>
                                <?php if ($isScheduled): ?>
                                    <span style="color: #f59e0b;"><i class="fas fa-clock"></i> Scheduled: <?php echo date('M j, Y g:i A', strtotime($announcement['scheduled_for'])); ?></span>
                                <?php endif; ?>
                                <?php if ($isExpired): ?>
                                    <span style="color: #ef4444;"><i class="fas fa-expired"></i> Expired</span>
                                <?php endif; ?>
                                <span><i class="fas fa-eye"></i> <?php echo $announcement['stats']['views']; ?> views</span>
                                <span><i class="fas fa-check-circle"></i> <?php echo $announcement['stats']['read']; ?> read</span>
                            </div>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars(substr($announcement['content'], 0, 300))); ?>
                            <?php if (strlen($announcement['content']) > 300): ?>
                                <button class="action-btn" onclick="alert('<?php echo addslashes(htmlspecialchars($announcement['content'])); ?>')">Read more</button>
                            <?php endif; ?>
                        </div>
                        <div class="announcement-actions">
                            <?php if ($isScheduled): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="send_now">
                                    <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" class="action-btn" onclick="return confirm('Send this announcement now?')">
                                        <i class="fas fa-paper-plane"></i> Send Now
                                    </button>
                                </form>
                            <?php endif; ?>
                            <a href="?edit=<?php echo $announcement['id']; ?>" class="action-btn">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Delete this announcement?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <button type="submit" class="action-btn delete">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include dirname(__DIR__, 2) . '/includes/admin-footer.php'; ?>
</body>
</html>