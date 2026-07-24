<?php
/**
 * Request Approved Email Template (Partial)
 * 
 * Data passed:
 * $user_name - Borrower name
 * $owner_name - Owner name
 * $book_title - Book title
 * $due_date - Expected return date
 * $owner_room - Owner's room number
 * $owner_phone - Owner's phone number
 * $base_url - Base URL
 */
// Set theme for Mailer
$data['type'] = 'success';

// Handle variable name variations
$display_name = $user_name ?? $borrower_name ?? 'Reader';
$display_date = $due_date ?? $expected_return ?? 'soon';
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
?>
<div class="greeting">🎉 Request Approved, <?php echo htmlspecialchars($display_name); ?>!</div>
<p style="text-align: center;">Great news! <strong><?php echo htmlspecialchars($owner_name ?? 'The owner'); ?></strong> has approved your request to borrow their book.</p>

<div style="font-weight: 700; font-size: 16px; color: #1e293b; margin-top: 30px; margin-bottom: 10px;">Pickup Details:</div>
<table style="width: 100%; border-collapse: collapse; margin: 15px 0; background-color: #f8fafc; border-radius: 12px; overflow: hidden;">
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b; width: 140px;">Book:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><strong>"<?php echo htmlspecialchars($book_title ?? 'Untitled'); ?>"</strong></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Owner:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo htmlspecialchars($owner_name ?? 'Owner'); ?></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Room:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo htmlspecialchars($owner_room ?? 'N/A'); ?></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Return By:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo (strtotime($display_date) > 0) ? date('F j, Y', strtotime($display_date)) : $display_date; ?></td></tr>
</table>

<p style="text-align: center; font-size: 14px; color: #64748b;">Please coordinate with the owner to pick up the book. Happy reading!</p>

<div style="text-align: center; margin-top: 30px;">
    <?php if (!empty($owner_phone)): ?>
    <a href="https://wa.me/88<?php echo preg_replace('/[^0-9]/', '', $owner_phone); ?>" style="display: inline-block; padding: 14px 35px; background-color: #25D366; color: #ffffff !important; text-decoration: none; border-radius: 12px; font-weight: 600; margin-bottom: 10px;">Contact on WhatsApp</a><br>
    <?php endif; ?>
    <a href="<?php echo $safe_url; ?>/requests/" class="button">View My Requests</a>
</div>