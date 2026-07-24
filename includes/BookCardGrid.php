<?php
/**
 * Reusable Book Card Grid Component
 * 
 * This component renders a grid of book cards using a consistent design
 * across different pages of the OpenShelf platform.
 * 
 * @param array $books Array of book data from database
 * @param array $options Configuration options:
 *   - 'id': ID for the grid container (default: '')
 *   - 'gridClass': CSS class for the grid container (default: 'book-grid')
 *   - 'showOwner': Whether to show the owner info footer (default: true)
 *   - 'extraInfoKey': Key in book array for additional info (e.g. 'borrower_name')
 *   - 'extraInfoLabel': Label for the extra info
 */
function renderBookCardGrid($books, $options = []) {
    if (empty($books)) {
        return;
    }
    
    $gridId = $options['id'] ?? '';
    $gridClass = $options['gridClass'] ?? 'book-grid';
    $showOwner = $options['showOwner'] ?? true;
    $extraInfoKey = $options['extraInfoKey'] ?? null;
    $extraInfoLabel = $options['extraInfoLabel'] ?? '';

    // Ensure CSS is included once
    static $cssIncluded = false;
    if (!$cssIncluded) {
        echo '<link rel="stylesheet" href="/assets/css/BookCardGrid.css">';
        $cssIncluded = true;
    }

    echo '<div class="' . htmlspecialchars($gridClass) . '" ' . ($gridId ? 'id="' . htmlspecialchars($gridId) . '"' : '') . '>';
    
    foreach ($books as $book) {
        // Standardize data
        $bookId = $book['id'] ?? ($book['book_id'] ?? '');
        $title = $book['title'] ?? 'Untitled';
        $author = $book['author'] ?? 'Unknown Author';
        $category = $book['category'] ?? 'General';
        $status = strtolower($book['status'] ?? 'available');
        $createdAt = $book['created_at'] ?? '';
        
        // Owner Info
        $ownerName = $book['owner_name'] ?? 'Unknown Owner';
        $ownerAvatar = !empty($book['owner_avatar']) && $book['owner_avatar'] !== 'default-avatar.jpg'
            ? '/uploads/profile/' . ltrim($book['owner_avatar'], '/')
            : '/assets/images/avatars/default.jpg';
            
        // Cover Image logic
        $coverImage = '/assets/images/default-book-cover.jpg';
        if (!empty($book['cover_image'])) {
            $rawCover = ltrim($book['cover_image'], '/');
            $baseDir = dirname(__DIR__) . '/uploads/book_cover/';
            
            // Strip thumb_ prefix if it exists to get the original image
            $originalFile = (strpos($rawCover, 'thumb_') === 0) ? substr($rawCover, 6) : $rawCover;
            
            if (file_exists($baseDir . $originalFile)) {
                $coverImage = '/uploads/book_cover/' . $originalFile;
            } elseif (file_exists($baseDir . $rawCover)) {
                $coverImage = '/uploads/book_cover/' . $rawCover;
            }
        }
        ?>
        <div class="book-card" 
             data-title="<?php echo htmlspecialchars(strtolower($title)); ?>" 
             data-author="<?php echo htmlspecialchars(strtolower($author)); ?>" 
             data-date="<?php echo htmlspecialchars($createdAt); ?>">
            
            <div class="book-cover-container">
                <img src="<?php echo htmlspecialchars($coverImage); ?>" 
                     alt="<?php echo htmlspecialchars($title); ?>"
                     loading="lazy"
                     onerror="this.src='/assets/images/default-book-cover.jpg';">
                <span class="book-badge badge-<?php echo $status; ?>">
                    <?php echo ucfirst($status); ?>
                </span>
            </div>
            
            <div class="book-info">
                <div class="book-category-tag">
                    <?php echo htmlspecialchars($category); ?>
                </div>
                <h3 class="book-title">
                    <a href="/book/?id=<?php echo htmlspecialchars($bookId); ?>">
                        <?php echo htmlspecialchars($title); ?>
                    </a>
                </h3>
                <p class="book-author">By <?php echo htmlspecialchars($author); ?></p>
                
                <?php if (!empty($book['rating_count']) && $book['rating_count'] > 0): 
                    $rating = $book['rating'] ?? 0;
                    $fullStars = floor($rating);
                    $hasHalfStar = ($rating - $fullStars) >= 0.5;
                    $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                ?>
                    <div class="book-rating" style="display: flex; align-items: center; gap: 0.2rem; margin-top: 0.35rem; font-size: 0.8rem; color: #f59e0b;">
                        <div class="stars" style="display: flex; gap: 1px;">
                            <?php for ($i = 0; $i < $fullStars; $i++): ?>
                                <i class="fas fa-star"></i>
                            <?php endfor; ?>
                            <?php if ($hasHalfStar): ?>
                                <i class="fas fa-star-half-alt"></i>
                            <?php endif; ?>
                            <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                                <i class="far fa-star"></i>
                            <?php endfor; ?>
                        </div>
                        <span style="font-weight: 700; margin-left: 0.25rem;"><?php echo number_format($rating, 1); ?></span>
                        <span style="color: var(--gray-500); font-weight: 400; font-size: 0.75rem;">(<?php echo $book['rating_count']; ?>)</span>
                    </div>
                <?php endif; ?>
                
                <?php if ($extraInfoKey && !empty($book[$extraInfoKey])): ?>
                    <p class="book-extra-info">
                        <?php echo htmlspecialchars($extraInfoLabel); ?>: 
                        <strong><?php echo htmlspecialchars($book[$extraInfoKey]); ?></strong>
                    </p>
                <?php endif; ?>

                <?php if ($showOwner): ?>
                    <div class="book-footer">
                        <div class="owner-info">
                            <img src="<?php echo htmlspecialchars($ownerAvatar); ?>" 
                                 alt="<?php echo htmlspecialchars($ownerName); ?>" 
                                 class="owner-avatar"
                                 onerror="this.src='/assets/images/avatars/default.jpg';">
                            <span class="owner-name"><?php echo htmlspecialchars($ownerName); ?></span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    
    echo '</div>';
}

/**
 * Render a skeleton loader for the Book Card Grid
 *
 * @param int $count Number of skeleton cards to render
 * @param array $options Configuration options
 */
function renderBookCardGridSkeleton($count = 4, $options = []) {
    $gridId = $options['id'] ?? '';
    $gridClass = $options['gridClass'] ?? 'book-grid';

    // Ensure CSS is included once
    static $skeletonCssIncluded = false;
    if (!$skeletonCssIncluded) {
        ?>
        <style>
            .skeleton-card { border: 1px solid var(--gray-200, #e2e8f0); box-shadow: none; pointer-events: none; }
            .skeleton { background: #e2e8f0; }
            .pulse { animation: pulse 1.5s infinite ease-in-out; }
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
            [data-theme="dark"] .skeleton { background: #334155; }
            [data-theme="dark"] .skeleton-card { border-color: #334155; }
            .skeleton-cover { aspect-ratio: 2 / 3; width: 100%; border-radius: 8px 8px 0 0; }
        </style>
        <?php
        $skeletonCssIncluded = true;
    }

    echo '<div class="' . htmlspecialchars($gridClass) . '" ' . ($gridId ? 'id="' . htmlspecialchars($gridId) . '"' : '') . '>';
    
    for ($i = 0; $i < $count; $i++) {
        ?>
        <div class="book-card skeleton-card">
            <div class="book-cover-container skeleton-cover skeleton pulse"></div>
            
            <div class="book-info">
                <div class="skeleton pulse" style="width: 40%; height: 1rem; margin-bottom: 0.5rem; border-radius: 4px;"></div>
                <div class="skeleton pulse" style="width: 85%; height: 1.25rem; margin-bottom: 0.5rem; border-radius: 4px;"></div>
                <div class="skeleton pulse" style="width: 60%; height: 1rem; margin-bottom: 1rem; border-radius: 4px;"></div>
                
                <div class="book-footer">
                    <div class="owner-info" style="gap: 0.5rem; width: 100%;">
                        <div class="skeleton pulse" style="width: 28px; height: 28px; border-radius: 50%;"></div>
                        <div class="skeleton pulse" style="width: 50%; height: 1rem; border-radius: 4px;"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    echo '</div>';
}
