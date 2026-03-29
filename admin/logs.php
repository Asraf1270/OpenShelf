<?php
/**
 * OpenShelf Admin Logs Viewer
 * View system, admin, and error logs
 */

session_start();

define('DATA_PATH', dirname(__DIR__) . '/data/');
define('LOG_PATH', dirname(__DIR__) . '/logs/');

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
$logs = [];

if (file_exists($logFile)) {
    $content = file_get_contents($logFile);
    $lines = explode("\n", trim($content));
    $logs = array_reverse(array_slice($lines, -200));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - OpenShelf Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        .logs-page {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .tabs {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        .tab-btn {
            padding: 0.6rem 1.2rem;
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 2rem;
            text-decoration: none;
            color: #0f172a;
        }
        .tab-btn.active {
            background: #6366f1;
            color: white;
            border-color: #6366f1;
        }
        .log-container {
            background: #0f172a;
            border-radius: 1rem;
            padding: 1rem;
            overflow-x: auto;
            font-family: monospace;
        }
        .log-entry {
            padding: 0.5rem;
            border-bottom: 1px solid #1e293b;
            color: #94a3b8;
            font-size: 0.8rem;
            white-space: pre-wrap;
            word-break: break-all;
        }
        .log-entry:hover {
            background: #1e293b;
        }
        .log-error {
            color: #f87171;
        }
        .log-warning {
            color: #fbbf24;
        }
        .actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            text-decoration: none;
            background: #f1f5f9;
            color: #0f172a;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/admin-header.php'; ?>
    
    <main>
        <div class="logs-page">
            <h1 style="margin-bottom: 1.5rem;">System Logs</h1>
            
            <div class="tabs">
                <a href="?type=admin" class="tab-btn <?php echo $logType === 'admin' ? 'active' : ''; ?>">
                    <i class="fas fa-user-shield"></i> Admin Logs
                </a>
                <a href="?type=user" class="tab-btn <?php echo $logType === 'user' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i> User Activity
                </a>
                <a href="?type=error" class="tab-btn <?php echo $logType === 'error' ? 'active' : ''; ?>">
                    <i class="fas fa-exclamation-triangle"></i> Error Logs
                </a>
                <a href="?type=mail" class="tab-btn <?php echo $logType === 'mail' ? 'active' : ''; ?>">
                    <i class="fas fa-envelope"></i> Mail Logs
                </a>
            </div>
            
            <div class="actions">
                <a href="/admin/logs/clear.php?type=<?php echo $logType; ?>" class="btn btn-danger" onclick="return confirm('Clear all logs?')">
                    <i class="fas fa-trash"></i> Clear Logs
                </a>
                <a href="/admin/logs/download.php?type=<?php echo $logType; ?>" class="btn">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
            
            <div class="log-container">
                <?php if (empty($logs)): ?>
                    <div class="log-entry">No logs available.</div>
                <?php else: ?>
                    <?php foreach ($logs as $log): ?>
                        <div class="log-entry <?php 
                            echo strpos($log, 'ERROR') !== false ? 'log-error' : 
                                (strpos($log, 'WARNING') !== false ? 'log-warning' : ''); 
                        ?>">
                            <?php echo htmlspecialchars($log); ?>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>
    
    <?php include dirname(__DIR__) . '/includes/admin-footer.php'; ?>
</body>
</html>