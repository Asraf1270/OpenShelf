<?php
/**
 * OpenShelf Admin Book Management
 * Modern UI with enhanced features, filtering, and bulk actions
 */

session_start();

// Configuration
define('DATA_PATH', dirname(__DIR__, 2) . '/data/');
define('BOOKS_PATH', dirname(__DIR__, 2) . '/books/');
define('USERS_PATH', dirname(__DIR__, 2) . '/users/');
define('UPLOAD_PATH', dirname(__DIR__, 2) . '/uploads/book_cover/');

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/**
 * Load all books from master JSON
 */
function loadAllBooks() {
    $booksFile = DATA_PATH . 'books.json';
    if (!file_exists($booksFile)) return [];
    return json_decode(file_get_contents($booksFile), true) ?? [];
}

/**
 * Load detailed book data
 */
function loadBookData($bookId) {
    $bookFile = BOOKS_PATH . $bookId . '.json';
    if (!file_exists($bookFile)) return null;
    return json_decode(file_get_contents($bookFile), true);
}

/**
 * Update book status
 */
function updateBookStatus($bookId, $status, $reason = '') {
    global $adminId;
    
    // Update master books.json
    $books = loadAllBooks();
    $updated = false;
    
    foreach ($books as &$book) {
        if ($book['id'] === $bookId) {
            $book['status'] = $status;
            $book['updated_at'] = date('Y-m-d H:i:s');
            $book['status_updated_by'] = $adminId;
            if ($status === 'unavailable') {
                $book['unavailable_reason'] = $reason;
            }
            $updated = true;
            break;
        }
    }
    
    if ($updated) {
        file_put_contents(DATA_PATH . 'books.json', json_encode($books, JSON_PRETTY_PRINT));
        
        // Update individual book file
        $bookData = loadBookData($bookId);
        if ($bookData) {
            $bookData['status'] = $status;
            $bookData['updated_at'] = date('Y-m-d H:i:s');
            $bookData['status_updated_by'] = $adminId;
            if ($status === 'unavailable') {
                $bookData['unavailable_reason'] = $reason;
            }
            file_put_contents(BOOKS_PATH . $bookId . '.json', json_encode($bookData, JSON_PRETTY_PRINT));
        }
        
        return true;
    }
    return false;
}

/**
 * Delete book
 */
function deleteBook($bookId) {
    $books = loadAllBooks();
    $bookIndex = -1;
    $bookData = null;
    
    foreach ($books as $index => $book) {
        if ($book['id'] === $bookId) {
            $bookIndex = $index;
            $bookData = $book;
            break;
        }
    }
    
    if ($bookIndex >= 0) {
        array_splice($books, $bookIndex, 1);
        $masterSaved = file_put_contents(DATA_PATH . 'books.json', json_encode($books, JSON_PRETTY_PRINT));
        
        // Delete individual book file
        $bookFile = BOOKS_PATH . $bookId . '.json';
        if (file_exists($bookFile)) {
            $archiveDir = DATA_PATH . 'archive/books/';
            if (!file_exists($archiveDir)) mkdir($archiveDir, 0755, true);
            rename($bookFile, $archiveDir . $bookId . '_' . time() . '.json');
        }
        
        // Delete cover images
        if (!empty($bookData['cover_image'])) {
            $coverPath = UPLOAD_PATH . $bookData['cover_image'];
            $thumbPath = UPLOAD_PATH . 'thumb_' . $bookData['cover_image'];
            if (file_exists($coverPath)) unlink($coverPath);
            if (file_exists($thumbPath)) unlink($thumbPath);
        }
        
        // Update user's book list
        updateUserBookList($bookData['owner_id'], $bookId, 'remove');
        
        return true;
    }
    return false;
}

/**
 * Update user's book list
 */
function updateUserBookList($userId, $bookId, $action) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) return false;
    
    $userData = json_decode(file_get_contents($userFile), true);
    
    if ($action === 'remove' && isset($userData['owner_books'])) {
        $userData['owner_books'] = array_values(array_filter($userData['owner_books'], fn($id) => $id !== $bookId));
        $userData['stats']['books_owned'] = count($userData['owner_books']);
        return file_put_contents($userFile, json_encode($userData, JSON_PRETTY_PRINT));
    }
    return false;
}

/**
 * Get user name by ID
 */
function getUserName($userId) {
    $usersFile = DATA_PATH . 'users.json';
    if (!file_exists($usersFile)) return 'Unknown';
    $users = json_decode(file_get_contents($usersFile), true) ?? [];
    foreach ($users as $user) {
        if ($user['id'] === $userId) return $user['name'];
    }
    return 'Unknown';
}

/**
 * Get unique categories
 */
function getAllCategories($books) {
    $categories = [];
    foreach ($books as $book) {
        if (!empty($book['category']) && !in_array($book['category'], $categories)) {
            $categories[] = $book['category'];
        }
    }
    sort($categories);
    return $categories;
}

// Load books
$books = loadAllBooks();

// Filters
$status = $_GET['status'] ?? 'all';
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

$filteredBooks = $books;
if ($status !== 'all') {
    $filteredBooks = array_filter($filteredBooks, fn($b) => ($b['status'] ?? '') === $status);
}
if ($category !== 'all') {
    $filteredBooks = array_filter($filteredBooks, fn($b) => ($b['category'] ?? '') === $category);
}
if (!empty($search)) {
    $searchLower = strtolower($search);
    $filteredBooks = array_filter($filteredBooks, fn($b) => 
        strpos(strtolower($b['title'] ?? ''), $searchLower) !== false ||
        strpos(strtolower($b['author'] ?? ''), $searchLower) !== false
    );
}

$categories = getAllCategories($books);
$totalBooks = count($books);
$availableBooks = count(array_filter($books, fn($b) => ($b['status'] ?? '') === 'available'));
$borrowedBooks = count(array_filter($books, fn($b) => ($b['status'] ?? '') === 'borrowed'));
$unavailableBooks = count(array_filter($books, fn($b) => ($b['status'] ?? '') === 'unavailable'));

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$total = count($filteredBooks);
$totalPages = ceil($total / $perPage);
$offset = ($page - 1) * $perPage;
$paginatedBooks = array_slice($filteredBooks, $offset, $perPage);

// Handle actions
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $bookId = $_POST['book_id'] ?? '';
    
    if ($action === 'update_status') {
        $status = $_POST['status'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        if (updateBookStatus($bookId, $status, $reason)) {
            $message = 'Book status updated successfully';
        } else {
            $error = 'Failed to update book status';
        }
    } elseif ($action === 'delete_book') {
        if (deleteBook($bookId)) {
            $message = 'Book deleted successfully';
        } else {
            $error = 'Failed to delete book';
        }
    } elseif ($action === 'bulk_delete') {
        $bookIds = $_POST['book_ids'] ?? [];
        $count = 0;
        foreach ($bookIds as $bid) {
            if (deleteBook($bid)) $count++;
        }
        $message = "Deleted {$count} books successfully";
    } elseif ($action === 'bulk_update_status') {
        $bookIds = $_POST['book_ids'] ?? [];
        $status = $_POST['bulk_status'] ?? '';
        $reason = trim($_POST['bulk_reason'] ?? '');
        $count = 0;
        foreach ($bookIds as $bid) {
            if (updateBookStatus($bid, $status, $reason)) $count++;
        }
        $message = "Updated status for {$count} books successfully";
    }
    
    // Reload books
    $books = loadAllBooks();
    $filteredBooks = $books;
    if ($status !== 'all') $filteredBooks = array_filter($filteredBooks, fn($b) => ($b['status'] ?? '') === $status);
    if ($category !== 'all') $filteredBooks = array_filter($filteredBooks, fn($b) => ($b['category'] ?? '') === $category);
    if (!empty($search)) $filteredBooks = array_filter($filteredBooks, fn($b) => 
        strpos(strtolower($b['title'] ?? ''), $searchLower ?? '') !== false ||
        strpos(strtolower($b['author'] ?? ''), $searchLower ?? '') !== false
    );
    $paginatedBooks = array_slice($filteredBooks, $offset, $perPage);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Management - OpenShelf Admin</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        /* Book Management Styles */
        .stats-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.25rem;
            text-align: center;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            color: #64748b;
            font-size: 0.85rem;
        }
        
        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        .filter-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        
        .filter-select {
            padding: 0.5rem 1rem;
            border: 1px solid #e2e8f0;
            border-radius: 2rem;
            background: white;
            font-size: 0.85rem;
            cursor: pointer;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-box input {
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 2rem;
            width: 250px;
            font-size: 0.85rem;
        }
        
        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .books-table-container {
            background: white;
            border-radius: 1rem;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .books-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .books-table th {
            text-align: left;
            padding: 1rem;
            background: #f8fafc;
            color: #475569;
            font-weight: 600;
            font-size: 0.85rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .books-table td {
            padding: 1rem;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }
        
        .books-table tr:hover td {
            background: #f8fafc;
        }
        
        .book-cover-small {
            width: 45px;
            height: 60px;
            border-radius: 0.5rem;
            object-fit: cover;
            background: #f1f5f9;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.25rem 0.75rem;
            border-radius: 2rem;
            font-size: 0.7rem;
            font-weight: 600;
        }
        
        .status-available {
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
        }
        
        .status-borrowed {
            background: rgba(245, 158, 11, 0.1);
            color: #f59e0b;
        }
        
        .status-reserved {
            background: rgba(99, 102, 241, 0.1);
            color: #6366f1;
        }
        
        .status-unavailable {
            background: rgba(239, 68, 68, 0.1);
            color: #ef4444;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 0.5rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: all 0.2s;
            color: white;
        }
        
        .action-btn.edit { background: #6366f1; }
        .action-btn.status { background: #f59e0b; }
        .action-btn.delete { background: #ef4444; }
        .action-btn.view { background: #10b981; }
        
        .action-btn:hover {
            transform: translateY(-2px);
        }
        
        .bulk-bar {
            background: #f1f5f9;
            border-radius: 0.75rem;
            padding: 0.75rem 1rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .bulk-bar.hidden {
            display: none;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }
        
        .page-link {
            padding: 0.5rem 0.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            text-decoration: none;
            color: #0f172a;
            font-size: 0.85rem;
        }
        
        .page-link.active {
            background: #6366f1;
            border-color: #6366f1;
            color: white;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            max-width: 450px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1.25rem;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 1.25rem;
        }
        
        .modal-footer {
            padding: 1.25rem;
            border-top: 1px solid #e2e8f0;
            display: flex;
            gap: 1rem;
        }
        
        @media (max-width: 768px) {
            .filters-bar {
                flex-direction: column;
                align-items: stretch;
            }
            
            .filter-group {
                flex-direction: column;
            }
            
            .search-box input {
                width: 100%;
            }
            
            .books-table th, .books-table td {
                padding: 0.75rem;
            }
            
            .action-buttons {
                flex-direction: column;
            }
        }
        
        .category-tag {
            background: #f1f5f9;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.75rem;
            color: #475569;
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/includes/admin-header.php'; ?>

    <div class="admin-content">
        <!-- Page Header -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem; flex-wrap: wrap; gap: 1rem;">
            <div>
                <h1 style="font-size: 1.5rem; font-weight: 700;">Book Management</h1>
                <p style="color: #64748b;">Manage and moderate all books in the library</p>
            </div>
            <div>
                <a href="/admin/books/export.php" class="btn-admin btn-admin-primary" style="display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-download"></i> Export Books
                </a>
            </div>
        </div>
        
        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success" style="background: rgba(16,185,129,0.1); color: #10b981; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error" style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 1rem; border-radius: 0.75rem; margin-bottom: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="stats-cards">
            <div class="stat-card">
                <div class="stat-number" style="color: #6366f1;"><?php echo $totalBooks; ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #10b981;"><?php echo $availableBooks; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #f59e0b;"><?php echo $borrowedBooks; ?></div>
                <div class="stat-label">Borrowed</div>
            </div>
            <div class="stat-card">
                <div class="stat-number" style="color: #ef4444;"><?php echo $unavailableBooks; ?></div>
                <div class="stat-label">Unavailable</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="filters-bar">
            <div class="filter-group">
                <select class="filter-select" id="statusFilter" onchange="applyFilter()">
                    <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>Available</option>
                    <option value="borrowed" <?php echo $status === 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                    <option value="reserved" <?php echo $status === 'reserved' ? 'selected' : ''; ?>>Reserved</option>
                    <option value="unavailable" <?php echo $status === 'unavailable' ? 'selected' : ''; ?>>Unavailable</option>
                </select>
                
                <select class="filter-select" id="categoryFilter" onchange="applyFilter()">
                    <option value="all" <?php echo $category === 'all' ? 'selected' : ''; ?>>All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <form method="GET" class="search-box" id="searchForm">
                <i class="fas fa-search"></i>
                <input type="text" name="search" placeholder="Search by title or author..." value="<?php echo htmlspecialchars($search); ?>">
                <input type="hidden" name="status" id="hiddenStatus" value="<?php echo $status; ?>">
                <input type="hidden" name="category" id="hiddenCategory" value="<?php echo $category; ?>">
            </form>
        </div>
        
        <!-- Bulk Actions Bar -->
        <div id="bulkBar" class="bulk-bar hidden">
            <span id="selectedCount">0 selected</span>
            <div style="display: flex; gap: 0.5rem;">
                <select id="bulkStatusSelect" class="filter-select" style="background: white;">
                    <option value="">Change Status</option>
                    <option value="available">Available</option>
                    <option value="borrowed">Borrowed</option>
                    <option value="reserved">Reserved</option>
                    <option value="unavailable">Unavailable</option>
                </select>
                <button class="btn-admin btn-admin-primary" onclick="bulkUpdateStatus()">Apply</button>
                <button class="btn-admin" style="background: #ef4444; color: white;" onclick="bulkDelete()">Delete Selected</button>
            </div>
        </div>
        
        <!-- Books Table -->
        <div class="books-table-container">
            <table class="books-table">
                <thead>
                    <tr>
                        <th width="40"><input type="checkbox" id="selectAll" onclick="toggleAll()"></th>
                        <th>Book</th>
                        <th>Owner</th>
                        <th>Category</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($paginatedBooks)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem;">
                                <i class="fas fa-book-open" style="font-size: 3rem; color: #cbd5e1;"></i>
                                <p style="margin-top: 1rem;">No books found</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($paginatedBooks as $book): 
                            $coverImage = !empty($book['cover_image']) ? '/uploads/book_cover/thumb_' . $book['cover_image'] : '/assets/images/default-book-cover.jpg';
                            $ownerName = getUserName($book['owner_id'] ?? '');
                            $bookStatus = $book['status'] ?? 'available';
                            $statusClass = $bookStatus === 'available' ? 'available' : ($bookStatus === 'borrowed' ? 'borrowed' : ($bookStatus === 'reserved' ? 'reserved' : 'unavailable'));
                        ?>
                            <tr>
                                <td>
                                    <input type="checkbox" class="book-checkbox" value="<?php echo $book['id']; ?>" onchange="updateSelectedCount()">
                                </td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <img src="<?php echo $coverImage; ?>" class="book-cover-small" alt="">
                                        <div>
                                            <div style="font-weight: 600;"><?php echo htmlspecialchars($book['title']); ?></div>
                                            <div style="font-size: 0.75rem; color: #64748b;"><?php echo htmlspecialchars($book['author']); ?></div>
                                            <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 0.25rem;">ID: <?php echo htmlspecialchars($book['id']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td style="font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($ownerName); ?>
                                </td>
                                <td>
                                    <span class="category-tag"><?php echo htmlspecialchars($book['category'] ?? 'Uncategorized'); ?></span>
                                </td>
                                <td>
                                    <span class="status-badge status-<?php echo $statusClass; ?>">
                                        <?php echo ucfirst($bookStatus); ?>
                                    </span>
                                    <?php if ($bookStatus === 'unavailable' && !empty($book['unavailable_reason'])): ?>
                                        <i class="fas fa-info-circle" style="color: #94a3b8; margin-left: 0.25rem; cursor: help;" title="<?php echo htmlspecialchars($book['unavailable_reason']); ?>"></i>
                                    <?php endif; ?>
                                </td>
                                <td style="font-size: 0.85rem;">
                                    <?php echo date('M j, Y', strtotime($book['created_at'] ?? 'now')); ?>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button class="action-btn edit" onclick="editBook('<?php echo $book['id']; ?>')" title="Edit Book">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn status" onclick="showStatusModal('<?php echo $book['id']; ?>', '<?php echo addslashes($book['title']); ?>')" title="Change Status">
                                            <i class="fas fa-sync-alt"></i>
                                        </button>
                                        <button class="action-btn view" onclick="viewBook('<?php echo $book['id']; ?>')" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button class="action-btn delete" onclick="deleteBook('<?php echo $book['id']; ?>', '<?php echo addslashes($book['title']); ?>')" title="Delete Book">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <div class="pagination">
                <a href="?page=<?php echo max(1, $page - 1); ?>&status=<?php echo $status; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                    <i class="fas fa-chevron-left"></i>
                </a>
                
                <?php for ($i = 1; $i <= min(5, $totalPages); $i++): ?>
                    <?php if ($i >= $page - 2 && $i <= $page + 2): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $i === $page ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($totalPages > 5 && $page < $totalPages - 2): ?>
                    <span class="page-link disabled">...</span>
                    <a href="?page=<?php echo $totalPages; ?>&status=<?php echo $status; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>" class="page-link"><?php echo $totalPages; ?></a>
                <?php endif; ?>
                
                <a href="?page=<?php echo min($totalPages, $page + 1); ?>&status=<?php echo $status; ?>&category=<?php echo urlencode($category); ?>&search=<?php echo urlencode($search); ?>" class="page-link <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                    <i class="fas fa-chevron-right"></i>
                </a>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Status Modal -->
    <div id="statusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-sync-alt" style="color: #f59e0b;"></i> Update Book Status</h3>
                <button onclick="closeModal('statusModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="book_id" id="statusBookId">
                    <div id="statusBookPreview" style="background: #f8fafc; padding: 0.75rem; border-radius: 0.5rem; margin-bottom: 1rem; text-align: center;">
                        <strong id="statusBookTitle"></strong>
                    </div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">New Status</label>
                        <select name="status" id="statusSelect" class="form-control-admin" onchange="toggleReasonField()">
                            <option value="available">Available</option>
                            <option value="borrowed">Borrowed</option>
                            <option value="reserved">Reserved</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="form-group" id="reasonGroup" style="display: none;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reason (required for unavailable)</label>
                        <textarea name="reason" class="form-control-admin" rows="3" placeholder="Please provide a reason..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-admin" style="background: #f59e0b; color: white;">Update Status</button>
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('statusModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Delete Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-trash" style="color: #ef4444;"></i> Delete Book</h3>
                <button onclick="closeModal('deleteModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete_book">
                    <input type="hidden" name="book_id" id="deleteBookId">
                    <p>Are you sure you want to delete <strong id="deleteBookTitle"></strong>?</p>
                    <p style="color: #ef4444; font-size: 0.85rem; margin-top: 0.5rem;">This action cannot be undone. All book data and cover images will be permanently removed.</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-admin" style="background: #ef4444; color: white;">Delete Book</button>
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Bulk Status Modal -->
    <div id="bulkStatusModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-sync-alt" style="color: #f59e0b;"></i> Bulk Update Status</h3>
                <button onclick="closeModal('bulkStatusModal')" style="background: none; border: none; font-size: 1.5rem; cursor: pointer;">&times;</button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="bulk_update_status">
                    <div id="bulkBookIds"></div>
                    <div class="form-group">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">New Status</label>
                        <select name="bulk_status" id="bulkStatusSelect" class="form-control-admin" onchange="toggleBulkReasonField()">
                            <option value="available">Available</option>
                            <option value="borrowed">Borrowed</option>
                            <option value="reserved">Reserved</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
                    </div>
                    <div class="form-group" id="bulkReasonGroup" style="display: none;">
                        <label style="display: block; margin-bottom: 0.5rem; font-weight: 500;">Reason (required for unavailable)</label>
                        <textarea name="bulk_reason" class="form-control-admin" rows="3" placeholder="Please provide a reason..."></textarea>
                    </div>
                    <p style="margin-top: 1rem; color: #64748b;">This will update <span id="bulkCount"></span> selected book(s).</p>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-admin" style="background: #f59e0b; color: white;">Update Selected</button>
                    <button type="button" class="btn-admin btn-outline" onclick="closeModal('bulkStatusModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let selectedBooks = new Set();
        
        function applyFilter() {
            const status = document.getElementById('statusFilter').value;
            const category = document.getElementById('categoryFilter').value;
            const search = document.querySelector('input[name="search"]').value;
            window.location.href = `?status=${status}&category=${encodeURIComponent(category)}&search=${encodeURIComponent(search)}`;
        }
        
        function editBook(bookId) {
            window.location.href = `/edit-book/?id=${bookId}`;
        }
        
        function showStatusModal(bookId, bookTitle) {
            document.getElementById('statusBookId').value = bookId;
            document.getElementById('statusBookTitle').textContent = bookTitle;
            document.getElementById('statusModal').classList.add('active');
        }
        
        function toggleReasonField() {
            const status = document.getElementById('statusSelect').value;
            const reasonGroup = document.getElementById('reasonGroup');
            if (status === 'unavailable') {
                reasonGroup.style.display = 'block';
                reasonGroup.querySelector('textarea').required = true;
            } else {
                reasonGroup.style.display = 'none';
                reasonGroup.querySelector('textarea').required = false;
            }
        }
        
        function toggleBulkReasonField() {
            const status = document.getElementById('bulkStatusSelect').value;
            const reasonGroup = document.getElementById('bulkReasonGroup');
            if (status === 'unavailable') {
                reasonGroup.style.display = 'block';
            } else {
                reasonGroup.style.display = 'none';
            }
        }
        
        function deleteBook(bookId, bookTitle) {
            document.getElementById('deleteBookId').value = bookId;
            document.getElementById('deleteBookTitle').textContent = bookTitle;
            document.getElementById('deleteModal').classList.add('active');
        }
        
        function viewBook(bookId) {
            window.open(`/book/?id=${bookId}`, '_blank');
        }
        
        function toggleAll() {
            const checkboxes = document.querySelectorAll('.book-checkbox');
            const selectAll = document.getElementById('selectAll');
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
                if (selectAll.checked) selectedBooks.add(cb.value);
                else selectedBooks.delete(cb.value);
            });
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            document.querySelectorAll('.book-checkbox').forEach(cb => {
                if (cb.checked) selectedBooks.add(cb.value);
                else selectedBooks.delete(cb.value);
            });
            const count = selectedBooks.size;
            const bulkBar = document.getElementById('bulkBar');
            if (count > 0) {
                document.getElementById('selectedCount').textContent = count + ' selected';
                bulkBar.classList.remove('hidden');
            } else {
                bulkBar.classList.add('hidden');
            }
        }
        
        function bulkUpdateStatus() {
            if (selectedBooks.size === 0) return;
            const status = document.getElementById('bulkStatusSelect').value;
            if (!status) {
                alert('Please select a status');
                return;
            }
            let html = '';
            selectedBooks.forEach(id => html += `<input type="hidden" name="book_ids[]" value="${id}">`);
            document.getElementById('bulkBookIds').innerHTML = html;
            document.getElementById('bulkCount').textContent = selectedBooks.size;
            document.getElementById('bulkStatusModal').classList.add('active');
        }
        
        function bulkDelete() {
            if (selectedBooks.size === 0) return;
            if (confirm(`Delete ${selectedBooks.size} selected book(s)? This cannot be undone.`)) {
                const form = document.createElement('form');
                form.method = 'POST';
                let html = '<input type="hidden" name="action" value="bulk_delete">';
                selectedBooks.forEach(id => html += `<input type="hidden" name="book_ids[]" value="${id}">`);
                form.innerHTML = html;
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function closeModal(modalId) {
            document.getElementById(modalId).classList.remove('active');
        }
        
        window.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) e.target.classList.remove('active');
        });
        
        let searchTimeout;
        document.querySelector('.search-box input').addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                document.getElementById('searchForm').submit();
            }, 500);
        });
    </script>
    
    <?php include dirname(__DIR__, 2) . '/includes/admin-footer.php'; ?>
</body>
</html>