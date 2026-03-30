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
        .header { background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 40px 20px; text-align: center; }
        .logo { font-size: 48px; margin-bottom: 10px; }
        .header h1 { color: #ffffff; margin: 0; font-size: 28px; font-weight: 700; font-family: sans-serif; }
        .content { padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155; }
        .reason-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; border-radius: 12px; margin: 20px 0; }
        .button { display: inline-block; padding: 14px 32px; background-color: #6366f1; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; text-align: center; }
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
                        <td class="header" style="background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%); padding: 40px 20px; text-align: center;">
                            <div class="logo">📋</div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: sans-serif;">Account Status Update</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155;">
                            <p style="margin: 0 0 20px;">Hello <?php echo htmlspecialchars($user_name); ?>,</p>
                            <p style="margin: 0 0 20px;">Thank you for your interest in joining OpenShelf. After reviewing your registration, we're unable to approve your account at this time.</p>
                            
                            <?php if (!empty($rejection_reason)): ?>
                            <div class="reason-box" style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 20px; border-radius: 12px; margin: 20px 0;">
                                <p style="margin: 0; font-weight: 600;">Reason for rejection:</p>
                                <p style="margin: 10px 0 0 0;"><?php echo nl2br(htmlspecialchars($rejection_reason)); ?></p>
                            </div>
                            <?php endif; ?>
                            
                            <p style="margin: 20px 0 10px;"><strong>What can you do?</strong></p>
                            <ul style="margin: 0 0 20px; padding-left: 20px;">
                                <li style="margin-bottom: 8px;">Ensure you're using a valid university email address</li>
                                <li style="margin-bottom: 8px;">Complete all required fields accurately</li>
                                <li style="margin-bottom: 8px;">Contact our support team if you believe this is an error</li>
                            </ul>
                            
                            <div style="text-align: center; margin-top: 30px;">
                                <a href="mailto:<?php echo $support_email; ?>" class="button" style="display: inline-block; padding: 14px 32px; background-color: #6366f1; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px;">Contact Support</a>
                            </div>
                            
                            <p style="margin-top: 30px; font-size: 14px; color: #64748b;">We encourage you to try registering again with accurate information. If you have questions, our support team is here to help.</p>
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