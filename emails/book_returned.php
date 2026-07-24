<?php
/**
 * Book Returned Email Template (Partial for borrower)
 * 
 * Data passed:
 * $borrower_name - Borrower's name
 * $book_title - Title of the returned book
 * $return_date - Date of return
 * $base_url - Base URL
 */
// Set theme for Mailer
$data['type'] = 'success';

// Handle URL safety
$safe_url = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
$display_name = $borrower_name ?? $user_name ?? 'Reader';
?>
<div class="greeting">Thank you, <?php echo htmlspecialchars($display_name); ?>!</div>
<p style="text-align: center;">We've successfully processed the return of the book you borrowed. We hope it was an enlightening read!</p>

<div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 16px; padding: 25px; text-align: center; margin: 30px 0;">
    <div style="font-size: 40px; margin-bottom: 15px;">📚</div>
    <div style="font-size: 20px; font-weight: 800; color: #065f46; margin-bottom: 5px;">"<?php echo htmlspecialchars($book_title ?? 'the book'); ?>"</div>
    <div style="color: #059669; font-size: 14px; font-weight: 600;">Returned on <?php echo (isset($return_date) && strtotime($return_date) > 0) ? date('F j, Y', strtotime($return_date)) : date('F j, Y'); ?></div>
</div>

<p style="text-align: center;">What's next? Browse our collection and find your next favorite book today.</p>

<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo $safe_url; ?>/books/" class="button">Find My Next Book</a>
</div>