<?php
/**
 * Book Returned Email Template (Partial for owner)
 * 
 * Data passed:
 * $owner_name - Book owner's name
 * $book_title - Book title
 * $borrower_name - Borrower's name
 * $base_url - Base URL
 * $book_id - Book ID
 */
// Set theme for Mailer
$data['type'] = 'success';

// Handle URL safety
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
?>
<div class="greeting">Great news, <?php echo htmlspecialchars($owner_name ?? 'Owner'); ?>!</div>
<p style="text-align: center;">Your book has been returned and is ready for its next adventure. Thank you for sharing with the community!</p>

<div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 16px; padding: 25px; text-align: center; margin: 30px 0;">
    <div style="font-size: 40px; margin-bottom: 15px;">📖</div>
    <div style="font-size: 20px; font-weight: 800; color: #065f46; margin-bottom: 5px;">"<?php echo htmlspecialchars($book_title ?? 'the book'); ?>"</div>
    <div style="color: #059669; font-size: 14px; font-weight: 600;">Returned by <?php echo htmlspecialchars($borrower_name ?? 'a user'); ?></div>
</div>

<p style="text-align: center;">Your book is now available for others to borrow again. You can view its current status in your dashboard.</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo $safe_url; ?>/book/?id=<?php echo $book_id ?? ''; ?>" class="button">View My Book</a>
</div>