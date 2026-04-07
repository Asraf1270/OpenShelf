<?php
/**
 * OpenShelf Edit Book Page
 * Allows book owners to edit their book details
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_PATH', dirname(__DIR__) . '/data/book/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/book_cover/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('COVER_WIDTH', 800);
define('COVER_HEIGHT', 1200);
define('COMPRESSION_QUALITY', 85);

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
    header('Location: /login/');
    exit;
}

$currentUserId = $_SESSION['user_id'];
$currentUserName = $_SESSION['user_name'] ?? 'Unknown';

// Get book ID from URL
$bookId = $_GET['id'] ?? '';
if (empty($bookId)) {
    header('Location: /books/');
    exit;
}

/**
 * Load book data from DB
 */
function loadBookData($bookId) {
    if (empty($bookId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    return $stmt->fetch() ?: null;
}

/**
 * Save book data to DB
 */
function saveBookData($bookId, $bookData) {
    $db = getDB();
    
    $sql = "UPDATE books SET 
                title = :title, 
                author = :author, 
                description = :description, 
                category = :category, 
                `condition` = :condition, 
                isbn = :isbn, 
                publication_year = :publication_year, 
                publisher = :publisher, 
                pages = :pages, 
                language = :language, 
                cover_image = :cover_image,
                updated_at = :updated_at
            WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute([
        ':title' => $bookData['title'],
        ':author' => $bookData['author'],
        ':description' => $bookData['description'],
        ':category' => $bookData['category'],
        ':condition' => $bookData['condition'],
        ':isbn' => $bookData['isbn'],
        ':publication_year' => $bookData['publication_year'],
        ':publisher' => $bookData['publisher'],
        ':pages' => $bookData['pages'],
        ':language' => $bookData['language'],
        ':cover_image' => $bookData['cover_image'],
        ':updated_at' => $bookData['updated_at'],
        ':id' => $bookId
    ]);
}

/**
 * Process and save book cover image
 */
function processCoverImage($file, $bookId) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'File size must be less than 10MB'];
    }
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, ALLOWED_TYPES)) {
        return ['error' => 'Only JPG, PNG, GIF, and WebP images are allowed'];
    }
    
    if (!file_exists(UPLOAD_PATH)) {
        mkdir(UPLOAD_PATH, 0755, true);
    }
    
    $timestamp = time();
    $webpFilename = $bookId . '_' . $timestamp . '.webp';
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
    
    // Get dimensions
    $width = imagesx($image);
    $height = imagesy($image);
    $ratio = $width / $height;
    $newWidth = COVER_WIDTH;
    $newHeight = COVER_HEIGHT;
    
    if ($ratio > 0.75) {
        $newHeight = $newWidth / $ratio;
    } else {
        $newWidth = $newHeight * $ratio;
    }
    
    // Resize
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    imagecopyresampled(
        $resized, $image,
        0, 0, 0, 0,
        $newWidth, $newHeight, $width, $height
    );
    
    // Create thumbnail
    $thumb = imagecreatetruecolor(300, 300);
    $size = min($width, $height);
    $x = ($width - $size) / 2;
    $y = ($height - $size) / 2;
    
    imagecopyresampled(
        $thumb, $image,
        0, 0, $x, $y,
        300, 300, $size, $size
    );
    
    // Save main image
    imagewebp($resized, $webpPath, COMPRESSION_QUALITY);
    
    // Save thumbnail
    $thumbPath = UPLOAD_PATH . 'thumb_' . $webpFilename;
    imagewebp($thumb, $thumbPath, COMPRESSION_QUALITY);
    
    imagedestroy($image);
    imagedestroy($resized);
    imagedestroy($thumb);
    
    return ['success' => true, 'filename' => $webpFilename];
}

/**
 * Process and save user profile image
 */
function processUserProfileImage($file, $userId) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['error' => 'File upload failed'];
    }
    
    $profileUploadPath = dirname(__DIR__) . '/uploads/profile/';
    if (!file_exists($profileUploadPath)) {
        mkdir($profileUploadPath, 0755, true);
    }
    
    $timestamp = time();
    $webpFilename = $userId . '_' . $timestamp . '.webp';
    $webpPath = $profileUploadPath . $webpFilename;
    
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    $image = null;
    switch ($mimeType) {
        case 'image/jpeg': $image = imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png': $image = imagecreatefrompng($file['tmp_name']); break;
        case 'image/gif': $image = imagecreatefromgif($file['tmp_name']); break;
        case 'image/webp': $image = imagecreatefromwebp($file['tmp_name']); break;
    }
    
    if (!$image) return ['error' => 'Failed to process image'];
    
    $width = imagesx($image);
    $height = imagesy($image);
    $size = min($width, $height);
    $thumb = imagecreatetruecolor(300, 300);
    
    imagecopyresampled($thumb, $image, 0, 0, ($width - $size) / 2, ($height - $size) / 2, 300, 300, $size, $size);
    imagewebp($thumb, $webpPath, 85);
    
    imagedestroy($image);
    imagedestroy($thumb);
    
    return ['success' => true, 'filename' => $webpFilename];
}

/**
 * Update user profile picture in DB and JSON files
 */
function updateUserProfilePic($userId, $filename) {
    $db = getDB();
    $uploadPath = dirname(__DIR__) . '/uploads/profile/';
    
    // Get old profile pic to delete
    $stmt = $db->prepare("SELECT profile_pic FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $oldPic = $stmt->fetchColumn() ?: 'default-avatar.jpg';
    
    // Update DB
    $stmt = $db->prepare("UPDATE users SET profile_pic = ?, updated_at = ? WHERE id = ?");
    if ($stmt->execute([$filename, date('Y-m-d H:i:s'), $userId])) {
        // Delete previous profile pic if it's not the default
        if ($oldPic !== 'default-avatar.jpg' && $oldPic !== $filename) {
            $oldFilePath = $uploadPath . $oldPic;
            if (file_exists($oldFilePath)) {
                unlink($oldFilePath);
            }
        }
    }
    
    // Update individual profile JSON
    $profileFile = USERS_PATH . $userId . '.json';
    if (file_exists($profileFile)) {
        $profile = json_decode(file_get_contents($profileFile), true);
        $profile['personal_info']['profile_pic'] = $filename;
        file_put_contents($profileFile, json_encode($profile, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }
    
    // Update session
    $_SESSION['user_avatar'] = $filename;
}


/**
 * Get list of book categories
 */
function getCategories() {
    return [
        'Fiction', 'Non-Fiction', 'Science Fiction', 'Fantasy', 'Mystery',
        'Thriller', 'Romance', 'Biography', 'History', 'Science',
        'Technology', 'Programming', 'Mathematics', 'Physics', 'Chemistry',
        'Biology', 'Literature', 'Poetry', 'Drama', 'Philosophy',
        'Psychology', 'Economics', 'Business', 'Self-Help', 'Health',
        'Sports', 'Travel', 'Art', 'Music', 'Education',
        'Textbook', 'Reference', 'Children', 'Young Adult', 'Comics',
        'Graphic Novel', 'Other'
    ];
}

/**
 * Get list of book conditions
 */
function getConditions() {
    return [
        'New' => 'Brand new, never read',
        'Like New' => 'Perfect condition, no wear',
        'Very Good' => 'Minor wear, clean copy',
        'Good' => 'Normal wear, may have markings',
        'Acceptable' => 'Well-read, usable condition',
        'Poor' => 'Damaged, but readable'
    ];
}

// Load book data
$book = loadBookData($bookId);
if (!$book) {
    header('Location: /books/');
    exit;
}

// Check if user is the owner
if ($book['owner_id'] !== $currentUserId) {
    $_SESSION['error'] = 'You do not have permission to edit this book';
    header('Location: /book/?id=' . $bookId);
    exit;
}

// Initialize variables
$title = $book['title'] ?? '';
$author = $book['author'] ?? '';
$description = $book['description'] ?? '';
$category = $book['category'] ?? '';
$condition = $book['condition'] ?? '';
$isbn = $book['isbn'] ?? '';
$publicationYear = $book['publication_year'] ?? '';
$publisher = $book['publisher'] ?? '';
$pages = $book['pages'] ?? '';
$language = $book['language'] ?? 'English';
$coverImage = $book['cover_image'] ?? '';

$errors = [];
$success = false;
$uploadedImage = null;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Sanitize inputs
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $condition = trim($_POST['condition'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $publicationYear = trim($_POST['publication_year'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $pages = trim($_POST['pages'] ?? '');
    $language = trim($_POST['language'] ?? '');
    
    // Validation
    if (empty($title)) {
        $errors['title'] = 'Book title is required';
    } elseif (strlen($title) < 2) {
        $errors['title'] = 'Title must be at least 2 characters';
    } elseif (strlen($title) > 200) {
        $errors['title'] = 'Title must be less than 200 characters';
    }
    
    if (empty($author)) {
        $errors['author'] = 'Author name is required';
    } elseif (strlen($author) < 2) {
        $errors['author'] = 'Author name must be at least 2 characters';
    } elseif (strlen($author) > 100) {
        $errors['author'] = 'Author name must be less than 100 characters';
    }
    
    if (empty($description)) {
        $errors['description'] = 'Description is required';
    } elseif (strlen($description) < 20) {
        $errors['description'] = 'Description must be at least 20 characters';
    } elseif (strlen($description) > 5000) {
        $errors['description'] = 'Description must be less than 5000 characters';
    }
    
    if (empty($category)) {
        $errors['category'] = 'Please select a category';
    }
    
    if (empty($condition)) {
        $errors['condition'] = 'Please select a condition';
    }
    
    if ($pages && !is_numeric($pages)) {
        $errors['pages'] = 'Pages must be a number';
    }
    
    
    // Handle book cover image upload
    if (isset($_FILES['cover_image']) && $_FILES['cover_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        $uploadResult = processCoverImage($_FILES['cover_image'], $bookId);
        
        if (isset($uploadResult['error'])) {
            $errors['cover_image'] = $uploadResult['error'];
        } else {
            $uploadedImage = $uploadResult['filename'];
            
            // Delete old cover image if exists
            if (!empty($coverImage)) {
                $oldPath = UPLOAD_PATH . $coverImage;
                $oldThumbPath = UPLOAD_PATH . 'thumb_' . $coverImage;
                if (file_exists($oldPath)) unlink($oldPath);
                if (file_exists($oldThumbPath)) unlink($oldThumbPath);
            }
        }
    }
    
    // Handle user profile picture upload
    if (isset($_FILES['user_profile_pic']) && $_FILES['user_profile_pic']['error'] !== UPLOAD_ERR_NO_FILE) {
        $userUploadResult = processUserProfileImage($_FILES['user_profile_pic'], $currentUserId);
        if (isset($userUploadResult['error'])) {
            $errors['user_profile_pic'] = $userUploadResult['error'];
        } else {
            updateUserProfilePic($currentUserId, $userUploadResult['filename']);
        }
    }

    
    // If no errors, update book data
    if (empty($errors)) {
        $updatedBook = $book;
        $updatedBook['title'] = $title;
        $updatedBook['author'] = $author;
        $updatedBook['description'] = $description;
        $updatedBook['category'] = $category;
        $updatedBook['condition'] = $condition;
        $updatedBook['isbn'] = $isbn;
        $updatedBook['publication_year'] = $publicationYear;
        $updatedBook['publisher'] = $publisher;
        $updatedBook['pages'] = $pages;
        $updatedBook['language'] = $language;
        $updatedBook['updated_at'] = date('Y-m-d H:i:s');
        
        if ($uploadedImage) {
            $updatedBook['cover_image'] = $uploadedImage;
        }
        
        if (saveBookData($bookId, $updatedBook)) {
            $success = true;
            
            // Refresh book data
            $book = loadBookData($bookId);
            $coverImage = $book['cover_image'] ?? '';
            
            // Show success message
            $_SESSION['success'] = 'Book updated successfully!';
            header('Location: /book/?id=' . $bookId);
            exit;
        } else {
            $errors['general'] = 'Failed to save book. Please try again.';
        }
    }
}

// Get categories and conditions
$categories = getCategories();
$conditions = getConditions();

// Current cover image path
$currentCoverPath = !empty($coverImage) ? '/uploads/book_cover/' . $coverImage : '/assets/images/default-book-cover.jpg';
$currentThumbPath = !empty($coverImage) ? '/uploads/book_cover/thumb_' . $coverImage : '/assets/images/default-book-cover.jpg';
?>

<?php 
// Add page-specific styles
?>
<style>
    /* Mobile-first CSS for Edit Book Page */

    .edit-container {
        max-width: 800px;
        margin: 0 auto;
        padding: 1.25rem;
    }

    .container {
        padding: 1rem 0;
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    /* Sidebar styles */
    aside {
        order: 2; /* Sidebar below main content on mobile */
    }

    .profile-side-card {
        background: #fff;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 1rem;
        text-align: center;
        border: 1px solid #e5e7eb;
    }

    .mini-avatar-preview {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto 0.75rem;
        border: 3px solid #3b82f6;
        overflow: hidden;
        background: #f3f4f6;
    }

    .mini-avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Main content */
    .edit-container {
        order: 1; /* Main content first on mobile */
        background: #fff;
        border-radius: 12px;
        padding: 1.5rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        border: 1px solid #e5e7eb;
    }

    /* Page header */
    .page-header {
        margin-bottom: 2rem;
        text-align: center;
    }

    .page-header h1 {
        font-size: 1.875rem;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
    }

    .page-header p {
        color: #6b7280;
        font-size: 0.875rem;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem 1rem;
        background: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        color: #374151;
        text-decoration: none;
        font-size: 0.875rem;
        margin-bottom: 1rem;
        transition: all 0.2s;
    }

    .back-btn:hover {
        background: #e5e7eb;
    }

    /* Cover image section */
    .cover-section {
        text-align: center;
        margin-bottom: 2rem;
        padding: 1.5rem;
        background: #f9fafb;
        border-radius: 12px;
        border: 2px dashed #d1d5db;
    }

    .cover-preview {
        width: 120px;
        height: 160px;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        margin: 0 auto 1rem;
        cursor: pointer;
        transition: transform 0.2s;
        border: 2px solid #fff;
    }

    .cover-preview:hover {
        transform: scale(1.05);
    }

    .cover-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .cover-placeholder {
        width: 120px;
        height: 160px;
        background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%);
        border-radius: 8px;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        color: #6b7280;
        cursor: pointer;
        margin: 0 auto 1rem;
        transition: all 0.2s;
        border: 2px solid #d1d5db;
    }

    .cover-placeholder:hover {
        background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
        border-color: #9ca3af;
    }

    .cover-placeholder i {
        font-size: 1.5rem;
    }

    .image-hint {
        font-size: 0.75rem;
        color: #6b7280;
        text-align: center;
        margin-top: 0.5rem;
    }

    /* Form styles */
    .form-group {
        margin-bottom: 1.5rem;
    }

    .form-label {
        display: block;
        font-weight: 600;
        color: #374151;
        margin-bottom: 0.5rem;
        font-size: 0.875rem;
    }

    .form-input,
    .form-textarea,
    .form-select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.2s, box-shadow 0.2s;
        background: #fff;
    }

    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus {
        outline: none;
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
    }

    .form-textarea {
        resize: vertical;
        min-height: 120px;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }

    .form-error {
        color: #dc2626;
        font-size: 0.75rem;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    .char-counter {
        font-size: 0.75rem;
        color: #6b7280;
        text-align: right;
        margin-top: 0.25rem;
    }

    .char-counter.warning {
        color: #f59e0b;
    }

    .char-counter.danger {
        color: #dc2626;
    }

    .condition-help {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.25rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }

    /* Buttons */
    .btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        padding: 0.75rem 1.5rem;
        border-radius: 8px;
        font-weight: 600;
        text-decoration: none;
        transition: all 0.2s;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
    }

    .btn-primary {
        background: #3b82f6;
        color: #fff;
    }

    .btn-primary:hover {
        background: #2563eb;
    }

    .btn-outline {
        background: transparent;
        color: #374151;
        border: 1px solid #d1d5db;
    }

    .btn-outline:hover {
        background: #f9fafb;
    }

    .btn-sm {
        padding: 0.5rem 1rem;
        font-size: 0.75rem;
    }

    .btn-block {
        width: 100%;
    }

    /* Form actions */
    .form-actions {
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        margin-top: 2rem;
    }

    .form-actions .btn {
        flex: 1;
    }

    /* Alerts */
    .alert {
        padding: 1rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 0.875rem;
    }

    .alert-danger {
        background: #fef2f2;
        color: #dc2626;
        border: 1px solid #fecaca;
    }

    /* Quick tips */
    .quick-tips ul {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .quick-tips li {
        margin-bottom: 0.5rem;
        display: flex;
        align-items: flex-start;
        gap: 0.5rem;
        font-size: 0.75rem;
        color: #4b5563;
    }

    .quick-tips li::before {
        content: "✓";
        color: #10b981;
        font-weight: bold;
        flex-shrink: 0;
    }

    /* Tablet and up */
    @media (min-width: 768px) {
        .container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
            padding: 2rem 0;
        }

        aside {
            order: 0; /* Sidebar back to left on larger screens */
        }

        .edit-container {
            padding: 2rem;
        }

        .form-actions {
            flex-direction: row;
        }

        .form-actions .btn:last-child {
            flex: 0 0 auto;
        }

        .cover-preview {
            width: 150px;
            height: 200px;
        }

        .cover-placeholder {
            width: 150px;
            height: 200px;
        }
    }

    /* Mobile adjustments */
    @media (max-width: 640px) {
        .form-row {
            grid-template-columns: 1fr;
        }

        .edit-container {
            padding: 1rem;
        }

        .cover-preview {
            width: 100px;
            height: 133px;
        }

        .cover-placeholder {
            width: 100px;
            height: 133px;
        }
    }

    /* Utility classes */
    .text-danger {
        color: #dc2626;
    }

    .text-center {
        text-align: center;
    }

    /* Hide file inputs */
    input[type="file"] {
        display: none;
    }

    /* Dark Mode Overrides */
    [data-theme="dark"] .profile-side-card,
    [data-theme="dark"] .edit-container {
        background: #1e293b;
        border-color: #334155;
    }
    
    [data-theme="dark"] .page-header h1,
    [data-theme="dark"] .form-label,
    [data-theme="dark"] .profile-side-card h3 {
        color: #f8fafc;
    }

    [data-theme="dark"] .page-header p {
        color: #cbd5e1;
    }

    [data-theme="dark"] .back-btn {
        background: #334155;
        border-color: #475569;
        color: #e2e8f0;
    }

    [data-theme="dark"] .back-btn:hover {
        background: #475569;
    }

    [data-theme="dark"] .cover-section {
        background: #0f172a;
        border-color: #334155;
    }

    [data-theme="dark"] .cover-placeholder {
        background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
        border-color: #334155;
        color: #94a3b8;
    }

    [data-theme="dark"] .form-input,
    [data-theme="dark"] .form-textarea,
    [data-theme="dark"] .form-select {
        background: #0f172a;
        border-color: #334155;
        color: #e2e8f0;
    }

    [data-theme="dark"] .form-input:focus,
    [data-theme="dark"] .form-textarea:focus,
    [data-theme="dark"] .form-select:focus {
        border-color: #818cf8;
        box-shadow: 0 0 0 3px rgba(129, 140, 248, 0.2);
    }

    [data-theme="dark"] .btn-outline {
        color: #cbd5e1;
        border-color: #475569;
    }
    
    [data-theme="dark"] .btn-outline:hover {
        background: #334155;
        color: #f8fafc;
    }

    [data-theme="dark"] .quick-tips li {
        color: #cbd5e1;
    }
</style>

    <main>
        <div class="container">
            <!-- Left Sidebar for User Profile -->
            <aside>
                <div class="profile-side-card">
                    <h3>Your Profile</h3>
                    <div class="mini-avatar-preview">
                        <img src="<?php 
                            $avatar = $_SESSION['user_avatar'] ?? 'default-avatar.jpg';
                            echo "/uploads/profile/" . $avatar; 
                        ?>" alt="Avatar" id="userAvatarPreview" onerror="this.src='/assets/images/avatars/default.jpg'">
                    </div>
                    <p style="font-weight: 600; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($currentUserName); ?></p>
                    <p style="font-size: 0.75rem; color: #6b7280; margin-bottom: 1rem;">Book Owner</p>
                    
                    <button type="button" class="btn btn-outline btn-sm btn-block" onclick="document.getElementById('user_profile_pic').click()">
                        <i class="fas fa-camera"></i> Change Photo
                    </button>
                    <input type="file" name="user_profile_pic" id="user_profile_pic" form="editForm" accept="image/*">
                    
                    <?php if (isset($errors['user_profile_pic'])): ?>
                        <p class="text-danger" style="font-size: 0.75rem; margin-top: 0.5rem;"><?php echo $errors['user_profile_pic']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="profile-side-card quick-tips">
                    <h4 style="font-size: 0.875rem; margin-bottom: 0.5rem;">Quick Tips</h4>
                    <ul>
                        <li>Use a clear front cover image.</li>
                        <li>Detailed descriptions help buyers.</li>
                        <li>Honest condition reports build trust.</li>
                    </ul>
                </div>
            </aside>

            <div class="edit-container">

                <!-- Page Header -->
                <div class="page-header">
                    <a href="/book/?id=<?php echo $bookId; ?>" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Book
                    </a>
                    <h1>
                        <i class="fas fa-edit"></i>
                        Edit Book
                    </h1>
                    <p>Update your book information</p>
                </div>
                
                <!-- Error Messages -->
                <?php if (!empty($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo htmlspecialchars($errors['general']); ?>
                    </div>
                <?php endif; ?>
                
                <!-- Edit Form -->
                <form method="POST" enctype="multipart/form-data" id="editForm">
                    <!-- Cover Image Section -->
                    <div class="cover-section">
                        <label for="cover_image" style="cursor: pointer;">
                            <?php if (!empty($coverImage)): ?>
                                <div class="cover-preview" id="coverPreview">
                                    <img src="<?php echo $currentThumbPath; ?>" alt="Book Cover" id="coverImagePreview">
                                </div>
                            <?php else: ?>
                                <div class="cover-placeholder" id="coverPlaceholder">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Upload Cover</span>
                                    <span style="font-size: 0.7rem;">Click to change</span>
                                </div>
                            <?php endif; ?>
                        </label>
                        <input type="file" name="cover_image" id="cover_image" accept="image/jpeg,image/png,image/gif,image/webp" onchange="previewCover(this)">
                        <div class="image-hint">
                            <i class="fas fa-info-circle"></i>
                            Max size: 10MB. Supported: JPG, PNG, GIF, WebP
                        </div>
                        <?php if (isset($errors['cover_image'])): ?>
                            <div class="form-error text-center">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo htmlspecialchars($errors['cover_image']); ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Book Details -->
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-book"></i>
                            Book Title <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="title" class="form-input" 
                               value="<?php echo htmlspecialchars($title); ?>" 
                               maxlength="200" required>
                        <?php if (isset($errors['title'])): ?>
                            <div class="form-error"><?php echo $errors['title']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-user"></i>
                            Author Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" name="author" class="form-input" 
                               value="<?php echo htmlspecialchars($author); ?>" 
                               maxlength="100" required>
                        <?php if (isset($errors['author'])): ?>
                            <div class="form-error"><?php echo $errors['author']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-tag"></i>
                                Category <span class="text-danger">*</span>
                            </label>
                            <select name="category" class="form-select" required>
                                <option value="">Select a category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat); ?>" 
                                        <?php echo $category === $cat ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category'])): ?>
                                <div class="form-error"><?php echo $errors['category']; ?></div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-star"></i>
                                Condition <span class="text-danger">*</span>
                            </label>
                            <select name="condition" class="form-select" required>
                                <option value="">Select condition</option>
                                <?php foreach ($conditions as $key => $desc): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" 
                                        <?php echo $condition === $key ? 'selected' : ''; ?>
                                        title="<?php echo htmlspecialchars($desc); ?>">
                                        <?php echo htmlspecialchars($key); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="condition-help" style="font-size: var(--font-size-xs); color: var(--text-tertiary); margin-top: var(--space-1);">
                                <i class="fas fa-info-circle"></i>
                                <?php echo $conditions[$condition] ?? 'Select a condition for more info'; ?>
                            </div>
                            <?php if (isset($errors['condition'])): ?>
                                <div class="form-error"><?php echo $errors['condition']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-barcode"></i>
                                ISBN
                            </label>
                            <input type="text" name="isbn" class="form-input" 
                                   value="<?php echo htmlspecialchars($isbn); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-calendar"></i>
                                Publication Year
                            </label>
                            <input type="text" name="publication_year" class="form-input" 
                                   value="<?php echo htmlspecialchars($publicationYear); ?>" 
                                   placeholder="e.g., 2024">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-building"></i>
                                Publisher
                            </label>
                            <input type="text" name="publisher" class="form-input" 
                                   value="<?php echo htmlspecialchars($publisher); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-file-alt"></i>
                                Pages
                            </label>
                            <input type="number" name="pages" class="form-input" 
                                   value="<?php echo htmlspecialchars($pages); ?>" 
                                   min="1">
                            <?php if (isset($errors['pages'])): ?>
                                <div class="form-error"><?php echo $errors['pages']; ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-language"></i>
                            Language
                        </label>
                        <input type="text" name="language" class="form-input" 
                               value="<?php echo htmlspecialchars($language); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">
                            <i class="fas fa-align-left"></i>
                            Description <span class="text-danger">*</span>
                        </label>
                        <textarea name="description" class="form-textarea" 
                                  rows="6" maxlength="5000"
                                  oninput="updateCharCount(this)"><?php echo htmlspecialchars($description); ?></textarea>
                        <div class="char-counter" id="charCount">0/5000 characters</div>
                        <?php if (isset($errors['description'])): ?>
                            <div class="form-error"><?php echo $errors['description']; ?></div>
                        <?php endif; ?>
                    </div>
                    <!-- Form Actions -->
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                        <a href="/book/?id=<?php echo $bookId; ?>" class="btn btn-outline">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <script>
        // Preview user avatar before upload
        function previewUserAvatar(input) {
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('userAvatarPreview').src = e.target.result;
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        // Preview cover image before upload

        function previewCover(input) {
            const preview = document.getElementById('coverImagePreview');
            const placeholder = document.getElementById('coverPlaceholder');
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (preview) {
                        preview.src = e.target.result;
                    } else {
                        // Create preview element if it doesn't exist
                        const coverPreview = document.querySelector('.cover-preview');
                        if (!coverPreview) {
                            const newPreview = document.createElement('div');
                            newPreview.className = 'cover-preview';
                            newPreview.id = 'coverPreview';
                            newPreview.innerHTML = `<img src="${e.target.result}" id="coverImagePreview">`;
                            input.parentElement.insertBefore(newPreview, input);
                        } else {
                            document.getElementById('coverImagePreview').src = e.target.result;
                        }
                        if (placeholder) placeholder.style.display = 'none';
                    }
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        // Character counter for description
        function updateCharCount(textarea) {
            const count = textarea.value.length;
            const charCounter = document.getElementById('charCount');
            const maxLength = 5000;
            
            charCounter.textContent = `${count}/${maxLength} characters`;
            
            if (count > maxLength * 0.9) {
                charCounter.classList.add('danger');
                charCounter.classList.remove('warning');
            } else if (count > maxLength * 0.75) {
                charCounter.classList.add('warning');
                charCounter.classList.remove('danger');
            } else {
                charCounter.classList.remove('warning', 'danger');
            }
        }
        
        // Update condition help text
        document.querySelector('select[name="condition"]').addEventListener('change', function() {
            const condition = this.value;
            const helpText = this.options[this.selectedIndex]?.title || '';
            const helpElement = document.querySelector('.condition-help');
            if (helpElement && helpText) {
                helpElement.innerHTML = `<i class="fas fa-info-circle"></i> ${helpText}`;
            }
        });
        
        // Initialize character counter
        const descriptionField = document.querySelector('textarea[name="description"]');
        if (descriptionField) {
            updateCharCount(descriptionField);
        }
        
        // Form validation
        document.getElementById('editForm').addEventListener('submit', function(e) {
            const title = document.querySelector('input[name="title"]').value.trim();
            const author = document.querySelector('input[name="author"]').value.trim();
            const description = document.querySelector('textarea[name="description"]').value.trim();
            const category = document.querySelector('select[name="category"]').value;
            const condition = document.querySelector('select[name="condition"]').value;
            
            if (!title) {
                e.preventDefault();
                alert('Please enter the book title');
                return false;
            }
            
            if (!author) {
                e.preventDefault();
                alert('Please enter the author name');
                return false;
            }
            
            if (!description || description.length < 20) {
                e.preventDefault();
                alert('Please enter a description (minimum 20 characters)');
                return false;
            }
            
            if (!category) {
                e.preventDefault();
                alert('Please select a category');
                return false;
            }
            
            if (!condition) {
                e.preventDefault();
                alert('Please select a condition');
                return false;
            }
        });
    </script>
    
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>