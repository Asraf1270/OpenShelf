<?php
/**
 * OpenShelf Books Listing Page
 * Ultra Modern, Clean, Mobile-First Book Cards
 */

session_start();
include dirname(__DIR__) . '/includes/header.php';

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');

/**
 * Load books from DB with cursor-based pagination
 */
function getBooks($search = '', $selectedCategories = [], $availability = '', $limit = 12, $cursor_date = null, $cursor_id = null) {
    $db = getDB();
    $where = ["1=1"];
    $params = [];

    if (!empty($availability)) {
        $where[] = "b.status = :availability";
        $params[':availability'] = $availability;
    }

    if (!empty($selectedCategories)) {
        $catPlaceholders = [];
        foreach ($selectedCategories as $i => $cat) {
            $key = ":cat$i";
            $catPlaceholders[] = $key;
            $params[$key] = $cat;
        }
        $where[] = "b.category IN (" . implode(',', $catPlaceholders) . ")";
    }

    if (!empty($search)) {
        $where[] = "(b.title LIKE :search1 OR b.author LIKE :search2 OR b.publisher LIKE :search3)";
        $params[':search1'] = "%$search%";
        $params[':search2'] = "%$search%";
        $params[':search3'] = "%$search%";
    }

    if ($cursor_date && $cursor_id) {
        $where[] = "(b.created_at < :c_date1 OR (b.created_at = :c_date2 AND b.id < :c_id))";
        $params[':c_date1'] = $cursor_date;
        $params[':c_date2'] = $cursor_date;
        $params[':c_id'] = $cursor_id;
    }

    $sql = "
        SELECT b.*, u.name as owner_name 
        FROM books b 
        LEFT JOIN users u ON b.owner_id = u.id 
        WHERE " . implode(' AND ', $where) . "
        ORDER BY b.created_at DESC, b.id DESC
        LIMIT :limit
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Get total books count for current filters
 */
function getBooksCount($search = '', $selectedCategories = [], $availability = '') {
    $db = getDB();
    $where = ["1=1"];
    $params = [];

    if (!empty($availability)) {
        $where[] = "b.status = :availability";
        $params[':availability'] = $availability;
    }

    if (!empty($selectedCategories)) {
        $catPlaceholders = [];
        foreach ($selectedCategories as $i => $cat) {
            $key = ":cat$i";
            $catPlaceholders[] = $key;
            $params[$key] = $cat;
        }
        $where[] = "b.category IN (" . implode(',', $catPlaceholders) . ")";
    }

    if (!empty($search)) {
        $where[] = "(b.title LIKE :search OR b.author LIKE :search OR b.publisher LIKE :search)";
        $params[':search'] = "%$search%";
    }

    $sql = "SELECT COUNT(*) FROM books b WHERE " . implode(' AND ', $where);
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return (int)$stmt->fetchColumn();
}

/**
 * Get unique categories from DB
 */
function getCategoriesFromDB() {
    $db = getDB();
    $stmt = $db->query("SELECT DISTINCT category FROM books WHERE category IS NOT NULL AND category != '' ORDER BY category ASC");
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

// Load and filter books
$search = $_GET['search'] ?? '';
$selectedCategories = isset($_GET['categories']) ? (array)$_GET['categories'] : [];
$availability = $_GET['availability'] ?? '';
$limit = 12;

$filteredBooks = getBooks($search, $selectedCategories, $availability, $limit);
$totalFilteredCount = getBooksCount($search, $selectedCategories, $availability);
$categories = getCategoriesFromDB();

// Get last book info for initial cursor
$lastBook = !empty($filteredBooks) ? end($filteredBooks) : null;
$initialCursor = [
    'date' => $lastBook ? $lastBook['created_at'] : null,
    'id' => $lastBook ? $lastBook['id'] : null
];

// Helper for generating URLs while keeping other GET params
function getUrlWithParam($param, $value) {
    $params = $_GET;
    if (empty($value)) {
        unset($params[$param]);
    } else {
        $params[$param] = $value;
    }
    return '?' . http_build_query($params);
}

/**
 * Toggle a category in the URL while keeping other parameters
 */
function toggleCategoryUrl($cat) {
    $params = $_GET;
    $selected = (array)($params['categories'] ?? []);
    if (in_array($cat, $selected)) {
        $selected = array_diff($selected, [$cat]);
    } else {
        $selected[] = $cat;
    }
    
    if (empty($selected)) {
        unset($params['categories']);
    } else {
        $params['categories'] = array_values($selected);
    }
    // Search reset is optional, but often better when switching categories
    // unset($params['page']); // If pagination is added
    return '?' . http_build_query($params);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Browse Books - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* ========================================
           MOBILE-FIRST ULTRA MODERN CSS
        ======================================== */
        
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --secondary: #8b5cf6;
            --success: #10b981;
            --danger: #f43f5e;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
            --radius-xl: 1.5rem;
            --radius-lg: 1rem;
            --radius-md: 0.75rem;
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.1);
            --shadow-hover: 0 20px 40px -10px rgba(99,102,241,0.2);
            --transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        body {
            background: var(--gray-50);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            color: var(--gray-800);
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Main Container */
        .books-main {
            padding: 2rem 1rem 4rem; width: 100%; max-width: 1400px; margin: 0 auto;
        }

        /* Search Bar (non-sticky, scrolls with page) */
        .search-bar-wrap {
            background: var(--header-bg);
            backdrop-filter: var(--header-blur);
            padding: 0.85rem 1rem;
            border-bottom: 1px solid var(--header-border);
            display: flex;
            justify-content: center;
            position: relative;
        }

        /* Sticky Category/Filter Bar */
        .minimal-top-bar {
            background: var(--header-bg);
            backdrop-filter: var(--header-blur);
            padding: 0.6rem 1rem;
            border-bottom: 1px solid var(--header-border);
            margin: 0;
            position: sticky;
            top: 56px;
            z-index: 990;
            display: flex;
            align-items: center;
        }

        .search-row {
            width: 100%;
            max-width: 720px;
            display: flex;
            justify-content: center;
            position: relative;
        }

        .youtube-search {
            display: flex;
            width: 100%;
            background: var(--gray-50);
            border: 1px solid var(--gray-200);
            border-radius: 40px;
            overflow: hidden;
            transition: all 0.2s;
        }

        .youtube-search:focus-within {
            border-color: var(--primary);
            box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
            background: white;
        }

        .search-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.6rem 1.25rem;
            font-size: 1rem;
            outline: none;
            color: var(--gray-800);
        }

        .search-btn {
            background: var(--gray-100);
            border: none;
            border-left: 1px solid var(--gray-200);
            padding: 0 1.5rem;
            cursor: pointer;
            color: var(--gray-600);
            transition: all 0.2s;
        }

        .search-btn:hover {
            background: var(--gray-200);
            color: var(--gray-900);
        }

        /* Category Pills (YouTube Chips) */
        .category-row {
            width: 100%;
            overflow-x: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
            display: flex;
            gap: 0.75rem;
            padding: 0.25rem 0;
        }
        .category-row::-webkit-scrollbar { display: none; }

        .chip {
            padding: 0.4rem 1rem;
            background: rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 20px;
            color: var(--gray-800);
            text-decoration: none;
            font-size: 0.85rem;
            font-weight: 500;
            white-space: nowrap;
            transition: all 0.2s;
        }

        .chip:hover { background: rgba(0, 0, 0, 0.1); }
        .chip.active {
            background: var(--gray-900);
            color: white;
            border-color: var(--gray-900);
        }

        .filter-controls { display: flex; gap: 0.5rem; align-items: center; }
        .styled-select {
            padding: 0.5rem 2rem 0.5rem 0.75rem; border: 1px solid var(--gray-200); border-radius: 8px;
            background: var(--gray-50); font-size: 0.85rem; font-weight: 600; color: var(--gray-700);
            cursor: pointer; transition: all 0.3s ease; outline: none; appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 0.5rem center;
        }
        .styled-select:focus { background-color: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }

        .books-header { display: flex; flex-direction: column; gap: 1rem; margin-bottom: 1.5rem; }
        .books-count { font-size: 1rem; color: var(--gray-600); }
        .books-count strong { color: var(--gray-900); font-weight: 700; font-size: 1.15rem; }

        /* Book Grid (Mobile First) */
        .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1.5rem; }
        
        .book-card {
            background: white; border-radius: var(--radius-xl); overflow: hidden;
            box-shadow: var(--shadow-sm); transition: var(--transition);
            display: flex; flex-direction: column; position: relative; border: 1px solid var(--gray-100);
            /* Initial state for IntersectionObserver Animation */
            opacity: 0; transform: translateY(30px) scale(0.95);
        }
        .book-card.show { opacity: 1; transform: translateY(0) scale(1); }
        .book-card:hover { transform: translateY(-8px) scale(1.02); box-shadow: var(--shadow-hover); border-color: #c7d2fe; z-index: 2; }

        .book-cover-container { 
            position: relative; 
            padding-top: 140%; 
            overflow: hidden; 
            background: #f1f5f9; 
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .book-cover-container img { 
            position: absolute; 
            top: 12px; 
            left: 12px; 
            right: 12px; 
            bottom: 12px; 
            width: calc(100% - 24px); 
            height: calc(100% - 24px); 
            object-fit: contain; 
            transition: transform 0.6s ease; 
            border-radius: 6px;
            filter: drop-shadow(0 10px 15px rgba(0,0,0,0.1));
        }
        .book-card:hover .book-cover-container img { transform: scale(1.05); }

        .book-badge {
            position: absolute; top: 0.75rem; right: 0.75rem; padding: 0.35rem 0.85rem;
            border-radius: 2rem; font-size: 0.7rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; z-index: 2; backdrop-filter: blur(8px);
        }
        .badge-available { background: rgba(16, 185, 129, 0.9); color: white; box-shadow: 0 4px 10px rgba(16,185,129,0.3); }
        .badge-borrowed { background: rgba(244, 63, 94, 0.9); color: white; box-shadow: 0 4px 10px rgba(244,63,94,0.3); }

        .book-info { padding: 1.25rem; flex: 1; display: flex; flex-direction: column; }
        .book-category-tag {
            font-size: 0.7rem; font-weight: 700; color: var(--primary); text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 0.5rem; background: rgba(99,102,241,0.1); 
            padding: 0.25rem 0.6rem; border-radius: 4px; display: inline-block; width: fit-content;
        }
        .book-title {
            font-size: 1.1rem; font-weight: 800; margin-bottom: 0.4rem; line-height: 1.4;
            color: var(--gray-900); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .book-title a { color: inherit; text-decoration: none; transition: color 0.2s; }
        .book-title a:hover { color: var(--primary); }
        .book-author { font-size: 0.85rem; color: var(--gray-500); margin-bottom: 1.25rem; font-weight: 500; }

        .book-footer {
            margin-top: auto; padding-top: 1rem; border-top: 1px dashed var(--gray-200);
            display: flex; align-items: center; justify-content: space-between;
        }
        .owner-info { display: flex; align-items: center; gap: 0.6rem; }
        .owner-avatar { width: 30px; height: 30px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .owner-name { font-size: 0.8rem; font-weight: 600; color: var(--gray-800); }

        /* Empty state */
        .empty-glass {
            text-align: center; padding: 4rem 1.5rem; background: white;
            border-radius: var(--radius-xl); border: 1px dashed var(--gray-300); margin: 0 auto; max-width: 600px;
        }
        .empty-icon-box {
            width: 80px; height: 80px; background: rgba(99,102,241,0.1); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 1.25rem;
            color: var(--primary); font-size: 2.5rem; animation: float 6s infinite;
        }
        .empty-glass h3 { font-size: 1.35rem; font-weight: 800; margin-bottom: 0.75rem; color: var(--gray-900); }
        .empty-glass p { color: var(--gray-500); margin-bottom: 1.5rem; font-size: 1rem; line-height: 1.6; }
        .btn-elegant {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; padding: 0.75rem 1.75rem; border-radius: 2rem; text-decoration: none;
            font-weight: 600; transition: var(--transition); display: inline-block; box-shadow: 0 4px 15px rgba(99,102,241,0.3);
        }
        .btn-elegant:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); }

        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-15px); } }
        @keyframes slideDown { from { opacity: 0; transform: translateY(-20px); } to { opacity: 1; transform: translateY(0); } }

        /* Tablet & Desktop Layouts (Progressive Enhancement) */
        @media (max-width: 639px) {
            .book-grid { 
                grid-template-columns: repeat(2, 1fr); 
                gap: 0.75rem; 
                padding: 0 0.5rem;
            }
            .book-info { padding: 0.75rem; }
            .book-title { font-size: 0.95rem; margin-bottom: 0.25rem; }
            .book-author { font-size: 0.75rem; margin-bottom: 0.5rem; }
            .book-category-tag { font-size: 0.65rem; padding: 0.15rem 0.4rem; }
            .owner-avatar { width: 24px; height: 24px; }
            .owner-name { font-size: 0.7rem; }
            .book-badge { top: 0.5rem; right: 0.5rem; padding: 0.25rem 0.5rem; font-size: 0.6rem; }
            
            .stat-item { padding: 0.75rem 0.5rem; }
            .stat-value { font-size: 1.5rem; }
            .stat-label { font-size: 0.65rem; }
        }

        @media (min-width: 640px) {
            .top-bar-row { flex-direction: row; }
            .search-bar-wrap { padding: 0.85rem 2rem; }
            .minimal-top-bar { padding: 0.5rem 2rem; }
            
            .search-box { max-width: 500px; }
            
            .filter-controls { justify-content: flex-end; }
            .styled-select { width: auto; min-width: 160px; }
            
            .books-header { flex-direction: row; }
        }

        @media (min-width: 1024px) {
            .books-hero h1 { font-size: 3.5rem; }
            .book-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 2rem; }
        }

        [data-theme="dark"] .search-bar-wrap { border-bottom-color: #334155; }
        [data-theme="dark"] .minimal-top-bar { border-bottom-color: #334155; }
        [data-theme="dark"] .youtube-search { background: #0f172a; border-color: #334155; }
        [data-theme="dark"] .search-input { color: #f8fafc; }
        [data-theme="dark"] .search-btn { background: #1e293b; border-left-color: #334155; color: #cbd5e1; }
        [data-theme="dark"] .search-btn:hover { background: #334155; color: white; }
        [data-theme="dark"] .chip { background: rgba(255, 255, 255, 0.1); color: #cbd5e1; }
        [data-theme="dark"] .chip:hover { background: rgba(255, 255, 255, 0.15); }
        [data-theme="dark"] .chip.active { background: #f8fafc; color: #0f172a; }
        [data-theme="dark"] .styled-select { background-color: #0f172a; border-color: #334155; color: #f8fafc; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23cbd5e1' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E"); }
        [data-theme="dark"] .books-count { color: #cbd5e1; }
        [data-theme="dark"] .books-count strong { color: #f8fafc; }
        [data-theme="dark"] .book-card { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .book-cover-container { background: #0f172a; }
        [data-theme="dark"] .book-title { color: #f8fafc; }
        [data-theme="dark"] .book-footer { border-color: #334155; }
        [data-theme="dark"] .owner-name { color: #cbd5e1; }
        [data-theme="dark"] .empty-glass { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .empty-glass h3 { color: #f8fafc; }
    </style>
</head>
<body>

    <main class="books-main">
    <!-- Search Bar (scrolls with page) -->
    <div class="search-bar-wrap">
        <div class="search-row">
            <form method="GET" class="youtube-search">
                <!-- Preserve categories & availability -->
                <?php if (!empty($selectedCategories)): ?>
                    <?php foreach ($selectedCategories as $cat): ?>
                        <input type="hidden" name="categories[]" value="<?php echo htmlspecialchars($cat); ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($availability)): ?>
                    <input type="hidden" name="availability" value="<?php echo htmlspecialchars($availability); ?>">
                <?php endif; ?>

                <input type="text" name="search" class="search-input" 
                       placeholder="Search books, authors, publishers, owners..." 
                       value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="search-btn" title="Search">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
    </div>

    <!-- Sticky Category / Filter Bar -->
    <div class="minimal-top-bar">
        <!-- Category Row -->
        <div class="category-row">
            <a href="<?php echo getUrlWithParam('categories', ''); ?>" 
               class="chip <?php echo empty($selectedCategories) ? 'active' : ''; ?>">
                All
            </a>
            <?php foreach ($categories as $cat): 
                $isActive = in_array($cat, $selectedCategories);
            ?>
                <a href="<?php echo toggleCategoryUrl($cat); ?>" 
                   class="chip <?php echo $isActive ? 'active' : ''; ?>">
                   <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
            
            <div class="filter-controls" style="margin-left: auto; padding-left: 1rem; border-left: 1px solid var(--gray-200);">
                <form method="GET" style="display: flex; gap: 0.5rem;">
                    <!-- Preserve search and categories -->
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    <?php if (!empty($selectedCategories)): ?>
                        <?php foreach ($selectedCategories as $cat): ?>
                            <input type="hidden" name="categories[]" value="<?php echo htmlspecialchars($cat); ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <select name="availability" class="styled-select" onchange="this.form.submit()">
                        <option value="">Status: All</option>
                        <option value="available" <?php echo $availability === 'available' ? 'selected' : ''; ?>>Available</option>
                        <option value="borrowed" <?php echo $availability === 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                    </select>

                    <?php if (!empty($search) || !empty($selectedCategories) || !empty($availability)): ?>
                        <a href="/books/" class="chip" style="color: var(--danger); background: rgba(244, 63, 94, 0.1);">
                            Clear
                        </a>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
    
    <div class="books-header">
        <div class="books-count" id="booksCountLabel">
            Showing <strong><?php echo count($filteredBooks); ?></strong> of <strong><?php echo $totalFilteredCount; ?></strong> books 
            <?php if (!empty($selectedCategories)): ?>
                in <span style="color:var(--primary)"><?php echo implode(', ', array_map('htmlspecialchars', $selectedCategories)); ?></span>
            <?php endif; ?>
        </div>
        <div>
            <select class="styled-select" onchange="sortBooks(this.value)" style="padding: 0.6rem 2.5rem 0.6rem 1rem; width: auto; font-size: 0.9rem;">
                <option value="newest">Sort: Newest First</option>
                <option value="title">Sort: Title A-Z</option>
                <option value="author">Sort: Author A-Z</option>
            </select>
        </div>
    </div>
    
    <!-- Books Grid -->
    <?php if (empty($filteredBooks)): ?>
        <div class="empty-glass">
            <div class="empty-icon-box">
                <i class="fas fa-book-open"></i>
            </div>
            <h3>No Books Found</h3>
            <p>We couldn't find any books matching your current filters. Try adjusting your search or explore different categories.</p>
            <a href="/books/" class="btn-elegant">View All Books</a>
        </div>
    <?php else: ?>
        <div class="book-grid" id="booksGrid">
            <?php foreach ($filteredBooks as $index => $book): 
                $ownerName = $book['owner_name'];
                $ownerAvatar = !empty($book['owner_avatar']) && $book['owner_avatar'] !== 'default-avatar.jpg'
                    ? '/uploads/profile/' . ltrim($book['owner_avatar'], '/')
                    : '/assets/images/avatars/default.jpg';
                
                $coverImage = !empty($book['cover_image']) 
                    ? '/uploads/book_cover/' . ltrim($book['cover_image'], '/') 
                    : '/assets/images/default-book-cover.jpg';
                    
                $status = strtolower($book['status'] ?? 'available');
            ?>
                <div class="book-card" data-title="<?php echo htmlspecialchars(strtolower($book['title'] ?? '')); ?>" 
                     data-author="<?php echo htmlspecialchars(strtolower($book['author'] ?? '')); ?>" 
                     data-date="<?php echo $book['created_at'] ?? ''; ?>">
                    
                    <div class="book-cover-container">
                        <img src="<?php echo htmlspecialchars($coverImage); ?>" 
                             alt="<?php echo htmlspecialchars($book['title'] ?? 'Book'); ?>"
                             loading="lazy"
                             onerror="this.src='/assets/images/default-book-cover.jpg';">
                        <span class="book-badge badge-<?php echo $status; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>
                    
                    <div class="book-info">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                            <div class="book-category-tag" style="margin-bottom: 0;">
                                <?php echo htmlspecialchars($book['category'] ?? 'General'); ?>
                            </div>
                            <?php if (isset($book['_match_type']) && $book['_match_type'] === 'related'): ?>
                                <span style="font-size: 0.65rem; color: var(--gray-500); background: var(--gray-100); padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 600;"><i class="fas fa-sparkles"></i> Related</span>
                            <?php endif; ?>
                        </div>
                        <h3 class="book-title">
                            <a href="/book/?id=<?php echo htmlspecialchars($book['id'] ?? ''); ?>">
                                <?php echo htmlspecialchars($book['title'] ?? 'Untitled'); ?>
                            </a>
                        </h3>
                        <p class="book-author">By <?php echo htmlspecialchars($book['author'] ?? 'Unknown'); ?></p>
                        
                        <div class="book-footer">
                            <div class="owner-info">
                                <img src="<?php echo htmlspecialchars($ownerAvatar); ?>" 
                                     alt="<?php echo htmlspecialchars($ownerName); ?>" 
                                     class="owner-avatar"
                                     onerror="this.src='/assets/images/avatars/default.jpg';">
                                <span class="owner-name"><?php echo htmlspecialchars($ownerName); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>


    <?php if ($totalFilteredCount > count($filteredBooks)): ?>
        <div id="infiniteScrollTrigger" style="height: 100px; margin-top: 2rem; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1rem;">
            <div id="loader" style="display: none; color: var(--primary);">
                <i class="fas fa-circle-notch fa-spin fa-2x"></i>
            </div>
            <button id="loadMoreBtn" class="btn-elegant" style="display: block; padding: 0.6rem 1.5rem; font-size: 0.9rem;">
                <i class="fas fa-plus"></i> Load More Books
            </button>
        </div>
    <?php endif; ?>
</main>

<script>
let cursorDate = <?php echo json_encode($initialCursor['date']); ?>;
let cursorId = <?php echo json_encode($initialCursor['id']); ?>;
let isLoading = false;
let hasMore = <?php echo ($totalFilteredCount > count($filteredBooks)) ? 'true' : 'false'; ?>;
const booksGrid = document.getElementById('booksGrid');
const loader = document.getElementById('loader');
const countLabel = document.querySelector('#booksCountLabel strong');

// Filters from PHP
const currentFilters = {
    search: <?php echo json_encode($search); ?>,
    categories: <?php echo json_encode($selectedCategories); ?>,
    availability: <?php echo json_encode($availability); ?>
};

document.addEventListener("DOMContentLoaded", () => {
    setupIntersectionObserver();
});

function setupIntersectionObserver() {
    const trigger = document.getElementById('infiniteScrollTrigger');
    if (!trigger) return;

    const observer = new IntersectionObserver((entries) => {
        if (entries[0].isIntersecting && !isLoading && hasMore) {
            console.log('Infinite scroll triggered');
            loadMoreBooks();
        }
    }, { 
        threshold: 0,
        rootMargin: '200px' // Load before the user reaches the very bottom
    });

    observer.observe(trigger);

    // Manual load more button as fallback
    const loadMoreBtn = document.getElementById('loadMoreBtn');
    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', () => {
            if (!isLoading && hasMore) {
                loadMoreBooks();
            }
        });
    }
    
    // Initial cards animation
    const cardObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('show');
                cardObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    document.querySelectorAll('.book-card').forEach(card => cardObserver.observe(card));
    window.cardObserver = cardObserver;
}

async function loadMoreBooks() {
    if (isLoading || !hasMore) return;
    
    isLoading = true;
    loader.style.display = 'block';

    const params = new URLSearchParams();
    if (currentFilters.search) params.append('search', currentFilters.search);
    currentFilters.categories.forEach(cat => params.append('categories[]', cat));
    if (currentFilters.availability) params.append('availability', currentFilters.availability);
    params.append('cursor_date', cursorDate);
    params.append('cursor_id', cursorId);
    params.append('limit', 12);

    try {
        const response = await fetch(`../api/books.php?${params.toString()}`);
        const result = await response.json();

        if (result.success && result.data.length > 0) {
            result.data.forEach(book => {
                const card = createBookCard(book);
                booksGrid.appendChild(card);
                window.cardObserver.observe(card);
            });

            cursorDate = result.cursor.date;
            cursorId = result.cursor.id;
            hasMore = result.has_more;
            
            // Update showing count
            const currentCount = booksGrid.querySelectorAll('.book-card').length;
            countLabel.textContent = currentCount;
        } else {
            hasMore = false;
        }
    } catch (error) {
        console.error('Error loading more books:', error);
    } finally {
        isLoading = false;
        loader.style.display = 'none';
        
        // Hide button if no more books
        const loadMoreBtn = document.getElementById('loadMoreBtn');
        if (loadMoreBtn) {
            loadMoreBtn.style.display = hasMore ? 'block' : 'none';
        }
    }
}

function createBookCard(book) {
    const div = document.createElement('div');
    div.className = 'book-card';
    div.dataset.title = book.title.toLowerCase();
    div.dataset.author = book.author.toLowerCase();
    div.dataset.date = book.created_at;

    const status = book.status.toLowerCase();
    
    div.innerHTML = `
        <div class="book-cover-container">
            <img src="${book.cover_image}" 
                 alt="${book.title}"
                 loading="lazy"
                 onerror="this.src='/assets/images/default-book-cover.jpg';">
            <span class="book-badge badge-${status}">
                ${status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        </div>
        
        <div class="book-info">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.5rem;">
                <div class="book-category-tag" style="margin-bottom: 0;">
                    ${book.category || 'General'}
                </div>
            </div>
            <h3 class="book-title">
                <a href="/book/?id=${book.id}">
                    ${book.title}
                </a>
            </h3>
            <p class="book-author">By ${book.author || 'Unknown'}</p>
            
            <div class="book-footer">
                <div class="owner-info">
                    <img src="${book.owner_avatar}" 
                         alt="${book.owner_name}" 
                         class="owner-avatar"
                         onerror="this.src='/assets/images/avatars/default.jpg';">
                    <span class="owner-name">${book.owner_name}</span>
                </div>
            </div>
        </div>
    `;
    return div;
}

function sortBooks(criteria) {
    const grid = document.getElementById('booksGrid');
    if (!grid) return;
    const books = Array.from(grid.children);
    
    books.sort((a, b) => {
        if (criteria === 'title') return a.dataset.title.localeCompare(b.dataset.title);
        if (criteria === 'author') return a.dataset.author.localeCompare(b.dataset.author);
        return new Date(b.dataset.date || 0) - new Date(a.dataset.date || 0);
    });
    
    books.forEach(book => {
        book.style.transform = 'scale(0.95)';
        book.style.opacity = '0';
        book.classList.remove('show');
    });
    
    setTimeout(() => {
        books.forEach(book => grid.appendChild(book));
        setTimeout(() => {
            books.forEach((book, index) => {
                setTimeout(() => {
                    book.style.transform = '';
                    book.style.opacity = '';
                    book.classList.add('show');
                }, index * 30);
            });
        }, 50);
    }, 300);
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>