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
    // Update master users.json
    $usersFile = DATA_PATH . 'users.json';
    $users = json_decode(file_get_contents($usersFile), true);
    $masterUpdated = false;
    
    foreach ($users as &$user) {
        if ($user['id'] === $userId) {
            $user['name'] = $data['name'];
            $user['phone'] = $data['phone'];
            $user['department'] = $data['department'];
            $user['session'] = $data['session'];
            $user['room_number'] = $data['room_number'];
            
            if ($newImageFile) {
                // Delete old image if not default
                if (isset($user['profile_pic']) && $user['profile_pic'] !== 'default-avatar.jpg') {
                    $oldFile = UPLOAD_PATH . $user['profile_pic'];
                    if (file_exists($oldFile)) {
                        unlink($oldFile);
                    }
                }
                $user['profile_pic'] = $newImageFile;
            }
            
            $user['updated_at'] = date('Y-m-d H:i:s');
            $masterUpdated = true;
            break;
        }
    }
    
    $masterSaved = file_put_contents(
        $usersFile,
        json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
    
    // Update profile JSON
    $profileFile = USERS_PATH . $userId . '.json';
    $profileData = json_decode(file_get_contents($profileFile), true);
    
    $profileData['personal_info'] = [
        'name' => $data['name'],
        'email' => $profileData['personal_info']['email'], // Email cannot be changed
        'department' => $data['department'],
        'session' => $data['session'],
        'phone' => $data['phone'],
        'room_number' => $data['room_number'],
        'bio' => $data['bio']
    ];
    
    if ($newImageFile) {
        $profileData['personal_info']['profile_pic'] = $newImageFile;
    }
    
    $profileSaved = file_put_contents(
        $profileFile,
        json_encode($profileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
    
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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Edit Profile specific styles */
        .edit-profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .edit-profile-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            animation: slideUp 0.5s ease;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .edit-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }
        
        .edit-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 20s linear infinite;
        }
        
        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .edit-header h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .edit-header p {
            opacity: 0.9;
            position: relative;
        }
        
        .edit-body {
            padding: 2rem;
        }
        
        /* Profile Image Section */
        .profile-image-section {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 2rem;
            border-bottom: 2px solid #f0f0f0;
        }
        
        .current-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            margin: 0 auto 1rem;
            overflow: hidden;
            border: 4px solid #667eea;
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
            position: relative;
            cursor: pointer;
        }
        
        .current-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .current-image:hover .image-overlay {
            opacity: 1;
        }
        
        .image-hint {
            color: #8898aa;
            font-size: 0.9rem;
            margin-top: 0.5rem;
        }
        
        .image-hint i {
            color: #667eea;
        }
        
        /* Form Styles */
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
            font-size: 0.95rem;
        }
        
        .form-label i {
            color: #667eea;
            width: 20px;
            margin-right: 0.5rem;
        }
        
        .input-group {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .input-group i {
            position: absolute;
            left: 1rem;
            color: #8898aa;
            transition: color 0.3s ease;
        }
        
        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.5rem;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }
        
        .input-group textarea {
            min-height: 100px;
            resize: vertical;
            padding-top: 1rem;
        }
        
        .input-group input:focus,
        .input-group textarea:focus,
        .input-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .input-group input:focus + i,
        .input-group textarea:focus + i {
            color: #667eea;
        }
        
        .input-group .char-counter {
            position: absolute;
            right: 1rem;
            bottom: 0.5rem;
            font-size: 0.8rem;
            color: #8898aa;
        }
        
        /* Error messages */
        .error-message {
            color: #f5365c;
            font-size: 0.85rem;
            margin-top: 0.25rem;
            padding-left: 2.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .error-message i {
            font-size: 0.9rem;
        }
        
        /* Success alert */
        .success-alert {
            background: rgba(45, 206, 137, 0.1);
            border: 2px solid #2dce89;
            color: #2dce89;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            animation: slideDown 0.3s ease;
        }
        
        .success-alert i {
            font-size: 1.2rem;
        }
        
        .success-alert a {
            color: #2dce89;
            font-weight: 600;
            margin-left: auto;
            text-decoration: none;
        }
        
        .success-alert a:hover {
            text-decoration: underline;
        }
        
        /* Error alert */
        .error-alert {
            background: rgba(245, 54, 92, 0.1);
            border: 2px solid #f5365c;
            color: #f5365c;
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Form actions */
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f0f0;
        }
        
        .btn-save {
            flex: 2;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }
        
        .btn-save:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.3);
        }
        
        .btn-save:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }
        
        .btn-cancel {
            flex: 1;
            padding: 1rem;
            background: white;
            color: #8898aa;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            text-decoration: none;
        }
        
        .btn-cancel:hover {
            background: #f8f9fa;
            border-color: #8898aa;
            color: #333;
        }
        
        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-top-color: white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Image upload modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 15px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideUp 0.3s ease;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h3 {
            color: #333;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .modal-close {
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #8898aa;
            transition: color 0.3s ease;
        }
        
        .modal-close:hover {
            color: #f5365c;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        /* Image preview */
        .image-preview {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            overflow: hidden;
            border: 3px solid #667eea;
        }
        
        .image-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        /* File input styling */
        .file-input-wrapper {
            position: relative;
            margin-bottom: 1rem;
        }
        
        .file-input-wrapper input[type="file"] {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
            z-index: 2;
        }
        
        .file-input-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 1rem;
            background: #f8f9fa;
            border: 2px dashed #667eea;
            border-radius: 10px;
            color: #667eea;
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }
        
        .file-input-label:hover {
            background: rgba(102, 126, 234, 0.1);
        }
        
        .file-info {
            font-size: 0.85rem;
            color: #8898aa;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .form-group.full-width {
                grid-column: span 1;
            }
            
            .edit-body {
                padding: 1.5rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn-save, .btn-cancel {
                width: 100%;
            }
        }
        
        /* Read-only fields hint */
        .readonly-hint {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #8898aa;
            font-size: 0.9rem;
        }
        
        .readonly-hint i {
            color: #667eea;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="container">
            <a href="/" class="navbar-brand">
                <i class="fas fa-book-open"></i> OpenShelf
            </a>
            <button class="navbar-toggler" id="navbarToggler">
                <i class="fas fa-bars"></i>
            </button>
            <div class="navbar-menu" id="navbarMenu">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a href="/" class="nav-link">
                            <i class="fas fa-home"></i> Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/books/" class="nav-link">
                            <i class="fas fa-book"></i> Books
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/feed/" class="nav-link">
                            <i class="fas fa-rss"></i> Feed
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="/notifications/" class="nav-link">
                            <i class="fas fa-bell"></i> Notifications
                        </a>
                    </li>
                </ul>
                <div class="profile-dropdown">
                    <div class="profile-trigger" id="profileTrigger">
                        <img src="<?php echo $profileImagePath; ?>" alt="Profile" class="profile-image">
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="dropdown-menu" id="dropdownMenu">
                        <a href="/profile/" class="dropdown-item">
                            <i class="fas fa-user"></i> My Profile
                        </a>
                        <a href="/add-book/" class="dropdown-item">
                            <i class="fas fa-plus-circle"></i> Add Book
                        </a>
                        <a href="/requests/" class="dropdown-item">
                            <i class="fas fa-exchange-alt"></i> My Requests
                        </a>
                        <a href="/edit-profile/" class="dropdown-item active">
                            <i class="fas fa-cog"></i> Settings
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="/logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <main class="main-content">
        <div class="edit-profile-container">
            <div class="edit-profile-card">
                <div class="edit-header">
                    <h1>
                        <i class="fas fa-user-edit"></i>
                        Edit Profile
                    </h1>
                    <p>Update your personal information and preferences</p>
                </div>
                
                <div class="edit-body">
                    <!-- Success Message -->
                    <?php if ($success): ?>
                        <div class="success-alert">
                            <i class="fas fa-check-circle"></i>
                            <span>Your profile has been updated successfully!</span>
                            <a href="/profile/">
                                View Profile <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    <?php endif; ?>
                    
                    <!-- General Error -->
                    <?php if (isset($errors['general'])): ?>
                        <div class="error-alert">
                            <i class="fas fa-exclamation-circle"></i>
                            <?php echo htmlspecialchars($errors['general']); ?>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Read-only hint -->
                    <div class="readonly-hint">
                        <i class="fas fa-info-circle"></i>
                        <span>Your email address cannot be changed. Contact admin for email changes.</span>
                    </div>
                    
                    <!-- Profile Image Section -->
                    <div class="profile-image-section">
                        <div class="current-image" onclick="openUploadModal()">
                            <img src="<?php echo $profileImagePath; ?>" alt="Profile">
                            <div class="image-overlay">
                                <i class="fas fa-camera"></i> Change
                            </div>
                        </div>
                        <div class="image-hint">
                            <i class="fas fa-info-circle"></i>
                            Click on your profile picture to change it
                        </div>
                        <?php if (isset($errors['profile_image'])): ?>
                            <div class="error-message" style="justify-content: center;">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['profile_image']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Edit Form -->
                    <form method="POST" id="editProfileForm">
                        <div class="form-grid">
                            <!-- Name -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-user"></i>
                                    Full Name
                                </label>
                                <div class="input-group">
                                    <i class="fas fa-user"></i>
                                    <input type="text" name="name" id="name" 
                                           value="<?php echo htmlspecialchars($name); ?>"
                                           placeholder="Enter your full name"
                                           maxlength="100" required>
                                </div>
                                <?php if (isset($errors['name'])): ?>
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['name']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Phone -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-phone"></i>
                                    Phone Number
                                </label>
                                <div class="input-group">
                                    <i class="fas fa-phone"></i>
                                    <input type="tel" name="phone" id="phone" 
                                           value="<?php echo htmlspecialchars($phone); ?>"
                                           placeholder="01XXXXXXXXX"
                                           maxlength="11" required>
                                </div>
                                <?php if (isset($errors['phone'])): ?>
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['phone']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Department -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-building"></i>
                                    Department
                                </label>
                                <div class="input-group">
                                    <i class="fas fa-building"></i>
                                    <input type="text" name="department" id="department" 
                                           value="<?php echo htmlspecialchars($department); ?>"
                                           placeholder="e.g., Computer Science"
                                           maxlength="100" required>
                                </div>
                                <?php if (isset($errors['department'])): ?>
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['department']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Session -->
                            <div class="form-group">
                                <label class="form-label">
                                    <i class="fas fa-calendar"></i>
                                    Session
                                </label>
                                <div class="input-group">
                                    <i class="fas fa-calendar"></i>
                                    <input type="text" name="session" id="session" 
                                           value="<?php echo htmlspecialchars($session); ?>"
                                           placeholder="YYYY-YY (e.g., 2023-24)"
                                           maxlength="7" required>
                                </div>
                                <?php if (isset($errors['session'])): ?>
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['session']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Room Number -->
                            <div class="form-group full-width">
                                <label class="form-label">
                                    <i class="fas fa-door-open"></i>
                                    Room Number
                                </label>
                                <div class="input-group">
                                    <i class="fas fa-door-open"></i>
                                    <input type="text" name="room_number" id="room_number" 
                                           value="<?php echo htmlspecialchars($roomNumber); ?>"
                                           placeholder="e.g., 101, Salimullah Muslim Hall"
                                           maxlength="50" required>
                                </div>
                                <?php if (isset($errors['room_number'])): ?>
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['room_number']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Bio -->
                            <div class="form-group full-width">
                                <label class="form-label">
                                    <i class="fas fa-align-left"></i>
                                    Bio
                                </label>
                                <div class="input-group">
                                    <i class="fas fa-align-left"></i>
                                    <textarea name="bio" id="bio" 
                                              placeholder="Tell us a little about yourself (max 500 characters)"
                                              maxlength="500"><?php echo htmlspecialchars($bio); ?></textarea>
                                    <span class="char-counter" id="charCount">0/500</span>
                                </div>
                                <?php if (isset($errors['bio'])): ?>
                                    <div class="error-message">
                                        <i class="fas fa-exclamation-circle"></i>
                                        <?php echo htmlspecialchars($errors['bio']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Hidden email field (for reference only) -->
                        <input type="hidden" name="email" value="<?php echo htmlspecialchars($userData['master']['email']); ?>">
                        
                        <!-- Form Actions -->
                        <div class="form-actions">
                            <button type="submit" class="btn-save" id="saveBtn">
                                <span class="btn-text">
                                    <i class="fas fa-save"></i> Save Changes
                                </span>
                                <span class="spinner" style="display: none;"></span>
                            </button>
                            <a href="/profile/" class="btn-cancel">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    
    <!-- Image Upload Modal -->
    <div id="uploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>
                    <i class="fas fa-camera"></i>
                    Update Profile Picture
                </h3>
                <button class="modal-close" onclick="closeUploadModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form method="POST" enctype="multipart/form-data" id="imageUploadForm">
                    <!-- Image Preview -->
                    <div class="image-preview" id="imagePreview">
                        <img src="<?php echo $profileImagePath; ?>" alt="Preview">
                    </div>
                    
                    <!-- File Input -->
                    <div class="file-input-wrapper">
                        <input type="file" name="profile_image" id="profileImage" 
                               accept="image/jpeg,image/png,image/gif,image/webp"
                               onchange="previewImage(this)">
                        <div class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Choose Image
                        </div>
                    </div>
                    
                    <div class="file-info">
                        <i class="fas fa-info-circle"></i>
                        <span>Max size: 5MB. Supported: JPG, PNG, GIF, WebP</span>
                    </div>
                    
                    <div class="file-info">
                        <i class="fas fa-image"></i>
                        <span>Image will be cropped to 300x300 and converted to WebP</span>
                    </div>
                    
                    <button type="submit" class="btn-save" style="margin-top: 1.5rem;" id="uploadBtn">
                        <span class="btn-text">
                            <i class="fas fa-upload"></i> Upload Photo
                        </span>
                        <span class="spinner" style="display: none;"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <p>&copy; 2024 OpenShelf. Share books, share knowledge.</p>
        </div>
    </footer>
    
    <script>
        // Mobile menu toggle
        document.getElementById('navbarToggler')?.addEventListener('click', function() {
            document.getElementById('navbarMenu').classList.toggle('show');
        });
        
        // Profile dropdown toggle
        document.getElementById('profileTrigger')?.addEventListener('click', function(e) {
            e.stopPropagation();
            document.getElementById('dropdownMenu').classList.toggle('show');
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function() {
            document.getElementById('dropdownMenu')?.classList.remove('show');
        });
        
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
        updateCharCount(); // Initial count
        
        // Phone number formatting
        document.getElementById('phone').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length > 11) {
                value = value.substr(0, 11);
            }
            e.target.value = value;
        });
        
        // Session formatting
        document.getElementById('session').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\D/g, '');
            if (value.length >= 4) {
                value = value.substr(0, 4) + '-' + value.substr(4, 2);
            }
            if (value.length > 7) {
                value = value.substr(0, 7);
            }
            e.target.value = value;
        });
        
        // Form submission with loading state
        document.getElementById('editProfileForm').addEventListener('submit', function(e) {
            const saveBtn = document.getElementById('saveBtn');
            saveBtn.disabled = true;
            saveBtn.querySelector('.btn-text').style.display = 'none';
            saveBtn.querySelector('.spinner').style.display = 'inline-block';
        });
        
        // Modal functions
        function openUploadModal() {
            document.getElementById('uploadModal').classList.add('active');
        }
        
        function closeUploadModal() {
            document.getElementById('uploadModal').classList.remove('active');
            document.getElementById('imageUploadForm').reset();
            document.getElementById('imagePreview').querySelector('img').src = '<?php echo $profileImagePath; ?>';
        }
        
        // Image preview
        function previewImage(input) {
            const preview = document.getElementById('imagePreview').querySelector('img');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    preview.src = e.target.result;
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Image upload form submission
        document.getElementById('imageUploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const fileInput = document.getElementById('profileImage');
            
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Please select an image to upload');
                return;
            }
            
            // Validate file size
            if (fileInput.files[0].size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB');
                return;
            }
            
            // Show loading state
            const uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.disabled = true;
            uploadBtn.querySelector('.btn-text').style.display = 'none';
            uploadBtn.querySelector('.spinner').style.display = 'inline-block';
            
            // Create FormData and submit
            const formData = new FormData(this);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // Parse the response to check for success
                if (html.includes('success-alert') || html.includes('profile has been updated')) {
                    closeUploadModal();
                    location.reload(); // Reload to show new image
                } else {
                    alert('Failed to upload image. Please try again.');
                    uploadBtn.disabled = false;
                    uploadBtn.querySelector('.btn-text').style.display = 'inline-block';
                    uploadBtn.querySelector('.spinner').style.display = 'none';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                uploadBtn.disabled = false;
                uploadBtn.querySelector('.btn-text').style.display = 'inline-block';
                uploadBtn.querySelector('.spinner').style.display = 'none';
            });
        });
        
        // Close modal when clicking outside
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('uploadModal');
            if (event.target === modal) {
                closeUploadModal();
            }
        });
        
        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeUploadModal();
            }
            
            // Ctrl+S to save form
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                document.getElementById('editProfileForm').submit();
            }
        });
        
        // Warn before leaving if form is dirty
        let formChanged = false;
        document.getElementById('editProfileForm').addEventListener('input', function() {
            formChanged = true;
        });
        
        window.addEventListener('beforeunload', function(e) {
            if (formChanged) {
                e.preventDefault();
                e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Form validation
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
        });
    </script>
</body>
</html>