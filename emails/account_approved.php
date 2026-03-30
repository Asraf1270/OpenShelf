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
        .header { background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 20px; text-align: center; }
        .logo { font-size: 48px; margin-bottom: 10px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; font-family: sans-serif; }
        .content { padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155; }
        .success-icon { font-size: 64px; text-align: center; margin-bottom: 20px; }
        .feature-list { background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0; }
        .feature-item { margin: 10px 0; font-size: 16px; }
        .feature-item i { color: #10b981; font-size: 20px; margin-right: 8px; }
        .button { display: inline-block; padding: 14px 32px; background-color: #10b981; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; text-align: center; }
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
                        <td class="header" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); padding: 40px 20px; text-align: center;">
                            <div class="logo">✅</div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: sans-serif;">Account Approved!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155;">
                            <div class="success-icon" style="text-align: center; font-size: 64px; margin-bottom: 20px;">🎉</div>
                            <h2 style="margin: 0 0 15px; text-align: center; font-size: 20px; color: #0f172a;">Welcome to OpenShelf, <?php echo htmlspecialchars($user_name); ?>!</h2>
                            <p style="text-align: center; margin: 0 0 20px;">Your account has been successfully approved. You can now start sharing and borrowing books!</p>
                            
                            <div class="feature-list" style="background: #f8fafc; border-radius: 12px; padding: 20px; margin: 20px 0;">
                                <div class="feature-item" style="margin: 10px 0;"><i>📚</i> Share your books with the community</div>
                                <div class="feature-item" style="margin: 10px 0;"><i>🤝</i> Borrow books from fellow students</div>
                                <div class="feature-item" style="margin: 10px 0;"><i>⭐</i> Write reviews and rate books</div>
                                <div class="feature-item" style="margin: 10px 0;"><i>💬</i> Connect with other book lovers</div>
                            </div>
                            
                            <div style="text-align: center; margin-top: 30px;">
                                <a href="<?php echo $login_url; ?>" class="button" style="display: inline-block; padding: 14px 32px; background-color: #10b981; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px;">Start Reading →</a>
                            </div>
                            
                            <p style="margin-top: 30px; font-size: 14px; color: #64748b; text-align: center;">Ready to begin? Log in and add your first book!</p>
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