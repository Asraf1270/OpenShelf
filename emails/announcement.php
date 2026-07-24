<?php
/**
 * Admin Announcement Email Template (Partial)
 * 
 * Data passed:
 * $user_name - User's name
 * $announcement_title - Title of the announcement
 * $announcement_content - Content of the announcement
 * $announcement_priority - Priority level (info, success, warning, danger)
 * $announcement_link - Link to view announcement
 */
$priorityColors = [
    'info'    => '#3b82f6',
    'success' => '#10b981',
    'warning' => '#f59e0b',
    'danger'  => '#ef4444'
];
// Handle safely
$priority = $announcement_priority ?? 'info';
$priorityColor = $priorityColors[$priority] ?? '#3b82f6';

// Set theme for Mailer
$data['type'] = ($priority === 'danger') ? 'danger' : (($priority === 'warning') ? 'warning' : 'info');

$display_name = $user_name ?? 'Reader';
?>
<div class="greeting">Hello, <?php echo htmlspecialchars($display_name); ?></div>
<p style="text-align: center;">An important update has been posted to the OpenShelf community:</p>

<div style="background-color: #f8fafc; border-left: 4px solid <?php echo $priorityColor; ?>; border-radius: 0 16px 16px 0; padding: 25px; margin: 30px 0;">
    <div style="display: inline-block; padding: 4px 12px; background-color: <?php echo $priorityColor; ?>; color: #ffffff; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 15px;"><?php echo htmlspecialchars($announcement_priority ?? 'INFO'); ?></div>
    <div style="font-size: 22px; font-weight: 700; color: #0f172a; margin-bottom: 15px;"><?php echo htmlspecialchars($announcement_title ?? 'New Announcement'); ?></div>
    <div style="line-height: 1.7; color: #374151;">
        <?php echo nl2br(htmlspecialchars($announcement_content ?? '')); ?>
    </div>
</div>

<div style="text-align: center; margin-top: 30px;">
    <a href="<?php echo $announcement_link ?? '#'; ?>" class="button">View Announcement</a>
</div>