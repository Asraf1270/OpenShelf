<?php
/**
 * Admin Notification Email Template
 * Sent to admins when important events happen (e.g. new registration)
 */
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 20px auto; padding: 20px; border: 1px solid #eee; border-radius: 10px; }
        .header { background: #4f46e5; color: white; padding: 20px; text-align: center; border-radius: 10px 10px 0 0; }
        .content { padding: 20px; }
        .info-table { width: 100%; border-collapse: collapse; margin: 20px 0; }
        .info-table td { padding: 10px; border-bottom: 1px solid #eee; }
        .info-table td:first-child { font-weight: bold; width: 140px; }
        .button { display: inline-block; padding: 12px 25px; background: #4f46e5; color: white !important; text-decoration: none; border-radius: 5px; margin-top: 20px; }
        .footer { font-size: 12px; color: #888; text-align: center; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Notification</h1>
        </div>
        <div class="content">
            <p>Hello <?php echo htmlspecialchars($admin_name); ?>,</p>
            
            <?php if ($notification_type === 'new_registration'): ?>
                <p>A new user has registered on OpenShelf and is awaiting your approval.</p>
                
                <table class="info-table">
                    <tr><td>Name:</td><td><?php echo htmlspecialchars($user_name); ?></td></tr>
                    <tr><td>Email:</td><td><?php echo htmlspecialchars($user_email); ?></td></tr>
                    <tr><td>Department:</td><td><?php echo htmlspecialchars($user_department); ?></td></tr>
                    <tr><td>Session:</td><td><?php echo htmlspecialchars($user_session); ?></td></tr>
                </table>
                
                <p>Please log in to the admin panel to review the registration.</p>
                <a href="<?php echo $admin_url; ?>" class="button">Review Registration</a>
            <?php else: ?>
                <p>An important event occurred: <strong><?php echo htmlspecialchars($notification_type); ?></strong></p>
                <p>User Involved: <?php echo htmlspecialchars($user_name); ?> (<?php echo htmlspecialchars($user_email); ?>)</p>
            <?php endif; ?>
        </div>
        <div class="footer">
            <p>&copy; <?php echo date('Y'); ?> OpenShelf Admin System</p>
        </div>
    </div>
</body>
</html>
