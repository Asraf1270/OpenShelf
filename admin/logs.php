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

    .logs-page {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1rem;
    }

    .tabs {
        display: flex;
        gap: 0.75rem;
        margin-bottom: 2rem;
        flex-wrap: wrap;
    }

    .tab-btn {
        padding: 0.75rem 1.5rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 2rem;
        text-decoration: none;
        color: var(--text-muted);
        font-weight: 700;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .tab-btn.active {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        box-shadow: 0 8px 16px rgba(44, 62, 80, 0.2);
    }

    .log-container {
        background: #0F172A;
        border-radius: var(--radius-md);
        padding: 1.5rem;
        overflow-x: auto;
        font-family: 'Fira Code', 'Courier New', monospace;
        border: 1px solid var(--border);
        box-shadow: inset 0 2px 10px rgba(0,0,0,0.3);
    }

    .log-entry {
        padding: 0.65rem;
        border-bottom: 1px solid rgba(255,255,255,0.05);
        color: #94A3B8;
        font-size: 0.85rem;
        white-space: pre-wrap;
        word-break: break-all;
    }

    .log-entry:hover {
        background: rgba(255,255,255,0.02);
    }

    .log-error {
        color: #F87171;
        background: rgba(248, 113, 113, 0.05);
    }

    .log-warning {
        color: #FBBF24;
    }

    .actions {
        display: flex;
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .btn-log {
        padding: 0.65rem 1.25rem;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.3s ease;
    }

    .btn-log-default {
        background: var(--surface);
        color: var(--text-main);
        border: 1px solid var(--border);
    }

    .btn-log-danger {
        background: #EF4444;
        color: white;
    }

    .btn-log:hover {
        transform: translateY(-2px);
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
                <a href="/admin/logs/clear.php?type=<?php echo $logType; ?>" class="btn-log btn-log-danger" onclick="return confirm('Clear all logs?')">
                    <i class="fas fa-trash"></i> Clear Logs
                </a>
                <a href="/admin/logs/download.php?type=<?php echo $logType; ?>" class="btn-log btn-log-default">
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