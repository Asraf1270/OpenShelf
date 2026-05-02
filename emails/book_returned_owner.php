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

// Set theme for Mailer if possible
if (isset($data) && is_array($data)) {
    $data['type'] = 'success';
}

// Handle URL safety
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : 'https://openshelf.free.nf');
$display_owner = $owner_name ?? 'Owner';
$display_borrower = $borrower_name ?? 'a community member';
?>
<div style="font-size: 20px; font-weight: 700; color: #1e293b; margin-bottom: 15px;">Great news, <?php echo htmlspecialchars($display_owner); ?>!</div>

<p style="margin-bottom: 25px;">Your book has been returned and is now available for its next adventure. Thank you for sharing with the community!</p>

<div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 16px; padding: 25px; text-align: center; margin: 25px 0;">
    <div style="font-size: 40px; margin-bottom: 15px;">📖</div>
    <div style="font-size: 18px; font-weight: 800; color: #065f46; margin-bottom: 5px;">"<?php echo htmlspecialchars($book_title ?? 'Your Book'); ?>"</div>
    <div style="color: #059669; font-size: 14px; font-weight: 600;">Returned by <?php echo htmlspecialchars($display_borrower); ?></div>
</div>

<p style="text-align: center; color: #64748b; font-size: 14px; margin-top: 20px;">
    Your book is now marked as <strong>Available</strong> in the library. 
    You can view its status or manage your collection in your dashboard.
</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo $safe_url; ?>/book/?id=<?php echo htmlspecialchars($book_id ?? ''); ?>" 
       style="display: inline-block; padding: 12px 25px; background-color: #10b981; color: #ffffff; text-decoration: none; border-radius: 10px; font-weight: 600;">
        View My Book
    </a>
</div>