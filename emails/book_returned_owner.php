<?php
/**
 * Book Return Confirmation Email (Owner)
 *
 * Variables:
 *  $owner_name      - Book owner's name
 *  $book_title      - Book title
 *  $borrower_name   - Borrower's name
 *  $return_date     - Date borrower filed the return
 *  $return_condition - Book condition reported by borrower
 *  $return_notes    - Optional notes from borrower
 *  $confirm_url     - Full URL to confirm physical receipt
 *  $reject_url      - Full URL to reject (flag not received)
 *  $book_id         - Book ID
 *  $base_url        - Base URL of the site
 */

if (isset($data) && is_array($data)) {
    $data['type'] = 'warning';
}

$safe_url         = $base_url ?? (defined('BASE_URL') ? BASE_URL : 'https://duopenshelf.top');
$display_owner    = htmlspecialchars($owner_name ?? 'Owner');
$display_borrower = htmlspecialchars($borrower_name ?? 'a community member');
$display_title    = htmlspecialchars($book_title ?? 'Your Book');
$display_date     = htmlspecialchars($return_date ?? date('Y-m-d'));
$display_condition = match(trim($return_condition ?? '')) {
    'damaged' => '⚠️ Has Damage',
    'same'    => '✅ Good / Intact',
    default   => htmlspecialchars(ucfirst($return_condition ?? 'Not specified')),
};
$display_notes    = htmlspecialchars($return_notes ?? '');
$safe_confirm     = htmlspecialchars($confirm_url ?? '#');
$safe_reject      = htmlspecialchars($reject_url ?? '#');
?>

<!-- Greeting -->
<div style="font-size:20px;font-weight:700;color:#1e293b;margin-bottom:12px;">
    Hi <?php echo $display_owner; ?>, action required!
</div>

<p style="margin-bottom:20px;color:#475569;">
    <strong><?php echo $display_borrower; ?></strong> has filed a return for your book.
    Please confirm that you have <strong>physically received</strong> the book back before it
    is marked as <em>Available</em> in the library.
</p>

<!-- Book card -->
<div style="background:#fffbeb;border:1px solid #fde68a;border-radius:16px;padding:24px;margin:20px 0;">
    <div style="font-size:36px;text-align:center;margin-bottom:12px;">📖</div>
    <div style="font-size:18px;font-weight:800;color:#92400e;text-align:center;margin-bottom:6px;">
        "<?php echo $display_title; ?>"
    </div>
    <div style="text-align:center;color:#b45309;font-size:14px;font-weight:600;margin-bottom:16px;">
        Returned by <?php echo $display_borrower; ?>
    </div>

    <table style="width:100%;border-collapse:collapse;font-size:14px;">
        <tr style="border-bottom:1px solid #fde68a;">
            <td style="padding:8px 4px;color:#78716c;width:40%;">📅 Return Date</td>
            <td style="padding:8px 4px;color:#1e293b;font-weight:600;"><?php echo $display_date; ?></td>
        </tr>
        <tr style="border-bottom:1px solid #fde68a;">
            <td style="padding:8px 4px;color:#78716c;">📚 Condition Reported</td>
            <td style="padding:8px 4px;color:#1e293b;font-weight:600;"><?php echo $display_condition; ?></td>
        </tr>
        <?php if (!empty($display_notes)): ?>
        <tr>
            <td style="padding:8px 4px;color:#78716c;vertical-align:top;">💬 Notes</td>
            <td style="padding:8px 4px;color:#1e293b;"><?php echo $display_notes; ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<!-- Action required banner -->
<div style="background:#fef3c7;border-left:4px solid #f59e0b;border-radius:8px;padding:14px 16px;margin:20px 0;font-size:14px;color:#92400e;">
    <strong>⚡ Action Required:</strong> The book will <u>not</u> be marked as returned until you confirm
    physical receipt below. Please only confirm once you have the book in hand.
</div>

<!-- CTA buttons -->
<table style="width:100%;margin:28px 0 8px;" cellspacing="0" cellpadding="0">
    <tr>
        <td style="text-align:center;padding-right:8px;">
            <a href="<?php echo $safe_confirm; ?>"
               style="display:inline-block;padding:14px 28px;background:#10b981;color:#fff;text-decoration:none;border-radius:12px;font-weight:700;font-size:15px;letter-spacing:0.3px;">
                ✅ Yes, I Received It
            </a>
        </td>
        <td style="text-align:center;padding-left:8px;">
            <a href="<?php echo $safe_reject; ?>"
               style="display:inline-block;padding:14px 28px;background:#ef4444;color:#fff;text-decoration:none;border-radius:12px;font-weight:700;font-size:15px;letter-spacing:0.3px;">
                ❌ I Have Not Received It
            </a>
        </td>
    </tr>
</table>

<p style="text-align:center;color:#94a3b8;font-size:12px;margin-top:24px;">
    If the buttons don't work, copy this link into your browser:<br>
    <a href="<?php echo $safe_confirm; ?>" style="color:#10b981;word-break:break-all;">
        <?php echo $safe_confirm; ?>
    </a>
</p>