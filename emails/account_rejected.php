<?php
/**
 * Account Rejected Email Template
 * Sent when admin rejects a user account
 * 
 * Variables:
 * $user_name - User's name
 * $rejection_reason - Reason for rejection
 * $support_email - Support email address
 * $base_url - Base URL
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Update - OpenShelf</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #ef4444, #dc2626); padding: 40px 20px; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .logo { font-size: 48px; margin-bottom: 10px; position: relative; }
        .header h1 { color: white; margin: 0; font-size: 28px; position: relative; }
        .content { padding: 40px 30px; }
        .reason-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .button { display: inline-block; padding: 12px 32px; background: #6366f1; color: white; text-decoration: none; border-radius: 40px; font-weight: 600; transition: transform 0.2s; }
        .button:hover { transform: translateY(-2px); background: #4f46e5; }
        .footer { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; background: #f8fafc; }
        @media (max-width: 480px) { .content { padding: 25px 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">📋</div>
                <h1>Account Status Update</h1>
            </div>
            <div class="content">
                <p>Hello <?php echo htmlspecialchars($user_name); ?>,</p>
                <p>Thank you for your interest in joining OpenShelf. After reviewing your registration, we're unable to approve your account at this time.</p>
                
                <?php if (!empty($rejection_reason)): ?>
                <div class="reason-box">
                    <p style="margin: 0; font-weight: 600;">Reason for rejection:</p>
                    <p style="margin: 10px 0 0 0;"><?php echo nl2br(htmlspecialchars($rejection_reason)); ?></p>
                </div>
                <?php endif; ?>
                
                <p><strong>What can you do?</strong></p>
                <ul>
                    <li>Ensure you're using a valid university email address</li>
                    <li>Complete all required fields accurately</li>
                    <li>Contact our support team if you believe this is an error</li>
                </ul>
                
                <div style="text-align: center;">
                    <a href="mailto:<?php echo $support_email; ?>" class="button">Contact Support</a>
                </div>
                
                <p style="margin-top: 30px; font-size: 14px; color: #64748b;">We encourage you to try registering again with accurate information. If you have questions, our support team is here to help.</p>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>