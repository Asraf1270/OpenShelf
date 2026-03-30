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
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; font-family: sans-serif; }
        .content { padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155; }
        .welcome-badge { background: #f8fafc; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; border-left: 4px solid #6366f1; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
        .button { display: inline-block; padding: 14px 32px; background-color: #6366f1; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; text-align: center; margin-top: 20px; }
        .footer { padding: 20px 30px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-family: sans-serif; font-size: 13px; color: #64748b; }
        .footer p { margin: 0 0 10px; }
        .steps { margin: 25px 0; }
        .step { background: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 15px; border: 1px solid #e2e8f0; text-align: center; }
        .step-number { width: 36px; height: 36px; background: #6366f1; color: white; border-radius: 50%; display: inline-block; line-height: 36px; font-weight: bold; margin-bottom: 10px; text-align: center; }
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
                            <div class="logo">📚</div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: sans-serif;">Welcome to OpenShelf!</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155;">
                            <h2 style="margin: 0 0 15px; color: #0f172a; font-size: 24px;">Hello <?php echo htmlspecialchars($user_name); ?>! 👋</h2>
                            <p style="margin: 0 0 20px;">We're thrilled to have you join the OpenShelf community. You're now part of a growing network of book lovers who share, borrow, and discover amazing reads together.</p>
                            
                            <div class="welcome-badge" style="background: #f8fafc; border-radius: 12px; padding: 20px; text-align: center; margin: 20px 0; border-left: 4px solid #6366f1; border-top: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0;">
                                <p style="margin: 0; font-weight: 600; color: #0f172a;">📖 Your Account Status: <span style="color: #d97706;">Pending Approval</span></p>
                                <p style="margin: 10px 0 0 0; font-size: 14px; color: #475569;">An administrator will review your registration soon. You'll receive an email once your account is approved.</p>
                            </div>
                            
                            <h3 style="margin: 30px 0 15px; color: #0f172a;">What's Next?</h3>
                            
                            <div class="steps" style="margin: 20px 0;">
                                <!-- Step 1 -->
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%; margin-bottom: 15px;">
                                    <tr>
                                        <td style="background: #f8fafc; border-radius: 12px; padding: 15px; border: 1px solid #e2e8f0; text-align: center;">
                                            <div class="step-number" style="width: 36px; height: 36px; background: #6366f1; color: white; border-radius: 50%; display: inline-block; line-height: 36px; font-weight: bold; margin-bottom: 10px; text-align: center;">1</div>
                                            <p style="margin: 0; color: #0f172a;"><strong>Wait for Approval</strong><br><span style="color: #64748b;">We'll verify your account within 24-48 hours</span></p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Step 2 -->
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%; margin-bottom: 15px;">
                                    <tr>
                                        <td style="background: #f8fafc; border-radius: 12px; padding: 15px; border: 1px solid #e2e8f0; text-align: center;">
                                            <div class="step-number" style="width: 36px; height: 36px; background: #6366f1; color: white; border-radius: 50%; display: inline-block; line-height: 36px; font-weight: bold; margin-bottom: 10px; text-align: center;">2</div>
                                            <p style="margin: 0; color: #0f172a;"><strong>Add Your Books</strong><br><span style="color: #64748b;">Share your collection with the community</span></p>
                                        </td>
                                    </tr>
                                </table>
                                
                                <!-- Step 3 -->
                                <table role="presentation" border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
                                    <tr>
                                        <td style="background: #f8fafc; border-radius: 12px; padding: 15px; border: 1px solid #e2e8f0; text-align: center;">
                                            <div class="step-number" style="width: 36px; height: 36px; background: #6366f1; color: white; border-radius: 50%; display: inline-block; line-height: 36px; font-weight: bold; margin-bottom: 10px; text-align: center;">3</div>
                                            <p style="margin: 0; color: #0f172a;"><strong>Start Borrowing</strong><br><span style="color: #64748b;">Discover and request books from others</span></p>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            
                            <p style="margin: 30px 0 20px; text-align: center;">While you wait, feel free to browse our library and see what's available!</p>
                            
                            <div style="text-align: center; margin-top: 25px;">
                                <a href="<?php echo $login_url; ?>" class="button" style="display: inline-block; padding: 14px 32px; background-color: #6366f1; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px;">Browse Books</a>
                            </div>
                            
                            <p style="margin-top: 30px; font-size: 14px; color: #64748b; border-top: 1px solid #e2e8f0; padding-top: 20px; text-align: center;">Need help? Contact us at <a href="mailto:support@openshelf.com" style="color: #6366f1; text-decoration: none;">support@openshelf.com</a></p>
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