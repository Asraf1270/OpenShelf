<?php
/**
 * OpenShelf Edit Book Page
 * Allows book owners to edit their book details
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_PATH', dirname(__DIR__) . '/books/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/book_cover/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('COVER_WIDTH', 800);
define('COVER_HEIGHT', 1200);
define('COMPRESSION_QUALITY', 85);

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
 * Load book data
 */
function loadBookData($bookId) {
    $bookFile = BOOKS_PATH . $bookId . '.json';
    if (!file_exists($bookFile)) return null;
    return json_decode(file_get_contents($bookFile), true);
}

/**
 * Save book data to both files
 */
function saveBookData($bookId, $bookData) {
    // Save individual book file
    $bookFile = BOOKS_PATH . $bookId . '.json';
    $individualSaved = file_put_contents(
        $bookFile,
        json_encode($bookData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
    
    // Update master books.json
    $masterBooksFile = DATA_PATH . 'books.json';
    if (file_exists($masterBooksFile)) {
        $masterBooks = json_decode(file_get_contents($masterBooksFile), true);
        foreach ($masterBooks as &$book) {
            if ($book['id'] === $bookId) {
                $book['title'] = $bookData['title'];
                $book['author'] = $bookData['author'];
                $book['description'] = $bookData['description'];
                $book['category'] = $bookData['category'];
                $book['condition'] = $bookData['condition'];
                $book['isbn'] = $bookData['isbn'];
                $book['publication_year'] = $bookData['publication_year'];
                $book['publisher'] = $bookData['publisher'];
                $book['pages'] = $bookData['pages'];
                $book['language'] = $bookData['language'];
                $book['updated_at'] = $bookData['updated_at'];
                break;
            }
        }
        file_put_contents($masterBooksFile, json_encode($masterBooks, JSON_PRETTY_PRINT));
    }
    
    return $individualSaved;
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
 * Update user profile picture in JSON files
 */
function updateUserProfilePic($userId, $filename) {
    $uploadPath = dirname(__DIR__) . '/uploads/profile/';
    
    // Update master users.json
    $usersFile = DATA_PATH . 'users.json';
    if (file_exists($usersFile)) {
        $users = json_decode(file_get_contents($usersFile), true);
        foreach ($users as &$user) {
            if ($user['id'] === $userId) {
                $oldPic = $user['profile_pic'] ?? 'default-avatar.jpg';
                
                // Delete previous profile pic if it's not the default
                if ($oldPic !== 'default-avatar.jpg' && $oldPic !== $filename) {
                    $oldFilePath = $uploadPath . $oldPic;
                    if (file_exists($oldFilePath)) {
                        unlink($oldFilePath);
                    }
                }
                
                $user['profile_pic'] = $filename;
                break;
            }
        }
        file_put_contents($usersFile, json_encode($users, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
    /* Edit Book Specific Styles */
    .edit-container {
        max-width: 800px;
        margin: 0 auto;
        padding: var(--space-5);
    }
    
    .cover-preview {
        width: 150px;
        height: 200px;
        border-radius: var(--radius-lg);
        overflow: hidden;
        box-shadow: var(--shadow-md);
        margin: 0 auto var(--space-4);
        cursor: pointer;
        transition: all var(--transition-fast);
    }
    
    .cover-preview:hover {
        transform: scale(1.02);
        box-shadow: var(--shadow-lg);
    }
    
    .cover-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    .cover-placeholder {
        width: 150px;
        height: 200px;
        background: var(--surface-hover);
        border-radius: var(--radius-lg);
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: var(--space-2);
        color: var(--text-tertiary);
        cursor: pointer;
        margin: 0 auto var(--space-4);
        transition: all var(--transition-fast);
    }
    
    .cover-placeholder:hover {
        background: var(--border);
        transform: scale(1.02);
    }
    
    .cover-placeholder i {
        font-size: 2rem;
    }
    
    .image-hint {
        font-size: var(--font-size-xs);
        color: var(--text-tertiary);
        text-align: center;
        margin-top: var(--space-2);
    }
    
    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--space-4);
    }
    
    @media (max-width: 640px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .edit-container {
            padding: var(--space-4);
        }
    }
    
    .char-counter {
        font-size: var(--font-size-xs);
        color: var(--text-tertiary);
        text-align: right;
        margin-top: var(--space-1);
    }
    
    .char-counter.warning {
        color: var(--warning);
    }
    
    .char-counter.danger {
        color: var(--danger);
    }

    /* Change Profile Picture Side Panel */
    .profile-side-card {
        background: white;
        border-radius: var(--radius-lg);
        padding: var(--space-4);
        box-shadow: var(--shadow-sm);
        margin-bottom: var(--space-4);
        text-align: center;
    }

    .mini-avatar-preview {
        width: 80px;
        height: 80px;
        border-radius: 50%;
        margin: 0 auto var(--space-2);
        border: 2px solid var(--primary);
        overflow: hidden;
    }

    .mini-avatar-preview img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
</style>

    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    
    <main>
        <div class="container" style="display: grid; grid-template-columns: 280px 1fr; gap: var(--space-6); padding: var(--space-6) 0;">
            <!-- Left Sidebar for User Profile -->
            <aside>
                <div class="profile-side-card">
                    <h3 style="font-size: var(--font-size-md); margin-bottom: var(--space-3);">Your Profile</h3>
                    <div class="mini-avatar-preview">
                        <img src="<?php 
                            $avatar = $_SESSION['user_avatar'] ?? 'default-avatar.jpg';
                            echo "/uploads/profile/" . $avatar; 
                        ?>" alt="Avatar" id="userAvatarPreview" onerror="this.src='/assets/images/avatars/default.jpg'">
                    </div>
                    <p style="font-weight: 600; margin-bottom: var(--space-1);"><?php echo htmlspecialchars($currentUserName); ?></p>
                    <p style="font-size: var(--font-size-xs); color: var(--text-tertiary); margin-bottom: var(--space-4);">Book Owner</p>
                    
                    <button type="button" class="btn btn-outline btn-sm btn-block" onclick="document.getElementById('user_profile_pic').click()">
                        <i class="fas fa-camera"></i> Change Photo
                    </button>
                    <input type="file" name="user_profile_pic" id="user_profile_pic" form="editForm" accept="image/*" style="display: none;" onchange="previewUserAvatar(this)">
                    
                    <?php if (isset($errors['user_profile_pic'])): ?>
                        <p class="text-danger" style="font-size: var(--font-size-xs); margin-top: var(--space-2);"><?php echo $errors['user_profile_pic']; ?></p>
                    <?php endif; ?>
                </div>

                <div class="profile-side-card" style="text-align: left;">
                    <h4 style="font-size: var(--font-size-sm); margin-bottom: var(--space-2);">Quick Tips</h4>
                    <ul style="font-size: var(--font-size-xs); color: var(--text-secondary); padding-left: var(--space-3);">
                        <li style="margin-bottom: var(--space-1);">Use a clear front cover image.</li>
                        <li style="margin-bottom: var(--space-1);">Detailed descriptions help buyers.</li>
                        <li>Honest condition reports build trust.</li>
                    </ul>
                </div>
            </aside>

            <div class="edit-container" style="margin: 0; max-width: none;">

                <!-- Page Header -->
                <div style="margin-bottom: var(--space-6);">
                    <div style="display: flex; align-items: center; gap: var(--space-3); margin-bottom: var(--space-2);">
                        <a href="/book/?id=<?php echo $bookId; ?>" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Book
                        </a>
                    </div>
                    <h1 style="font-size: var(--font-size-xl); margin-bottom: var(--space-2);">
                        <i class="fas fa-edit" style="color: var(--primary);"></i>
                        Edit Book
                    </h1>
                    <p style="color: var(--text-tertiary);">Update your book information</p>
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
                    <div style="text-align: center; margin-bottom: var(--space-6);">
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
                        <input type="file" name="cover_image" id="cover_image" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="previewCover(this)">
                        <div class="image-hint">
                            <i class="fas fa-info-circle"></i>
                            Max size: 10MB. Supported: JPG, PNG, GIF, WebP
                        </div>
                        <?php if (isset($errors['cover_image'])): ?>
                            <div class="form-error" style="justify-content: center;">
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
                    <div style="display: flex; gap: var(--space-3); margin-top: var(--space-6);">
                        <button type="submit" class="btn btn-primary" style="flex: 2;">
                            <i class="fas fa-save"></i>
                            Save Changes
                        </button>
                        <a href="/book/?id=<?php echo $bookId; ?>" class="btn btn-outline" style="flex: 1;">
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