<?php
/**
 * OpenShelf Add Book System
 * Stores book data in both /data/books.json (master) and /data/book/[book_id].json (detailed)
 */

session_start();

// Include database connection
require_once dirname(__DIR__) . '/includes/db.php';

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_DATA_PATH', dirname(__DIR__) . '/data/book/');
define('USERS_PATH', dirname(__DIR__) . '/users/');
define('UPLOAD_PATH', dirname(__DIR__) . '/uploads/book_cover/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
define('COVER_WIDTH', 800);
define('COVER_HEIGHT', 1200);
define('THUMBNAIL_SIZE', 300);
define('COMPRESSION_QUALITY', 85);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['redirect_after_login'] = '/add-book/';
    header('Location: /login/');
    exit;
}

$userId = $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Unknown';

/**
 * Generate a secure 10-character book ID
 */
function generateBookId() {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ123456789';
    $bookId = '';
    for ($i = 0; $i < 10; $i++) {
        $bookId .= $characters[random_int(0, strlen($characters) - 1)];
    }
    return $bookId;
}

/**
 * Check if book ID exists in database
 */
function bookIdExists($bookId) {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    return $stmt->fetch() !== false;
}

/**
 * Save book data to MySQL database
 */
function saveBookToDB($data) {
    $db = getDB();
    $sql = "INSERT INTO books (
                id, title, author, description, category, `condition`, 
                cover_image, owner_id, owner_name, status, created_at, 
                updated_at, views, times_borrowed, isbn, publication_year, 
                publisher, pages, language, reviews, comments, tags
            ) VALUES (
                :id, :title, :author, :description, :category, :condition, 
                :cover_image, :owner_id, :owner_name, :status, :created_at, 
                :updated_at, :views, :times_borrowed, :isbn, :publication_year, 
                :publisher, :pages, :language, :reviews, :comments, :tags
            )";
    
    $stmt = $db->prepare($sql);
    return $stmt->execute($data);
}

/**
 * Update user's owned books list
 */
function updateUserBooksList($userId, $bookId) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) return false;
    
    $userData = json_decode(file_get_contents($userFile), true);
    if (!isset($userData['owner_books'])) {
        $userData['owner_books'] = [];
    }
    $userData['owner_books'][] = $bookId;
    $userData['stats']['books_owned'] = count($userData['owner_books']);
    
    return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT));
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
    
    // Load image
    $image = null;
    switch ($mimeType) {
        case 'image/jpeg': $image = imagecreatefromjpeg($file['tmp_name']); break;
        case 'image/png': $image = imagecreatefrompng($file['tmp_name']); break;
        case 'image/gif': $image = imagecreatefromgif($file['tmp_name']); break;
        case 'image/webp': $image = imagecreatefromwebp($file['tmp_name']); break;
    }
    
    if (!$image) return ['error' => 'Failed to process image'];
    
    // Resize
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
    
    $resized = imagecreatetruecolor($newWidth, $newHeight);
    
    if ($mimeType === 'image/png' || $mimeType === 'image/gif') {
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $transparent = imagecolorallocatealpha($resized, 255, 255, 255, 127);
        imagefilledrectangle($resized, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Create thumbnail
    $thumb = imagecreatetruecolor(THUMBNAIL_SIZE, THUMBNAIL_SIZE);
    $size = min($width, $height);
    $x = ($width - $size) / 2;
    $y = ($height - $size) / 2;
    
    imagecopyresampled($thumb, $image, 0, 0, $x, $y, THUMBNAIL_SIZE, THUMBNAIL_SIZE, $size, $size);
    
    // Save images
    imagewebp($resized, $webpPath, COMPRESSION_QUALITY);
    imagewebp($thumb, UPLOAD_PATH . 'thumb_' . $webpFilename, COMPRESSION_QUALITY);
    
    imagedestroy($image);
    imagedestroy($resized);
    imagedestroy($thumb);
    
    return ['success' => true, 'filename' => $webpFilename];
}

/**
 * Get list of categories
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
 * Get list of conditions
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

// Initialize variables
$title = $author = $description = $category = $condition = '';
$errors = [];
$success = false;

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $title = trim($_POST['title'] ?? '');
    $author = trim($_POST['author'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $condition = trim($_POST['condition'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $publicationYear = trim($_POST['publication_year'] ?? '');
    $publisher = trim($_POST['publisher'] ?? '');
    $pages = trim($_POST['pages'] ?? '');
    $language = trim($_POST['language'] ?? 'English');
    
    // Validation
    if (empty($title)) $errors['title'] = 'Book title is required';
    elseif (strlen($title) < 2) $errors['title'] = 'Title must be at least 2 characters';
    elseif (strlen($title) > 200) $errors['title'] = 'Title must be less than 200 characters';
    
    if (empty($author)) $errors['author'] = 'Author name is required';
    elseif (strlen($author) < 2) $errors['author'] = 'Author name must be at least 2 characters';
    elseif (strlen($author) > 100) $errors['author'] = 'Author name must be less than 100 characters';
    
    if (empty($description)) $errors['description'] = 'Description is required';
    elseif (strlen($description) < 20) $errors['description'] = 'Description must be at least 20 characters';
    elseif (strlen($description) > 5000) $errors['description'] = 'Description must be less than 5000 characters';
    
    if (empty($category)) $errors['category'] = 'Please select a category';
    if (empty($condition)) $errors['condition'] = 'Please select a condition';
    
    // Handle image upload
    if (!isset($_FILES['cover_image']) || $_FILES['cover_image']['error'] === UPLOAD_ERR_NO_FILE) {
        $errors['cover_image'] = 'Book cover image is required';
    }
    
    if (empty($errors)) {
        // Generate unique book ID
        do {
            $bookId = generateBookId();
        } while (bookIdExists($bookId));
        
        // Process image
        $uploadResult = processCoverImage($_FILES['cover_image'], $bookId);
        
        if (isset($uploadResult['error'])) {
            $errors['cover_image'] = $uploadResult['error'];
        } else {
            $coverImage = $uploadResult['filename'];
            
            // Prepare book data for DB
            $bookData = [
                'id' => $bookId,
                'title' => $title,
                'author' => $author,
                'description' => $description,
                'category' => $category,
                'condition' => $condition,
                'cover_image' => $coverImage,
                'owner_id' => $userId,
                'owner_name' => $userName,
                'status' => 'available',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
                'views' => 0,
                'times_borrowed' => 0,
                'isbn' => $isbn ?: null,
                'publication_year' => $publicationYear ?: null,
                'publisher' => $publisher ?: null,
                'pages' => $pages ? intval($pages) : null,
                'language' => $language,
                'reviews' => json_encode([]),
                'comments' => json_encode([]),
                'tags' => json_encode([])
            ];
            
            // Save to database
            $saved = saveBookToDB($bookData);
            $userUpdated = updateUserBooksList($userId, $bookId);
            
            if ($saved && $userUpdated) {
                $success = true;
                $addedBookId = $bookId;
                
                // Clear form
                $title = $author = $description = '';
                $category = $condition = '';
            } else {
                $errors['general'] = 'Failed to save book to database. Please try again.';
                
                // Clean up uploaded image if save failed
                if (file_exists(UPLOAD_PATH . $coverImage)) {
                    unlink(UPLOAD_PATH . $coverImage);
                    unlink(UPLOAD_PATH . 'thumb_' . $coverImage);
                }
            }
        }
    }
}

$categories = getCategories();
$conditions = getConditions();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Book - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .add-container { max-width: 800px; margin: 0 auto; padding: var(--space-5); }
        .cover-preview { width: 150px; height: 200px; margin: 0 auto var(--space-4); border-radius: var(--radius-lg); overflow: hidden; box-shadow: var(--shadow-md); cursor: pointer; transition: transform var(--transition-fast); }
        .cover-preview:hover { transform: scale(1.02); }
        .cover-preview img { width: 100%; height: 100%; object-fit: cover; }
        .cover-placeholder { width: 150px; height: 200px; margin: 0 auto var(--space-4); background: var(--surface-hover); border-radius: var(--radius-lg); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: var(--space-2); color: var(--text-tertiary); cursor: pointer; transition: all var(--transition-fast); }
        .cover-placeholder:hover { background: var(--border); transform: scale(1.02); }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: var(--space-4); }
        @media (max-width: 640px) { .form-row { grid-template-columns: 1fr; } .add-container { padding: var(--space-4); } }
        .char-counter { font-size: var(--font-size-xs); color: var(--text-tertiary); text-align: right; margin-top: var(--space-1); }
        .char-counter.danger { color: var(--danger); }
        .char-counter.warning { color: var(--warning); }
    </style>
</head>
<body>
    <?php include dirname(__DIR__) . '/includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="add-container">
                <div style="margin-bottom: var(--space-6);">
                    <h1><i class="fas fa-plus-circle" style="color: var(--primary);"></i> Add New Book</h1>
                    <p class="text-muted">Share your book with the community</p>
                </div>
                
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> Book added successfully!
                        <a href="/book/?id=<?php echo $addedBookId; ?>" class="btn btn-sm btn-outline ms-3">View Book</a>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger"><?php echo $errors['general']; ?></div>
                <?php endif; ?>
                
                <form method="POST" enctype="multipart/form-data" id="addForm">
                    <!-- Cover Image -->
                    <div class="text-center mb-4">
                        <label for="cover_image" style="cursor: pointer;">
                            <div id="coverPreview" class="cover-placeholder">
                                <i class="fas fa-cloud-upload-alt fa-2x"></i>
                                <span>Click to upload cover</span>
                            </div>
                        </label>
                        <input type="file" name="cover_image" id="cover_image" accept="image/jpeg,image/png,image/gif,image/webp" style="display: none;" onchange="previewCover(this)">
                        <div class="small text-muted">Max size: 10MB. Supported: JPG, PNG, GIF, WebP</div>
                        <?php if (isset($errors['cover_image'])): ?>
                            <div class="text-danger small mt-1"><?php echo $errors['cover_image']; ?></div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group"><label class="form-label">Book Title *</label><input type="text" name="title" class="form-input" value="<?php echo htmlspecialchars($title); ?>" maxlength="200" required><?php if(isset($errors['title'])) echo '<div class="text-danger small">'.$errors['title'].'</div>'; ?></div>
                    <div class="form-group"><label class="form-label">Author Name *</label><input type="text" name="author" class="form-input" value="<?php echo htmlspecialchars($author); ?>" maxlength="100" required><?php if(isset($errors['author'])) echo '<div class="text-danger small">'.$errors['author'].'</div>'; ?></div>
                    
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Category *</label><select name="category" class="form-select" required><option value="">Select category</option><?php foreach($categories as $cat): ?><option value="<?php echo $cat; ?>" <?php echo $category===$cat?'selected':''; ?>><?php echo $cat; ?></option><?php endforeach; ?></select><?php if(isset($errors['category'])) echo '<div class="text-danger small">'.$errors['category'].'</div>'; ?></div>
                        <div class="form-group"><label class="form-label">Condition *</label><select name="condition" class="form-select" required><option value="">Select condition</option><?php foreach($conditions as $key=>$desc): ?><option value="<?php echo $key; ?>" <?php echo $condition===$key?'selected':''; ?> title="<?php echo $desc; ?>"><?php echo $key; ?></option><?php endforeach; ?></select><?php if(isset($errors['condition'])) echo '<div class="text-danger small">'.$errors['condition'].'</div>'; ?></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">ISBN</label><input type="text" name="isbn" class="form-input" value="<?php echo htmlspecialchars($isbn ?? ''); ?>"></div>
                        <div class="form-group"><label class="form-label">Publication Year</label><input type="text" name="publication_year" class="form-input" value="<?php echo htmlspecialchars($publicationYear ?? ''); ?>"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group"><label class="form-label">Publisher</label><input type="text" name="publisher" class="form-input" value="<?php echo htmlspecialchars($publisher ?? ''); ?>"></div>
                        <div class="form-group"><label class="form-label">Pages</label><input type="number" name="pages" class="form-input" value="<?php echo htmlspecialchars($pages ?? ''); ?>" min="1"></div>
                    </div>
                    
                    <div class="form-group"><label class="form-label">Language</label><input type="text" name="language" class="form-input" value="<?php echo htmlspecialchars($language ?? 'English'); ?>"></div>
                    
                    <div class="form-group"><label class="form-label">Description *</label><textarea name="description" class="form-textarea" rows="6" maxlength="5000" oninput="updateCharCount(this)"><?php echo htmlspecialchars($description); ?></textarea><div class="char-counter" id="charCount">0/5000 characters</div><?php if(isset($errors['description'])) echo '<div class="text-danger small">'.$errors['description'].'</div>'; ?></div>
                    
                    <div class="d-flex gap-3 mt-4"><button type="submit" class="btn btn-primary flex-grow-1">Add Book to Library</button><a href="/profile/" class="btn btn-outline">Cancel</a></div>
                </form>
            </div>
        </div>
    </main>
    
    <script>
        function previewCover(input) {
            const preview = document.getElementById('coverPreview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.innerHTML = `<img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">`;
                    preview.classList.remove('cover-placeholder');
                    preview.classList.add('cover-preview');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }
        function updateCharCount(textarea) {
            const count = textarea.value.length;
            const max = 5000;
            const counter = document.getElementById('charCount');
            counter.textContent = `${count}/${max} characters`;
            if (count > max * 0.9) counter.classList.add('danger'), counter.classList.remove('warning');
            else if (count > max * 0.75) counter.classList.add('warning'), counter.classList.remove('danger');
            else counter.classList.remove('warning', 'danger');
        }
        const desc = document.querySelector('textarea[name="description"]');
        if (desc) updateCharCount(desc);
    </script>
    
    <?php include dirname(__DIR__) . '/includes/footer.php'; ?>
</body>
</html>