<?php
/**
 * Overdue Book Email Template (Partial)
 * 
 * Data passed:
 * $borrower_name - Borrower's name
 * $book_title - Book title
 * $due_date - Expected return date
 * $overdue_days - Days overdue
 * $owner_name - Book owner's name
 * $owner_phone - Owner's phone number
 * $base_url - Base URL
 */
// Set theme for Mailer
$data['type'] = 'danger';

// Handle URL safety
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
$display_name = $borrower_name ?? $user_name ?? 'Reader';
?>
<div class="greeting">Hello, <?php echo htmlspecialchars($display_name); ?></div>
<p style="text-align: center;">This is an urgent reminder that a book you borrowed is now overdue. Please arrange to return it as soon as possible.</p>

<div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 16px; padding: 25px; text-align: center; margin: 30px 0;">
    <p style="font-weight: 700; color: #991b1b; margin: 0;">Overdue Status:</p>
    <div style="font-size: 48px; font-weight: 900; color: #ef4444; margin: 10px 0;"><?php echo $overdue_days ?? '0'; ?> Days</div>
    <p style="color: #b91c1c; font-weight: 600; margin: 0;">Past Due Date</p>
</div>

<table style="width: 100%; border-collapse: collapse; margin: 25px 0; background-color: #f8fafc; border-radius: 12px; overflow: hidden;">
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b; width: 140px;">Book:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><strong>"<?php echo htmlspecialchars($book_title ?? 'Untitled'); ?>"</strong></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Due Date:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo (isset($due_date) && strtotime($due_date) > 0) ? date('F j, Y', strtotime($due_date)) : 'N/A'; ?></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Owner:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo htmlspecialchars($owner_name ?? 'Owner'); ?></td></tr>
</table>

<p style="text-align: center; font-size: 14px; color: #64748b;">Please contact the owner to coordinate the return. Being prompt helps keep our community trusted and reliable.</p>

<div style="text-align: center; margin-top: 30px;">
    <?php if (!empty($owner_phone)): ?>
    <a href="https://wa.me/88<?php echo preg_replace('/[^0-9]/', '', $owner_phone); ?>" style="display: inline-block; padding: 14px 35px; background-color: #25D366; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 600; margin-bottom: 10px;">Contact via WhatsApp</a><br>
    <?php endif; ?>
    <a href="<?php echo $safe_url; ?>/requests/" class="button">View My Borrowed Books</a>
</div>