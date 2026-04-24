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

// Include database connection
require_once dirname(__DIR__, 2) . '/includes/db.php';
require_once dirname(__DIR__, 2) . '/includes/search_helper.php';

// Check admin login
if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

$adminId = $_SESSION['admin_id'];
$adminName = $_SESSION['admin_name'] ?? 'Admin';

/**
 * Load paginated books from DB with filters
 */
function loadBooks($status = 'all', $category = 'all', $search = '', $offset = 0, $perPage = 20) {
    $db = getDB();
    $where = ["1=1"];
    $params = [];

    if ($status !== 'all') {
        $where[] = "status = :status";
        $params[':status'] = $status;
    }
    if ($category !== 'all') {
        $where[] = "category = :category";
        $params[':category'] = $category;
    }
    
    applySearchFilter($search, ['title', 'author'], $where, $params);

    $sql = "SELECT * FROM books WHERE " . implode(' AND ', $where);

    $sql .= " ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
    
    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', (int)$perPage, PDO::PARAM_INT);
    $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll();
}

/**
 * Get total count of filtered books
 */
function getBooksCount($status = 'all', $category = 'all', $search = '') {
    $db = getDB();
    $where = ["1=1"];
    $params = [];

    if ($status !== 'all') {
        $where[] = "status = :status";
        $params[':status'] = $status;
    }
    if ($category !== 'all') {
        $where[] = "category = :category";
        $params[':category'] = $category;
    }
    
    applySearchFilter($search, ['title', 'author'], $where, $params);

    $sql = "SELECT COUNT(*) FROM books WHERE " . implode(' AND ', $where);

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * Load detailed book data from DB
 */
function loadBookData($bookId) {
    if (empty($bookId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    return $stmt->fetch() ?: null;
}

/**
 * Update book status in DB
 */
function updateBookStatus($bookId, $status, $reason = '') {
    global $adminId;
    $db = getDB();
    
    // Note: only update the columns that actually exist in the schema
    $sql = "UPDATE books SET 
                status = :status, 
                updated_at = :updated_at, 
                status_updated_by = :status_updated_by
            WHERE id = :id";
    
    $params = [
        ':status'            => $status,
        ':updated_at'        => date('Y-m-d H:i:s'),
        ':status_updated_by' => $adminId,
        ':id'                => $bookId,
    ];
    
    try {
        $stmt = $db->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log('updateBookStatus error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Delete book from DB
 */
function deleteBook($bookId) {
    $db = getDB();
    
    // Get book data first for cover image deletion
    $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    $bookData = $stmt->fetch();
    
    if (!$bookData) return false;
    
    $stmt = $db->prepare("DELETE FROM books WHERE id = ?");
    $masterSaved = $stmt->execute([$bookId]);
    
    if ($masterSaved) {
        // Archive book data if individual file exists
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
 * Get user name by ID from DB
 */
function getUserName($userId) {
    if (empty($userId)) return 'Unknown';
    $db = getDB();
    $stmt = $db->prepare("SELECT name FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->fetchColumn() ?: 'Unknown';
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

// Filters
$status = $_GET['status'] ?? 'all';
$category = $_GET['category'] ?? 'all';
$search = $_GET['search'] ?? '';

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Load data from DB efficiently
$paginatedBooks = loadBooks($status, $category, $search, $offset, $perPage);
$total = getBooksCount($status, $category, $search);
$totalPages = ceil($total / $perPage);

// Stats for the cards (cached or separate queries)
$db = getDB();
$totalBooks = (int)$db->query("SELECT COUNT(*) FROM books")->fetchColumn();
$availableBooks = (int)$db->query("SELECT COUNT(*) FROM books WHERE status = 'available'")->fetchColumn();
$borrowedBooks = (int)$db->query("SELECT COUNT(*) FROM books WHERE status = 'borrowed'")->fetchColumn();
$unavailableBooks = (int)$db->query("SELECT COUNT(*) FROM books WHERE status = 'unavailable'")->fetchColumn();

/**
 * Get unique categories from DB
 */
function getCategoriesFromDB() {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}
$categories = getCategoriesFromDB();

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
    
    // Refresh data after action
    $paginatedBooks = loadBooks($status, $category, $search, $offset, $perPage);
    $total = getBooksCount($status, $category, $search);
    $totalPages = ceil($total / $perPage);
    
    // Refresh stats
    $totalBooks = (int)$db->query("SELECT COUNT(*) FROM books")->fetchColumn();
    $availableBooks = (int)$db->query("SELECT COUNT(*) FROM books WHERE status = 'available'")->fetchColumn();
    $borrowedBooks = (int)$db->query("SELECT COUNT(*) FROM books WHERE status = 'borrowed'")->fetchColumn();
    $unavailableBooks = (int)$db->query("SELECT COUNT(*) FROM books WHERE status = 'unavailable'")->fetchColumn();
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
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2.5rem;
            flex-wrap: wrap;
            gap: 1.5rem;
        }

        .header-title h1 {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -1px;
            color: var(--text-main);
            margin-bottom: 0.5rem;
        }

        .header-title p {
            color: var(--text-muted);
            font-weight: 500;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            text-align: center;
            border: 1px solid #f1f5f9;
            transition: var(--transition);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px -5px rgba(0,0,0,0.05);
        }

        .stat-value {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.25rem;
            letter-spacing: -1.5px;
        }

        .stat-label {
            color: var(--text-muted);
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .filters-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1.5rem;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: white;
            border-radius: 20px;
            border: 1px solid #f1f5f9;
        }

        .filter-group {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .filter-select {
            padding: 0.75rem 1.25rem;
            border-radius: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            font-size: 0.9rem;
            font-weight: 600;
            color: #475569;
            cursor: pointer;
            transition: var(--transition);
            min-width: 160px;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%2364748b'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 1.25rem;
        }

        .filter-select:hover {
            border-color: var(--primary);
            color: var(--primary);
        }

        .search-box {
            position: relative;
        }

        .search-box input {
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            width: 320px;
            font-size: 0.9rem;
            font-family: inherit;
            transition: var(--transition);
        }

        .search-box input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
        }

        .search-box i {
            position: absolute;
            left: 1.1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 1rem;
        }

        .table-container {
            background: white;
            border-radius: 24px;
            overflow-x: auto;
            border: 1px solid #f1f5f9;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
            margin-bottom: 2rem;
            -webkit-overflow-scrolling: touch;
        }

        .books-table {
            width: 100%;
            border-collapse: collapse;
        }

        .books-table th {
            text-align: left;
            padding: 1.25rem 1.5rem;
            background: #f8fafc;
            color: #64748b;
            font-weight: 700;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid #f1f5f9;
        }

        .books-table td {
            padding: 1.25rem 1.5rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            color: var(--text-main);
            font-size: 0.95rem;
        }

        .books-table tr:hover td {
            background: #fcfdfe;
        }

        .book-info {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .book-cover-small {
            width: 48px;
            height: 64px;
            border-radius: 8px;
            object-fit: cover;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .book-title { font-weight: 700; margin-bottom: 2px; }
        .book-author { font-size: 0.8rem; color: var(--text-muted); font-weight: 500; }

        .status-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.4rem 0.85rem;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-available { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .status-borrowed { background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .status-reserved { background: rgba(99, 102, 241, 0.1); color: #6366f1; }
        .status-unavailable { background: rgba(239, 68, 68, 0.1); color: #ef4444; }

        .action-buttons {
            display: flex;
            gap: 0.6rem;
        }

        .action-btn {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            color: white;
            font-size: 1rem;
        }

        .action-btn.edit { background: var(--primary); box-shadow: 0 4px 10px rgba(99, 102, 241, 0.2); }
        .action-btn.status { background: #f59e0b; box-shadow: 0 4px 10px rgba(245, 158, 11, 0.2); }
        .action-btn.delete { background: #ef4444; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.2); }
        .action-btn.view { background: #10b981; box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2); }

        .action-btn:hover {
            transform: translateY(-3px);
            filter: brightness(1.1);
        }

        .bulk-bar {
            background: #1e293b;
            color: white;
            border-radius: 16px;
            padding: 1rem 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            animation: slideIn 0.3s ease-out;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.2);
        }

        @keyframes slideIn {
            from { transform: translateY(-10px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .bulk-bar.hidden { display: none; }

        .pagination {
            display: flex;
            justify-content: center;
            gap: 0.75rem;
            margin-top: 3rem;
        }

        .page-link {
            padding: 0.75rem 1.1rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            text-decoration: none;
            color: #475569;
            font-size: 0.9rem;
            font-weight: 600;
            background: white;
            transition: var(--transition);
        }

        .page-link.active {
            background: var(--primary);
            border-color: var(--primary);
            color: white;
            box-shadow: 0 8px 16px rgba(99, 102, 241, 0.2);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(8px);
            align-items: center;
            justify-content: center;
            z-index: 2000;
            padding: 1.5rem;
        }

        .modal.active { display: flex; }

        .modal-content {
            background: white;
            border-radius: 24px;
            max-width: 500px;
            width: 100%;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            overflow: hidden;
            animation: modalIn 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .modal-header {
            padding: 1.5rem 2rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h3 { font-weight: 800; letter-spacing: -0.5px; }

        .modal-body { padding: 2rem; }

        .modal-footer {
            padding: 1.5rem 2rem;
            background: #f8fafc;
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
        }

        .category-tag {
            background: #f1f5f9;
            padding: 0.35rem 0.85rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 600;
            color: #475569;
        }

        @media (max-width: 768px) {
            .page-header { margin-bottom: 1.5rem; }
            .filters-bar { padding: 1rem; }
            .search-box input { width: 100%; }
        }
    </style>
</head>
<body>
    <?php include dirname(__DIR__, 2) . '/includes/admin-header.php'; ?>

    <div class="admin-content">
        <!-- Page Header -->
        <div class="page-header">
            <div class="header-title">
                <h1>Book Management</h1>
                <p>Manage and moderate all books in the library with ease</p>
            </div>
            <div>
                <a href="/admin/books/export.php" class="btn-admin btn-admin-primary">
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
        
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value" style="color: var(--primary);"><?php echo $totalBooks; ?></div>
                <div class="stat-label">Total Books</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #10b981;"><?php echo $availableBooks; ?></div>
                <div class="stat-label">Available</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #f59e0b;"><?php echo $borrowedBooks; ?></div>
                <div class="stat-label">Borrowed</div>
            </div>
            <div class="stat-card">
                <div class="stat-value" style="color: #ef4444;"><?php echo $unavailableBooks; ?></div>
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
        <div class="table-container">
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
                                    <div class="book-info">
                                        <img src="<?php echo $coverImage; ?>" class="book-cover-small" alt="">
                                        <div>
                                            <div class="book-title"><?php echo htmlspecialchars($book['title']); ?></div>
                                            <div class="book-author"><?php echo htmlspecialchars($book['author']); ?></div>
                                            <div style="font-size: 0.7rem; color: #94a3b8; margin-top: 2px;">ID: <?php echo htmlspecialchars($book['id']); ?></div>
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
                        <select name="status" id="statusSelect" class="form-control-admin">
                            <option value="available">Available</option>
                            <option value="borrowed">Borrowed</option>
                            <option value="reserved">Reserved</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
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
                        <select name="bulk_status" id="bulkStatusSelect" class="form-control-admin">
                            <option value="available">Available</option>
                            <option value="borrowed">Borrowed</option>
                            <option value="reserved">Reserved</option>
                            <option value="unavailable">Unavailable</option>
                        </select>
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