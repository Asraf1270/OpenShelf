<?php
/**
 * OpenShelf Backup Restore
 * Restore data from a backup
 */

session_start();

define('DATA_PATH', dirname(__DIR__, 2) . '/data/');
define('BACKUP_PATH', dirname(__DIR__, 2) . '/backups/');

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$backupName = $_GET['name'] ?? '';
$backupDir = BACKUP_PATH . $backupName . '/';

if (empty($backupName) || !is_dir($backupDir)) {
    $_SESSION['error'] = 'Invalid backup';
    header('Location: /admin/backup/');
    exit;
}

function restoreBackup($source, $destination) {
    $files = scandir($source);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;
        
        $src = $source . $file;
        $dst = $destination . $file;
        
        if (is_dir($src)) {
            if (!is_dir($dst)) mkdir($dst, 0755, true);
            restoreBackup($src . '/', $dst . '/');
        } else {
            copy($src, $dst);
        }
    }
}

// Create backup of current data before restore
$currentBackup = date('Y-m-d_H-i-s') . '_auto';
$autoBackupDir = BACKUP_PATH . $currentBackup . '/';
mkdir($autoBackupDir, 0755, true);

$files = glob(DATA_PATH . '*.json');
foreach ($files as $file) {
    copy($file, $autoBackupDir . basename($file));
}

$booksDir = DATA_PATH . 'book/';
if (is_dir($booksDir)) {
    mkdir($autoBackupDir . 'book/', 0755, true);
    $bookFiles = glob($booksDir . '*.json');
    foreach ($bookFiles as $file) {
        copy($file, $autoBackupDir . 'book/' . basename($file));
    }
}

// Restore from selected backup
restoreBackup($backupDir, DATA_PATH);

$_SESSION['success'] = 'Backup restored successfully. Auto-backup created: ' . $currentBackup;
header('Location: /admin/backup/');
exit;