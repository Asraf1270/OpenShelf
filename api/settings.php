<?php
/**
 * OpenShelf Settings API Endpoints
 * Handles account updates and password changes via AJAX
 */

header('Content-Type: application/json');
session_start();

// Include database and dependencies
require_once dirname(__DIR__) . '/includes/db.php';
require_once dirname(__DIR__) . '/includes/helpers.php';

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(4101); // Unauthorized status
    echo json_encode([
        'success' => false,
        'message' => 'Please log in to update settings.'
    ]);
    exit;
}

$userId = $_SESSION['user_id'];
$db = getDB();

// Handle GET request to retrieve user profile data
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $db->prepare("SELECT id, name, email, department, session, phone, room_number, hall, profile_pic FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode([
                'success' => false,
                'message' => 'User not found.'
            ]);
            exit;
        }

        // Fetch bio from json file if available
        $bio = '';
        $userFile = dirname(__DIR__) . '/users/' . $userId . '.json';
        if (file_exists($userFile)) {
            $userData = json_decode(file_get_contents($userFile), true);
            $bio = $userData['personal_info']['bio'] ?? '';
        }
        $user['bio'] = $bio;

        echo json_encode([
            'success' => true,
            'user' => $user
        ]);
        exit;
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to fetch user data: ' . $e->getMessage()
        ]);
        exit;
    }
}

// Handle POST request to modify user settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $name = trim($_POST['name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $department = trim($_POST['department'] ?? '');
        $session = trim($_POST['session'] ?? '');
        $roomNumber = trim($_POST['room_number'] ?? '');
        $hall = trim($_POST['hall'] ?? '');
        $bio = trim($_POST['bio'] ?? '');

        $errors = [];

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
        } elseif (!preg_match('/^01[3-9]\d{8}$/', $phone)) {
            $errors['phone'] = 'Please enter a valid Bangladeshi phone number';
        } else {
            // Check uniqueness
            $stmt = $db->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $stmt->execute([$phone, $userId]);
            if ($stmt->fetch()) {
                $errors['phone'] = 'This phone number is already registered to another account';
            }
        }

        if (empty($department)) {
            $errors['department'] = 'Department is required';
        } elseif (strlen($department) > 100) {
            $errors['department'] = 'Department name is too long';
        }

        if (empty($session)) {
            $errors['session'] = 'Session is required';
        } elseif (!preg_match('/^\d{4}-\d{2}$/', $session)) {
            $errors['session'] = 'Session must be in format YYYY-YY (e.g., 2023-24)';
        }

        if (empty($roomNumber)) {
            $errors['room_number'] = 'Room number is required';
        } elseif (strlen($roomNumber) > 50) {
            $errors['room_number'] = 'Room number is too long';
        }

        if (empty($hall)) {
            $errors['hall'] = 'Hall selection is required';
        } elseif (!in_array($hall, ['1', '2', '3'])) {
            $errors['hall'] = 'Invalid hall selected';
        }

        if (strlen($bio) > 500) {
            $errors['bio'] = 'Bio must be less than 500 characters';
        }

        // Process profile photo if uploaded
        $newImageFile = null;
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $file = $_FILES['profile_image'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors['profile_image'] = 'File upload failed.';
            } elseif ($file['size'] > $maxFileSize) {
                $errors['profile_image'] = 'File size must be less than 5MB.';
            } else {
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mimeType = finfo_file($finfo, $file['tmp_name']);
                finfo_close($finfo);

                if (!in_array($mimeType, $allowedTypes)) {
                    $errors['profile_image'] = 'Only JPG, PNG, GIF, and WebP images are allowed.';
                } else {
                    // Create upload directory if needed
                    $uploadPath = dirname(__DIR__) . '/uploads/profile/';
                    if (!file_exists($uploadPath)) {
                        mkdir($uploadPath, 0755, true);
                    }

                    $timestamp = time();
                    $webpFilename = $userId . '_' . $timestamp . '.webp';
                    $webpPath = $uploadPath . $webpFilename;

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
                        $errors['profile_image'] = 'Failed to process image.';
                    } else {
                        // Crop square
                        $width = imagesx($image);
                        $height = imagesy($image);
                        $size = min($width, $height);
                        $x = ($width - $size) / 2;
                        $y = ($height - $size) / 2;

                        $thumb = imagecreatetruecolor(300, 300);

                        // Preserve transparency
                        if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
                            imagealphablending($thumb, false);
                            imagesavealpha($thumb, true);
                            $transparent = imagecolorallocatealpha($thumb, 255, 255, 255, 127);
                            imagefilledrectangle($thumb, 0, 0, 300, 300, $transparent);
                        }

                        // Resize
                        imagecopyresampled($thumb, $image, 0, 0, $x, $y, 300, 300, $size, $size);

                        // Save as WebP
                        $success = imagewebp($thumb, $webpPath, 80);
                        imagedestroy($image);
                        imagedestroy($thumb);

                        if (!$success) {
                            $errors['profile_image'] = 'Failed to save processed image.';
                        } else {
                            $newImageFile = $webpFilename;
                        }
                    }
                }
            }
        }

        // Return validation errors if any
        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit;
        }

        try {
            // Get current profile pic to delete later if updated
            $stmt = $db->prepare("SELECT profile_pic, email FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $userMeta = $stmt->fetch();
            $currentProfilePic = $userMeta['profile_pic'] ?: 'default-avatar.jpg';
            $userEmail = $userMeta['email'] ?: '';

            // Update main database
            $sql = "UPDATE users SET 
                        name = :name, 
                        phone = :phone, 
                        department = :department, 
                        session = :session, 
                        room_number = :room_number, 
                        hall = :hall,
                        updated_at = :updated_at";
            
            $params = [
                ':name' => $name,
                ':phone' => $phone,
                ':department' => $department,
                ':session' => $session,
                ':room_number' => $roomNumber,
                ':hall' => $hall,
                ':updated_at' => date('Y-m-d H:i:s'),
                ':id' => $userId
            ];

            if ($newImageFile) {
                // Delete previous profile pic if it's not the default
                if ($currentProfilePic !== 'default-avatar.jpg') {
                    $oldFilePath = dirname(__DIR__) . '/uploads/profile/' . $currentProfilePic;
                    if (file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }
                $sql .= ", profile_pic = :profile_pic";
                $params[':profile_pic'] = $newImageFile;
                $currentProfilePic = $newImageFile;
            }

            $sql .= " WHERE id = :id";
            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            // Update individual profile JSON file for backward compatibility
            $usersPath = dirname(__DIR__) . '/users/';
            if (!file_exists($usersPath)) {
                mkdir($usersPath, 0755, true);
            }
            $profileFile = $usersPath . $userId . '.json';
            $profileData = [];
            if (file_exists($profileFile)) {
                $profileData = json_decode(file_get_contents($profileFile), true);
            }

            $profileData['personal_info'] = [
                'name' => $name,
                'email' => $profileData['personal_info']['email'] ?? $userEmail,
                'department' => $department,
                'session' => $session,
                'phone' => $phone,
                'room_number' => $roomNumber,
                'hall' => $hall,
                'bio' => $bio,
                'profile_pic' => $currentProfilePic
            ];

            file_put_contents(
                $profileFile,
                json_encode($profileData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );

            // Sync with current session
            $_SESSION['user_name'] = $name;
            $_SESSION['user_avatar'] = $currentProfilePic;
            $_SESSION['user_hall'] = $hall;

            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully!',
                'profile_pic_url' => '/uploads/profile/' . $currentProfilePic
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    if ($action === 'change_password') {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        $errors = [];

        if (empty($currentPassword)) {
            $errors['current_password'] = 'Current password is required';
        }
        if (empty($newPassword)) {
            $errors['new_password'] = 'New password is required';
        } elseif (strlen($newPassword) < 6) {
            $errors['new_password'] = 'New password must be at least 6 characters';
        }
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match';
        }

        if (!empty($errors)) {
            echo json_encode([
                'success' => false,
                'errors' => $errors
            ]);
            exit;
        }

        try {
            // Verify current password
            $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $dbHash = $stmt->fetchColumn();

            if (!$dbHash || !password_verify($currentPassword, $dbHash)) {
                echo json_encode([
                    'success' => false,
                    'errors' => ['current_password' => 'Incorrect current password']
                ]);
                exit;
            }

            // Update with new password hash
            $newHash = password_hash($newPassword, PASSWORD_BCRYPT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newHash, $userId]);

            echo json_encode([
                'success' => true,
                'message' => 'Password changed successfully!'
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'Failed to update password: ' . $e->getMessage()
            ]);
            exit;
        }
    }

    echo json_encode([
        'success' => false,
        'message' => 'Invalid action.'
    ]);
    exit;
}

http_response_code(405);
echo json_encode([
    'success' => false,
    'message' => 'Method not allowed.'
]);
exit;
