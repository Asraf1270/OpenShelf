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
        'Fiction',
        'Self Development',
        'Science',
        'Religion',
        'Islamic',
        'Technology',
        'Business',
        'Health',
        'Arts',
        'Education',
        'History',
        'Biography',
        'Law'
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
    :root {
        --primary: #2C3E50;
        --secondary: #4C9F8A;
        --accent: #3A7B6B;
        --bg: #F8F9FA;
        --surface: #ffffff;
        --border: #E2E8F0;
        --text-main: #0F172A;
        --text-muted: #5A6C7D;
        --radius: 16px;
        --shadow: 0 10px 25px rgba(44, 62, 80, 0.05);
        --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    [data-theme="dark"] {
        --bg: #0F172A;
        --surface: #1E293B;
        --border: #334155;
        --text-main: #F8F9FA;
        --text-muted: #94A3B8;
        --shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    body {
        background: var(--bg);
        color: var(--text-main);
        transition: background 0.3s ease;
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }

    .edit-page-container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 1.5rem;
        display: grid;
        grid-template-columns: 1fr;
        gap: 1.5rem;
    }

    @media (min-width: 992px) {
        .edit-page-container {
            grid-template-columns: 1fr 320px;
        }
    }

    /* Sidebar styles */
    aside {
        order: 2; /* Sidebar below main content on mobile */
    }

    .profile-side-card {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        text-align: center;
        border: 1px solid var(--border);
    }

    .mini-avatar-preview {
        width: 80px;
        height: 80px;
        border-radius: 20px;
        margin: 0 auto 0.75rem;
        border: 3px solid var(--secondary);
        overflow: hidden;
        background: var(--bg);
    }

    .mini-avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* Main content */
    .edit-form-card {
        background: var(--surface);
        border-radius: var(--radius);
        padding: 1.5rem;
        box-shadow: var(--shadow);
        border: 1px solid var(--border);
    }

    /* Page header */
    .page-header {
        margin-bottom: 2rem;
        text-align: center;
    }

    .page-header h1 {
        font-size: 2rem;
        font-weight: 850;
        color: var(--text-main);
        margin-bottom: 0.5rem;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.75rem;
        letter-spacing: -1px;
    }

    .page-header p {
        color: var(--text-muted);
        font-size: 0.95rem;
        font-weight: 500;
    }

    .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        background: var(--surface);
        border: 1px solid var(--border);
        border-radius: 10px;
        color: var(--text-main);
        text-decoration: none;
        font-size: 0.9rem;
        font-weight: 600;
        margin-bottom: 1.5rem;
        transition: var(--transition);
    }

    .back-btn:hover {
        background: var(--bg);
        transform: translateX(-3px);
    }

    /* Cover image section */
    .cover-section {
        text-align: center;
        margin-bottom: 2rem;
        padding: 2rem;
        background: var(--bg);
        border-radius: var(--radius);
        border: 2px dashed var(--border);
        transition: var(--transition);
    }

    .cover-section:hover {
        border-color: var(--secondary);
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
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 0.6rem;
        font-size: 0.9rem;
    }

    .form-input,
    .form-textarea,
    .form-select {
        width: 100%;
        padding: 0.85rem;
        border: 1px solid var(--border);
        border-radius: 12px;
        font-size: 1rem;
        transition: var(--transition);
        background: var(--surface);
        color: var(--text-main);
    }

    .form-input:focus,
    .form-textarea:focus,
    .form-select:focus {
        outline: none;
        border-color: var(--secondary);
        box-shadow: 0 0 0 4px rgba(76, 159, 138, 0.1);
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
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: #fff;
    }

    .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(44, 62, 80, 0.2);
        filter: brightness(1.1);
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

</style>

    <main class="edit-page-container">
        <!-- Main Form Column -->
        <div class="edit-form-card">
            <a href="/book/?id=<?php echo $bookId; ?>" class="back-btn">
                <i class="fas fa-arrow-left"></i> Back to Book
            </a>

            <!-- Page Header -->
            <div class="page-header">
                <h1><i class="fas fa-edit" style="color: var(--secondary);"></i> Edit Book</h1>
                <p>Update your book's information and keep your shelf fresh.</p>
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
                                    <?php echo $condition === $key ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($key); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
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

        <!-- Sidebar Column -->
        <aside class="edit-sidebar">
            <div class="profile-side-card">
                <h3 style="font-size: 1.1rem; font-weight: 800; margin-bottom: 1rem;">Owner Identity</h3>
                <div class="mini-avatar-preview">
                    <img src="<?php 
                        $avatar = $_SESSION['user_avatar'] ?? 'default-avatar.jpg';
                        echo "/uploads/profile/" . $avatar; 
                    ?>" alt="Avatar" id="userAvatarPreview" onerror="this.src='/assets/images/default-avatar.jpg'">
                </div>
                <p style="font-weight: 700; margin-bottom: 0.25rem;"><?php echo htmlspecialchars($currentUserName); ?></p>
                <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 1.25rem;">OpenShelf Member</p>
                
                <button type="button" class="btn btn-outline btn-sm btn-block" onclick="document.getElementById('user_profile_pic').click()">
                    <i class="fas fa-camera"></i> Change Photo
                </button>
                <input type="file" name="user_profile_pic" id="user_profile_pic" form="editForm" accept="image/*" onchange="previewUserAvatar(this)">
                
                <?php if (isset($errors['user_profile_pic'])): ?>
                    <p class="text-danger" style="font-size: 0.75rem; margin-top: 0.5rem;"><?php echo $errors['user_profile_pic']; ?></p>
                <?php endif; ?>
            </div>

            <div class="profile-side-card" style="text-align: left;">
                <h4 style="font-size: 0.9rem; font-weight: 800; margin-bottom: 0.75rem;">Quality Guidelines</h4>
                <ul style="list-style: none; padding: 0; margin: 0; font-size: 0.85rem; color: var(--text-muted); line-height: 1.6;">
                    <li style="margin-bottom: 0.5rem;"><i class="fas fa-check" style="color: var(--secondary); margin-right: 8px;"></i> High quality covers attract 3x more borrowers.</li>
                    <li style="margin-bottom: 0.5rem;"><i class="fas fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Honest condition reports build lasting trust.</li>
                    <li><i class="fas fa-check" style="color: var(--secondary); margin-right: 8px;"></i> Accurate ISBNs help people find your book.</li>
                </ul>
            </div>
        </aside>
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