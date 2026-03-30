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
    <!--[if mso]>
    <style type="text/css">
        table {border-collapse: collapse;}
        .container {border: 1px solid #e2e8f0;}
    </style>
    <![endif]-->
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; line-height: 1.6; color: #334155; background-color: #f1f5f9; margin: 0; padding: 0; -webkit-font-smoothing: antialiased; }
        .wrapper { width: 100%; background-color: #f1f5f9; padding: 40px 0; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06); }
        .header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 40px 20px; text-align: center; }
        .logo { font-size: 48px; margin-bottom: 10px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 24px; font-weight: 700; font-family: sans-serif; }
        .content { padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155; }
        .announcement-badge { display: inline-block; padding: 4px 12px; background: <?php echo $priorityColor; ?>; color: #ffffff; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 15px; }
        .announcement-title { font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 20px; }
        .announcement-content { background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid <?php echo $priorityColor; ?>; line-height: 1.7; }
        .button { display: inline-block; padding: 14px 32px; background-color: <?php echo $priorityColor; ?>; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; text-align: center; margin-top: 15px; }
        .footer { padding: 20px 30px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-family: sans-serif; font-size: 13px; color: #64748b; }
        .footer p { margin: 0 0 10px; }
        @media only screen and (max-width: 600px) {
            .wrapper { padding: 20px 10px !important; }
            .content { padding: 30px 20px !important; }
            .header { padding: 30px 20px !important; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9;">
    <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="wrapper" style="width: 100%; background-color: #f1f5f9;">
        <tr>
            <td align="center">
                <table role="presentation" border="0" cellpadding="0" cellspacing="0" class="container" style="width: 100%; max-width: 600px; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);">
                    <tr>
                        <td class="header" style="background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 40px 20px; text-align: center;">
                            <div class="logo">📢</div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: sans-serif;">OpenShelf Announcement</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155;">
                            <p style="margin: 0 0 20px;">Hello <strong><?php echo htmlspecialchars($user_name); ?></strong>,</p>
                            
                            <div style="text-align: center;">
                                <div class="announcement-badge" style="display: inline-block; padding: 4px 12px; background: <?php echo $priorityColor; ?>; color: #ffffff; border-radius: 20px; font-size: 12px; font-weight: 600; margin-bottom: 15px;"><?php echo strtoupper($announcement_priority ?? 'INFO'); ?></div>
                            </div>
                            
                            <div class="announcement-title" style="font-size: 24px; font-weight: 700; color: #0f172a; margin-bottom: 20px;"><?php echo htmlspecialchars($announcement_title); ?></div>
                            
                            <div class="announcement-content" style="background: #f8fafc; padding: 20px; border-radius: 12px; margin: 20px 0; border-left: 4px solid <?php echo $priorityColor; ?>; line-height: 1.7;">
                                <?php echo nl2br(htmlspecialchars($announcement_content)); ?>
                            </div>
                            
                            <div style="text-align: center;">
                                <a href="<?php echo $announcement_link ?? $base_url . '/announcements/'; ?>" class="button" style="display: inline-block; padding: 14px 32px; background-color: <?php echo $priorityColor; ?>; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; text-align: center; margin-top: 15px;">View Details</a>
                            </div>
                            
                            <p style="margin-top: 30px; font-size: 14px; color: #64748b;">Stay connected with the OpenShelf community!</p>
                        </td>
                    </tr>
                    <tr>
                        <td class="footer" style="padding: 20px 30px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-family: sans-serif; font-size: 13px; color: #64748b;">
                            <p style="margin: 0;">&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                            <p style="margin: 5px 0 0;">This is an automated message, please do not reply.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>