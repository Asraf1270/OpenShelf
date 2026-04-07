<?php
/**
 * OpenShelf Edit Profile System
 * 
 * Allows users to update their personal information,
 * including name, phone, department, session, room number,
 * bio, and profile image.
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/profile/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('THUMBNAIL_SIZE', 300);
define('COMPRESSION_QUALITY', 80);

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/edit-profile/';
    header('Location: /login/');
    exit;
}

$userId = $_SESSION['user_id'];

/**
 * Load user data from DB and profile file
 */
function loadUserData($userId) {
    if (empty($userId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $masterUser = $stmt->fetch();
    
    if (!$masterUser) return null;
    
    // Load detailed profile
    $profileFile = USERS_PATH . $userId . '.json';
    if (!file_exists($profileFile)) {
        return ['master' => $masterUser, 'profile' => null];
    }
    
    $profileData = json_decode(file_get_contents($profileFile), true);
    
    return [
        'master' => $masterUser,
        'profile' => $profileData
    ];
}

/**
 * Validate phone number (Bangladesh format)
 */
function validatePhone($phone) {
    return preg_match('/^01[3-9]\d{8}$/', $phone);
}

/**
 * Validate session format (YYYY-YY)
 */
function validateSession($session) {
    return preg_match('/^\d{4}-\d{2}$/', $session);
}

/**
 * Check if phone is unique (excluding current user) from DB
 */
function isPhoneUnique($phone, $userId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
    $stmt->execute([$phone, $userId]);
    return $stmt->fetch() === false;
}

/**
 * Process and save profile image
 */
function processProfileImage($file, $userId) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'File size must be less than 5MB'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_TYPES)) {
        return ['error' => 'Only JPG, PNG, GIF, and WebP images are allowed'];
    }
    
    // Create upload directory if needed
    if (!file_exists(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    $timestamp = time();
    $webpFilename = $userId . '_' . $timestamp . '.webp';
    $webpPath = UPLOAD_PATH . $webpFilename;
    
    // Load image based on MIME type
    $image = null;
    switch ($mimeType) {
        case 'image/jpeg':
            $image = imagecreatefromjpeg($file['tmp_name']);
            break;
        case 'image/png':
            $image = imagecreatefrompng($file['tmp_name']);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($file['tmp_name']);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($file['tmp_name']);
            break;
    }
    
    if (!$image) {
        return ['error' => 'Failed to process image'];
    }
    
    // Get original dimensions and crop square
    $width = imagesx($image);
    $height = imagesy($image);
    $size = min($width, $height);
    $x = ($width - $size) / 2;
    $y = ($height - $size) / 2;
    
    // Create square thumbnail
    $thumb = imagecreatetruecolor(THUMBNAIL_SIZE, THUMBNAIL_SIZE);
    
    // Preserve transparency for PNG/GIF
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
        imagefilledrectangle($thumb, 0, 0, THUMBNAIL_SIZE, THUMBNAIL_SIZE, $transparent);
    }
    
    // Resize and crop
    imagecopyresampled(
        $thumb, $image,
        0, 0, $x, $y,
        THUMBNAIL_SIZE, THUMBNAIL_SIZE, $size, $size
    );
    
    // Save as WebP
    $success = imagewebp($thumb, $webpPath, COMPRESSION_QUALITY);
    
    imagedestroy($image);
    imagedestroy($thumb);
    
    if (!$success) {
        return ['error' => 'Failed to save processed image'];
    }
    
    return ['success' => true, 'filename' => $webpFilename];
}

/**
 * Update user profile data in DB and JSON files
 */
function updateUserProfile($userId, $data, $newImageFile = null) {
    $db = getDB();
    $uploadPath = dirname(__DIR__) . '/uploads/profile/';
    
    // Get current profile pic
    $stmt = $db->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $currentProfilePic = $stmt->fetchColumn() ?: 'default-avatar.jpg';
    
    // Prepare update query
    $sql = "UPDATE users SET 
                name = :name, 
                phone = :phone, 
                department = :department, 
                session = :session, 
                room_number = :room_number, 
                updated_at = :updated_at";
    
    $params = [
        ':name' => $data['name'],
        ':phone' => $data['phone'],
        ':department' => $data['department'],
        ':session' => $data['session'],
        ':room_number' => $data['room_number'],
        ':updated_at' => date('Y-m-d H:i:s'),
        ':id' => $userId
    ];
    
    if ($newImageFile) {
        // Delete previous profile pic if it's not the default
        if ($currentProfilePic !== 'default-avatar.jpg') {
            $oldFilePath = $uploadPath . $currentProfilePic;
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
        $sql .= ", profile_pic = :profile_pic";
        $params[':profile_pic'] = $newImageFile;
        $currentProfilePic = $newImageFile;
    }
    
    $sql .= " WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    $masterSaved = $stmt->execute($params);
    
    // Update individual profile JSON
    $profileFile = USERS_PATH . $userId . '.json';
    if (file_exists($profileFile)) {
        $profileData = json_decode(file_get_contents($profileFile), true);
        
        $profileData['personal_info'] = [
            'name' => $data['name'],
            'email' => $profileData['personal_info']['email'] ?? '',
            'department' => $data['department'],
            'session' => $data['session'],
            'phone' => $data['phone'],
            'room_number' => $data['room_number'],
            'bio' => $data['bio'],
            'profile_pic' => $currentProfilePic
        ];
        
        $profileSaved = file_put_contents(
            $profileFile,
            json_encode($profileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    } else {
        $profileSaved = true;
    }
    
    // Update session
    $_SESSION['user_avatar'] = $currentProfilePic;
    
    return $masterSaved && $profileSaved;
}

// Load current user data
$userData = loadUserData($userId);
if (!$userData) {
    die('User not found');
}

// Initialize variables with current data
$name = $userData['master']['name'];
$phone = $userData['master']['phone'];
$department = $userData['master']['department'];
$session = $userData['master']['session'];
$roomNumber = $userData['master']['room_number'];
$bio = $userData['profile']['personal_info']['bio'] ?? '';
$profileImage = $userData['master']['profile_pic'] ?? 'default-avatar.jpg';

// Handle form submission
$errors = [];
$success = false;
$imageUploaded = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $session = trim($_POST['session'] ?? '');
    $roomNumber = trim($_POST['room_number'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    
    // Validation
    if (empty($name)) {
        $errors['name'] = 'Name is required';
    } elseif (strlen($name) < 3) {
        $errors['name'] = 'Name must be at least 3 characters';
    } elseif (strlen($name) > 100) {
        $errors['name'] = 'Name must be less than 100 characters';
    }
    
    if (empty($phone)) {
        $errors['phone'] = 'Phone number is required';
    } elseif (!validatePhone($phone)) {
        $errors['phone'] = 'Please enter a valid Bangladeshi phone number';
    } elseif (!isPhoneUnique($phone, $userId)) {
        $errors['phone'] = 'This phone number is already registered to another account';
    }
    
    if (empty($department)) {
        $errors['department'] = 'Department is required';
    } elseif (strlen($department) > 100) {
        $errors['department'] = 'Department name is too long';
    }
    
    if (empty($session)) {
        $errors['session'] = 'Session is required';
    } elseif (!validateSession($session)) {
        $errors['session'] = 'Session must be in format YYYY-YY (e.g., 2023-24)';
    }
    
    if (empty($roomNumber)) {
        $errors['room_number'] = 'Room number is required';
    } elseif (strlen($roomNumber) > 50) {
        $errors['room_number'] = 'Room number is too long';
    }
    
    if (strlen($bio) > 500) {
        $errors['bio'] = 'Bio must be less than 500 characters';
    }
    
    // Handle image upload if provided
    $newImageFile = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = processProfileImage($_FILES['profile_image'], $userId);
        
        if (isset($uploadResult['error'])) {
            $errors['profile_image'] = $uploadResult['error'];
        } else {
            $newImageFile = $uploadResult['filename'];
            $imageUploaded = true;
        }
    }
    
    // If no errors, update profile
    if (empty($errors)) {
        $updateData = [
            'name' => $name,
            'phone' => $phone,
            'department' => $department,
            'session' => $session,
            'room_number' => $roomNumber,
            'bio' => $bio
        ];
        
        if (updateUserProfile($userId, $updateData, $newImageFile)) {
            $success = true;
            
            // Update session name
            $_SESSION['user_name'] = $name;
            
            // Reload user data
            $userData = loadUserData($userId);
            $profileImage = $newImageFile ?? $profileImage;
        } else {
            $errors['general'] = 'Failed to update profile. Please try again.';
        }
    }
}

// Get profile image path
$profileImagePath = '/uploads/profile/' . $profileImage;

?>
<?php 
// Add page-specific styles
?>
<style>
    :root {
        --primary: #4f46e5;
        --secondary: #7c3aed;
        --accent: #db2777;
        --bg: #f5f7ff;
        --card: rgba(255, 255, 255, 0.88);
        --border: rgba(99, 102, 241, 0.18);
        --text: #1f2937;
        --muted: #4b5563;
        --radius: 22px;
    }

    * { box-sizing: border-box; }
    body { background: var(--bg); color: var(--text); }

    .edit-profile-wrapper {
        min-height: calc(100vh - 80px);
        width: 100%;
        padding: 1.5rem 0.75rem 2rem;
        display: flex;
        justify-content: center;
        align-items: flex-start;
    }

    .split-container {
        width: min(100%, 960px);
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
        background: var(--card);
        border-radius: var(--radius);
        border: 1px solid var(--border);
        backdrop-filter: blur(12px);
        box-shadow: 0 24px 60px rgba(31, 41, 55, 0.15);
        overflow: hidden;
        animation: entranceSnap 0.8s ease-out;
    }

    @keyframes entranceSnap {
        from { opacity: 0; transform: translateY(24px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }

    .info-panel, .form-panel {
        padding: 1.4rem;
    }

    .info-panel {
        background: linear-gradient(135deg, rgba(79,70,229,0.85), rgba(124,58,237,0.85));
        color: #f8fafc;
        border-radius: var(--radius) var(--radius) 0 0;
        position: relative;
        overflow: hidden;
        z-index: 1;
    }

    .info-panel::before {
        content: '';
        position: absolute;
        inset: 0;
        background: radial-gradient(circle at 10% 10%, rgba(255,255,255,0.2), transparent 45%),
                    radial-gradient(circle at 80% 25%, rgba(255,255,255,0.12), transparent 40%);
        pointer-events: none;
    }

    .info-panel > div {
        position: relative;
        z-index: 2;
    }

    .info-panel h2 {
        font-size: 1.9rem;
        margin-bottom: 0.6rem;
        letter-spacing: -0.02em;
    }

    .info-panel p {
        line-height: 1.6;
        opacity: 0.95;
        margin-bottom: 1rem;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 0.75rem;
        border-radius: 14px;
        background: rgba(255,255,255,0.2);
        border: 1px solid rgba(255,255,255,0.25);
        transition: transform 0.28s ease, background 0.28s ease;
    }

    .stat-item:hover {
        transform: translateX(4px);
        background: rgba(255,255,255,0.35);
    }

    .form-panel {
        background: rgba(255, 255, 255, 0.85);
        border: 1px solid rgba(99, 102, 241, 0.15);
        border-radius: 0 0 var(--radius) var(--radius);
        box-shadow: inset 0 0 0 1px rgba(255, 255, 255, 0.3);
    }

    .field-group {
        margin-bottom: 0.9rem;
        opacity: 0;
        animation: fadeInUp 0.6s ease forwards;
    }

    .field-group:nth-child(1) { animation-delay: 0.08s; }
    .field-group:nth-child(2) { animation-delay: 0.12s; }
    .field-group:nth-child(3) { animation-delay: 0.16s; }
    .field-group:nth-child(4) { animation-delay: 0.2s; }
    .field-group:nth-child(5) { animation-delay: 0.24s; }
    .field-group:nth-child(6) { animation-delay: 0.28s; }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(12px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .avatar-upload-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.7rem;
        margin-bottom: 1.1rem;
    }

    .avatar-preview-wrapper {
        width: 132px;
        height: 132px;
        border-radius: 22% 78% 80% 20% / 24% 51% 49% 76%;
        overflow: hidden;
        border: 4px solid rgba(99,102,241,0.4);
        box-shadow: 0 16px 30px rgba(79,70,229,0.25);
        transition: transform 0.45s ease;
    }

    .avatar-preview-wrapper:hover {
        transform: translateY(-3px) scale(1.02);
    }

    .avatar-preview-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.35s ease;
    }

    .avatar-preview-wrapper img:hover {
        transform: scale(1.03);
    }

    .premium-input-wrapper {
        position: relative;
        font-size: 0.92rem;
    }

    .premium-input-wrapper i {
        position: absolute;
        left: 0.9rem;
        top: 50%;
        transform: translateY(-50%);
        color: #6b7280;
        pointer-events: none;
    }

    .premium-input {
        width: 100%;
        min-height: 44px;
        border: 1px solid rgba(99, 102, 241, 0.4);
        border-radius: 14px;
        background: white;
        color: #111827;
        padding: 0.9rem 0.9rem 0.9rem 2.6rem;
        transition: border-color 0.25s ease, box-shadow 0.25s ease;
    }

    .premium-input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79,70,229,0.14);
    }

    textarea.premium-input {
        height: 120px;
        resize: vertical;
    }

    .count-badge {
        position: absolute;
        right: 0.8rem;
        bottom: 0.5rem;
        background: rgba(99, 102, 241, 0.14);
        color: #4b5563;
        padding: 0.2rem 0.46rem;
        border-radius: 999px;
        font-size: 0.72rem;
    }

    .actions-panel {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 1.1rem;
    }

    .btn-primary, .btn-outline {
        border-radius: 14px;
        height: 50px;
        font-size: 0.98rem;
        font-weight: 700;
        padding: 0 1rem;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .btn-primary {
        background: linear-gradient(120deg, var(--primary), var(--accent));
        color: white;
        border: none;
        box-shadow: 0 10px 20px rgba(79,70,229,0.35);
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 24px rgba(79,70,229,0.35);
    }

    .btn-outline {
        color: var(--secondary);
        border: 2px solid var(--secondary);
        background: white;
    }

    .btn-outline:hover {
        background: rgba(124,58,237,0.08);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(124,58,237,0.12);
    }

    .form-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.95rem;
    }

    .field-group.full-width { grid-column: span 1; }

    @media (min-width: 840px) {
        .split-container {
            grid-template-columns: 340px 1fr;
        }

        .info-panel {
            border-radius: var(--radius) 0 0 var(--radius);
            padding: 1.6rem;
        }

        .form-panel {
            border-radius: 0 var(--radius) var(--radius) 0;
            padding: 1.5rem;
        }

        .form-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .field-group.full-width { grid-column: span 2; }

        .actions-panel {
            flex-direction: row;
        }

        .actions-panel button, .actions-panel a {
            width: auto;
            flex: 1;
        }
    }

    @media (min-width: 1140px) {
        .edit-profile-wrapper {
            padding: 2.5rem 0.75rem 2.5rem;
        }

        .info-panel h2 {
            font-size: 2.2rem;
        }

        .form-panel {
            padding: 2rem;
        }
    }

    /* Dark Mode Overrides */
    :root[data-theme="dark"] {
        --bg: #0f172a;
        --card: #1e293b;
        --border: #334155;
        --text: #f8fafc;
        --muted: #94a3b8;
    }

    [data-theme="dark"] .form-panel {
        background: var(--card);
        border-color: var(--border);
        box-shadow: none;
    }

    [data-theme="dark"] .premium-input {
        background: #0f172a;
        border-color: var(--border);
        color: var(--text);
    }
    
    [data-theme="dark"] .premium-input:focus {
        background: #1e293b;
        border-color: var(--primary);
    }

    [data-theme="dark"] .premium-input-wrapper i {
        color: var(--muted);
    }

    [data-theme="dark"] .btn-outline {
        background: transparent;
        color: #e2e8f0;
        border-color: var(--border);
    }

    [data-theme="dark"] .btn-outline:hover {
        background: rgba(255, 255, 255, 0.05);
        color: #fff;
    }

    [data-theme="dark"] .count-badge {
        background: #334155;
        color: #cbd5e1;
    }
</style>

    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    
    <!-- Main Content -->
    <main class="main-content edit-profile-wrapper">
        <div class="split-container">
            <!-- Left Panel -->
            <div class="info-panel">
                <div>
                    <h2>Shape Your Identity</h2>
                    <p>Your profile is the heart of your OpenShelf journey. Keep it updated to build trust within the community.</p>
                </div>
                
                <div class="info-stats">
                    <div class="stat-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>Secure Account</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-star"></i>
                        <span>Reputation Powered</span>
                    </div>
                    <div class="stat-item">
                        <i class="fas fa-bolt"></i>
                        <span>Instant Access</span>
                    </div>
                </div>
                
                <div style="font-size: 0.85rem; opacity: 0.7; font-weight: 500;">
                    &copy; 2024 OpenShelf Ecosystem.
                </div>
            </div>
            
            <!-- Right Panel -->
            <div class="form-panel">
                <!-- Success Message -->
                <?php if ($success): ?>
                    <div class="alert alert-success" style="border-radius: 16px; margin-bottom: var(--space-6);">
                        <div style="display: flex; align-items: center; gap: var(--space-3);">
                            <i class="fas fa-check-circle" style="font-size: 1.25rem;"></i>
                            <div>
                                <strong>Success!</strong> Your profile state has been synchronized. 
                                <a href="/profile/" style="color: inherit; text-decoration: underline; font-weight: 700;">View Profile</a>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- General Error -->
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger" style="border-radius: 16px; margin-bottom: var(--space-6);">
                        <i class="fas fa-exclamation-triangle"></i>
                        <?php echo htmlspecialchars($errors['general']); ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" id="editProfileForm" enctype="multipart/form-data">
                    <!-- Avatar Context -->
                    <div class="avatar-upload-container">
                        <div class="avatar-preview-wrapper" onclick="document.getElementById('profile_image').click()">
                            <img src="<?php echo $profileImagePath; ?>" alt="Profile" id="mainAvatarDisplay">
                            <div class="avatar-edit-badge">
                                <i class="fas fa-camera"></i>
                            </div>
                        </div>
                        <input type="file" name="profile_image" id="profile_image" style="display: none;" accept="image/*" onchange="previewAvatar(this)">
                        
                        <div style="text-align: center; margin-top: 1rem;">
                            <span style="font-size: 0.8rem; color: var(--text-tertiary);">Click image to select new photo</span>
                        </div>
                        
                        <?php if (isset($errors['profile_image'])): ?>
                            <div class="text-danger" style="font-size: 0.85rem; margin-top: 5px;">
                                <i class="fas fa-times-circle"></i> <?php echo htmlspecialchars($errors['profile_image']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-grid">
                        <!-- Full Name -->
                        <div class="field-group">
                            <label class="premium-label"><i class="fas fa-user"></i> Full Identity</label>
                            <div class="premium-input-wrapper">
                                <input type="text" name="name" class="premium-input" 
                                       value="<?php echo htmlspecialchars($name); ?>"
                                       placeholder="Name" required>
                                <i class="fas fa-id-card"></i>
                            </div>
                            <?php if (isset($errors['name'])): ?>
                                <span class="text-danger" style="font-size: 0.75rem;"><?php echo $errors['name']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Phone -->
                        <div class="field-group">
                            <label class="premium-label"><i class="fas fa-phone"></i> Contact Link</label>
                            <div class="premium-input-wrapper">
                                <input type="tel" name="phone" id="phone" class="premium-input" 
                                       value="<?php echo htmlspecialchars($phone); ?>"
                                       placeholder="01XXXXXXXXX" required>
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <?php if (isset($errors['phone'])): ?>
                                <span class="text-danger" style="font-size: 0.75rem;"><?php echo $errors['phone']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Department -->
                        <div class="field-group">
                            <label class="premium-label"><i class="fas fa-university"></i> Faculty / Dept</label>
                            <div class="premium-input-wrapper">
                                <input type="text" name="department" class="premium-input" 
                                       value="<?php echo htmlspecialchars($department); ?>"
                                       placeholder="Department" required>
                                <i class="fas fa-graduation-cap"></i>
                            </div>
                            <?php if (isset($errors['department'])): ?>
                                <span class="text-danger" style="font-size: 0.75rem;"><?php echo $errors['department']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Session -->
                        <div class="field-group">
                            <label class="premium-label"><i class="fas fa-clock"></i> Academic Session</label>
                            <div class="premium-input-wrapper">
                                <input type="text" name="session" id="session" class="premium-input" 
                                       value="<?php echo htmlspecialchars($session); ?>"
                                       placeholder="YYYY-YY" required>
                                <i class="fas fa-hourglass-half"></i>
                            </div>
                            <?php if (isset($errors['session'])): ?>
                                <span class="text-danger" style="font-size: 0.75rem;"><?php echo $errors['session']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Room -->
                        <div class="field-group full-width">
                            <label class="premium-label"><i class="fas fa-map-marker-alt"></i> Residential Hub</label>
                            <div class="premium-input-wrapper">
                                <input type="text" name="room_number" class="premium-input" 
                                       value="<?php echo htmlspecialchars($roomNumber); ?>"
                                       placeholder="e.g., 603, Salimullah Hall" required>
                                <i class="fas fa-home"></i>
                            </div>
                            <?php if (isset($errors['room_number'])): ?>
                                <span class="text-danger" style="font-size: 0.75rem;"><?php echo $errors['room_number']; ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Bio -->
                        <div class="field-group full-width">
                            <label class="premium-label"><i class="fas fa-pen-nib"></i> Narrative / Bio</label>
                            <div class="premium-input-wrapper">
                                <textarea name="bio" id="bio" class="premium-input form-textarea" 
                                          placeholder="A brief story about you..."><?php echo htmlspecialchars($bio); ?></textarea>
                                <i class="fas fa-quote-left" style="top: 1rem;"></i>
                                <span class="count-badge" id="charCount">0/500</span>
                            </div>
                            <?php if (isset($errors['bio'])): ?>
                                <span class="text-danger" style="font-size: 0.75rem;"><?php echo $errors['bio']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="actions-panel">
                        <button type="submit" class="btn btn-primary" id="saveBtn" style="flex: 2; height: 56px; border-radius: 18px;">
                            <span class="btn-text">
                                <i class="fas fa-save" style="margin-right: 10px;"></i> Commit Changes
                            </span>
                            <span class="spinner" id="btnSpinner" style="display: none;"></span>
                        </button>
                        <a href="/profile/" class="btn btn-outline" style="flex: 1; height: 56px; border-radius: 18px; display: flex; align-items: center; justify-content: center;">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
    
    <script>
        // Bio character counter
        const bio = document.getElementById('bio');
        const charCount = document.getElementById('charCount');
        
        function updateCharCount() {
            const count = bio.value.length;
            charCount.textContent = count + '/500';
            
            if (count > 450) {
                charCount.style.color = '#fb6340';
            } else if (count > 400) {
                charCount.style.color = '#f5365c';
            } else {
                charCount.style.color = '#8898aa';
            }
        }
        
        bio.addEventListener('input', updateCharCount);
        updateCharCount();
        
        // Phone formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '').substr(0, 11);
            e.target.value = value;
        });
        
        // Session formatting
        document.getElementById('session').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 4) value = value.substr(0, 4) + '-' + value.substr(4, 2);
            e.target.value = value.substr(0, 7);
        });
        
        // Avatar preview
        function previewAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('mainAvatarDisplay').src = e.target.result;
                    // Add a little pop animation
                    const img = document.getElementById('mainAvatarDisplay');
                    img.style.transform = 'scale(1.1)';
                    setTimeout(() => img.style.transform = 'scale(1)', 300);
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Form Loading & Validation
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            const phone = document.getElementById('phone').value;
            const phoneRegex = /^01[3-9]\d{8}$/;
            
            if (!phoneRegex.test(phone)) {
                e.preventDefault();
                alert('Please enter a valid Bangladeshi phone number (11 digits starting with 01)');
                return false;
            }
            
            const session = document.getElementById('session').value;
            const sessionRegex = /^\d{4}-\d{2}$/;
            
            if (!sessionRegex.test(session)) {
                e.preventDefault();
                alert('Please enter session in format YYYY-YY (e.g., 2023-24)');
                return false;
            }

            const btn = document.getElementById('saveBtn');
            btn.disabled = true;
            btn.querySelector('.btn-text').style.opacity = '0.3';
            document.getElementById('btnSpinner').style.display = 'inline-block';
        });

        // Warn before leaving if form is dirty
        let formChanged = false;
        document.getElementById('editProfileForm').addEventListener('input', () => formChanged = true);
        window.addEventListener('beforeunload', (e) => {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    </script>
</body>
</html>