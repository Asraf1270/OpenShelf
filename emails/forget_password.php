<?php
/**
 * Forget Password Email Template (Partial)
 * 
 * Data passed:
 * $otp - Verification code
 * $expiry_minutes - Minutes until code expires
 * $base_url - Base URL
 */
// Set theme for Mailer
$data['type'] = 'info';

// Handle URL safety
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
?>
<div class="greeting">Password Reset Request</div>
<p style="text-align: center;">We received a request to reset your OpenShelf account password. Use the following verification code to proceed:</p>

<div style="background: #f1f5f9; border: 2px solid #e2e8f0; padding: 25px; text-align: center; font-size: 42px; font-weight: 800; letter-spacing: 10px; color: #4f46e5; border-radius: 16px; margin: 30px 0;">
    <?php echo $otp ?? '000000'; ?>
</div>

<div style="text-align: center; font-size: 14px; color: #64748b; margin-top: -10px; margin-bottom: 30px;">
    This code is valid for <strong><?php echo $expiry_minutes ?? '15'; ?> minutes</strong>.
</div>

<p style="text-align: center; font-size: 14px; color: #64748b;">If you did not request a password reset, you can safely ignore this email. For your security, never share this code with anyone.</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo $safe_url; ?>/login/reset_password.php" class="button">Reset My Password</a>
</div>

