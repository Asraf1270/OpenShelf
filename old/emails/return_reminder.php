<?php
/**
 * Return Reminder Email Template (Partial)
 * 
 * Data passed:
 * $borrower_name - Borrower's name
 * $book_title - Book title
 * $due_date - Expected return date
 * $days_remaining - Days until due (negative if overdue)
 * $owner_name - Book owner's name
 * $base_url - Base URL
 */
$days = $days_remaining ?? 0;
$isOverdue = $days < 0;
$overdueDays = abs($days);
$themeColor = $isOverdue ? '#ef4444' : '#f59e0b';
$themeBg = $isOverdue ? '#fef2f2' : '#fffbeb';
$themeBorder = $isOverdue ? '#fecaca' : '#fde68a';

// Set theme for Mailer
$data['type'] = $isOverdue ? 'danger' : 'warning';

// Handle URL safety
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
$display_name = $borrower_name ?? $user_name ?? 'Reader';
?>
<div class="greeting">Hello, <?php echo htmlspecialchars($display_name); ?></div>
<p style="text-align: center;"><?php echo $isOverdue ? 'This is a reminder that the book you borrowed is past its due date.' : 'This is a friendly reminder that a book you borrowed is due soon.'; ?></p>

<div style="background-color: <?php echo $themeBg; ?>; border: 1px solid <?php echo $themeBorder; ?>; border-radius: 16px; padding: 25px; text-align: center; margin: 30px 0;">
    <p style="font-weight: 700; color: <?php echo $themeColor; ?>; margin: 0;"><?php echo $isOverdue ? 'Overdue Status:' : 'Time Remaining:'; ?></p>
    <div style="font-size: 48px; font-weight: 900; color: <?php echo $themeColor; ?>; margin: 10px 0;"><?php echo $isOverdue ? $overdueDays : $days; ?> Days</div>
    <p style="color: <?php echo $themeColor; ?>; font-weight: 600; margin: 0;"><?php echo $isOverdue ? 'Past Return Date' : 'Until Return Date'; ?></p>
</div>

<table style="width: 100%; border-collapse: collapse; margin: 25px 0; background-color: #f8fafc; border-radius: 12px; overflow: hidden;">
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b; width: 140px;">Book:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><strong>"<?php echo htmlspecialchars($book_title ?? 'Untitled'); ?>"</strong></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Owner:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo htmlspecialchars($owner_name ?? 'Owner'); ?></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Due Date:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo (isset($due_date) && strtotime($due_date) > 0) ? date('F j, Y', strtotime($due_date)) : 'N/A'; ?></td></tr>
</table>

<p style="text-align: center; font-size: 14px; color: #64748b;">Please plan to return the book by the due date to ensure others can enjoy it too.</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo $safe_url; ?>/requests/" class="button">View My Borrowed Books</a>
</div>