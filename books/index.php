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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    
    <style>
        /* ========================================
           ULTRA MODERN BOOKS PAGE 
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
            --gray-500: #64748b;
            --gray-600: #475569;
            --gray-800: #1e293b;
            --gray-900: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.5);
            --shadow-soft: 0 10px 40px -10px rgba(0,0,0,0.05);
            --shadow-hover: 0 20px 40px -10px rgba(99,102,241,0.15);
            --radius-xl: 1.5rem;
            --radius-lg: 1rem;
            --radius-md: 0.75rem;
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

        /* Hero Section */
        .books-hero {
            position: relative;
            padding: 6rem 1rem 5rem;
            text-align: center;
            background: linear-gradient(135deg, #e0e7ff 0%, #ede9fe 100%);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .hero-shape-1 {
            position: absolute; width: 400px; height: 400px;
            background: radial-gradient(circle, rgba(99,102,241,0.3) 0%, transparent 70%);
            top: -10%; left: -5%; filter: blur(40px); animation: float 10s infinite;
        }
        .hero-shape-2 {
            position: absolute; width: 500px; height: 500px;
            background: radial-gradient(circle, rgba(139,92,246,0.3) 0%, transparent 70%);
            bottom: -20%; right: -10%; filter: blur(50px); animation: float 12s infinite reverse;
        }

        .books-hero h1 {
            font-size: 3.5rem; font-weight: 800;
            background: linear-gradient(135deg, var(--gray-900), var(--primary));
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            margin-bottom: 1rem; position: relative; z-index: 2;
            animation: fadeUp 0.8s ease-out;
        }
        .books-hero p {
            font-size: 1.2rem; color: var(--gray-600); max-width: 600px; margin: 0 auto;
            position: relative; z-index: 2; animation: fadeUp 1s ease-out 0.2s both;
        }

        /* Stats Bar */
        .stats-bar {
            display: flex; justify-content: center; flex-wrap: wrap; gap: 2rem;
            margin: -3.5rem auto 3rem; position: relative; z-index: 10;
            max-width: 900px; padding: 0 1.5rem;
            animation: fadeUp 1s ease-out 0.4s both;
        }
        .stat-item {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border); border-radius: var(--radius-lg);
            padding: 1.5rem 2rem; text-align: center; flex: 1; min-width: 200px;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.1); transition: var(--transition);
        }
        .stat-item:hover { transform: translateY(-5px); box-shadow: var(--shadow-hover); }
        .stat-value { font-size: 2.5rem; font-weight: 800; color: var(--primary); margin-bottom: 0.25rem; }
        .stat-label { font-size: 0.85rem; font-weight: 600; color: var(--gray-600); text-transform: uppercase; letter-spacing: 1px; }

        /* Main Container */
        .books-main {
            max-width: 1400px; margin: 0 auto; padding: 0 1.5rem 4rem;
        }

        /* Filter Section */
        .filter-glass {
            background: white; border-radius: var(--radius-xl);
            padding: 1.5rem; display: flex; gap: 1rem; align-items: center; justify-content: space-between;
            box-shadow: var(--shadow-soft); flex-wrap: wrap; border: 1px solid var(--gray-200);
            margin-bottom: 2rem;
        }
        .search-box {
            position: relative; flex: 1; min-width: 250px;
        }
        .search-box i { position: absolute; left: 1.25rem; top: 50%; transform: translateY(-50%); color: var(--gray-500); }
        .search-input {
            width: 100%; padding: 1rem 1rem 1rem 3rem; border: 2px solid transparent;
            border-radius: var(--radius-lg); background: var(--gray-50);
            font-size: 1rem; transition: all 0.3s ease; color: var(--gray-800);
        }
        .search-input:focus { background: white; border-color: var(--primary); outline: none; box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
        .filter-controls { display: flex; gap: 1rem; flex-wrap: wrap; flex: 1; justify-content: flex-end; }
        .styled-select {
            padding: 1rem 1.5rem; border: 2px solid transparent; border-radius: var(--radius-lg);
            background: var(--gray-50); font-size: 0.95rem; font-weight: 500; color: var(--gray-800);
            cursor: pointer; transition: all 0.3s ease; outline: none; appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat; background-position: right 1rem center; padding-right: 3rem;
            min-width: 160px;
        }
        .styled-select:focus { background-color: white; border-color: var(--primary); box-shadow: 0 0 0 4px rgba(99,102,241,0.1); }
        .clear-btn { display: inline-flex; align-items: center; gap: 0.5rem; color: var(--danger); font-size: 0.9rem; font-weight: 500; text-decoration: none; padding: 0.5rem 1rem; border-radius: 2rem; background: rgba(244,63,94,0.1); transition: all 0.2s; }
        .clear-btn:hover { background: rgba(244,63,94,0.2); }

        /* Books Header */
        .books-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem; }
        .books-count { font-size: 1.1rem; color: var(--gray-600); }
        .books-count strong { color: var(--gray-900); font-weight: 700; font-size: 1.25rem; }

        /* Book Grid */
        .book-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 2rem; }
        .book-card {
            background: white; border-radius: var(--radius-xl); overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); transition: var(--transition);
            display: flex; flex-direction: column; position: relative; border: 1px solid var(--gray-100);
            animation: fadeUp 0.6s ease-out backwards;
        }
        .book-card:hover { transform: translateY(-10px) scale(1.02); box-shadow: var(--shadow-hover); border-color: #c7d2fe; z-index: 2; }

        .book-cover-container { position: relative; padding-top: 140%; overflow: hidden; background: #f1f5f9; }
        .book-cover-container img { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease; }
        .book-card:hover .book-cover-container img { transform: scale(1.08); }

        .book-badge {
            position: absolute; top: 1rem; right: 1rem; padding: 0.4rem 1rem;
            border-radius: 2rem; font-size: 0.75rem; font-weight: 700; text-transform: uppercase;
            letter-spacing: 0.5px; z-index: 2; backdrop-filter: blur(8px);
        }
        .badge-available { background: rgba(16, 185, 129, 0.9); color: white; box-shadow: 0 4px 10px rgba(16,185,129,0.3); }
        .badge-borrowed { background: rgba(244, 63, 94, 0.9); color: white; box-shadow: 0 4px 10px rgba(244,63,94,0.3); }

        .book-info { padding: 1.5rem; flex: 1; display: flex; flex-direction: column; }
        .book-category-tag {
            font-size: 0.75rem; font-weight: 600; color: var(--primary); text-transform: uppercase;
            letter-spacing: 0.5px; margin-bottom: 0.5rem; background: rgba(99,102,241,0.1); padding: 0.2rem 0.6rem; border-radius: 4px; display: inline-block; width: fit-content;
        }
        .book-title {
            font-size: 1.15rem; font-weight: 700; margin-bottom: 0.5rem; line-height: 1.4;
            color: var(--gray-900); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
        }
        .book-title a { color: inherit; text-decoration: none; transition: color 0.2s; }
        .book-title a:hover { color: var(--primary); }
        .book-author { font-size: 0.9rem; color: var(--gray-500); margin-bottom: 1.5rem; font-weight: 500; }

        .book-footer {
            margin-top: auto; padding-top: 1rem; border-top: 1px solid var(--gray-100);
            display: flex; align-items: center; justify-content: space-between;
        }
        .owner-info { display: flex; align-items: center; gap: 0.75rem; }
        .owner-avatar { width: 32px; height: 32px; border-radius: 50%; object-fit: cover; border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .owner-name { font-size: 0.85rem; font-weight: 500; color: var(--gray-800); }

        /* Empty state */
        .empty-glass {
            text-align: center; padding: 5rem 2rem; background: white;
            border-radius: var(--radius-xl); border: 1px solid var(--gray-200); margin: 0 auto; max-width: 600px;
            box-shadow: 0 10px 30px -10px rgba(0,0,0,0.05);
        }
        .empty-icon-box {
            width: 100px; height: 100px; background: rgba(99,102,241,0.1); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem;
            color: var(--primary); font-size: 3rem;
        }
        .empty-glass h3 { font-size: 1.5rem; font-weight: 700; margin-bottom: 0.75rem; color: var(--gray-900); }
        .empty-glass p { color: var(--gray-500); margin-bottom: 2rem; font-size: 1.1rem; }
        .btn-elegant {
            background: linear-gradient(135deg, var(--primary), var(--secondary));
            color: white; padding: 0.75rem 2rem; border-radius: 2rem; text-decoration: none;
            font-weight: 600; transition: var(--transition); display: inline-block; box-shadow: 0 4px 15px rgba(99,102,241,0.3);
        }
        .btn-elegant:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(99,102,241,0.4); color: white; }

        /* Staggered animation delays for grid items */
        .book-card:nth-child(1) { animation-delay: 0.1s; }
        .book-card:nth-child(2) { animation-delay: 0.15s; }
        .book-card:nth-child(3) { animation-delay: 0.2s; }
        .book-card:nth-child(4) { animation-delay: 0.25s; }
        .book-card:nth-child(5) { animation-delay: 0.3s; }
        .book-card:nth-child(6) { animation-delay: 0.35s; }
        .book-card:nth-child(7) { animation-delay: 0.4s; }
        .book-card:nth-child(8) { animation-delay: 0.45s; }

        @keyframes float { 0%, 100% { transform: translateY(0) scale(1); } 50% { transform: translateY(-20px) scale(1.05); } }
        @keyframes fadeUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        /* Responsive */
        @media (max-width: 768px) {
            .books-hero { padding: 4rem 1rem 3rem; }
            .books-hero h1 { font-size: 2.5rem; }
            .stats-bar { padding: 0 1rem; gap: 1rem; margin-top: -2rem; }
            .stat-item { min-width: 140px; padding: 1rem; }
            .stat-value { font-size: 2rem; }
            .filter-glass { flex-direction: column; align-items: stretch; }
            .filter-controls { flex-direction: column; }
            .search-box { min-width: 100%; }
            .book-grid { grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem; }
        }
        @media (max-width: 480px) {
            .book-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Hero Section -->
    <section class="books-hero">
        <div class="hero-shape-1"></div>
        <div class="hero-shape-2"></div>
        <h1><i class="fas fa-layer-group"></i> Library Collection</h1>
        <p>Explore thousands of books shared by the community. Find your next great read tailored to your interests.</p>
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
    
    <!-- Search and Filter -->
    <div class="filter-glass">
        <form method="GET" style="display: contents;">
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" name="search" class="search-input" 
                       placeholder="Search by book title or author..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            
            <div class="filter-controls">
                <select name="category" class="styled-select" onchange="this.form.submit()">
                    <option value="">All Categories</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo htmlspecialchars($cat); ?>" <?php echo $category === $cat ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($cat); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                
                <select name="availability" class="styled-select" onchange="this.form.submit()">
                    <option value="">Status: All</option>
                    <option value="available" <?php echo $availability === 'available' ? 'selected' : ''; ?>>Available Now</option>
                    <option value="borrowed" <?php echo $availability === 'borrowed' ? 'selected' : ''; ?>>Currently Borrowed</option>
                </select>
            </div>
            
            <?php if (!empty($search) || !empty($category) || !empty($availability)): ?>
                <a href="/books/" class="clear-btn"><i class="fas fa-times"></i> Clear Filters</a>
            <?php endif; ?>
        </form>
    </div>
    
    <!-- Results Header -->
    <div class="books-header">
        <div class="books-count">
            Showing <strong><?php echo count($filteredBooks); ?></strong> books
        </div>
        <div>
            <select class="styled-select" onchange="sortBooks(this.value)" style="padding: 0.6rem 2.5rem 0.6rem 1rem; min-width: auto; font-size: 0.9rem;">
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
            <?php foreach ($filteredBooks as $book): 
                $ownerName = getUserName($book['owner_id'] ?? '');
                $ownerAvatar = getUserAvatar($book['owner_id'] ?? '');
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
                                <img src="/uploads/profile/<?php echo $ownerAvatar; ?>" 
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
function sortBooks(criteria) {
    const grid = document.getElementById('booksGrid');
    if (!grid) return;
    const books = Array.from(grid.children);
    
    books.sort((a, b) => {
        if (criteria === 'title') return a.dataset.title.localeCompare(b.dataset.title);
        if (criteria === 'author') return a.dataset.author.localeCompare(b.dataset.author);
        return new Date(b.dataset.date || 0) - new Date(a.dataset.date || 0);
    });
    
    // Animate reordering
    grid.style.opacity = '0';
    setTimeout(() => {
        books.forEach(book => grid.appendChild(book));
        grid.style.opacity = '1';
    }, 200);
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