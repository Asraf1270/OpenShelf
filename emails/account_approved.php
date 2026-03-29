<?php
/**
 * Account Approved Email Template
 * Sent when admin approves a user account
 * 
 * Variables:
 * $user_name - User's name
 * $login_url - Login page URL
 * $base_url - Base URL
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Approved - OpenShelf</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #10b981, #059669); padding: 40px 20px; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .logo { font-size: 48px; margin-bottom: 10px; position: relative; }
        .header h1 { color: white; margin: 0; font-size: 28px; position: relative; }
        .content { padding: 40px 30px; }
        .success-icon { font-size: 64px; text-align: center; margin-bottom: 20px; }
        .feature-list { background: #f1f5f9; border-radius: 12px; padding: 20px; margin: 20px 0; }
        .feature-item { display: flex; align-items: center; gap: 12px; padding: 8px 0; }
        .feature-item i { color: #10b981; font-size: 20px; }
        .button { display: inline-block; padding: 12px 32px; background: linear-gradient(135deg, #10b981, #059669); color: white; text-decoration: none; border-radius: 40px; font-weight: 600; transition: transform 0.2s; }
        .button:hover { transform: translateY(-2px); }
        .footer { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; background: #f8fafc; }
        @media (max-width: 480px) { .content { padding: 25px 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">✅</div>
                <h1>Account Approved!</h1>
            </div>
            <div class="content">
                <div class="success-icon">🎉</div>
                <h2 style="margin-top: 0; text-align: center;">Welcome to OpenShelf, <?php echo htmlspecialchars($user_name); ?>!</h2>
                <p style="text-align: center;">Your account has been successfully approved. You can now start sharing and borrowing books!</p>
                
                <div class="feature-list">
                    <div class="feature-item"><i>📚</i> <span>Share your books with the community</span></div>
                    <div class="feature-item"><i>🤝</i> <span>Borrow books from fellow students</span></div>
                    <div class="feature-item"><i>⭐</i> <span>Write reviews and rate books</span></div>
                    <div class="feature-item"><i>💬</i> <span>Connect with other book lovers</span></div>
                </div>
                
                <div style="text-align: center;">
                    <a href="<?php echo $login_url; ?>" class="button">Start Reading →</a>
                </div>
                
                <p style="margin-top: 30px; font-size: 14px; color: #64748b; text-align: center;">Ready to begin? Log in and add your first book!</p>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>