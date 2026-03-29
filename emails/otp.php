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
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; line-height: 1.6; color: #0f172a; background: #f8fafc; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .card { background: white; border-radius: 20px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); overflow: hidden; }
        .header { background: linear-gradient(135deg, #6366f1, #8b5cf6); padding: 40px 20px; text-align: center; position: relative; overflow: hidden; }
        .header::before { content: ''; position: absolute; top: -50%; right: -50%; width: 200%; height: 200%; background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%); animation: rotate 20s linear infinite; }
        @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }
        .logo { font-size: 48px; margin-bottom: 10px; position: relative; }
        .header h1 { color: white; margin: 0; font-size: 28px; position: relative; }
        .content { padding: 40px 30px; }
        .otp-box { background: #f1f5f9; border: 2px dashed #6366f1; padding: 20px; text-align: center; font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #6366f1; border-radius: 16px; margin: 20px 0; }
        .warning-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; border-radius: 12px; margin: 20px 0; font-size: 14px; }
        .info-row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #e2e8f0; }
        .footer { padding: 20px; text-align: center; border-top: 1px solid #e2e8f0; font-size: 12px; color: #64748b; background: #f8fafc; }
        @media (max-width: 480px) { .content { padding: 25px 20px; } .otp-box { font-size: 28px; letter-spacing: 4px; } }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <div class="logo">🔐</div>
                <h1>Admin Login Verification</h1>
            </div>
            <div class="content">
                <p>Hello,</p>
                <p>You have requested to log in to the OpenShelf Admin Panel. Use the following One-Time Password (OTP) to complete your login:</p>
                
                <div class="otp-box">
                    <?php echo $otp; ?>
                </div>
                
                <div class="warning-box">
                    <p style="margin: 0; font-weight: 600;">⚠️ Security Notice</p>
                    <p style="margin: 10px 0 0 0;">This OTP is valid for <strong><?php echo $expiry_minutes; ?> minutes</strong>. Never share this code with anyone.</p>
                </div>
                
                <p><strong>Request Details:</strong></p>
                <div style="background: #f8fafc; padding: 15px; border-radius: 12px; margin: 15px 0;">
                    <div class="info-row"><span>IP Address:</span> <span><?php echo htmlspecialchars($ip_address); ?></span></div>
                    <div class="info-row"><span>Browser:</span> <span><?php echo htmlspecialchars($user_agent); ?></span></div>
                    <div class="info-row"><span>Time:</span> <span><?php echo date('Y-m-d H:i:s'); ?></span></div>
                </div>
                
                <p style="font-size: 14px; color: #ef4444;"><strong>If you didn't request this, please ignore this email and ensure your account security.</strong></p>
                
                <div style="text-align: center; margin-top: 25px;">
                    <a href="<?php echo $base_url; ?>/admin/login/" class="button" style="display: inline-block; padding: 10px 24px; background: #6366f1; color: white; text-decoration: none; border-radius: 40px;">Go to Login</a>
                </div>
            </div>
            <div class="footer">
                <p>&copy; <?php echo date('Y'); ?> OpenShelf. All rights reserved.</p>
                <p>This is an automated message, please do not reply.</p>
            </div>
        </div>
    </div>
</body>
</html>