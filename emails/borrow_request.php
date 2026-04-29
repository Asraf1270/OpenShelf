<?php
/**
 * Borrow Request Email Template (Partial)
 * 
 * Data passed:
 * $owner_name - Owner name
 * $borrower_name - Requester's name
 * $book_title - Book title
 * $book_author - Book author
 * $duration_days - Requested duration
 * $message - Personal message from borrower
 * $borrower_department - Borrower's department
 * $borrower_phone - Borrower's phone number
 * $base_url - Base URL
 */
// Set theme for Mailer
$data['type'] = 'warning';

// Handle URL safety
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
?>
<div class="greeting">Hello, <?php echo htmlspecialchars($owner_name ?? 'Owner'); ?></div>
<p style="text-align: center;">Good news! Someone is interested in borrowing your book. Here are the details:</p>

<div style="background-color: #fffbeb; border: 1px solid #fde68a; border-radius: 16px; padding: 25px; margin: 25px 0; text-align: center;">
    <div style="font-size: 30px; margin-bottom: 10px;">📖</div>
    <div style="font-size: 20px; font-weight: 800; color: #92400e;">"<?php echo htmlspecialchars($book_title ?? 'Untitled'); ?>"</div>
    <div style="color: #b45309; font-size: 14px;">by <?php echo htmlspecialchars($book_author ?? 'Unknown'); ?></div>
</div>

<div style="font-weight: 700; font-size: 16px; color: #1e293b; margin-top: 30px;">Borrower Information:</div>
<table style="width: 100%; border-collapse: collapse; margin: 15px 0; background-color: #f8fafc; border-radius: 12px; overflow: hidden;">
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b; width: 140px;">Name:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo htmlspecialchars($borrower_name ?? 'A user'); ?></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Department:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo htmlspecialchars($borrower_department ?? 'N/A'); ?></td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Duration:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo $duration_days ?? '14'; ?> days</td></tr>
    <tr><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; font-weight: 700; color: #64748b;">Phone:</td><td style="padding: 12px 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px;"><?php echo htmlspecialchars($borrower_phone ?? 'N/A'); ?></td></tr>
</table>

<?php if (!empty($message)): ?>
<div style="font-weight: 700; font-size: 16px; color: #1e293b; margin-top: 20px;">Message:</div>
<div style="background-color: #f1f5f9; border-radius: 12px; padding: 20px; margin: 15px 0; font-style: italic; color: #475569;">
    "<?php echo nl2br(htmlspecialchars($message)); ?>"
</div>
<?php endif; ?>

<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo $safe_url; ?>/requests/" class="button">Respond to Request</a>
</div>