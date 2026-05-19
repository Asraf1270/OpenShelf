<?php
/**
 * OpenShelf Settings Dashboard Menu
 * Acts as the hub to navigate to separate settings sub-pages
 */

session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/settings/';
    header('Location: /login/');
    exit;
}

$userId = $_SESSION['user_id'];

// Include header
require_once dirname(__DIR__) . '/includes/header.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Fetch current user details
$db = getDB();
$stmt = $db->prepare("SELECT name, email, department, hall, profile_pic FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("User not found.");
}

// Format avatar path
$avatarName = $user['profile_pic'] ?: 'default-avatar.jpg';
$avatarPath = '/uploads/profile/' . $avatarName;
if (!file_exists(dirname(__DIR__) . $avatarPath) || $avatarName === 'default-avatar.jpg') {
    $avatarPath = '/assets/images/avatars/default.jpg';
}

$hallName = getHallName($user['hall'] ?? '');
?>

<style>
    :root {
        --hub-bg: #f8fafc;
        --hub-card-bg: rgba(255, 255, 255, 0.9);
        --hub-border: rgba(44, 62, 80, 0.12);
        --hub-hover: #f1f5f9;
        --hub-radius: 24px;
        --hub-inner-radius: 16px;
        --hub-transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        --hub-shadow: 0 10px 30px rgba(0, 0, 0, 0.04);
        --hub-active-teal: #4C9F8A;
    }

    :root[data-theme="dark"] {
        --hub-bg: #0f172a;
        --hub-card-bg: #1e293b;
        --hub-border: #334155;
        --hub-hover: #1e293b;
        --hub-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .settings-hub-wrapper {
        min-height: calc(100vh - 140px);
        background: var(--hub-bg);
        color: var(--text-primary);
        padding: 2.5rem 1rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        transition: var(--hub-transition);
    }

    .settings-hub-container {
        width: 100%;
        max-width: 680px;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
        animation: hubEntrance 0.5s ease-out;
    }

    @keyframes hubEntrance {
        from { opacity: 0; transform: translateY(15px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* =========================================
       USER SUMMARY PROFILE CARD
    ========================================= */
    .user-summary-card {
        background: var(--hub-card-bg);
        border: 1px solid var(--hub-border);
        border-radius: var(--hub-radius);
        padding: 1.75rem;
        box-shadow: var(--hub-shadow);
        backdrop-filter: blur(10px);
        display: flex;
        align-items: center;
        gap: 1.5rem;
        position: relative;
        overflow: hidden;
        flex-wrap: wrap;
    }

    @media (max-width: 480px) {
        .user-summary-card {
            flex-direction: column;
            text-align: center;
            padding: 1.5rem 1.25rem;
            gap: 1rem;
        }
    }

    .user-summary-avatar {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid var(--hub-border);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        flex-shrink: 0;
    }

    .user-summary-details {
        flex: 1;
        min-width: 0;
    }

    .user-summary-name {
        font-size: 1.3rem;
        font-weight: 700;
        margin-bottom: 0.2rem;
        word-wrap: break-word;
    }

    .user-summary-email {
        font-size: 0.85rem;
        color: var(--text-secondary);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        word-break: break-all;
    }

    @media (max-width: 480px) {
        .user-summary-email {
            justify-content: center;
        }
    }

    .user-summary-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
    }

    @media (max-width: 480px) {
        .user-summary-badges {
            justify-content: center;
        }
    }

    .user-summary-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        font-size: 0.72rem;
        padding: 0.25rem 0.75rem;
        border-radius: 999px;
        background: var(--primary-soft);
        color: var(--primary);
        font-weight: 600;
        white-space: nowrap;
    }

    /* =========================================
       MENU ITEMS
    ========================================= */
    .hub-menu-list {
        background: var(--hub-card-bg);
        border: 1px solid var(--hub-border);
        border-radius: var(--hub-radius);
        box-shadow: var(--hub-shadow);
        overflow: hidden;
        display: flex;
        flex-direction: column;
    }

    .hub-menu-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 1.25rem 1.5rem;
        text-decoration: none;
        color: inherit;
        transition: var(--hub-transition);
        border-bottom: 1px solid var(--hub-border);
    }

    .hub-menu-item:last-child {
        border-bottom: none;
    }

    .hub-menu-item:hover {
        background: var(--surface-hover);
    }

    .hub-menu-left {
        display: flex;
        align-items: center;
        gap: 1.25rem;
    }

    .hub-icon-container {
        width: 46px;
        height: 46px;
        border-radius: var(--hub-inner-radius);
        background: var(--primary-soft);
        color: var(--primary);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        transition: var(--hub-transition);
    }

    .hub-menu-item:hover .hub-icon-container {
        transform: scale(1.05);
    }

    .hub-title-box {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
    }

    .hub-menu-title {
        font-weight: 600;
        font-size: 1.05rem;
    }

    .hub-menu-desc {
        font-size: 0.8rem;
        color: var(--text-secondary);
    }

    .hub-menu-arrow {
        color: var(--text-tertiary);
        transition: var(--hub-transition);
    }

    .hub-menu-item:hover .hub-menu-arrow {
        transform: translateX(4px);
        color: var(--primary);
    }

    /* =========================================
       DISPLAY & APPEARANCE CARD
    ========================================= */
    .hub-theme-card {
        background: var(--hub-card-bg);
        border: 1px solid var(--hub-border);
        border-radius: var(--hub-radius);
        padding: 1.5rem 1.75rem;
        box-shadow: var(--hub-shadow);
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .theme-card-header {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .theme-card-header i {
        color: var(--primary);
        font-size: 1.2rem;
    }

    .theme-card-title {
        font-weight: 700;
        font-size: 1.1rem;
    }

    .theme-toggle-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }

    .theme-toggle-btn {
        border: 2px solid var(--hub-border);
        border-radius: var(--hub-inner-radius);
        padding: 1rem;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.6rem;
        font-weight: 600;
        font-size: 0.92rem;
        background: var(--settings-input-bg, #ffffff);
        color: var(--text-secondary);
        transition: var(--hub-transition);
    }

    :root[data-theme="dark"] .theme-toggle-btn {
        background: #0f172a;
    }

    .theme-toggle-btn:hover {
        border-color: var(--text-secondary);
        transform: translateY(-1px);
    }

    .theme-toggle-btn.active {
        border-color: var(--hub-active-teal);
        background: var(--primary-soft);
        color: var(--primary);
    }
</style>

<div class="settings-hub-wrapper">
    <div class="settings-hub-container">
        <!-- Profile Overview -->
        <div class="user-summary-card">
            <img src="<?php echo htmlspecialchars($avatarPath); ?>" alt="Avatar" class="user-summary-avatar">
            <div class="user-summary-details">
                <div class="user-summary-name"><?php echo htmlspecialchars($user['name'] ?? ''); ?></div>
                <div class="user-summary-email">
                    <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user['email'] ?? ''); ?>
                </div>
                <div class="user-summary-badges">
                    <span class="user-summary-badge">
                        <i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($user['department'] ?? 'No Department'); ?>
                    </span>
                    <?php if ($hallName !== 'N/A'): ?>
                        <span class="user-summary-badge">
                            <i class="fas fa-hotel"></i> <?php echo htmlspecialchars($hallName); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Settings Options List -->
        <nav class="hub-menu-list">
            <!-- Account Management -->
            <a href="/settings/edit-profile/" class="hub-menu-item">
                <div class="hub-menu-left">
                    <div class="hub-icon-container">
                        <i class="fas fa-user-gear"></i>
                    </div>
                    <div class="hub-title-box">
                        <span class="hub-menu-title">Account Management</span>
                        <span class="hub-menu-desc">Update your name, contact details, hall residency, and bio</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right hub-menu-arrow"></i>
            </a>

            <!-- Change Password -->
            <a href="/settings/change-password/" class="hub-menu-item">
                <div class="hub-menu-left">
                    <div class="hub-icon-container">
                        <i class="fas fa-shield-halved"></i>
                    </div>
                    <div class="hub-title-box">
                        <span class="hub-menu-title">Privacy & Security</span>
                        <span class="hub-menu-desc">Update password credentials and keep your account safe</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right hub-menu-arrow"></i>
            </a>

            <!-- Help & Support -->
            <a href="/settings/help/" class="hub-menu-item">
                <div class="hub-menu-left">
                    <div class="hub-icon-container">
                        <i class="fas fa-circle-question"></i>
                    </div>
                    <div class="hub-title-box">
                        <span class="hub-menu-title">Help & Support</span>
                        <span class="hub-menu-desc">Read FAQs, borrowing rules, guidelines, and contact support</span>
                    </div>
                </div>
                <i class="fas fa-chevron-right hub-menu-arrow"></i>
            </a>
        </nav>

        <!-- Display & Theme Selector (No separate folder) -->
        <div class="hub-theme-card">
            <div class="theme-card-header">
                <i class="fas fa-palette"></i>
                <span class="theme-card-title">Display & Appearance</span>
            </div>
            
            <div class="theme-toggle-row">
                <button class="theme-toggle-btn" id="hubThemeLight" onclick="toggleHubTheme('light')">
                    <i class="fas fa-sun"></i> Light Theme
                </button>
                <button class="theme-toggle-btn" id="hubThemeDark" onclick="toggleHubTheme('dark')">
                    <i class="fas fa-moon"></i> Dark Theme
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const savedTheme = localStorage.getItem('theme') || 'light';
        updateHubThemeUI(savedTheme);
    });

    function toggleHubTheme(theme) {
        if (theme === 'dark') {
            document.documentElement.setAttribute('data-theme', 'dark');
            localStorage.setItem('theme', 'dark');
        } else {
            document.documentElement.removeAttribute('data-theme');
            localStorage.setItem('theme', 'light');
        }
        updateHubThemeUI(theme);
    }

    function updateHubThemeUI(theme) {
        const lightBtn = document.getElementById('hubThemeLight');
        const darkBtn = document.getElementById('hubThemeDark');

        if (!lightBtn || !darkBtn) return;

        lightBtn.classList.remove('active');
        darkBtn.classList.remove('active');

        if (theme === 'dark') {
            darkBtn.classList.add('active');
        } else {
            lightBtn.classList.add('active');
        }
    }
</script>

<?php
// Include footer
require_once dirname(__DIR__) . '/includes/footer.php';
?>
