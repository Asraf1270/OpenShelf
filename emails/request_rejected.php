<?php
/**
 * Request Rejected Email Template (Partial)
 * 
 * Data passed:
 * $user_name - Borrower name
 * $book_title - Book title
 * $rejection_reason - Reason for rejection
 * $base_url - Base URL
 */
// Set theme for Mailer
$data['type'] = 'neutral';

// Handle variable name variations
$display_name = $user_name ?? $borrower_name ?? 'Reader';
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
?>
<div class="greeting">Hello, <?php echo htmlspecialchars($display_name); ?></div>
<p style="text-align: center;">We wanted to let you know that your request for <strong>"<?php echo htmlspecialchars($book_title ?? 'the book'); ?>"</strong> has been declined by the owner.</p>

<?php if (!empty($rejection_reason)): ?>
<div style="background-color: #fef2f2; border: 1px solid #fecaca; border-radius: 16px; padding: 25px; margin: 30px 0;">
    <p style="font-weight: 700; color: #991b1b; margin: 0 0 10px 0;">Reason provided:</p>
    <p style="color: #b91c1c; margin: 0;"><?php echo nl2br(htmlspecialchars($rejection_reason)); ?></p>
</div>
<?php endif; ?>

<p style="text-align: center; font-size: 14px; color: #64748b;">Don't worry! There are plenty of other books available. You can try requesting a different book or browse the library for new arrivals.</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo $safe_url; ?>/books/" class="button">Browse More Books</a>
</div>