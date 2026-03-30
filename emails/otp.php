<?php
/**
 * OTP Email Template
 * Sent for admin login verification
 * 
 * Variables:
 * $otp - OTP code
 * $expiry_minutes - Minutes until OTP expires
 * $ip_address - IP address of requester
 * $user_agent - User agent of requester
 * $base_url - Base URL
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login OTP - OpenShelf</title>
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
        .otp-box { background: #f8fafc; border: 2px dashed #6366f1; padding: 20px; text-align: center; font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #6366f1; border-radius: 16px; margin: 20px 0; }
        .warning-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; border-radius: 12px; margin: 20px 0; font-size: 14px; }
        .info-row { display: table; width: 100%; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { display: table-cell; font-weight: 600; width: 30%; }
        .info-value { display: table-cell; color: #475569; }
        .button { display: inline-block; padding: 14px 32px; background-color: #6366f1; color: #ffffff !important; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px; text-align: center; margin-top: 15px; }
        .footer { padding: 20px 30px; text-align: center; background-color: #f8fafc; border-top: 1px solid #e2e8f0; font-family: sans-serif; font-size: 13px; color: #64748b; }
        .footer p { margin: 0 0 10px; }
        @media only screen and (max-width: 600px) {
            .wrapper { padding: 20px 10px !important; }
            .content { padding: 30px 20px !important; }
            .header { padding: 30px 20px !important; }
            .otp-box { font-size: 28px !important; letter-spacing: 4px !important; }
            .info-label, .info-value { display: block; width: 100%; margin-bottom: 5px; }
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
                            <div class="logo">🔐</div>
                            <h1 style="color: #ffffff; margin: 0; font-size: 28px; font-family: sans-serif;">Admin Login Verification</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="content" style="padding: 40px 30px; font-family: sans-serif; font-size: 16px; line-height: 1.6; color: #334155;">
                            <p style="margin: 0 0 15px;">Hello,</p>
                            <p style="margin: 0 0 20px;">You have requested to log in to the OpenShelf Admin Panel. Use the following One-Time Password (OTP) to complete your login:</p>
                            
                            <div class="otp-box" style="background: #f8fafc; border: 2px dashed #6366f1; padding: 20px; text-align: center; font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #6366f1; border-radius: 16px; margin: 20px 0;">
                                <?php echo $otp; ?>
                            </div>
                            
                            <div class="warning-box" style="background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; border-radius: 12px; margin: 20px 0; font-size: 14px;">
                                <p style="margin: 0; font-weight: 600; color: #b91c1c;">⚠️ Security Notice</p>
                                <p style="margin: 10px 0 0 0; color: #991b1b;">This OTP is valid for <strong><?php echo $expiry_minutes; ?> minutes</strong>. Never share this code with anyone.</p>
                            </div>
                            
                            <p style="margin: 20px 0 10px;"><strong>Request Details:</strong></p>
                            <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin: 15px 0; border: 1px solid #e2e8f0;">
                                <div class="info-row"><div class="info-label">IP Address:</div> <div class="info-value"><?php echo htmlspecialchars($ip_address); ?></div></div>
                                <div class="info-row"><div class="info-label">Browser:</div> <div class="info-value"><?php echo htmlspecialchars($user_agent); ?></div></div>
                                <div class="info-row" style="border-bottom: none;"><div class="info-label">Time:</div> <div class="info-value"><?php echo date('Y-m-d H:i:s'); ?></div></div>
                            </div>
                            
                            <p style="font-size: 14px; color: #ef4444; margin-top: 20px;"><strong>If you didn't request this, please ignore this email and ensure your account security.</strong></p>
                            
                            <div style="text-align: center; margin-top: 25px;">
                                <a href="<?php echo $base_url; ?>/admin/login/" class="button" style="display: inline-block; padding: 14px 32px; background-color: #6366f1; color: #ffffff; text-decoration: none; border-radius: 40px; font-weight: 600; font-size: 16px;">Go to Login</a>
                            </div>
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