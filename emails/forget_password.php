<?php
/**
 * Forget Password Email Template
 * 
 * Variables:
 * $otp - Verification code
 * $expiry_minutes - Minutes until code expires
 */
?>
<p style="margin: 0 0 20px;">We received a request to reset your OpenShelf account password. Use the following verification code to proceed:</p>

<div class="otp-box" style="background: #f8fafc; border: 2px dashed #667eea; padding: 20px; text-align: center; font-size: 36px; font-weight: bold; letter-spacing: 10px; color: #667eea; border-radius: 10px; margin: 20px 0;">
    <?php echo $otp; ?>
</div>

<p class="warning" style="color: #f5365c; font-size: 14px; margin: 20px 0;">
    This code is valid for <strong><?php echo $expiry_minutes; ?> minutes</strong>. If you did not request a password reset, please ignore this email or contact support if you're concerned about your account security.
</p>

<p class="text-muted" style="color: #8898aa; font-size: 14px; border-top: 1px solid #e9ecef; padding-top: 15px; margin-top: 15px;">
    For your security, never share this code with anyone. Our team will never ask for your verification code.
</p>
