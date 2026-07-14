<?php
/**
 * Return Rejected – Email to Borrower
 *
 * Variables:
 *  $borrower_name - Borrower's name
 *  $book_title    - Book title
 *  $owner_name    - Owner's name
 *  $reject_reason - Optional reason from owner
 *  $book_id       - Book ID
 *  $base_url      - Base URL
 */

if (isset($data) && is_array($data)) {
    $data['type'] = 'danger';
}

$safe_url         = $base_url ?? (defined('BASE_URL') ? BASE_URL : 'https://duopenshelf.top');
$display_borrower = htmlspecialchars($borrower_name ?? 'Borrower');
$display_owner    = htmlspecialchars($owner_name ?? 'the owner');
$display_title    = htmlspecialchars($book_title ?? 'Your Book');
$display_reason   = htmlspecialchars($reject_reason ?? '');
?>

<div style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:12px;">
    Action Required, <?php echo $display_borrower; ?>
</div>

<p style="margin-bottom:20px;color:#475569;">
    <strong><?php echo $display_owner; ?></strong> has indicated that they have <strong>not yet
    physically received</strong> the book you filed a return for. Please contact the owner to
    arrange delivery/pick-up.
</p>

<div style="background:#fef2f2;border:1px solid #fecaca;border-radius:16px;padding:22px;margin:20px 0;">
    <div style="font-size:32px;text-align:center;margin-bottom:10px;">⚠️</div>
    <div style="font-size:17px;font-weight:800;color:#991b1b;text-align:center;margin-bottom:6px;">
        "<?php echo $display_title; ?>"
    </div>
    <div style="text-align:center;color:#dc2626;font-size:14px;font-weight:600;">
        Return not confirmed by <?php echo $display_owner; ?>
    </div>
    <?php if (!empty($display_reason)): ?>
    <div style="margin-top:14px;padding-top:12px;border-top:1px solid #fecaca;font-size:13px;color:#7f1d1d;">
        <strong>Owner's message:</strong> <?php echo $display_reason; ?>
    </div>
    <?php endif; ?>
</div>

<p style="color:#475569;font-size:14px;">
    The book remains marked as <strong>Borrowed</strong> until the owner confirms receipt.
    Please ensure the book is returned to the owner and ask them to confirm via their email link
    or notifications.
</p>

<div style="text-align:center;margin-top:28px;">
    <a href="<?php echo $safe_url; ?>/requests/"
       style="display:inline-block;padding:12px 28px;background:#ef4444;color:#fff;text-decoration:none;border-radius:12px;font-weight:700;">
        View My Requests
    </a>
</div>
