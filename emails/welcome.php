<?php
/**
 * Welcome Email Template
 * Sent when a new user registers
 * 
 * Variables:
 * $user_name - New user's name
 * $user_email - User's email
 * $login_url - Login page URL
 * $base_url - Base URL of the website
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to OpenShelf</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #0f172a;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
        }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: #ffffff; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%); padding: 40px 20px; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .logo { font-size: 48px; margin-bottom: 10px; position: relative; }
        .header h1 { color: white; margin: 0; font-size: 28px; font-weight: 700; position: relative; }
        .content { padding: 40px 30px; }
        .welcome-badge { background: #f1f5f9; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; border-left: 4px solid #6366f1; }
        .button { display: inline-block; padding: 12px 32px; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; text-decoration: none; border-radius: 40px; font-weight: 600; margin: 20px 0; transition: transform 0.2s; }
        .button:hover { transform: translateY(-2px); }
        .footer { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; background: #f8fafc; }
        .steps { display: flex; gap: 20px; margin: 30px 0; flex-wrap: wrap; }
        .step { flex: 1; text-align: center; padding: 15px; background: #f8fafc; border-radius: 12px; }
        .step-number { width: 36px; height: 36px; background: #6366f1; color: white; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-weight: bold; margin-bottom: 10px; }
        @media (max-width: 480px) { .steps { flex-direction: column; } .content { padding: 25px 20px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">📚</div>
                <h1>Welcome to OpenShelf!</h1>
            </div>
            <div class="content">
                <h2 style="margin-top: 0;">Hello <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
                <p>We're thrilled to have you join the OpenShelf community. You're now part of a growing network of book lovers who share, borrow, and discover amazing reads together.</p>
                
                <div class="welcome-badge">
                    <p style="margin: 0; font-weight: 600;">📖 Your Account Status: <span style="color: #f59e0b;">Pending Approval</span></p>
                    <p style="margin: 10px 0 0 0; font-size: 14px;">An administrator will review your registration soon. You'll receive an email once your account is approved.</p>
                </div>
                
                <h3>What's Next?</h3>
                <div class="steps">
                    <div class="step">
                        <div class="step-number">1</div>
                        <p><strong>Wait for Approval</strong><br>We'll verify your account within 24-48 hours</p>
                    </div>
                    <div class="step">
                        <div class="step-number">2</div>
                        <p><strong>Add Your Books</strong><br>Share your collection with the community</p>
                    </div>
                    <div class="step">
                        <div class="step-number">3</div>
                        <p><strong>Start Borrowing</strong><br>Discover and request books from others</p>
                    </div>
                </div>
                
                <p>While you wait, feel free to browse our library and see what's available!</p>
                
                <div style="text-align: center;">
                    <a href="<?php echo $login_url; ?>" class="button">Browse Books</a>
                </div>
                
                <p style="margin-top: 30px; font-size: 14px; color: #64748b;">Need help? Contact us at <a href="mailto:support@openshelf.com" style="color: #6366f1;">support@openshelf.com</a></p>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>