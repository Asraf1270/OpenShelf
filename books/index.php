<?php
/**
 * OpenShelf Books Listing Page
 * Modern, Clean, Mobile-First Book Cards
 */

session_start();
include dirname(__DIR__) . '/includes/header.php';

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_PATH', dirname(__DIR__) . '/books/');

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
function filterBooks($books, $search, $category, $availability) {
    return array_filter($books, function($book) use ($search, $category, $availability) {
        if (!empty($search)) {
            $searchLower = strtolower($search);
            $titleMatch = stripos($book['title'] ?? '', $searchLower) !== false;
            $authorMatch = stripos($book['author'] ?? '', $searchLower) !== false;
            if (!$titleMatch && !$authorMatch) return false;
        }
        
        if (!empty($category) && ($book['category'] ?? '') !== $category) {
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
 * Get user name
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
 * Get user avatar
 */
function getUserAvatar($userId) {
    $userFile = dirname(__DIR__) . '/users/' . $userId . '.json';
    if (file_exists($userFile)) {
        $userData = json_decode(file_get_contents($userFile), true);
        return $userData['personal_info']['profile_pic'] ?? 'default-avatar.jpg';
    }
    return 'default-avatar.jpg';
}

// Load and filter books
$allBooks = loadAllBooks();
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$availability = $_GET['availability'] ?? '';

$filteredBooks = filterBooks($allBooks, $search, $category, $availability);
$categories = getCategories($allBooks);

// Stats
$totalBooks = count($allBooks);
$availableBooks = count(array_filter($allBooks, fn($b) => ($b['status'] ?? '') === 'available'));
$totalCategories = count($categories);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Browse Books - OpenShelf</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ========================================
           MODERN BOOKS PAGE - AMAZON/GOOGLE STYLE
        ======================================== */
        
        :root {
            --primary: #2563eb;
            --primary-dark: #1d4ed8;
            --success: #10b981;
            --success-dark: #059669;
            --warning: #f59e0b;
            --danger: #ef4444;
            --gray-50: #f8fafc;
            --gray-100: #f1f5f9;
            --gray-200: #e2e8f0;
            --gray-300: #cbd5e1;
            --gray-600: #475569;
            --gray-700: #334155;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --shadow-sm: 0 1px 2px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
            --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1);
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: var(--gray-50);
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            color: var(--gray-800);
            line-height: 1.5;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }

        /* ========== PAGE HEADER ========== */
        .hero {
            background: linear-gradient(135deg, var(--primary) 0%, #1e3a8a 100%);
            border-radius: 0 0 2rem 2rem;
            padding: 2.5rem 1.5rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
            animation: rotate 30s linear infinite;
        }

        @keyframes rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .hero h1 {
            font-size: 2rem;
            font-weight: 700;
            color: white;
            margin-bottom: 0.5rem;
            position: relative;
        }

        .hero p {
            color: rgba(255,255,255,0.85);
            font-size: 1rem;
            position: relative;
        }

        /* ========== STATS CARDS ========== */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 1.25rem 0.75rem;
            border-radius: var(--radius-lg);
            text-align: center;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            border: 1px solid var(--gray-200);
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .stat-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--gray-600);
            font-weight: 500;
        }

        /* ========== FILTER CARD ========== */
        .filter-card {
            background: white;
            border-radius: var(--radius-lg);
            padding: 1.25rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 2rem;
            border: 1px solid var(--gray-200);
        }

        .filter-form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .search-wrapper {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-400);
            font-size: 0.9rem;
        }

        .form-input, .form-select {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.75rem;
            border: 1.5px solid var(--gray-200);
            border-radius: 2rem;
            font-size: 0.9rem;
            transition: var(--transition);
            background: white;
        }

        .form-input:focus, .form-select:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(37,99,235,0.1);
        }

        .filter-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .clear-btn {
            text-align: center;
            margin-top: 0.5rem;
        }

        .clear-btn a {
            color: var(--gray-500);
            font-size: 0.85rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: var(--transition);
        }

        .clear-btn a:hover {
            color: var(--danger);
        }

        /* ========== RESULTS HEADER ========== */
        .results-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .results-count {
            font-size: 0.9rem;
            color: var(--gray-600);
        }

        .results-count strong {
            color: var(--gray-800);
            font-weight: 600;
        }

        .sort-select {
            padding: 0.5rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 2rem;
            font-size: 0.85rem;
            background: white;
            cursor: pointer;
        }

        /* ========== BOOK GRID - MOBILE FIRST ========== */
        .book-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 3rem;
        }

        /* ========== MODERN BOOK CARD ========== */
        .book-card {
            background: white;
            border-radius: var(--radius-md);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--gray-200);
            position: relative;
        }

        .book-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-xl);
            border-color: var(--primary);
        }

        /* Book Cover */
        .book-cover {
            position: relative;
            aspect-ratio: 2 / 3;
            background: linear-gradient(135deg, var(--gray-100), var(--gray-200));
            overflow: hidden;
        }

        .book-cover img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.4s ease;
        }

        .book-card:hover .book-cover img {
            transform: scale(1.05);
        }

        /* Status Badge */
        .book-status {
            position: absolute;
            top: 10px;
            right: 10px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.65rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            background: rgba(0,0,0,0.7);
            backdrop-filter: blur(4px);
            color: white;
            z-index: 2;
        }

        .status-available { background: var(--success); }
        .status-borrowed { background: var(--danger); }
        .status-reserved { background: var(--warning); }

        /* Book Info */
        .book-info {
            padding: 0.9rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .book-title {
            font-size: 0.9rem;
            font-weight: 600;
            line-height: 1.35;
            color: var(--gray-800);
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .book-title a {
            color: inherit;
            text-decoration: none;
            transition: color var(--transition);
        }

        .book-title a:hover {
            color: var(--primary);
        }

        .book-author {
            font-size: 0.75rem;
            color: var(--gray-500);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .book-category {
            display: inline-block;
            background: var(--gray-100);
            color: var(--gray-600);
            font-size: 0.7rem;
            padding: 0.2rem 0.7rem;
            border-radius: 20px;
            font-weight: 500;
        }

        .book-owner {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.25rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--gray-200);
            font-size: 0.7rem;
            color: var(--gray-500);
        }

        .owner-avatar {
            width: 22px;
            height: 22px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid var(--gray-200);
        }

        /* ========== EMPTY STATE ========== */
        .empty-state {
            text-align: center;
            padding: 3rem 1.5rem;
            background: white;
            border-radius: var(--radius-lg);
            border: 1px solid var(--gray-200);
            margin: 2rem 0;
        }

        .empty-state i {
            font-size: 3.5rem;
            color: var(--gray-300);
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--gray-500);
            margin-bottom: 1.5rem;
        }

        .btn-primary {
            display: inline-block;
            padding: 0.6rem 1.5rem;
            background: var(--primary);
            color: white;
            border-radius: 2rem;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .btn-primary:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }

        /* ========== RESPONSIVE BREAKPOINTS ========== */
        @media (min-width: 480px) {
            .hero h1 { font-size: 2.2rem; }
            .book-grid { gap: 1.25rem; }
        }

        @media (min-width: 640px) {
            .book-grid { grid-template-columns: repeat(3, 1fr); gap: 1.5rem; }
            .book-info { padding: 1rem; }
            .book-title { font-size: 0.95rem; }
        }

        @media (min-width: 768px) {
            .hero { padding: 3rem 2rem; }
            .hero h1 { font-size: 2.5rem; }
            .stats-grid { gap: 1.5rem; }
            .stat-value { font-size: 2rem; }
            .book-grid { grid-template-columns: repeat(4, 1fr); }
        }

        @media (min-width: 1024px) {
            .book-grid { grid-template-columns: repeat(5, 1fr); gap: 1.75rem; }
            .filter-form { flex-direction: row; align-items: center; gap: 1rem; }
            .search-wrapper { flex: 2; }
            .filter-row { flex: 2; }
        }

        @media (min-width: 1280px) {
            .book-grid { grid-template-columns: repeat(6, 1fr); }
        }

        /* Animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .book-card {
            animation: fadeInUp 0.4s ease forwards;
        }
    </style>
</head>
<body>

<div class="container">
    <!-- Hero Section -->
    <div class="hero">
        <h1><i class="fas fa-book-open"></i> Browse Books</h1>
        <p>Discover your next favorite read from our community</p>
    </div>
    
    <!-- Quick Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value" style="color: var(--primary);"><?php echo $totalBooks; ?></div>
            <div class="stat-label">Total Books</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--success);"><?php echo $availableBooks; ?></div>
            <div class="stat-label">Available Now</div>
        </div>
        <div class="stat-card">
            <div class="stat-value" style="color: var(--warning);"><?php echo $totalCategories; ?></div>
            <div class="stat-label">Categories</div>
        </div>
    </div>
    
    <!-- Search and Filter -->
    <div class="filter-card">
        <form method="GET" class="filter-form">
            <div class="search-wrapper">
                <i class="fas fa-search search-icon"></i>
                <input type="text" name="search" class="form-input" 
                       placeholder="Search by title or author..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-row">
                <select name="category" class="form-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="availability" class="form-select" onchange="this.form.submit()">
                    <option value="">All Books</option>
                    <option value="available" <?php echo $availability === 'available' ? 'selected' : ''; ?>>Available Only</option>
                    <option value="borrowed" <?php echo $availability === 'borrowed' ? 'selected' : ''; ?>>Borrowed</option>
                </select>
            </div>
            
            <?php if (!empty($search) || !empty($category) || !empty($availability)): ?>
                <div class="clear-btn">
                    <a href="/books/"><i class="fas fa-times-circle"></i> Clear all filters</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Results Header -->
    <div class="results-header">
        <div class="results-count">
            <strong><?php echo count($filteredBooks); ?></strong> books found
        </div>
        <div>
            <select class="sort-select" onchange="sortBooks(this.value)">
                <option value="newest">Newest First</option>
                <option value="title">Title A-Z</option>
                <option value="author">Author A-Z</option>
            </select>
        </div>
    </div>
    
    <!-- Books Grid -->
    <?php if (empty($filteredBooks)): ?>
        <div class="empty-state">
            <i class="fas fa-book-open"></i>
            <h3>No Books Found</h3>
            <p>Try adjusting your search or explore different categories</p>
            <a href="/books/" class="btn-primary">View All Books</a>
        </div>
    <?php else: ?>
        <div class="book-grid" id="booksGrid">
            <?php foreach ($filteredBooks as $book): 
                $ownerName = getUserName($book['owner_id'] ?? '');
                $ownerAvatar = getUserAvatar($book['owner_id'] ?? '');
                $coverImage = !empty($book['cover_image']) 
                    ? '/uploads/book_cover/' . ltrim($book['cover_image'], '/') 
                    : '/assets/images/default-book-cover.jpg';
                $status = strtolower($book['status'] ?? 'available');
            ?>
                <div class="book-card" data-title="<?php echo strtolower($book['title'] ?? ''); ?>" 
                     data-author="<?php echo strtolower($book['author'] ?? ''); ?>" 
                     data-date="<?php echo $book['created_at'] ?? ''; ?>">
                    
                    <div class="book-cover">
                        <img src="<?php echo htmlspecialchars($coverImage); ?>" 
                             alt="<?php echo htmlspecialchars($book['title'] ?? 'Book'); ?>"
                             loading="lazy"
                             onerror="this.src='/assets/images/default-book-cover.jpg';">
                        <span class="book-status status-<?php echo $status; ?>">
                            <?php echo ucfirst($status); ?>
                        </span>
                    </div>
                    
                    <div class="book-info">
                        <h3 class="book-title">
                            <a href="/book/?id=<?php echo htmlspecialchars($book['id'] ?? ''); ?>">
                                <?php echo htmlspecialchars($book['title'] ?? 'Untitled'); ?>
                            </a>
                        </h3>
                        <p class="book-author">by <?php echo htmlspecialchars($book['author'] ?? 'Unknown'); ?></p>
                        
                        <div class="book-category">
                            <?php echo htmlspecialchars($book['category'] ?? 'General'); ?>
                        </div>
                        
                        <div class="book-owner">
                            <img src="/uploads/profile/<?php echo $ownerAvatar; ?>" 
                                 alt="<?php echo htmlspecialchars($ownerName); ?>" 
                                 class="owner-avatar"
                                 onerror="this.src='/assets/images/avatars/default.jpg';">
                            <span><?php echo htmlspecialchars($ownerName); ?></span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function sortBooks(criteria) {
    const grid = document.getElementById('booksGrid');
    if (!grid) return;
    const books = Array.from(grid.children);
    
    books.sort((a, b) => {
        if (criteria === 'title') return a.dataset.title.localeCompare(b.dataset.title);
        if (criteria === 'author') return a.dataset.author.localeCompare(b.dataset.author);
        return new Date(b.dataset.date || 0) - new Date(a.dataset.date || 0);
    });
    
    books.forEach(book => grid.appendChild(book));
}

// Debounced search
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