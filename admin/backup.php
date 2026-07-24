<?php
session_start();
/**
 * OpenShelf Admin Backup Manager
 * Create and manage backups
 */

define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BACKUP_PATH', dirname(__DIR__) . '/backups/');

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

if (!file_exists(BACKUP_PATH)) {
    mkdir(BACKUP_PATH, 0755, true);
}

function recursiveCopy($source, $destination) {
    if (!is_dir($destination)) {
        mkdir($destination, 0755, true);
    }
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $src = $source . DIRECTORY_SEPARATOR . $file;
        $dst = $destination . DIRECTORY_SEPARATOR . $file;
        if (is_dir($src)) {
            recursiveCopy($src, $dst);
        } else {
            copy($src, $dst);
        }
    }
}

function recursiveDelete($dir) {
    if (!is_dir($dir)) return;
    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $file;
        if (is_dir($path)) {
            recursiveDelete($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

function createBackup() {
    $timestamp = date('Y-m-d_H-i-s');
    $backupDir = BACKUP_PATH . $timestamp . '/';
    
    if (!is_dir($backupDir)) {
        mkdir($backupDir, 0755, true);
    }
    
    // Backup Data Files (Recursive)
    recursiveCopy(DATA_PATH, $backupDir . 'data');
    
    // Backup Database Tables
    $db = getDB();
    $tables = ['users', 'books', 'borrow_requests', 'announcements', 'categories', 'admins', 'announcement_read_status', 'login_otps'];
    
    $dbBackupDir = $backupDir . 'database/';
    mkdir($dbBackupDir, 0755, true);
    
    foreach ($tables as $table) {
        try {
            $stmt = $db->query("SELECT * FROM $table");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            file_put_contents($dbBackupDir . $table . '.json', json_encode($data, JSON_PRETTY_PRINT));
        } catch (Exception $e) {
            // Table might not exist yet
            file_put_contents($dbBackupDir . $table . '_error.txt', $e->getMessage());
        }
    }
    
    return $timestamp;
}

function getBackups() {
    $backups = [];
    $dirs = glob(BACKUP_PATH . '*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $name = basename($dir);
        // Handle names like Y-m-d_H-i-s or Y-m-d_H-i-s_auto
        $timestampPart = substr($name, 0, 19); 
        $dateObj = DateTime::createFromFormat('Y-m-d_H-i-s', $timestampPart);
        
        $backups[] = [
            'name' => $name,
            'date' => $dateObj ? $dateObj->format('M j, Y H:i:s') : 'Unknown',
            'size' => round(getDirSize($dir) / 1024, 2),
            'path' => $dir,
            'is_auto' => strpos($name, '_auto') !== false
        ];
    }
    rsort($backups);
    return $backups;
}

function getDirSize($dir) {
    $size = 0;
    foreach (glob(rtrim($dir, '/').'/*', GLOB_NOSORT) as $file) {
        $size += is_file($file) ? filesize($file) : getDirSize($file);
    }
    return $size;
}

$message = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

// Clear session messages
unset($_SESSION['success'], $_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $backup = createBackup();
        $message = "Backup created: $backup";
    } elseif ($action === 'delete') {
        $name = $_POST['name'] ?? '';
        $path = BACKUP_PATH . $name;
        if (is_dir($path) && strpos($name, '..') === false) {
            recursiveDelete($path);
            $message = "Backup deleted";
        }
    }
}

$backups = getBackups();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Backup - OpenShelf Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
    :root {
        --primary: #2C3E50;
        --secondary: #4C9F8A;
        --bg: #F8F9FA;
        --surface: #ffffff;
        --border: #E2E8F0;
        --text-main: #0F172A;
        --text-muted: #5A6C7D;
        --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
        --radius-lg: 24px;
        --radius-md: 16px;
    }

    [data-theme="dark"] {
        --bg: #0F172A;
        --surface: #1E293B;
        --border: #334155;
        --text-main: #F8F9FA;
        --text-muted: #94A3B8;
    }

    body {
        background: var(--bg);
        color: var(--text-main);
        transition: background 0.3s ease;
    }

    .backup-page {
        max-width: 800px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .create-card {
        background: var(--surface);
        padding: 2.5rem;
        border-radius: var(--radius-md);
        margin-bottom: 2.5rem;
        text-align: center;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    .backup-list {
        background: var(--surface);
        border-radius: var(--radius-md);
        overflow: hidden;
        border: 1px solid var(--border);
        box-shadow: var(--shadow-sm);
    }

    .backup-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 1.25rem 2rem;
        border-bottom: 1px solid var(--border);
        transition: background 0.3s ease;
    }

    .backup-item:last-child {
        border-bottom: none;
    }

    .backup-item:hover {
        background: rgba(76, 159, 138, 0.05);
    }

    .backup-name {
        font-weight: 750;
        color: var(--text-main);
        font-size: 1.05rem;
    }

    .backup-date {
        font-size: 0.8rem;
        color: var(--text-muted);
        font-weight: 600;
        margin-top: 0.25rem;
    }

    .backup-size {
        font-size: 0.8rem;
        color: var(--secondary);
        font-weight: 700;
        margin-top: 0.2rem;
    }

    .btn {
        padding: 0.75rem 1.5rem;
        border-radius: 12px;
        font-weight: 750;
        cursor: pointer;
        border: none;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-danger {
        background: #EF4444;
        color: white;
    }

    .btn-sm {
        padding: 0.45rem 1rem;
        font-size: 0.8rem;
    }

    .btn:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/admin-header.php'; ?>
    
    <main>
        <div class="backup-page">
            <h1 style="margin-bottom: 1.5rem;">Backup Manager</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success" style="background: rgba(16,185,129,0.1); color: #10b981; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger" style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="create-card">
                <i class="fas fa-database" style="font-size: 3.5rem; color: var(--primary); margin-bottom: 1.5rem; opacity: 0.8;"></i>
                <h3 style="font-weight: 850; letter-spacing: -1px;">Create a New Backup</h3>
                <p style="color: var(--text-muted); margin-bottom: 1.5rem; font-weight: 500;">Safely archive all user data, book records, and system settings.</p>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Backup Now
                    </button>
                </form>
            </div>
            
            <div class="backup-list">
                <div class="backup-item" style="background: var(--bg);">
                    <strong style="text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1.2px; color: var(--text-muted);">Available Backups</strong>
                    <span style="text-transform: uppercase; font-size: 0.75rem; letter-spacing: 1.2px; color: var(--text-muted);">Actions</span>
                </div>
                <?php if (empty($backups)): ?>
                    <div class="backup-item">No backups found. Create your first backup.</div>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <div class="backup-item">
                            <div>
                                <div class="backup-name">
                                    <?php echo $backup['name']; ?>
                                    <?php if ($backup['is_auto']): ?>
                                        <span style="background: #e2e8f0; color: #64748b; font-size: 0.7rem; padding: 0.1rem 0.4rem; border-radius: 1rem; margin-left: 0.5rem;">Auto</span>
                                    <?php endif; ?>
                                </div>
                                <div class="backup-date"><i class="far fa-calendar-alt"></i> <?php echo $backup['date']; ?></div>
                                <div class="backup-size"><i class="fas fa-hdd"></i> <?php echo $backup['size']; ?> KB</div>
                            </div>
                            <div class="actions" style="display: flex; gap: 0.5rem;">
                                <a href="/admin/backup/restore.php?name=<?php echo urlencode($backup['name']); ?>" class="btn btn-primary btn-sm" onclick="return confirm('Restore this backup? Current data will be overwritten.')">Restore</a>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="name" value="<?php echo $backup['name']; ?>">
                                    <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Delete this backup?')">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include dirname(__DIR__) . '/includes/admin-footer.php'; ?>
</body>
</html>