<?php
/**
 * OpenShelf Books Listing Page
 * Ultra Modern, Clean, Mobile-First Book Cards
 */

session_start();
include dirname(__DIR__) . '/includes/header.php';
include dirname(__DIR__) . '/includes/BookCardGrid.php';
include dirname(__DIR__) . '/includes/BookCardList.php';

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
            SELECT b.id, b.title, b.author, b.category, b.status, b.created_at, b.cover_image, b.rating, b.rating_count, b.owner_id, b.hall, u.name as owner_name, u.profile_pic as owner_avatar, u.hall as owner_hall
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

        /* Sticky Category/Filter Bar */
        .minimal-top-bar {
            background: var(--header-bg);
            padding: 0.4rem 1rem;
            border-bottom: 1px solid var(--header-border);
            margin: 0;
            position: sticky;
            top: 72px; /* Default: Header height */
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

        .minimal-top-bar::-webkit-scrollbar {
            display: none;
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

        [data-theme="dark"] .empty-glass { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .empty-glass h3 { color: #f8fafc; }
    </style>

    <div class="books-main">
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
        <!-- Desktop/Tablet View (Grid) -->
        <div id="desktop-view-wrapper" class="hide-on-mobile">
            <?php renderBookCardGrid($filteredBooks, ['id' => 'booksGridDesktop']); ?>
        </div>
        
        <!-- Mobile View (List) -->
        <div id="mobile-view-wrapper" class="show-on-mobile">
            <?php renderBookCardList($filteredBooks, ['id' => 'booksGridMobile']); ?>
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
const booksGridDesktop = document.getElementById('booksGridDesktop');
const booksGridMobile = document.getElementById('booksGridMobile');
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
            const hSearch = document.querySelector('.header-search-input');
            if (hSearch) hSearch.value = '';
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
    const searchInput = document.querySelector('.header-search-input');
    if (!searchInput) return;

    // Pre-fill header search with current PHP search filter if set
    if (currentFilters.search && searchInput.value === '') {
        searchInput.value = currentFilters.search;
    }

    const handleSearch = debounce(async (e) => {
        const query = e.target.value.trim();
        if (query === currentFilters.search) return;

        currentFilters.search = query;
        await refreshBooks();
    }, 400);

    searchInput.addEventListener('input', handleSearch);
    
    // Prevent form submission to keep it AJAX
    const form = document.getElementById('headerSearchForm');
    if (form) {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            const query = searchInput.value.trim();
            currentFilters.search = query;
            refreshBooks();
        });
    }
}

async function refreshBooks() {
    // Reset state
    cursorDate = null;
    cursorId = null;
    hasMore = true;
    if (booksGridDesktop) booksGridDesktop.innerHTML = '';
    if (booksGridMobile) booksGridMobile.innerHTML = '';
    
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
                const gridCard = createBookCardGrid(book);
                const listCard = createBookCardList(book);
                
                if (booksGridDesktop) {
                    booksGridDesktop.appendChild(gridCard);
                    window.cardObserver.observe(gridCard);
                }
                
                if (booksGridMobile) {
                    booksGridMobile.appendChild(listCard);
                    window.cardObserver.observe(listCard);
                }
            });

            cursorDate = result.cursor.date;
            cursorId = result.cursor.id;
            hasMore = result.has_more;
            
            // Update showing count
            const currentCount = booksGridDesktop ? booksGridDesktop.querySelectorAll('.book-card').length : 0;
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

function createBookCardGrid(book) {
    const div = document.createElement('div');
    div.className = 'book-card';
    div.dataset.title = book.title.toLowerCase();
    div.dataset.author = book.author.toLowerCase();
    div.dataset.date = book.created_at;

    const status = book.status.toLowerCase();
    const rating = parseFloat(book.rating) || 0;
    
    div.innerHTML = `
        <div class="book-cover-container">
            <img src="${book.cover_image}" alt="${book.title}" loading="lazy" onerror="this.src='/assets/images/default-book-cover.jpg';">
            <span class="book-badge badge-${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
        </div>
        <div class="book-info">
            <div class="book-category-tag">${book.category || 'General'}</div>
            <h3 class="book-title"><a href="/book/?id=${book.id}">${book.title}</a></h3>
            <p class="book-author">By ${book.author || 'Unknown'}</p>
            ${getRatingHtml(book)}
            <div class="book-footer">
                <div class="owner-info">
                    <img src="${book.owner_avatar}" alt="${book.owner_name}" class="owner-avatar" onerror="this.src='/assets/images/avatars/default.jpg';">
                    <span class="owner-name">${book.owner_name}</span>
                </div>
            </div>
        </div>
    `;
    return div;
}

function createBookCardList(book) {
    const div = document.createElement('div');
    div.className = 'book-card-list';
    div.dataset.title = book.title.toLowerCase();
    div.dataset.author = book.author.toLowerCase();
    div.dataset.date = book.created_at;

    const status = book.status.toLowerCase();
    const rating = parseFloat(book.rating) || 0;
    
    // Simple hall mapping in JS (matching helpers.php)
    const halls = {'1': 'Amar Ekushey Hall', '2': 'Dr. Muhammad Shahidullah Hall', '3': 'Fazlul Huq Muslim Hall'};
    const displayHall = halls[book.owner_hall || book.hall] || book.owner_hall || book.hall || 'N/A Hall';
    
    const ratingCount = parseInt(book.rating_count) || 0;
    let ratingRowHtml = '';
    if (ratingCount > 0) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = (rating - fullStars) >= 0.5;
        const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
        
        let starsHtml = '';
        for (let i = 0; i < fullStars; i++) {
            starsHtml += '<i class="fas fa-star active"></i>';
        }
        if (hasHalfStar) {
            starsHtml += '<i class="fas fa-star-half-alt active"></i>';
        }
        for (let i = 0; i < emptyStars; i++) {
            starsHtml += '<i class="far fa-star"></i>';
        }
        
        ratingRowHtml = `
            <div class="rating-row">
                <div class="stars-mini">${starsHtml}</div>
                <span class="rating-value">${rating.toFixed(1)}</span>
                <span class="rating-count" style="color: #888; font-size: 0.7rem; font-weight: 500;">(${ratingCount})</span>
            </div>
        `;
    }

    div.innerHTML = `
        <div class="status-sign status-${status}" title="${status.charAt(0).toUpperCase() + status.slice(1)}"></div>
        <div class="card-cover-section">
            <img src="${book.cover_image}" alt="${book.title}" class="book-cover-image" onerror="this.src='/assets/images/default-book-cover.jpg';">
        </div>
        <div class="card-info-section">
            <h3 class="card-title">
                <a href="/book/?id=${book.id}" style="text-decoration: none; color: inherit;">${book.title}</a>
            </h3>
            <p class="card-author">${book.author || 'Unknown'}</p>
            <p class="category-label">${book.category || 'General'}</p>
            ${ratingRowHtml}
            <div class="card-owner">
                <img src="${book.owner_avatar}" alt="${book.owner_name}" class="owner-avatar" onerror="this.src='/assets/images/avatars/default.jpg';">
                <div class="owner-details">
                    <a href="/profile/?id=${book.owner_id}" class="owner-name">${book.owner_name}</a>
                    <span class="owner-hall">${displayHall}</span>
                </div>
            </div>
        </div>
    `;
    return div;
}

function getRatingHtml(book) {
    const rating = parseFloat(book.rating) || 0;
    const ratingCount = parseInt(book.rating_count) || 0;
    
    if (ratingCount === 0) return '';

    const fullStars = Math.floor(rating);
    const hasHalfStar = (rating - fullStars) >= 0.5;
    const emptyStars = 5 - fullStars - (hasHalfStar ? 1 : 0);
    
    let starsHtml = '';
    for (let i = 0; i < fullStars; i++) starsHtml += '<i class="fas fa-star"></i>';
    if (hasHalfStar) starsHtml += '<i class="fas fa-star-half-alt"></i>';
    for (let i = 0; i < emptyStars; i++) starsHtml += '<i class="far fa-star"></i>';
    
    return `
        <div class="book-rating" style="display: flex; align-items: center; gap: 0.2rem; margin-top: 0.35rem; font-size: 0.8rem; color: #f59e0b;">
            <div class="stars" style="display: flex; gap: 1px;">${starsHtml}</div>
            <span style="font-weight: 700; margin-left: 0.25rem;">${rating.toFixed(1)}</span>
            <span style="color: var(--gray-500); font-weight: 400; font-size: 0.75rem;">(${ratingCount})</span>
        </div>
    `;
}

function sortBooks(criteria) {
    [booksGridDesktop, booksGridMobile].forEach(grid => {
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
    });
}
</script>

<?php
$hideFooter = true;
include dirname(__DIR__) . '/includes/footer.php';
?>