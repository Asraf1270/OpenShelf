<?php
/**
 * Welcome Email Template (Partial)
 * 
 * Data passed:
 * $user_name - New user's name
 * $login_url - Login page URL
 * $base_url - Base URL
 */
// Set theme for Mailer
$data['type'] = 'info';

// Handle URL safety
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
$final_login_url = $login_url ?? ($safe_url . '/login/');
?>
<div class="greeting">Welcome to the family, <?php echo htmlspecialchars($user_name ?? 'Reader'); ?>! 👋</div>
<p style="text-align: center;">We're thrilled to have you join our community of book lovers. You're now one step closer to sharing and discovering amazing books.</p>

<div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 16px; padding: 20px; text-align: center; margin: 25px 0;">
    <p style="margin: 0; font-weight: 700; color: #166534;">Registration Successful! 🎉</p>
    <p style="margin: 10px 0 0 0; font-size: 14px; color: #15803d;">Your account is ready. You can now start exploring and contributing to our community library.</p>
</div>

<div style="font-weight: 700; font-size: 16px; color: #1e293b; margin-top: 30px; margin-bottom: 15px;">What's next?</div>

<div style="background-color: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 12px; border: 1px solid #f1f5f9;">
    <div style="display: flex; align-items: center; gap: 15px;">
        <span style="background-color: #4f46e5; color: #ffffff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">1</span>
        <span style="font-size: 14px; margin-left: 10px;"><strong>Complete Your Profile</strong> – Add your hall and contact details to connect with others.</span>
    </div>
</div>

<div style="background-color: #f8fafc; border-radius: 12px; padding: 15px; margin-bottom: 12px; border: 1px solid #f1f5f9;">
    <div style="display: flex; align-items: center; gap: 15px;">
        <span style="background-color: #4f46e5; color: #ffffff; width: 28px; height: 28px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 700; flex-shrink: 0;">2</span>
        <span style="font-size: 14px; margin-left: 10px;"><strong>Add Your Collection</strong> – Start by listing books you're willing to share.</span>
    </div>
</div>

<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo $final_login_url; ?>" class="button">Go to Dashboard</a>
</div>