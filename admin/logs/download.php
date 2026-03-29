<?php
/**
 * OpenShelf Download Logs
 * Download log files
 */

session_start();

define('LOG_PATH', dirname(__DIR__, 2) . '/logs/');

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$logType = $_GET['type'] ?? 'admin';
$logFiles = [
    'admin' => 'admin_audit.log',
    'user' => 'user_activity.log',
    'error' => 'error.log',
    'mail' => 'mail.log'
];

$logFile = LOG_PATH . ($logFiles[$logType] ?? 'admin_audit.log');
$filename = $logType . '_logs_' . date('Y-m-d') . '.log';

if (file_exists($logFile)) {
    header('Content-Type: text/plain');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    readfile($logFile);
} else {
    $_SESSION['error'] = 'Log file not found';
    header('Location: /admin/logs/?type=' . $logType);
}
exit;