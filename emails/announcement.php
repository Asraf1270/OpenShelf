<?php
/**
 * Admin Announcement Email Template
 * Sent to users when admin posts an announcement
 * 
 * Variables:
 * $user_name - User's name
 * $announcement_title - Title of the announcement
 * $announcement_content - Content of the announcement
 * $announcement_priority - Priority level (info, success, warning, danger)
 * $announcement_link - Link to view announcement
 * $base_url - Base URL
 */
$priorityColors = [
    'info' => '#3b82f6',
    'success' => '#10b981',
    'warning' => '#f59e0b',
    'danger' => '#ef4444'
];
$priorityColor = $priorityColors[$announcement_priority ?? 'info'] ?? '#3b82f6';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($announcement_title); ?> - OpenShelf</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 40px 20px; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .logo { font-size: 48px; margin-bottom: 10px; position: relative; }
        .header h1 { color: white; margin: 0; font-size: 24px; position: relative; }
        .content { padding: 40px 30px; }
        .announcement-badge { display: inline-block; padding: 4px 12px; background: <?php echo $priorityColor; ?>; color: white; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 15px; }
        .announcement-title { font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 20px; }
        .announcement-content { background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid <?php echo $priorityColor; ?>; line-height: 1.7; }
        .button { display: inline-block; padding: 10px 24px; background: <?php echo $priorityColor; ?>; color: white; text-decoration: none; border-radius: 40px; font-weight: 600; margin-top: 15px; }
        .footer { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; background: #f8fafc; }
        @media (max-width: 480px) { .content { padding: 25px 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">📢</div>
                <h1>OpenShelf Announcement</h1>
            </div>
            <div class="content">
                <p>Hello <strong><?php echo htmlspecialchars($user_name); ?></strong>,</p>
                
                <div style="text-align: center;">
                    <div class="announcement-badge"><?php echo strtoupper($announcement_priority ?? 'INFO'); ?></div>
                </div>
                
                <div class="announcement-title"><?php echo htmlspecialchars($announcement_title); ?></div>
                
                <div class="announcement-content">
                    <?php echo nl2br(htmlspecialchars($announcement_content)); ?>
                </div>
                
                <div style="text-align: center;">
                    <a href="<?php echo $announcement_link ?? $base_url . '/announcements/'; ?>" class="button">View Details</a>
                </div>
                
                <p style="margin-top: 30px; font-size: 14px; color: #64748b;">Stay connected with the OpenShelf community!</p>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>