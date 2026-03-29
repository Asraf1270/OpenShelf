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

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/edit-profile/';
    header('Location: /login/');
    exit;
}

$userId = $_SESSION['user_id'];

/**
 * Load user data from both files
 */
function loadUserData($userId) {
    // Load from master users.json
    $usersFile = DATA_PATH . 'users.json';
    if (!file_exists($usersFile)) {
        return null;
    }
    
    $users = json_decode(file_get_contents($usersFile), true) ?? [];
    $masterUser = null;
    
    foreach ($users as $user) {
        if ($user['id'] === $userId) {
            $masterUser = $user;
            break;
        }
    }
    
    if (!$masterUser) {
        return null;
    }
    
    // Load detailed profile
    $profileFile = USERS_PATH . $userId . '.json';
    if (!file_exists($profileFile)) {
        return null;
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
 * Check if phone is unique (excluding current user)
 */
function isPhoneUnique($phone, $userId) {
    $usersFile = DATA_PATH . 'users.json';
    if (!file_exists($usersFile)) {
        return true;
    }
    
    $users = json_decode(file_get_contents($usersFile), true) ?? [];
    
    foreach ($users as $user) {
        if ($user['phone'] === $phone && $user['id'] !== $userId) {
            return false;
        }
    }
    
    return true;
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
 * Update user profile data
 */
function updateUserProfile($userId, $data, $newImageFile = null) {
    // Filesystem path for profile uploads
    $uploadPath = dirname(__DIR__) . '/uploads/profile/';
    
    // Update master users.json
    $usersFile = DATA_PATH . 'users.json';
    $users = json_decode(file_get_contents($usersFile), true);
    $currentProfilePic = 'default-avatar.jpg';
    
    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $currentProfilePic = $user['profile_pic'] ?? 'default-avatar.jpg';
            
            $user['name'] = $data['name'];
            $user['phone'] = $data['phone'];
            $user['department'] = $data['department'];
            $user['session'] = $data['session'];
            $user['room_number'] = $data['room_number'];
            $user['updated_at'] = date('Y-m-d H:i:s');
            
            if ($newImageFile) {
                // Delete previous profile pic if it's not the default
                if ($currentProfilePic !== 'default-avatar.jpg') {
                    $oldFilePath = $uploadPath . $currentProfilePic;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                $user['profile_pic'] = $newImageFile;
                $currentProfilePic = $newImageFile;
            }
            break;
        }
    }
    
    $masterSaved = file_put_contents(
        $usersFile,
        json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
    
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
            'profile_pic' => $currentProfilePic // Always add/update the reference
        ];
        
        $profileSaved = file_put_contents(
            $profileFile,
            json_encode($profileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    } else {
        $profileSaved = true; // Profile file missing but we updated master
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
        --midnight-aurora: linear-gradient(135deg, #4f46e5 0%, #7c3aed 50%, #db2777 100%);
        --pearl-glass: rgba(255, 255, 255, 0.45);
        --glass-stroke: rgba(255, 255, 255, 0.6);
        --glow-shadow: 0 25px 70px -15px rgba(99, 102, 241, 0.35);
    }

    .edit-profile-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: calc(100vh - 80px);
        background: #fbfbfd;
        background-image: radial-gradient(at 0% 0%, rgba(99, 102, 241, 0.1) 0, transparent 50%), 
                          radial-gradient(at 100% 0%, rgba(219, 39, 119, 0.08) 0, transparent 50%);
        padding: 100px var(--space-4) var(--space-12);
    }

    .split-container {
        display: grid;
        grid-template-columns: 380px 1fr;
        max-width: 1100px;
        width: 100%;
        background: var(--pearl-glass);
        backdrop-filter: blur(40px);
        -webkit-backdrop-filter: blur(40px);
        border: 1px solid var(--glass-stroke);
        border-radius: 40px;
        box-shadow: var(--glow-shadow);
        overflow: hidden;
        animation: entranceSnap 1s cubic-bezier(0.19, 1, 0.22, 1);
    }

    @keyframes entranceSnap {
        from { opacity: 0; transform: scale(0.96) translateY(40px); }
        to { opacity: 1; transform: scale(1) translateY(0); }
    }

    /* Info Panel Upgrade */
    .info-panel {
        background: var(--midnight-aurora);
        color: white;
        padding: var(--space-12) var(--space-8);
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        position: relative;
        overflow: hidden;
    }

    .info-panel::after {
        content: '';
        position: absolute;
        top: -10%;
        right: -10%;
        width: 150%;
        height: 150%;
        background: radial-gradient(circle at center, rgba(255,255,255,0.1) 0%, transparent 60%);
        animation: pulseFloating 12s infinite alternate ease-in-out;
    }

    @keyframes pulseFloating {
        from { transform: translate(0, 0) rotate(0deg) scale(1); opacity: 0.5; }
        to { transform: translate(-50px, 30px) rotate(5deg) scale(1.2); opacity: 0.8; }
    }

    .info-panel h2 {
        font-size: 2.8rem;
        font-weight: 900;
        line-height: 1;
        margin-bottom: var(--space-6);
        letter-spacing: -0.03em;
        text-shadow: 0 4px 15px rgba(0,0,0,0.15);
    }

    .info-panel p {
        font-size: 1.15rem;
        font-weight: 500;
        opacity: 0.9;
        line-height: 1.7;
    }

    .stat-item {
        display: flex;
        align-items: center;
        gap: var(--space-4);
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(10px);
        padding: var(--space-4) var(--space-5);
        border-radius: 20px;
        border: 1px solid rgba(255, 255, 255, 0.25);
        transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        cursor: default;
    }

    .stat-item:hover {
        background: rgba(255, 255, 255, 0.25);
        transform: translateX(12px);
    }

    /* Form Panel Staggered delay logic */
    .form-panel {
        padding: var(--space-12);
        background: rgba(255, 255, 255, 0.3);
    }

    .field-group {
        opacity: 0;
        animation: fadeInUp 0.7s cubic-bezier(0.23, 1, 0.32, 1) forwards;
    }

    @keyframes fadeInUp {
        from { opacity: 0; transform: translateY(24px); }
        to { opacity: 1; transform: translateY(0); }
    }

    .field-group:nth-child(1) { animation-delay: 0.1s; }
    .field-group:nth-child(2) { animation-delay: 0.15s; }
    .field-group:nth-child(3) { animation-delay: 0.2s; }
    .field-group:nth-child(4) { animation-delay: 0.25s; }
    .field-group:nth-child(5) { animation-delay: 0.3s; }
    .field-group:nth-child(6) { animation-delay: 0.35s; }

    /* Centered Avatar Unit */
    .avatar-upload-container {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        margin-bottom: var(--space-10);
        text-align: center;
    }

    .avatar-preview-wrapper {
        position: relative;
        width: 160px;
        height: 160px;
        transition: transform 0.5s cubic-bezier(0.33, 1.45, 0.74, 1);
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .avatar-preview-wrapper:hover {
        transform: scale(1.1) rotate(2deg);
    }

    .avatar-preview-wrapper img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        border-radius: 38% 62% 63% 37% / 41% 44% 56% 59%;
        box-shadow: 0 20px 40px rgba(99, 102, 241, 0.3);
        border: 6px solid #fff;
        animation: organicMorph 10s infinite alternate ease-in-out;
    }

    @keyframes organicMorph {
        0% { border-radius: 38% 62% 63% 37% / 41% 44% 56% 59%; }
        50% { border-radius: 65% 35% 45% 55% / 55% 45% 65% 35%; }
        100% { border-radius: 38% 62% 63% 37% / 41% 44% 56% 59%; }
    }

    /* Precision Icons & Shimmer */
    .premium-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .premium-input-wrapper i {
        position: absolute;
        left: 1.2rem;
        top: 50%;
        transform: translateY(-50%);
        color: var(--text-tertiary);
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .premium-input:focus + i {
        color: #4f46e5;
        transform: translateY(-50%) scale(1.2);
    }

    .premium-input {
        width: 100%;
        background: rgba(255, 255, 255, 0.9) !important;
        border: 2px solid #edeff2;
        border-radius: 20px;
        padding: 1.1rem 1rem 1.1rem 3rem;
        font-weight: 500;
        transition: all 0.3s ease;
    }

    .premium-input:focus {
        background: white !important;
        border-color: #6366f1;
        box-shadow: 0 10px 30px rgba(99, 102, 241, 0.2);
    }

    /* Actions Panel & Harmonized Buttons */
    .actions-panel {
        display: flex;
        gap: var(--space-4);
        margin-top: var(--space-10);
    }

    .btn-primary {
        background: var(--midnight-aurora);
        font-weight: 700;
        letter-spacing: 0.01em;
        position: relative;
        overflow: hidden;
    }

    .btn-primary::after {
        content: '';
        position: absolute;
        top: -50%;
        left: -150%;
        width: 150%;
        height: 200%;
        background: linear-gradient(to right, transparent, rgba(255,255,255,0.25), transparent);
        transform: rotate(45deg);
        transition: all 0.8s ease;
    }

    .btn-primary:hover::after {
        left: 150%;
    }

    .btn-outline {
        border: 2px solid #7c3aed;
        color: #7c3aed;
        background: rgba(124, 58, 237, 0.05);
        font-weight: 700;
        transition: all 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
    }

    .btn-outline:hover {
        background: #7c3aed;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(124, 58, 237, 0.25);
    }

    /* Grid layout */
    .form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: var(--space-6);
    }

    .field-group.full-width { grid-column: span 2; }

    /* Media Queries for Mobile Perfection */
    @media (max-width: 1024px) {
        .split-container { grid-template-columns: 1fr; border-radius: 30px; }
        .info-panel { padding: var(--space-10); border-radius: 0; min-height: auto; }
        .edit-profile-wrapper { padding: 100px var(--space-4) var(--space-6); }
        .info-panel h2 { font-size: 2.2rem; }
    }

    @media (max-width: 640px) {
        .form-grid { grid-template-columns: 1fr; }
        .field-group.full-width { grid-column: span 1; }
        .form-panel { padding: var(--space-8); }
        .actions-panel { flex-direction: column; }
        .actions-panel button, .actions-panel a { width: 100%; }
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