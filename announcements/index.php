<?php
/**
 * OpenShelf Announcements Page
 * Display all announcements to users
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/announcements/';
    header('Location: /login/');
    exit;
}

$currentUserId = $_SESSION['user_id'];

/**
 * Load announcements
 */
function loadAnnouncements() {
    $announcementsFile = DATA_PATH . 'announcements.json';
    if (!file_exists($announcementsFile)) {
        return ['announcements' => [], 'user_read_status' => []];
    }
    return json_decode(file_get_contents($announcementsFile), true) ?? ['announcements' => [], 'user_read_status' => []];
}

/**
 * Save announcements
 */
function saveAnnouncements($data) {
    $announcementsFile = DATA_PATH . 'announcements.json';
    return file_put_contents($announcementsFile, json_encode($data, JSON_PRETTY_PRINT));
}

// Load data
$data = loadAnnouncements();
$announcements = $data['announcements'];
$userReadStatus = $data['user_read_status'];

// Mark announcement as read if viewing specific one
$selectedId = $_GET['id'] ?? '';
if ($selectedId) {
    $alreadyRead = false;
    foreach ($userReadStatus as $status) {
        if ($status['announcement_id'] === $selectedId && $status['user_id'] === $currentUserId) {
            $alreadyRead = true;
            break;
        }
    }
    
    if (!$alreadyRead) {
        $userReadStatus[] = [
            'announcement_id' => $selectedId,
            'user_id' => $currentUserId,
            'read_at' => date('Y-m-d H:i:s')
        ];
        
        // Update stats
        foreach ($announcements as &$ann) {
            if ($ann['id'] === $selectedId) {
                $ann['stats']['read']++;
                break;
            }
        }
        
        saveAnnouncements(['announcements' => $announcements, 'user_read_status' => $userReadStatus]);
    }
    
    // Find selected announcement
    $selectedAnnouncement = null;
    foreach ($announcements as $ann) {
        if ($ann['id'] === $selectedId) {
            $selectedAnnouncement = $ann;
            break;
        }
    }
}

// Filter active announcements
$activeAnnouncements = array_filter($announcements, function($ann) {
    // Check if expired
    if (!empty($ann['expires_at']) && strtotime($ann['expires_at']) < time()) {
        return false;
    }
    // Check if scheduled (not yet sent)
    if (!empty($ann['scheduled_for']) && strtotime($ann['scheduled_for']) > time()) {
        return false;
    }
    return true;
});

// Sort by date (newest first)
usort($activeAnnouncements, fn($a, $b) => strtotime($b['created_at']) - strtotime($a['created_at']));

include dirname(__DIR__) . '/includes/header.php';
?>

<div class="container" style="max-width: 800px; margin: 0 auto; padding: var(--space-5);">
    <!-- Page Header -->
    <div style="margin-bottom: var(--space-6);">
        <h1 style="font-size: var(--font-size-xl);">
            <i class="fas fa-bullhorn" style="color: var(--primary);"></i>
            Announcements
        </h1>
        <p style="color: var(--text-tertiary);">Important updates and news from the OpenShelf team</p>
    </div>
    
    <?php if ($selectedAnnouncement): ?>
        <!-- Single Announcement View -->
        <a href="/announcements/" class="btn btn-outline btn-sm mb-4">
            <i class="fas fa-arrow-left"></i> Back to All Announcements
        </a>
        
        <div class="card">
            <div class="card-header">
                <h2 class="card-title"><?php echo htmlspecialchars($selectedAnnouncement['title']); ?></h2>
                <div class="flex justify-between items-center mt-2">
                    <span class="badge badge-<?php echo $selectedAnnouncement['priority'] === 'danger' ? 'danger' : ($selectedAnnouncement['priority'] === 'warning' ? 'warning' : ($selectedAnnouncement['priority'] === 'success' ? 'success' : 'primary')); ?>">
                        <?php echo strtoupper($selectedAnnouncement['priority'] ?? 'INFO'); ?>
                    </span>
                    <span class="text-muted" style="font-size: var(--font-size-xs);">
                        <i class="far fa-calendar"></i> <?php echo date('F j, Y', strtotime($selectedAnnouncement['created_at'])); ?>
                    </span>
                </div>
            </div>
            <div class="card-body">
                <div style="line-height: 1.8;">
                    <?php echo nl2br(htmlspecialchars($selectedAnnouncement['content'])); ?>
                </div>
            </div>
        </div>
        
    <?php else: ?>
        <!-- All Announcements List -->
        <?php if (empty($activeAnnouncements)): ?>
            <div class="card text-center" style="padding: var(--space-8);">
                <i class="fas fa-bullhorn" style="font-size: 3rem; color: var(--text-disabled); margin-bottom: var(--space-4);"></i>
                <h3>No Announcements</h3>
                <p class="text-muted">Check back later for updates from the OpenShelf team.</p>
            </div>
        <?php else: ?>
            <?php foreach ($activeAnnouncements as $announcement): 
                $isRead = false;
                foreach ($userReadStatus as $status) {
                    if ($status['announcement_id'] === $announcement['id'] && $status['user_id'] === $currentUserId) {
                        $isRead = true;
                        break;
                    }
                }
                $priorityColor = $announcement['priority'] === 'danger' ? 'danger' : ($announcement['priority'] === 'warning' ? 'warning' : ($announcement['priority'] === 'success' ? 'success' : 'primary'));
            ?>
                <div class="card mb-4 <?php echo !$isRead ? 'border-l-4 border-l-primary' : ''; ?>">
                    <div class="card-body">
                        <div class="flex justify-between items-start mb-2">
                            <h3 class="card-title mb-0">
                                <a href="?id=<?php echo $announcement['id']; ?>" class="text-dark hover:text-primary">
                                    <?php echo htmlspecialchars($announcement['title']); ?>
                                </a>
                            </h3>
                            <span class="badge badge-<?php echo $priorityColor; ?>">
                                <?php echo strtoupper($announcement['priority'] ?? 'INFO'); ?>
                            </span>
                        </div>
                        <div class="announcement-preview">
                            <?php echo nl2br(htmlspecialchars(substr($announcement['content'], 0, 200))); ?>
                            <?php if (strlen($announcement['content']) > 200): ?>
                                <a href="?id=<?php echo $announcement['id']; ?>" class="text-primary">Read more</a>
                            <?php endif; ?>
                        </div>
                        <div class="flex justify-between items-center mt-3">
                            <span class="text-muted" style="font-size: var(--font-size-xs);">
                                <i class="far fa-calendar"></i> <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?>
                            </span>
                            <?php if (!$isRead): ?>
                                <span class="badge badge-primary">New</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>