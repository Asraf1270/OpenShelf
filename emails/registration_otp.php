<?php
/**
 * Registration OTP Email Template (Partial)
 * 
 * Data passed:
 * $otp - OTP code
 * $expiry_minutes - Minutes until OTP expires
 * $user_name - Name of the user
 * $base_url - Base URL
 * $ip_address - IP address of requester
 */
// Set theme for Mailer
$data['type'] = 'info';

// Handle URL safety
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
$display_name = $user_name ?? 'Friend';
?>
<div class="greeting">Welcome to the community, <?php echo htmlspecialchars($display_name); ?>!</div>
<p style="text-align: center;">Thank you for joining OpenShelf. To complete your registration and verify your email address, please use the verification code below:</p>

<div style="background: #f1f5f9; border: 2px solid #e2e8f0; padding: 25px; text-align: center; font-size: 42px; font-weight: 800; letter-spacing: 10px; color: #4f46e5; border-radius: 16px; margin: 30px 0;">
    <?php echo $otp ?? '000000'; ?>
</div>

<div style="text-align: center; font-size: 14px; color: #64748b; margin-top: -10px; margin-bottom: 30px;">
    This code will expire in <strong><?php echo $expiry_minutes ?? '15'; ?> minutes</strong>.
</div>

<p style="text-align: center; font-size: 14px; color: #64748b;">If you did not create an account on OpenShelf, you can safely ignore this email.</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo $safe_url; ?>/register/verify.php" class="button">Verify My Account</a>
</div>

<div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #f1f5f9; font-size: 11px; color: #94a3b8; text-align: center;">
    Requester IP: <?php echo htmlspecialchars($ip_address ?? 'N/A'); ?>
</div>
