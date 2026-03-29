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
 * Load all books
 */
function loadAllBooks() {
    $booksFile = DATA_PATH . 'books.json';
    if (!file_exists($booksFile)) {
        return [];
    }
    return json_decode(file_get_contents($booksFile), true) ?? [];
}

/**
 * Get unique categories
 */
function getCategories($books) {
    $categories = [];
    foreach ($books as $book) {
        if (!empty($book['category']) && !in_array($book['category'], $categories)) {
            $categories[] = $book['category'];
        }
    }
    sort($categories);
    return $categories;
}

/**
 * Filter books
 */
function filterBooks($books, $search, $categories, $availability) {
    return array_filter($books, function($book) use ($search, $categories, $availability) {
        if (!empty($search)) {
            $searchLower = strtolower($search);
            $titleMatch = stripos($book['title'] ?? '', $searchLower) !== false;
            $authorMatch = stripos($book['author'] ?? '', $searchLower) !== false;
            if (!$titleMatch && !$authorMatch) return false;
        }
        
        if (!empty($categories) && !in_array($book['category'] ?? '', (array)$categories)) {
            return false;
        }
        
        if (!empty($availability)) {
            $status = $book['status'] ?? 'available';
            if ($availability === 'available' && $status !== 'available') return false;
            if ($availability === 'borrowed' && $status !== 'borrowed') return false;
        }
        
        return true;
    });
}

/**
 * Get user info (Optimized Single Load)
 */
function getUserInfo($userId) {
    static $users = null;
    if ($users === null) {
        $usersFile = DATA_PATH . 'users.json';
        $users = file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) ?? [] : [];
    }
    foreach ($users as $user) {
        if ($user['id'] === $userId) {
            return [
                'name' => $user['name'] ?? 'Unknown',
                'avatar' => $user['profile_pic'] ?? 'default-avatar.jpg'
            ];
        }
    }
    return ['name' => 'Unknown', 'avatar' => 'default-avatar.jpg'];
}

// Load and filter books
$allBooks = loadAllBooks();
$search = $_GET['search'] ?? '';
$selectedCategories = isset($_GET['categories']) ? (array)$_GET['categories'] : [];
$availability = $_GET['availability'] ?? '';

$filteredBooks = filterBooks($allBooks, $search, $selectedCategories, $availability);
$categories = getCategories($allBooks);

// Stats
$totalBooks = count($allBooks);
$availableBooks = count(array_filter($allBooks, fn($b) => ($b['status'] ?? '') === 'available'));
$totalCategories = count($categories);

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

        /* Hero Section (Mobile First) */
        .books-hero {
            position: relative;
            padding: 3rem 1rem 4rem;
            text-align: center;
            background: linear-gradient(135deg, #e0e7ff 0%, #ede9fe 100%);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .hero-shape {
            position: absolute;
            background: radial-gradient(circle, rgba(99,102,241,0.2) 0%, transparent 70%);
            border-radius: 50%;
            filter: blur(40px);
            z-index: 0;
        }
        .hero-shape-1 {
            width: 300px; height: 300px; top: -10%; left: -20%;
            animation: float 10s infinite;
        }
        .hero-shape-2 {
            width: 350px; height: 350px; bottom: -20%; right: -20%;
            animation: float 12s infinite reverse;
            background: radial-gradient(circle, rgba(139,92,246,0.2) 0%, transparent 70%);
        }

        .books-hero-content {
            position: relative; z-index: 2;
        }
        .books-hero h1 {
            font-size: 2.2rem; font-weight: 800;
            background: linear-gradient(135deg, var(--gray-900), var(--primary));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 0.75rem;
            animation: slideDown 0.6s ease-out;
        }
        .books-hero p {
            font-size: 1rem; color: var(--gray-600); max-width: 600px; margin: 0 auto;
            animation: slideDown 0.8s ease-out 0.1s both;
        }

        /* Stats Bar (Mobile First) */
        .stats-bar {
            display: flex; flex-direction: column; gap: 1rem;
            margin: -2.5rem 1rem 2rem; position: relative; z-index: 10;
        }
        .stat-item {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border); border-radius: var(--radius-lg);
            padding: 1.25rem 1rem; text-align: center;
            box-shadow: var(--shadow-sm); transition: var(--transition);
        }
        .stat-value { font-size: 2rem; font-weight: 800; color: var(--primary); line-height: 1.1; }
        .stat-label { font-size: 0.8rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; letter-spacing: 1px; margin-top: 0.25rem; }

        /* Main Container */
        .books-main {
            padding: 0 1rem 4rem; width: 100%; max-width: 1400px; margin: 0 auto;
        }

        /* Category Pills Scrollbar */
        .category-pills-wrap {
            margin-bottom: 1.5rem; position: relative;
        }
        .category-pills {
            display: flex; gap: 0.75rem; overflow-x: auto; padding: 0.5rem 0 1rem 0;
            scrollbar-width: none; -ms-overflow-style: none; scroll-behavior: smooth;
        }
        .category-pills::-webkit-scrollbar { display: none; }
        .category-pill {
            padding: 0.6rem 1.25rem; background: white; border: 1px solid var(--gray-200);
            border-radius: 2rem; color: var(--gray-600); font-size: 0.9rem; font-weight: 600;
            white-space: nowrap; text-decoration: none; transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0,0,0,0.02);
        }
        .category-pill:hover { background: var(--gray-50); color: var(--primary); border-color: var(--primary); transform: translateY(-2px); }
        .category-pill.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; border-color: transparent; box-shadow: 0 4px 10px rgba(99,102,241,0.3); }

        /* Filter Section */
        .filter-glass {
            background: white; border-radius: var(--radius-lg); padding: 1rem;
            display: flex; flex-direction: column; gap: 1rem;
            box-shadow: var(--shadow-sm); border: 1px solid var(--gray-200); margin-bottom: 2rem;
        }
        .search-box { position: relative; width: 100%; }
        .search-box i { position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--gray-500); }
        .search-input {
            width: 100%; padding: 0.85rem 1rem 0.85rem 3rem; border: 2px solid var(--gray-100);
            border-radius: var(--radius-md); background: var(--gray-50);
            font-size: 0.95rem; transition: all 0.3s ease; color: var(--gray-800);
        }
        .search-input:focus { background: white; border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
        
        .filter-controls { display: flex; flex-direction: column; gap: 0.75rem; width: 100%; }
        .styled-select {
            padding: 0.85rem 1.25rem; border: 2px solid var(--gray-100); border-radius: var(--radius-md);
            background: var(--gray-50); font-size: 0.9rem; font-weight: 500; color: var(--gray-800);
            cursor: pointer; transition: all 0.3s ease; outline: none; appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 1rem center; padding-right: 2.5rem; width: 100%;
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
            .books-hero { padding: 4rem 2rem 5rem; }
            .books-hero h1 { font-size: 3rem; }
            .hero-shape-1 { width: 400px; height: 400px; }
            .hero-shape-2 { width: 500px; height: 500px; }
            
            .stats-bar { flex-direction: row; margin: -3.5rem auto 3rem; max-width: 900px; padding: 0 1.5rem; }
            .stat-item { flex: 1; padding: 1.5rem; }
            .stat-value { font-size: 2.5rem; }
            
            .filter-glass { flex-direction: row; padding: 1.25rem 1.5rem; }
            .filter-controls { flex-direction: row; flex: 1; justify-content: flex-end; width: auto; }
            .styled-select { width: auto; min-width: 180px; }
            
            .books-header { flex-direction: row; }
        }

        @media (min-width: 1024px) {
            .books-hero h1 { font-size: 3.5rem; }
            .book-grid { grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 2rem; }
        }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <section class="books-hero">
        <div class="hero-shape hero-shape-1"></div>
        <div class="hero-shape hero-shape-2"></div>
        <div class="books-hero-content">
            <h1><i class="fas fa-layer-group"></i> Library Collection</h1>
            <p>Explore thousands of books shared by the community. Find your next great read tailored to your interests.</p>
        </div>
    </section>
    
    <!-- Quick Stats -->
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-value"><?php echo $totalBooks; ?></div>
            <div class="stat-label">Total Books</div>
        </div>
        <div class="stat-item">
            <div class="stat-value" style="color: var(--success);"><?php echo $availableBooks; ?></div>
            <div class="stat-label">Available Now</div>
        </div>
        <div class="stat-item">
            <div class="stat-value" style="color: var(--secondary);"><?php echo $totalCategories; ?></div>
            <div class="stat-label">Categories</div>
        </div>
    </div>

<main class="books-main">

    <!-- Horizontal Category Top Bar -->
    <div class="category-pills-wrap">
        <div class="category-pills">
            <a href="<?php echo getUrlWithParam('categories', ''); ?>" 
               class="category-pill <?php echo empty($selectedCategories) ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> All Categories
            </a>
            <?php foreach ($categories as $cat): 
                $isActive = in_array($cat, $selectedCategories);
            ?>
                <a href="<?php echo toggleCategoryUrl($cat); ?>" 
                   class="category-pill <?php echo $isActive ? 'active' : ''; ?>">
                   <?php if($isActive): ?><i class="fas fa-check-circle"></i> <?php endif; ?>
                   <?php echo htmlspecialchars($cat); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="filter-glass">
        <form method="GET" style="display: contents;">
            <!-- Preserve multiple category selection when searching -->
            <?php if (!empty($selectedCategories)): ?>
                <?php foreach ($selectedCategories as $cat): ?>
                    <input type="hidden" name="categories[]" value="<?php echo htmlspecialchars($cat); ?>">
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="search-input" 
                       placeholder="Search by book title or author..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-controls">
                <select name="availability" class="styled-select" onchange="this.form.submit()">
                    <option value="">Status: All</option>
                    <option value="available" <?php echo $availability === 'available' ? 'selected' : ''; ?>>Available Now</option>
                    <option value="borrowed" <?php echo $availability === 'borrowed' ? 'selected' : ''; ?>>Currently Borrowed</option>
                </select>
                
                <?php if (!empty($search) || !empty($selectedCategories) || !empty($availability)): ?>
                    <a href="/books/" class="styled-select" style="text-align: center; text-decoration: none; color: var(--danger); background-image: none; padding-right: 1.25rem;">
                        <i class="fas fa-times"></i> Clear Filters
                    </a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
    <!-- Results Header -->
    <div class="books-header">
        <div class="books-count">
            Showing <strong><?php echo count($filteredBooks); ?></strong> books 
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
                $ownerInfo = getUserInfo($book['owner_id'] ?? '');
                $ownerName = $ownerInfo['name'];
                $ownerAvatar = !empty($ownerInfo['avatar']) && $ownerInfo['avatar'] !== 'default-avatar.jpg'
                    ? '/uploads/profile/' . ltrim($ownerInfo['avatar'], '/')
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
</main>

<script>
document.addEventListener("DOMContentLoaded", () => {
    // Elegant Intersection Observer for stagger entry animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                // Add a slight delay based on the element's position on screen relative to others
                const rect = entry.target.getBoundingClientRect();
                const delay = (rect.left / window.innerWidth) * 200 + (rect.top % window.innerHeight / window.innerHeight) * 200;
                
                setTimeout(() => {
                    entry.target.classList.add('show');
                }, delay);
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: "0px 0px -50px 0px" });
    
    document.querySelectorAll('.book-card').forEach(card => observer.observe(card));
});

function sortBooks(criteria) {
    const grid = document.getElementById('booksGrid');
    if (!grid) return;
    const books = Array.from(grid.children);
    
    books.sort((a, b) => {
        if (criteria === 'title') return a.dataset.title.localeCompare(b.dataset.title);
        if (criteria === 'author') return a.dataset.author.localeCompare(b.dataset.author);
        return new Date(b.dataset.date || 0) - new Date(a.dataset.date || 0);
    });
    
    // Animate reordering elegantly
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
                }, index * 50); // Stagger re-show
            });
        }, 50);
    }, 300);
}

// Debounce search input for automatic submission
let searchTimeout;
const searchInput = document.querySelector('input[name="search"]');
if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => this.form.submit(), 600);
    });
}
</script>

<?php include dirname(__DIR__) . '/includes/footer.php'; ?>