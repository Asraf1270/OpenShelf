<?php
/**
 * Return Confirmed – Email to Borrower
 *
 * Variables:
 *  $borrower_name - Borrower's name
 *  $book_title    - Book title
 *  $owner_name    - Owner's name
 *  $confirm_date  - Date/time the owner confirmed
 *  $book_id       - Book ID
 *  $base_url      - Base URL of the site
 */

if (isset($data) && is_array($data)) {
    $data['type'] = 'success';
}

$safe_url         = $base_url ?? (defined('BASE_URL') ? BASE_URL : 'https://duopenshelf.top');
$display_borrower = htmlspecialchars($borrower_name ?? 'Borrower');
$display_owner    = htmlspecialchars($owner_name ?? 'the owner');
$display_title    = htmlspecialchars($book_title ?? 'Your Book');
$display_date     = htmlspecialchars($confirm_date ?? date('Y-m-d'));
?>

<div style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:12px;">
    Great news, <?php echo $display_borrower; ?>!
</div>

<p style="margin-bottom:20px;color:#475569;">
    <strong><?php echo $display_owner; ?></strong> has confirmed that they received
    the book back. Your return is now <strong>complete</strong> — thank you for
    being a great member of the OpenShelf community! 🎉
</p>

<!-- Book confirmed card -->
<div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:16px;padding:24px;text-align:center;margin:20px 0;">
    <div style="font-size:40px;margin-bottom:12px;">📖</div>
    <div style="font-size:18px;font-weight:800;color:#065f46;margin-bottom:6px;">
        "<?php echo $display_title; ?>"
    </div>
    <div style="color:#059669;font-size:14px;font-weight:600;margin-bottom:4px;">
        Return confirmed by <?php echo $display_owner; ?>
    </div>
    <div style="color:#6b7280;font-size:13px;">
        <?php echo $display_date; ?>
    </div>
</div>

<p style="text-align:center;color:#64748b;font-size:14px;margin-top:16px;">
    The book is now marked as <strong>Available</strong> for the community.
    Feel free to borrow another book anytime!
</p>

<div style="text-align:center;margin-top:28px;">
    <a href="<?php echo $safe_url; ?>/books/"
       style="display:inline-block;padding:12px 28px;background:#10b981;color:#fff;text-decoration:none;border-radius:12px;font-weight:700;">
        Browse More Books
    </a>
</div>
