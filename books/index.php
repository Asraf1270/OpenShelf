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
function getBooks($search = '', $selectedCategories = [], $availability = '', $hall = '', $limit = 25, $cursor_date = null, $cursor_id = null) {
    try {
        $db = getDB();
        list($where, $params) = prepareBookQuery($search, $selectedCategories, $availability, $hall);


        if ($cursor_date && $cursor_id) {
            $where[] = "(b.created_at < :c_date1 OR (b.created_at = :c_date2 AND b.id < :c_id))";
            $params[':c_date1'] = $cursor_date;
            $params[':c_date2'] = $cursor_date;
            $params[':c_id'] = $cursor_id;
        }

        $sql = "
            SELECT b.*, u.name as owner_name, u.profile_pic as owner_avatar 
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
    } catch (PDOException $e) {
        error_log("Database Error in getBooks: " . $e->getMessage());
        return [];
    }
}

/**
 * Get total books count for current filters
 */
function getBooksCount($search = '', $selectedCategories = [], $availability = '', $hall = '') {
    try {
        $db = getDB();
        list($where, $params) = prepareBookQuery($search, $selectedCategories, $availability, $hall);

        $sql = "SELECT COUNT(*) FROM books b WHERE " . implode(' AND ', $where);
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log("Database Error in getBooksCount: " . $e->getMessage());
        return 0;
    }
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
$hallFilter = $_GET['hall'] ?? '';
$limit = 25;

$filteredBooks = getBooks($search, $selectedCategories, $availability, $hallFilter, $limit);

// Suggest related books if results are few
if (!empty($search) && count($filteredBooks) < 4) {
    $db = getDB();
    $excludeIds = array_column($filteredBooks, 'id');
    $related = getRelatedBooksForSearch($db, $search, $excludeIds, 8 - count($filteredBooks));
    $filteredBooks = array_merge($filteredBooks, $related);
}

$totalFilteredCount = getBooksCount($search, $selectedCategories, $availability, $hallFilter);
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

<style>
        /* ========================================
           MOBILE-FIRST ULTRA MODERN CSS
        ======================================== */
        
        :root {
            --primary: #2C3E50;
            --primary-dark: #1a252f;
            --secondary: #4C9F8A;
            --success: #2E8B57;
            --danger: #C65D5D;
            --gray-50: #F8F9FA;
            --gray-100: #F1F5F9;
            --gray-200: #E2E8F0;
            --gray-300: #cbd5e1;
            --gray-400: #94a3b8;
            --gray-500: #5A6C7D;
            --gray-600: #4a5568;
            --gray-700: #334155;
            --gray-800: #0F172A;
            --gray-900: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
            --radius-xl: 1.5rem;
            --radius-lg: 1rem;
            --radius-md: 0.75rem;
            --shadow-sm: 0 4px 6px -1px rgba(0,0,0,0.05);
            --shadow-md: 0 10px 15px -3px rgba(0,0,0,0.08);
            --shadow-hover: 0 20px 40px -10px rgba(44,62,80,0.2);
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
            padding: 0 1rem 4rem;
            width: 100%;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Search Bar (sticky below the header) */
        .search-bar-wrap {
            background: var(--header-bg);
            padding: 0.5rem 1rem;
            border-bottom: 1px solid var(--header-border);
            display: flex;
            justify-content: center;
            position: sticky;
            top: 56px;
            z-index: 995;
            margin: 0;
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            max-height: 80px;
            overflow: hidden;
        }

        .search-bar-wrap.hidden {
            transform: translateY(-100%);
            opacity: 0;
            max-height: 0;
            padding-top: 0;
            padding-bottom: 0;
            border-bottom-width: 0;
        }

        /* Sticky Category/Filter Bar */
        .minimal-top-bar {
            background: var(--header-bg);
            padding: 0.4rem 1rem;
            border-bottom: 1px solid var(--header-border);
            margin: 0;
            position: sticky;
            top: 104px; /* Default: Header (56) + Search (48) */
            z-index: 990;
            display: flex;
            align-items: center;
            gap: 1rem;
            overflow-x: auto;
            white-space: nowrap;
            scrollbar-width: none;
            -ms-overflow-style: none;
            transition: top 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* When header is hidden, category bar sticks to top */
        body.header-hidden .minimal-top-bar {
            top: 0;
            border-top: 1px solid var(--header-border);
        }

        body.header-hidden .search-bar-wrap {
            transform: translateY(-150%);
            opacity: 0;
            pointer-events: none;
        }

        .minimal-top-bar::-webkit-scrollbar {
            display: none;
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
            align-items: center;
            width: 100%;
            background: var(--gray-100);
            border: none;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
            padding: 2px 4px;
        }

        .youtube-search:focus-within {
            box-shadow: 0 8px 30px rgba(99, 102, 241, 0.15);
            transform: translateY(-1px);
        }

        .search-input-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            padding-left: 1.25rem;
            position: relative;
        }

        .search-input-wrapper i {
            color: var(--gray-400);
            font-size: 1rem;
            position: absolute;
            left: 1.25rem;
        }

        .search-input {
            flex: 1;
            border: none;
            background: transparent;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            font-size: 0.95rem;
            outline: none;
            color: var(--gray-800);
            width: 100%;
        }

        .search-btn {
            background: var(--primary);
            border: none;
            padding: 0.5rem 1.25rem;
            cursor: pointer;
            color: white;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .search-btn:hover {
            background: var(--primary-dark);
            transform: scale(1.02);
        }

        /* Category Pills (YouTube Chips) */
        .category-row {
            width: 100%;
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.65rem;
            padding: 0.25rem 0;
            min-height: 40px;
        }

        .filter-controls {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.65rem;
            flex: 0 0 auto;
            min-width: 220px;
            padding-left: 0;
            border-left: none;
            margin-left: auto;
        }

        .minimal-top-bar .filter-controls {
            justify-content: flex-end;
        }

        .radio-group {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .radio-item {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.85rem;
            color: var(--gray-700);
            cursor: pointer;
        }

        .radio-item input {
            accent-color: var(--primary);
        }

        .btn-clear {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: var(--gray-100);
            color: var(--gray-700);
            border: 1px solid var(--gray-200);
            transition: all 0.2s ease;
        }

        .btn-clear:hover {
            background: var(--primary);
            color: white;
            border-color: transparent;
        }

        .chip {
            padding: 0.4rem 0.85rem;
            background: #f2f2f2;
            border: none;
            border-radius: 6px;
            color: #0f172a;
            text-decoration: none;
            font-size: 0.8rem;
            font-weight: 600;
            white-space: nowrap;
            transition: background 0.2s;
        }

        .chip:hover { background: #e5e5e5; }
        
        .chip.active {
            background: #0f172a;
            color: white;
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

        /* Book Grid (Modern Fluid Layout) */
        .book-grid { 
            display: grid; 
            grid-template-columns: repeat(2, 1fr); 
            gap: 1.25rem; 
        }
        
        .book-card {
            background: white; 
            border-radius: 20px; 
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.04); 
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex; 
            flex-direction: column; 
            position: relative; 
            border: none;
            /* Initial state for IntersectionObserver Animation */
            opacity: 0; 
            transform: translateY(20px);
        }
        
        .book-card.show { 
            opacity: 1; 
            transform: translateY(0); 
        }
        
        .book-card:hover { 
            transform: translateY(-8px); 
            box-shadow: 0 20px 40px rgba(0,0,0,0.08); 
        }

        .book-cover-container { 
            position: relative; 
            padding: 1rem;
            padding-top: 130%; 
            overflow: hidden; 
            background: #f8fafc; 
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .book-cover-container img { 
            position: absolute; 
            top: 1rem; 
            left: 1rem; 
            right: 1rem; 
            bottom: 1rem; 
            width: calc(100% - 2rem); 
            height: calc(100% - 2rem); 
            object-fit: contain; 
            transition: transform 0.6s ease; 
            border-radius: 12px;
            filter: drop-shadow(0 8px 16px rgba(0,0,0,0.1));
        }
        .book-card:hover .book-cover-container img { transform: scale(1.05); }

        .book-badge {
            position: absolute; 
            top: 1.25rem; 
            right: 1.25rem; 
            padding: 0.4rem 0.9rem;
            border-radius: 2rem; 
            font-size: 0.75rem; 
            font-weight: 700; 
            text-transform: uppercase;
            letter-spacing: 0.5px; 
            z-index: 2; 
            backdrop-filter: blur(4px);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .badge-available { background: rgba(39, 174, 96, 0.85); }
        .badge-borrowed { background: rgba(231, 76, 60, 0.85); }
        .badge-reserved { background: rgba(52, 152, 219, 0.85); }

        .book-info { padding: 1.25rem; flex: 1; display: flex; flex-direction: column; }
        .book-category-tag {
            font-size: 0.75rem; 
            font-weight: 600; 
            color: var(--primary); 
            text-transform: uppercase;
            letter-spacing: 1px; 
            margin-bottom: 0.4rem; 
            opacity: 0.7;
        }
        .book-title {
            font-size: 1.1rem; 
            font-weight: 800; 
            margin-bottom: 0.35rem; 
            line-height: 1.4;
            color: var(--gray-900); 
            display: -webkit-box; 
            -webkit-line-clamp: 2; 
            -webkit-box-orient: vertical; 
            overflow: hidden;
        }
        .book-title a { color: inherit; text-decoration: none; transition: color 0.2s; }
        .book-title a:hover { color: var(--primary); }
        .book-author { font-size: 0.85rem; color: var(--gray-500); margin-bottom: 1.25rem; font-weight: 500; }

        .book-footer {
            margin-top: auto; 
            padding-top: 1rem; 
            border-top: 1px solid var(--gray-100);
            display: flex; 
            align-items: center; 
            justify-content: space-between;
        }
        .owner-info { display: flex; align-items: center; gap: 0.75rem; }
        .owner-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; border: 1.5px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
        .owner-name { font-size: 0.8rem; font-weight: 600; color: var(--gray-700); }

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
                gap: 1rem; 
                padding: 0 0.5rem;
            }
            .book-info { padding: 1rem; }
            .book-title { font-size: 0.9rem; }
            .owner-avatar { width: 22px; height: 22px; }
            .book-badge { top: 0.75rem; right: 0.75rem; padding: 0.25rem 0.6rem; font-size: 0.65rem; }
        }

        @media (min-width: 640px) {
            .book-grid { grid-template-columns: repeat(3, 1fr); }
            .search-bar-wrap { padding: 0.85rem 2rem; }
            .minimal-top-bar { padding: 0.5rem 2rem; }
            .styled-select { width: auto; min-width: 160px; }
            .books-header { flex-direction: row; }
        }

        @media (min-width: 1024px) {
            .book-grid { grid-template-columns: repeat(4, 1fr); gap: 2rem; }
        }

        @media (min-width: 1400px) {
            .book-grid { grid-template-columns: repeat(5, 1fr); }
        }

        [data-theme="dark"] .search-bar-wrap { border-bottom-color: #334155; }
        [data-theme="dark"] .minimal-top-bar { border-bottom-color: #334155; }
        [data-theme="dark"] .youtube-search { background: #0f172a; border-color: #334155; }
        [data-theme="dark"] .search-input { color: #f8fafc; }
        [data-theme="dark"] .search-btn { background: #1e293b; border-left-color: #334155; color: #cbd5e1; }
        [data-theme="dark"] .search-btn:hover { background: #334155; color: white; }
        [data-theme="dark"] .chip { background: #334155; color: #f8fafc; }
        [data-theme="dark"] .chip:hover { background: #475569; }
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

    <div class="books-main">
    <!-- Search Bar (scrolls with page) -->
    <div class="search-bar-wrap">
        <div class="search-row">
            <form method="GET" action="/books/" class="youtube-search">
                <!-- Preserve categories & availability -->
                <?php if (!empty($selectedCategories)): ?>
                    <?php foreach ($selectedCategories as $cat): ?>
                        <input type="hidden" name="categories[]" value="<?php echo htmlspecialchars($cat); ?>">
                    <?php endforeach; ?>
                <?php endif; ?>
                <?php if (!empty($availability)): ?>
                    <input type="hidden" name="availability" value="<?php echo htmlspecialchars($availability); ?>">
                <?php endif; ?>

                <div class="search-input-wrapper">
                    <i class="fas fa-search"></i>
                    <input type="text" name="search" id="searchInput" class="search-input" 
                           placeholder="Search books, authors, publishers..." 
                           value="<?php echo htmlspecialchars($search); ?>"
                           autocomplete="off">
                </div>
                <button type="submit" class="search-btn">
                    <span>Search</span>
                </button>
            </form>
        </div>
    </div>

    <!-- Sticky Category / Filter Bar -->
    <div class="minimal-top-bar">
        <!-- Category Row -->
        <div class="category-row">
            <a href="<?php echo getUrlWithParam('categories', ''); ?>" 
               class="chip category-chip <?php echo empty($selectedCategories) ? 'active' : ''; ?>"
               data-category="">
                All
            </a>
            <?php foreach ($categories as $cat): 
                $isActive = in_array($cat, $selectedCategories);
            ?>
                <a href="<?php echo toggleCategoryUrl($cat); ?>" 
                   class="chip category-chip <?php echo $isActive ? 'active' : ''; ?>"
                   data-category="<?php echo htmlspecialchars($cat); ?>">
                   <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
            
            <div class="filter-controls" style="margin-left: auto; padding-left: 1rem; border-left: 1px solid var(--gray-200);">
                <form method="GET" action="/books/" style="display: flex; gap: 0.5rem;">
                    <!-- Preserve search and categories -->
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                    <?php if (!empty($selectedCategories)): ?>
                        <?php foreach ($selectedCategories as $cat): ?>
                            <input type="hidden" name="categories[]" value="<?php echo htmlspecialchars($cat); ?>">
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="radio-group">
                        <label class="radio-item">
                            <input type="radio" name="availability" value="" class="availability-radio" <?php echo empty($availability) ? 'checked' : ''; ?>>
                            <span>All</span>
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="availability" value="available" class="availability-radio" <?php echo $availability === 'available' ? 'checked' : ''; ?>>
                            <span>Available</span>
                        </label>
                        <label class="radio-item">
                            <input type="radio" name="availability" value="borrowed" class="availability-radio" <?php echo $availability === 'borrowed' ? 'checked' : ''; ?>>
                            <span>Borrowed</span>
                        </label>
                    </div>

                    <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_hall'])): ?>
                        <div style="width: 1px; height: 24px; background: var(--gray-200); margin: 0 0.5rem;"></div>
                        <div class="radio-group">
                            <label class="radio-item" title="<?php echo htmlspecialchars(getHallName($_SESSION['user_hall'])); ?>">
                                <input type="checkbox" name="hall" value="<?php echo htmlspecialchars($_SESSION['user_hall']); ?>" class="hall-checkbox" <?php echo $hallFilter === $_SESSION['user_hall'] ? 'checked' : ''; ?>>
                                <span style="font-weight: 700; color: var(--primary);">My Hall</span>
                            </label>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($search) || !empty($selectedCategories) || !empty($availability) || !empty($hallFilter)): ?>
                        <a href="#" class="btn-clear" id="clearFiltersBtn" title="Clear all filters">
                            <i class="fas fa-times"></i>
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
                        <div class="book-category-tag">
                            <?php echo htmlspecialchars($book['category'] ?? 'General'); ?>
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
</div>

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
    availability: <?php echo json_encode($availability); ?>,
    hall: <?php echo json_encode($hallFilter); ?>
};

document.addEventListener("DOMContentLoaded", () => {
    setupIntersectionObserver();
    setupInstantSearch();
    setupFilterListeners();
});

function setupFilterListeners() {
    // Category Chips
    document.querySelectorAll('.category-chip').forEach(chip => {
        chip.addEventListener('click', (e) => {
            e.preventDefault();
            const cat = chip.dataset.category;
            
            if (!cat) {
                currentFilters.categories = [];
            } else {
                const index = currentFilters.categories.indexOf(cat);
                if (index > -1) {
                    currentFilters.categories.splice(index, 1);
                } else {
                    currentFilters.categories.push(cat);
                }
            }
            
            // Update UI state
            document.querySelectorAll('.category-chip').forEach(c => {
                const cCat = c.dataset.category;
                const isActive = (!cCat && currentFilters.categories.length === 0) || 
                                 (cCat && currentFilters.categories.includes(cCat));
                c.classList.toggle('active', isActive);
            });
            
            refreshBooks();
        });
    });

    // Availability Radios
    document.querySelectorAll('.availability-radio').forEach(radio => {
        radio.addEventListener('change', (e) => {
            currentFilters.availability = e.target.value;
            refreshBooks();
        });
    });

    // Clear Filters
    const clearBtn = document.getElementById('clearFiltersBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            currentFilters.search = '';
            currentFilters.categories = [];
            currentFilters.availability = '';
            
            // Update UI
            document.getElementById('searchInput').value = '';
            document.querySelectorAll('.category-chip').forEach(c => {
                c.classList.toggle('active', !c.dataset.category);
            });
            document.querySelectorAll('.availability-radio').forEach(r => {
                r.checked = r.value === '';
            });
            document.querySelectorAll('.hall-checkbox').forEach(c => {
                c.checked = false;
            });
            
            refreshBooks();
            clearBtn.style.display = 'none';
        });
    }

    // Hall Checkbox
    document.querySelectorAll('.hall-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', (e) => {
            currentFilters.hall = e.target.checked ? e.target.value : '';
            refreshBooks();
        });
    });
}



// Debounce helper to prevent excessive API calls
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function setupInstantSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;

    const handleSearch = debounce(async (e) => {
        const query = e.target.value.trim();
        if (query === currentFilters.search) return;

        currentFilters.search = query;
        await refreshBooks();
    }, 400);

    searchInput.addEventListener('input', handleSearch);
    
    // Prevent form submission to keep it AJAX
    searchInput.closest('form').addEventListener('submit', (e) => {
        e.preventDefault();
        const query = searchInput.value.trim();
        currentFilters.search = query;
        refreshBooks();
    });
}

async function refreshBooks() {
    // Reset state
    cursorDate = null;
    cursorId = null;
    hasMore = true;
    booksGrid.innerHTML = '';
    
    // Show loader
    loader.style.display = 'block';
    
    // Trigger first load
    await loadMoreBooks();
    
    // Update URL without reloading
    const url = new URL(window.location);
    if (currentFilters.search) url.searchParams.set('search', currentFilters.search);
    else url.searchParams.delete('search');
    
    url.searchParams.delete('categories[]');
    currentFilters.categories.forEach(cat => url.searchParams.append('categories[]', cat));
    
    if (currentFilters.availability) url.searchParams.set('availability', currentFilters.availability);
    else url.searchParams.delete('availability');
    
    if (currentFilters.hall) url.searchParams.set('hall', currentFilters.hall);
    else url.searchParams.delete('hall');
    
    window.history.pushState({}, '', url);

    // Show/Hide Clear button
    const clearBtn = document.getElementById('clearFiltersBtn');
    if (clearBtn) {
        const hasFilters = currentFilters.search || currentFilters.categories.length > 0 || currentFilters.availability || currentFilters.hall;
        clearBtn.style.display = hasFilters ? 'flex' : 'none';
    }
}

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
    if (currentFilters.hall) params.append('hall', currentFilters.hall);
    params.append('cursor_date', cursorDate);
    params.append('cursor_id', cursorId);
    params.append('limit', 25);

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