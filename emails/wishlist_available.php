<?php
/**
 * Wishlist Available Email Template (Partial)
 *
 * Data passed:
 * $user_name     – Wishlist user's name
 * $book_title    – Book title
 * $book_author   – Book author
 * $book_id       – Book ID (for link)
 * $base_url      – Base URL
 * $queue_position – User's position in wishlist (1 = first in line)
 */

// Set theme colour for Mailer
$data['type'] = 'success';

$safe_url   = $base_url ?? (defined('BASE_URL') ? BASE_URL : '');
$book_link  = $safe_url . '/book/?id=' . urlencode($book_id ?? '');
?>

<div class="greeting">Hello, <?php echo htmlspecialchars($user_name ?? 'Reader'); ?> 👋</div>

<p style="text-align: center; color: #475569; margin-bottom: 0;">
    Great news! A book on your wishlist is now available to borrow.
</p>

<!-- Book card -->
<div style="background: linear-gradient(135deg, #f0fdf4, #dcfce7); border: 1px solid #bbf7d0; border-radius: 16px; padding: 28px; margin: 28px 0; text-align: center;">
    <div style="font-size: 36px; margin-bottom: 12px;">📗</div>
    <div style="font-size: 22px; font-weight: 800; color: #15803d; margin-bottom: 6px;">
        "<?php echo htmlspecialchars($book_title ?? 'Untitled'); ?>"
    </div>
    <div style="color: #166534; font-size: 14px; font-weight: 500;">
        by <?php echo htmlspecialchars($book_author ?? 'Unknown Author'); ?>
    </div>
</div>

<?php if (!empty($queue_position) && $queue_position == 1): ?>
<div style="background: #fffbeb; border: 1px solid #fde68a; border-radius: 12px; padding: 16px 20px; margin: 0 0 24px; display: flex; align-items: center; gap: 12px;">
    <span style="font-size: 24px;">🥇</span>
    <div>
        <div style="font-weight: 700; color: #92400e; font-size: 15px;">You're first in line!</div>
        <div style="font-size: 13px; color: #b45309; margin-top: 2px;">
            Be quick — request to borrow before someone else does.
        </div>
    </div>
</div>
<?php else: ?>
<div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 20px; margin: 0 0 24px; font-size: 14px; color: #475569;">
    You added this book to your wishlist. Head over and request it now before it's gone!
</div>
<?php endif; ?>

<div style="text-align: center; margin-top: 10px;">
    <a href="<?php echo $book_link; ?>" class="button"
       style="background-color: #16a34a; padding: 14px 40px; font-size: 16px;">
        Borrow This Book &rarr;
    </a>
</div>

<p style="text-align: center; font-size: 12px; color: #94a3b8; margin-top: 28px;">
    You received this because you wishlisted this book on OpenShelf.<br>
    This notification will not be sent again for the same availability window.
</p>
