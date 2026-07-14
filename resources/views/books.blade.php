@extends('layouts.app')

@section('content')
<div class="books-main">
    <!-- Sticky Category / Filter Bar -->
    <div class="minimal-top-bar">
        <span class="filter-by-label">Filter by</span>
        <!-- Category Row -->
        <div class="category-row">
            <a href="{{ route('books', request()->except('categories')) }}" 
               class="chip category-chip {{ empty($selectedCategories) ? 'active' : '' }}"
               data-category="">
                All
            </a>
            @foreach ($categories as $cat)
                @php($isActive = in_array($cat, $selectedCategories))
                <a href="#"
                   class="chip category-chip {{ $isActive ? 'active' : '' }}"
                   data-category="{{ $cat }}">
                   {{ $cat }}
                </a>
            @endforeach
        </div>
    </div>

    @php($hasFilters = !empty($search) || !empty($selectedCategories) || !empty($availability) || !empty($hallFilter))
    <div class="books-header">
        <!-- Sort dropdown (left) -->
        <div class="sort-controls">
            <select class="styled-select" id="sortSelect" onchange="sortBooks(this.value)">
                <option value="newest" {{ $sortParam === 'newest' ? 'selected' : '' }}>Sort: Newest First</option>
                <option value="oldest" {{ $sortParam === 'oldest' ? 'selected' : '' }}>Sort: Oldest First</option>
                <option value="title" {{ $sortParam === 'title' ? 'selected' : '' }}>Sort: Title A-Z</option>
                <option value="author" {{ $sortParam === 'author' ? 'selected' : '' }}>Sort: Author A-Z</option>
            </select>
        </div>

        <!-- Filters button + panel (right) -->
        <div class="filter-wrapper">
            <button class="filter-btn {{ ($hasFilters && (!empty($availability) || !empty($hallFilter))) ? 'active' : '' }}" id="filtersToggleBtn">
                <span>Filters</span>
                <i class="fas fa-sliders-h"></i>
            </button>

            @if ($hasFilters)
            <a href="#" class="btn-clear" id="clearFiltersBtn" title="Clear all filters" style="flex-shrink:0;">
                <i class="fas fa-times"></i>
            </a>
            @else
            <a href="#" class="btn-clear" id="clearFiltersBtn" title="Clear all filters" style="display:none; flex-shrink:0;">
                <i class="fas fa-times"></i>
            </a>
            @endif
            <!-- Filter dropdown panel -->
            <div class="filter-panel" id="filterPanel">
                <div class="filter-panel-title">Status Filters</div>

                <div class="filter-section-label">Status</div>
                <div class="filter-radio-group">
                    <label class="filter-option">
                        <input type="radio" name="filterStatus" value="" {{ empty($availability) ? 'checked' : '' }}>
                        All
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="filterStatus" value="available" {{ $availability === 'available' ? 'checked' : '' }}>
                        Available
                    </label>
                    <label class="filter-option">
                        <input type="radio" name="filterStatus" value="borrowed" {{ $availability === 'borrowed' ? 'checked' : '' }}>
                        Borrowed
                    </label>
                </div>

                @if ($userHall)
                <div class="filter-section-label">Library</div>
                <div class="filter-check-group">
                    <label class="filter-option">
                        <input type="checkbox" id="hallCheckbox" value="{{ $userHall }}" {{ !empty($hallFilter) ? 'checked' : '' }}>
                        My Hall
                    </label>
                </div>
                @endif
            <div class="filter-panel-actions">
                    <button class="btn-apply-filters" id="applyFiltersBtn">Apply Filters</button>
                    <button class="btn-cancel-filters" id="cancelFiltersBtn">Cancel</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Books Grid -->
    @if ($filteredBooks->isEmpty())
        <div class="empty-glass">
            <div class="empty-icon-box">
                <i class="fas fa-book-open"></i>
            </div>
            <h3>No Books Found</h3>
            <p>We couldn't find any books matching your current filters. Try adjusting your search or explore different categories.</p>
            <a href="{{ route('books') }}" class="btn-elegant">View All Books</a>
        </div>
    @else
        <div id="desktop-view-wrapper" class="hide-on-mobile">
            <x-book-card-grid :books="$filteredBooks" id="booksGridDesktop" />
        </div>

        <div id="mobile-view-wrapper" class="show-on-mobile">
            <x-book-card-list :books="$filteredBooks" id="booksGridMobile" />
        </div>

        @if ($filteredBooks->count() >= $limit)
        <div id="infiniteScrollTrigger" style="margin-top: 2rem; width: 100%;">
            <div id="skeleton-desktop-wrapper" class="hide-on-mobile" style="display: none;">
                <x-book-card-grid :skeleton="true" :count="4" />
            </div>

            <div id="skeleton-mobile-wrapper" class="show-on-mobile" style="display: none;">
                <x-book-card-list :skeleton="true" :count="3" />
            </div>
        </div>
        @endif
    @endif
</div>


@endsection

@push('scripts')
<script>

let cursorDate = @json($initialCursor['date']);
let cursorId = @json($initialCursor['id']);
let isLoading = false;
let hasMore = {{ $filteredBooks->count() >= $limit ? 'true' : 'false' }};
const booksGridDesktop = document.getElementById('booksGridDesktop');
const booksGridMobile = document.getElementById('booksGridMobile');
const skeletonDesktop = document.getElementById('skeleton-desktop-wrapper');
const skeletonMobile = document.getElementById('skeleton-mobile-wrapper');

// Filters from PHP
const currentFilters = {
    search: @json($search),
    categories: @json($selectedCategories),
    availability: @json($availability),
    hall: @json($hallFilter)
};

let currentSort = @json($sortParam);

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
            const hSearch = document.querySelector('.search-input');
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
    const searchInput = document.querySelector('.search-input');
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
    const form = document.querySelector('.search-form');
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
        const response = await fetch(`/api/books?${params.toString()}`);
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
            <img src="${book.cover_image}" alt="${book.title}" loading="lazy" onerror="this.src='{{ asset('images/default-book-cover.jpg') }}';">
            <span class="book-badge badge-${status}">${status.charAt(0).toUpperCase() + status.slice(1)}</span>
        </div>
        <div class="book-info">
            <div class="book-category-tag">${book.category || 'General'}</div>
            <h3 class="book-title"><a href="/book?id=${book.id}">${book.title}</a></h3>
            <p class="book-author">By ${book.author || 'Unknown'}</p>
            ${getRatingHtml(book)}
            <div class="book-footer">
                <div class="owner-info">
                    <img src="${book.owner_avatar}" alt="${book.owner_name}" class="owner-avatar" onerror="this.src='{{ asset('images/avatars/default.jpg') }}';">
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
        <a href="/book?id=${book.id}" class="cover-link">
            <img src="${book.cover_image}" alt="${book.title}" class="book-cover-image" onerror="this.src='{{ asset('images/default-book-cover.jpg') }}';">
        </a>
        <div class="card-info-section">
            <a href="/book?id=${book.id}" class="book-info-link">
                <h3 class="card-title">${book.title}</h3>
                <p class="card-author">${book.author || 'Unknown'}</p>
                <p class="category-label">${book.category || 'General'}</p>
                ${ratingRowHtml}
            </a>
            <a href="/profile?id=${book.owner_id}" class="owner-link-area">
                <img src="${book.owner_avatar}" alt="${book.owner_name}" class="owner-avatar" onerror="this.src='{{ asset('images/avatars/default.jpg') }}';">
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
@endpush
