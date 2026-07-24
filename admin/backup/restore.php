<?php
/**
 * OpenShelf Backup Restore
 * Restore data from a backup
 */

session_start();

define('DATA_PATH', dirname(__DIR__, 2) . '/data/');
define('BACKUP_PATH', dirname(__DIR__, 2) . '/backups/');

require_once dirname(__DIR__, 2) . '/includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$backupName = $_GET['name'] ?? '';
$backupDir = BACKUP_PATH . $backupName . '/';

if (empty($backupName) || !is_dir($backupDir)) {
    $_SESSION['error'] = 'Invalid backup';
    header('Location: /admin/backup.php');
    exit;
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

// 1. Create auto-backup of current state
$timestamp = date('Y-m-d_H-i-s') . '_auto';
$autoBackupDir = BACKUP_PATH . $timestamp . '/';
mkdir($autoBackupDir, 0755, true);

// Backup Files
recursiveCopy(DATA_PATH, $autoBackupDir . 'data');

// Backup DB
$db = getDB();
$tables = ['users', 'books', 'borrow_requests', 'announcements', 'categories', 'admins', 'announcement_read_status', 'login_otps'];
$dbBackupDir = $autoBackupDir . 'database/';
mkdir($dbBackupDir, 0755, true);

foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT * FROM $table");
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        file_put_contents($dbBackupDir . $table . '.json', json_encode($data, JSON_PRETTY_PRINT));
    } catch (Exception $e) {}
}

// 2. Restore Files from Backup
if (is_dir($backupDir . 'data')) {
    // Optional: Clear current data directory before restore? 
    // Usually safer to just overwrite to avoid deleting non-backed up important files.
    recursiveCopy($backupDir . 'data', DATA_PATH);
} else {
    // Legacy backup format support (files in root of backup)
    $files = glob($backupDir . '*.json');
    foreach ($files as $file) {
        copy($file, DATA_PATH . basename($file));
    }
}

// 3. Restore Database from Backup
$dbBackupDir = $backupDir . 'database/';
if (is_dir($dbBackupDir)) {
    $db->beginTransaction();
    try {
        // Disable foreign key checks for restoration
        $db->exec("SET FOREIGN_KEY_CHECKS = 0");
        
        foreach ($tables as $table) {
            $jsonFile = $dbBackupDir . $table . '.json';
            if (file_exists($jsonFile)) {
                $data = json_decode(file_get_contents($jsonFile), true);
                if (is_array($data)) {
                    // Clear existing table
                    $db->exec("TRUNCATE TABLE $table");
                    
                    if (!empty($data)) {
                        $columns = array_keys($data[0]);
                        $colNames = implode(', ', $columns);
                        $placeholders = implode(', ', array_map(fn($col) => ":$col", $columns));
                        
                        $sql = "INSERT INTO $table ($colNames) VALUES ($placeholders)";
                        $stmt = $db->prepare($sql);
                        
                        foreach ($data as $row) {
                            $stmt->execute($row);
                        }
                    }
                }
            }
        }
        
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        $_SESSION['error'] = 'Database restoration failed: ' . $e->getMessage();
        header('Location: /admin/backup.php');
        exit;
    }
}

$_SESSION['success'] = 'Backup restored successfully. Auto-backup created: ' . $timestamp;
header('Location: /admin/backup.php');
exit;