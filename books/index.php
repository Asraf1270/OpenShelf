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
function getBooks($search = '', $selectedCategories = [], $availability = '', $hall = '', $limit = 25, $cursor_date = null, $cursor_id = null, $sort = 'newest') {
    try {
        $db = getDB();
        list($where, $params) = prepareBookQuery($search, $selectedCategories, $availability, $hall);


        if ($cursor_date && $cursor_id) {
            if ($sort === 'oldest') {
                $where[] = "(b.created_at > :c_date1 OR (b.created_at = :c_date2 AND b.id > :c_id))";
            } else {
                $where[] = "(b.created_at < :c_date1 OR (b.created_at = :c_date2 AND b.id < :c_id))";
            }
            $params[':c_date1'] = $cursor_date;
            $params[':c_date2'] = $cursor_date;
            $params[':c_id'] = $cursor_id;
        }

        $orderClause = ($sort === 'oldest') ? "ORDER BY b.created_at ASC, b.id ASC" : "ORDER BY b.created_at DESC, b.id DESC";

        $sql = "
            SELECT b.id, b.title, b.author, b.category, b.status, b.created_at, b.cover_image, b.rating, b.rating_count, b.owner_id, b.hall, u.name as owner_name, u.profile_pic as owner_avatar, u.hall as owner_hall
            FROM books b 
            LEFT JOIN users u ON b.owner_id = u.id 
            WHERE " . implode(' AND ', $where) . "
            $orderClause
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
$sortParam = $_GET['sort'] ?? 'newest';
$limit = 25;

$filteredBooks = getBooks($search, $selectedCategories, $availability, $hallFilter, $limit, null, null, $sortParam);

// Suggest related books if results are few
if (!empty($search) && count($filteredBooks) < 4) {
    $db = getDB();
    $excludeIds = array_column($filteredBooks, 'id');
    $related = getRelatedBooksForSearch($db, $search, $excludeIds, 8 - count($filteredBooks));
    $filteredBooks = array_merge($filteredBooks, $related);
}


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

        /* Filter-by label in category bar */
        .filter-by-label {
            font-size: 0.8rem;
            font-weight: 700;
            color: var(--gray-600);
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Books header: sort + filter in one row */
        .books-header {
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.75rem;
            margin-top: 5rem;
            margin-bottom: 1.25rem;
            width: 100%;
        }
        .sort-controls {
            flex: 1;
            min-width: 0;
        }
        .filter-wrapper {
            flex: 1;
            min-width: 0;
            position: relative;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            justify-content: flex-end;
        }

        /* Styled select for sort */
        .styled-select {
            width: 100%;
            padding: 0.6rem 2.2rem 0.6rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            background: var(--gray-50);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
            cursor: pointer;
            transition: all 0.25s ease;
            outline: none;
            appearance: none;
            box-sizing: border-box;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.6rem center;
        }
        .styled-select:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(44,62,80,0.1); }

        /* Filters button */
        .filter-btn {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            width: 100%;
            padding: 0.6rem 1rem;
            border: 1px solid var(--gray-200);
            border-radius: 10px;
            background: var(--gray-50);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--gray-700);
            cursor: pointer;
            transition: all 0.25s ease;
            white-space: nowrap;
            box-sizing: border-box;
        }
        .filter-btn:hover, .filter-btn.active {
            background: white;
            border-color: var(--primary);
            color: var(--primary);
        }
        .filter-btn i { font-size: 0.85rem; }

        /* Filter dropdown panel */
        .filter-panel {
            display: none;
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            width: 280px;
            background: white;
            border: 1px solid var(--gray-200);
            border-radius: 14px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.12);
            padding: 1.25rem;
            z-index: 1000;
            animation: panelSlideIn 0.18s ease;
        }
        .filter-panel.show { display: block; }
        @keyframes panelSlideIn {
            from { opacity: 0; transform: translateY(-6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        .filter-panel-title {
            font-size: 1rem;
            font-weight: 700;
            color: var(--gray-800);
            margin-bottom: 1rem;
        }
        .filter-section-label {
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: var(--gray-500);
            margin-bottom: 0.5rem;
        }
        .filter-radio-group, .filter-check-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        .filter-option {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.9rem;
            color: var(--gray-700);
            cursor: pointer;
        }
        .filter-option input { accent-color: var(--primary); cursor: pointer; }
        .filter-panel-actions {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
            margin-top: 0.25rem;
        }
        .btn-apply-filters {
            width: 100%;
            padding: 0.7rem;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s ease;
        }
        .btn-apply-filters:hover { background: var(--primary-dark); }
        .btn-cancel-filters {
            width: 100%;
            padding: 0.65rem;
            background: var(--gray-100);
            color: var(--gray-600);
            border: none;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-cancel-filters:hover { background: var(--gray-200); }

        /* Dark mode support */
        [data-theme="dark"] .filter-panel {
            background: #1e293b;
            border-color: #334155;
        }
        [data-theme="dark"] .styled-select,
        [data-theme="dark"] .filter-btn {
            background: #1e293b;
            border-color: #334155;
            color: #cbd5e1;
        }


        [data-theme="dark"] .empty-glass { background: #1e293b; border-color: #334155; }
        [data-theme="dark"] .empty-glass h3 { color: #f8fafc; }
    </style>

    <div class="books-main">
    <!-- Sticky Category / Filter Bar -->
    <div class="minimal-top-bar">
        <span class="filter-by-label">Filter by</span>
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
        </div>
    </div>

    <?php $hasFilters = !empty($search) || !empty($selectedCategories) || !empty($availability) || !empty($hallFilter); ?>
    <div class="books-header">
        <!-- Sort dropdown (left) -->
        <div class="sort-controls">
            <select class="styled-select" id="sortSelect" onchange="sortBooks(this.value)">
                <option value="newest" <?php echo ($sortParam === 'newest') ? 'selected' : ''; ?>>Sort: Newest First</option>
                <option value="oldest" <?php echo ($sortParam === 'oldest') ? 'selected' : ''; ?>>Sort: Oldest First</option>
                <option value="title" <?php echo ($sortParam === 'title') ? 'selected' : ''; ?>>Sort: Title A-Z</option>
                <option value="author" <?php echo ($sortParam === 'author') ? 'selected' : ''; ?>>Sort: Author A-Z</option>
            </select>
        </div>

        <!-- Filters button + panel (right) -->
        <div class="filter-wrapper">
            <button class="filter-btn <?php echo ($hasFilters && (!empty($availability) || !empty($hallFilter))) ? 'active' : ''; ?>" id="filtersToggleBtn">
                <span>Filters</span>
                <i class="fas fa-sliders-h"></i>
            </button>

            <?php if ($hasFilters): ?>
            <a href="#" class="btn-clear" id="clearFiltersBtn" title="Clear all filters" style="flex-shrink:0;">
                <i class="fas fa-times"></i>
            </a>
            <?php else: ?>
            <a href="#" class="btn-clear" id="clearFiltersBtn" title="Clear all filters" style="display:none; flex-shrink:0;">
                <i class="fas fa-times"></i>
            </a>
            <?php endif; ?>

            <!-- Filter dropdown panel -->
            <div class="filter-panel" id="filterPanel">
                <div class="filter-panel-title">Status Filters</div>

                <div class="filter-section-label">Status</div>
                <div class="filter-radio-group">
                    <label class="filter-option">
                        <input type="radio" name="filterStatus" value="" <?php echo empty($availability) ? 'checked' : ''; ?>>
                        All
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="filterStatus" value="available" <?php echo $availability === 'available' ? 'checked' : ''; ?>>
                        Available
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="filterStatus" value="borrowed" <?php echo $availability === 'borrowed' ? 'checked' : ''; ?>>
                        Borrowed
                    </label>
                </div>

                <?php if (isset($_SESSION['user_id']) && !empty($_SESSION['user_hall'])): ?>
                <div class="filter-section-label">Library</div>
                <div class="filter-check-group">
                    <label class="filter-option">
                        <input type="checkbox" id="hallCheckbox" value="<?php echo htmlspecialchars($_SESSION['user_hall']); ?>" <?php echo !empty($hallFilter) ? 'checked' : ''; ?>>
                        My Hall
                    </label>
                </div>
                <?php endif; ?>

                <div class="filter-panel-actions">
                    <button class="btn-apply-filters" id="applyFiltersBtn">Apply Filters</button>
                    <button class="btn-cancel-filters" id="cancelFiltersBtn">Cancel</button>
                </div>
            </div>
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


    <?php if (count($filteredBooks) >= $limit): ?>
        <div id="infiniteScrollTrigger" style="margin-top: 2rem; width: 100%;">
            <!-- Desktop/Tablet View Skeleton -->
            <div id="skeleton-desktop-wrapper" class="hide-on-mobile" style="display: none;">
                <?php if (function_exists('renderBookCardGridSkeleton')) renderBookCardGridSkeleton(4); ?>
            </div>
            
            <!-- Mobile View Skeleton -->
            <div id="skeleton-mobile-wrapper" class="show-on-mobile" style="display: none;">
                <?php if (function_exists('renderBookCardListSkeleton')) renderBookCardListSkeleton(3); ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
let cursorDate = <?php echo json_encode($initialCursor['date']); ?>;
let cursorId = <?php echo json_encode($initialCursor['id']); ?>;
let isLoading = false;
let hasMore = <?php echo (count($filteredBooks) >= $limit) ? 'true' : 'false'; ?>;
const booksGridDesktop = document.getElementById('booksGridDesktop');
const booksGridMobile = document.getElementById('booksGridMobile');
const skeletonDesktop = document.getElementById('skeleton-desktop-wrapper');
const skeletonMobile = document.getElementById('skeleton-mobile-wrapper');

// Filters from PHP
const currentFilters = {
    search: <?php echo json_encode($search); ?>,
    categories: <?php echo json_encode($selectedCategories); ?>,
    availability: <?php echo json_encode($availability); ?>,
    hall: <?php echo json_encode($hallFilter); ?>
};

let currentSort = '<?php echo htmlspecialchars($sortParam); ?>';

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

    // ── Filter panel toggle ────────────────────────────────────────
    const filtersToggleBtn = document.getElementById('filtersToggleBtn');
    const filterPanel      = document.getElementById('filterPanel');

    if (filtersToggleBtn && filterPanel) {
        filtersToggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            filterPanel.classList.toggle('show');
            filtersToggleBtn.classList.toggle('active', filterPanel.classList.contains('show'));
        });

        // Close when clicking outside
        document.addEventListener('click', (e) => {
            if (!filterPanel.contains(e.target) && e.target !== filtersToggleBtn) {
                filterPanel.classList.remove('show');
                filtersToggleBtn.classList.remove('active');
            }
        });
    }

    // Apply filters from panel
    const applyBtn = document.getElementById('applyFiltersBtn');
    if (applyBtn) {
        applyBtn.addEventListener('click', () => {
            const statusRadio = document.querySelector('input[name="filterStatus"]:checked');
            currentFilters.availability = statusRadio ? statusRadio.value : '';

            const hallCb = document.getElementById('hallCheckbox');
            currentFilters.hall = (hallCb && hallCb.checked) ? hallCb.value : '';

            // Update filter button active state
            const hasActiveFilter = currentFilters.availability || currentFilters.hall;
            if (filtersToggleBtn) filtersToggleBtn.classList.toggle('active', !!hasActiveFilter);

            // Show/hide clear button
            const clearBtn = document.getElementById('clearFiltersBtn');
            if (clearBtn) {
                const anyFilter = currentFilters.search || currentFilters.categories.length || currentFilters.availability || currentFilters.hall;
                clearBtn.style.display = anyFilter ? 'inline-flex' : 'none';
            }

            filterPanel.classList.remove('show');
            refreshBooks();
        });
    }

    // Cancel — just close panel
    const cancelBtn = document.getElementById('cancelFiltersBtn');
    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            filterPanel.classList.remove('show');
            if (filtersToggleBtn) filtersToggleBtn.classList.remove('active');
        });
    }

    // ── Clear all filters ─────────────────────────────────────────
    const clearBtn = document.getElementById('clearFiltersBtn');
    if (clearBtn) {
        clearBtn.addEventListener('click', (e) => {
            e.preventDefault();
            currentFilters.search = '';
            currentFilters.categories = [];
            currentFilters.availability = '';
            currentFilters.hall = '';

            // Reset UI
            const hSearch = document.querySelector('.header-search-input');
            if (hSearch) hSearch.value = '';
            document.querySelectorAll('.category-chip').forEach(c => {
                c.classList.toggle('active', !c.dataset.category);
            });
            // Reset panel radios/checkbox
            const allRadio = document.querySelector('input[name="filterStatus"][value=""]');
            if (allRadio) allRadio.checked = true;
            const hallCb = document.getElementById('hallCheckbox');
            if (hallCb) hallCb.checked = false;
            if (filtersToggleBtn) filtersToggleBtn.classList.remove('active');

            refreshBooks();
            clearBtn.style.display = 'none';
        });
    }
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
    if (skeletonDesktop) skeletonDesktop.style.display = 'block';
    if (skeletonMobile) skeletonMobile.style.display = 'block';
    
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
    
    if (currentSort !== 'newest') url.searchParams.set('sort', currentSort);
    else url.searchParams.delete('sort');
    
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

    // Fallback infinite scroll is intersection observer, button removed
    
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
    if (skeletonDesktop) skeletonDesktop.style.display = 'block';
    if (skeletonMobile) skeletonMobile.style.display = 'block';

    const params = new URLSearchParams();
    if (currentFilters.search) params.append('search', currentFilters.search);
    currentFilters.categories.forEach(cat => params.append('categories[]', cat));
    if (currentFilters.availability) params.append('availability', currentFilters.availability);
    if (currentFilters.hall) params.append('hall', currentFilters.hall);
    params.append('sort', currentSort);
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
            

        } else {
            hasMore = false;
        }
    } catch (error) {
        console.error('Error loading more books:', error);
    } finally {
        isLoading = false;
        if (skeletonDesktop) skeletonDesktop.style.display = 'none';
        if (skeletonMobile) skeletonMobile.style.display = 'none';
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
        <a href="/book/?id=${book.id}" class="cover-link">
            <img src="${book.cover_image}" alt="${book.title}" class="book-cover-image" onerror="this.src='/assets/images/default-book-cover.jpg';">
        </a>
        <div class="card-info-section">
            <a href="/book/?id=${book.id}" class="book-info-link">
                <h3 class="card-title">${book.title}</h3>
                <p class="card-author">${book.author || 'Unknown'}</p>
                <p class="category-label">${book.category || 'General'}</p>
                ${ratingRowHtml}
            </a>
            <a href="/profile/?id=${book.owner_id}" class="owner-link-area">
                <img src="${book.owner_avatar}" alt="${book.owner_name}" class="owner-avatar" onerror="this.src='/assets/images/avatars/default.jpg';">
                <div class="owner-details">
                    <span class="owner-name">${book.owner_name}</span>
                    <span class="owner-hall">${displayHall}</span>
                </div>
            </a>
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
    currentSort = criteria;

    // Date-based sorts: re-fetch from API so cursor pagination is correct
    if (criteria === 'newest' || criteria === 'oldest') {
        refreshBooks();
        return;
    }

    [booksGridDesktop, booksGridMobile].forEach(grid => {
        if (!grid) return;
        const books = Array.from(grid.children);
        books.sort((a, b) => {
            if (criteria === 'title') return a.dataset.title.localeCompare(b.dataset.title);
            if (criteria === 'author') return a.dataset.author.localeCompare(b.dataset.author);
            return 0;
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