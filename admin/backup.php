<?php
/**
 * OpenShelf Admin Backup Manager
 * Create and manage backups
 */

session_start();

define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BACKUP_PATH', dirname(__DIR__) . '/backups/');

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

if (!file_exists(BACKUP_PATH)) {
    mkdir(BACKUP_PATH, 0755, true);
}

function createBackup() {
    $timestamp = date('Y-m-d_H-i-s');
    $backupDir = BACKUP_PATH . $timestamp . '/';
    mkdir($backupDir, 0755, true);
    
    $files = glob(DATA_PATH . '*.json');
    foreach ($files as $file) {
        copy($file, $backupDir . basename($file));
    }
    
    $booksDir = DATA_PATH . 'book/';
    if (is_dir($booksDir)) {
        $bookFiles = glob($booksDir . '*.json');
        mkdir($backupDir . 'book/', 0755, true);
        foreach ($bookFiles as $file) {
            copy($file, $backupDir . 'book/' . basename($file));
        }
    }
    
    return $timestamp;
}

function getBackups() {
    $backups = [];
    $dirs = glob(BACKUP_PATH . '*', GLOB_ONLYDIR);
    foreach ($dirs as $dir) {
        $name = basename($dir);
        $backups[] = [
            'name' => $name,
            'date' => DateTime::createFromFormat('Y-m-d_H-i-s', $name)->format('M j, Y H:i:s'),
            'size' => round(getDirSize($dir) / 1024, 2),
            'path' => $dir
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

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'create') {
        $backup = createBackup();
        $message = "Backup created: $backup";
    } elseif ($action === 'delete') {
        $name = $_POST['name'] ?? '';
        $path = BACKUP_PATH . $name;
        if (is_dir($path)) {
            array_map('unlink', glob("$path/*/*"));
            array_map('unlink', glob("$path/*"));
            rmdir($path);
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
        .backup-page {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .create-card {
            background: white;
            padding: 1.5rem;
            border-radius: 1rem;
            margin-bottom: 2rem;
            text-align: center;
        }
        .backup-list {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
        }
        .backup-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
        }
        .backup-name {
            font-weight: 500;
        }
        .backup-date {
            font-size: 0.8rem;
            color: #64748b;
        }
        .backup-size {
            font-size: 0.8rem;
            color: #10b981;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            cursor: pointer;
            border: none;
        }
        .btn-primary {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/admin-header.php'; ?>
    
    <main>
        <div class="backup-page">
            <h1 style="margin-bottom: 1.5rem;">Backup Manager</h1>
            
            <?php if ($message): ?>
                <div class="alert alert-success" style="background: rgba(16,185,129,0.1); color: #10b981; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1rem;"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <div class="create-card">
                <i class="fas fa-database" style="font-size: 3rem; color: #6366f1; margin-bottom: 1rem;"></i>
                <h3>Create a New Backup</h3>
                <p style="color: #64748b; margin-bottom: 1rem;">Backup all user data, books, and settings</p>
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <button type="submit" class="btn btn-primary">Create Backup Now</button>
                </form>
            </div>
            
            <div class="backup-list">
                <div class="backup-item" style="background: #f8fafc;">
                    <strong>Available Backups</strong>
                    <span>Actions</span>
                </div>
                <?php if (empty($backups)): ?>
                    <div class="backup-item">No backups found. Create your first backup.</div>
                <?php else: ?>
                    <?php foreach ($backups as $backup): ?>
                        <div class="backup-item">
                            <div>
                                <div class="backup-name"><?php echo $backup['name']; ?></div>
                                <div class="backup-date"><?php echo $backup['date']; ?></div>
                                <div class="backup-size"><?php echo $backup['size']; ?> KB</div>
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