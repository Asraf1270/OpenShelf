<?php
/**
 * Reusable Book Card List Component (Self-Contained)
 * 
 * This component renders a horizontal card layout precisely matching
 * the structure and style of planning_sketch/BookCardList.svg.
 * All styles are inlined to ensure perfect loading and portability.
 */
function renderBookCardList($books, $options = []) {
    if (empty($books)) {
        return;
    }
    
    $listId = $options['id'] ?? '';
    $listClass = $options['listClass'] ?? 'book-list';
    $showOwner = $options['showOwner'] ?? true;
    $extraInfoKey = $options['extraInfoKey'] ?? null;
    $extraInfoLabel = $options['extraInfoLabel'] ?? '';

    // Inlined Component Styles
    static $listStylesRendered = false;
    if (!$listStylesRendered) {
        ?>
        <style>
            .book-list {
                display: flex;
                flex-direction: column;
                gap: 0.75rem; /* Reduced gap between cards */
                width: 100%;
                max-width: 800px;
                margin: 0 auto;
                padding: 0.5rem; /* Reduced container padding */
            }

            .book-card-list {
                display: flex;
                flex-direction: row;
                align-items: center;
                background: #ffffff;
                border-radius: 16px;
                padding: 12px;
                position: relative;
                box-shadow: 0 4px 15px rgba(0,0,0,0.06);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                border: 1px solid #f0f0f0;
                color: inherit;
            }

            /* Cover clickable link */
            .book-card-list .cover-link {
                flex: 0 0 80px;
                height: 110px;
                margin-right: 12px;
                display: block;
                text-decoration: none;
                flex-shrink: 0;
            }

            /* Book info clickable link (title, author, category, rating) */
            .book-card-list .book-info-link {
                display: flex;
                flex-direction: column;
                text-decoration: none;
                color: inherit;
                flex: 1;
                min-width: 0;
            }

            .book-card-list .book-info-link:hover .card-title { color: var(--primary, #2C3E50); }

            /* Owner clickable area */
            .book-card-list .owner-link-area {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-top: 8px;
                padding: 6px 4px;
                padding-top: 6px;
                border-top: 1px solid #f5f5f5;
                text-decoration: none;
                color: inherit;
                border-radius: 8px;
                transition: background 0.15s ease;
            }

            .book-card-list .owner-link-area:hover {
                background: rgba(0,0,0,0.03);
            }

            .book-card-list:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            }

            /* LEFT: Book Cover */
            .book-card-list .book-cover-image {
                width: 80px;
                height: 110px;
                object-fit: cover;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                display: block;
            }

            /* RIGHT: Content */
            .book-card-list .card-info-section {
                flex: 1;
                display: flex;
                flex-direction: column;
                min-width: 0;
            }

            .book-card-list .card-title {
                font-size: 1.05rem;
                font-weight: 700;
                color: #1a1a1a;
                margin: 0 0 2px 0;
                line-height: 1.2;
                padding-right: 30px; /* Space for the status dot */
                word-wrap: break-word;
                display: -webkit-box;
                --webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .book-card-list .card-author {
                font-size: 0.85rem;
                color: #666;
                margin: 0 0 4px 0;
            }

            .book-card-list .category-label {
                font-size: 0.8rem;
                color: #888;
                font-weight: 500;
                margin-bottom: 4px;
            }

            .book-card-list .rating-row {
                display: flex;
                align-items: center;
                gap: 6px;
                margin-bottom: auto;
            }

            .book-card-list .stars-mini {
                display: flex;
                gap: 2px;
                color: #e2e8f0;
                font-size: 0.7rem;
            }

            .book-card-list .stars-mini i.active { color: #f59e0b; }
            .book-card-list .rating-value { font-size: 0.75rem; font-weight: 700; color: #444; }
            .book-card-list .rating-count { font-size: 0.7rem; color: #888; font-weight: 500; }

            /* Minimalist Status Sign (Top Right) */
            .book-card-list .status-sign {
                position: absolute;
                top: 12px;
                right: 12px;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                box-shadow: 0 0 0 3px #fff;
            }

            .book-card-list .status-available { background: #4ade80; }
            .book-card-list .status-borrowed { background: #f87171; }
            .book-card-list .status-reserved { background: #60a5fa; }

            /* BOTTOM: Owner Section */
            .book-card-list .owner-avatar {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                object-fit: cover;
                background: #eee;
                flex-shrink: 0;
            }

            .book-card-list .owner-details { display: flex; flex-direction: column; line-height: 1.1; }
            .book-card-list .owner-name { font-size: 0.8rem; font-weight: 700; color: #333; }
            .book-card-list .owner-hall { font-size: 0.7rem; color: #888; font-weight: 500; }

            /* Dark Mode */
            [data-theme="dark"] .book-card-list { background: #1e293b; border-color: #334155; }
            [data-theme="dark"] .book-info-link .card-title { color: #f8fafc; }
            [data-theme="dark"] .book-info-link .card-author { color: #94a3b8; }
            [data-theme="dark"] .book-info-link .category-label { color: #64748b; }
            [data-theme="dark"] .rating-value { color: #cbd5e1; }
            [data-theme="dark"] .rating-count { color: #64748b; }
            [data-theme="dark"] .owner-name { color: #f8fafc; }
            [data-theme="dark"] .owner-hall { color: #64748b; }
            [data-theme="dark"] .owner-link-area { border-top-color: #334155; }
            [data-theme="dark"] .owner-link-area:hover { background: rgba(255,255,255,0.05); }
            [data-theme="dark"] .status-sign { box-shadow: 0 0 0 3px #1e293b; }
        </style>
        <?php
        $listStylesRendered = true;
    }

    echo '<div class="' . htmlspecialchars($listClass) . '" ' . ($listId ? 'id="' . htmlspecialchars($listId) . '"' : '') . '>';
    
    foreach ($books as $book) {
        $bookId = $book['id'] ?? ($book['book_id'] ?? '');
        $title = $book['title'] ?? 'Untitled';
        $author = $book['author'] ?? 'Unknown Author';
        $category = $book['category'] ?? 'General';
        $status = strtolower($book['status'] ?? 'available');
        $rating = $book['rating'] ?? 0;
        
        $ownerId = $book['owner_id'] ?? '';
        $ownerName = $book['owner_name'] ?? 'Owner';
        $ownerAvatar = !empty($book['owner_avatar']) && $book['owner_avatar'] !== 'default-avatar.jpg'
            ? '/uploads/profile/' . ltrim($book['owner_avatar'], '/')
            : '/assets/images/avatars/default.jpg';
        
        $rawHall = $book['owner_hall'] ?? ($book['hall'] ?? '');
        $displayHall = function_exists('getHallName') ? getHallName($rawHall) : $rawHall;
            
        $coverImage = '/assets/images/default-book-cover.jpg';
        if (!empty($book['cover_image'])) {
            $rawCover = ltrim($book['cover_image'], '/');
            $baseDir = dirname(__DIR__) . '/uploads/book_cover/';
            $originalFile = (strpos($rawCover, 'thumb_') === 0) ? substr($rawCover, 6) : $rawCover;
            if (file_exists($baseDir . $originalFile)) {
                $coverImage = '/uploads/book_cover/' . $originalFile;
            } elseif (file_exists($baseDir . $rawCover)) {
                $coverImage = '/uploads/book_cover/' . $rawCover;
            }
        }
        ?>
        <div class="book-card-list" data-book-id="<?php echo htmlspecialchars($bookId); ?>">
            <!-- Minimalist Status Sign -->
            <div class="status-sign status-<?php echo $status; ?>" title="<?php echo ucfirst($status); ?>"></div>

            <!-- LEFT: Cover (clickable → book page) -->
            <a href="/book/?id=<?php echo htmlspecialchars($bookId); ?>" class="cover-link">
                <img src="<?php echo htmlspecialchars($coverImage); ?>" 
                     alt="<?php echo htmlspecialchars($title); ?>"
                     class="book-cover-image"
                     onerror="this.src='/assets/images/default-book-cover.jpg';">
            </a>

            <!-- RIGHT: Content -->
            <div class="card-info-section">
                <!-- Book info (clickable → book page) -->
                <a href="/book/?id=<?php echo htmlspecialchars($bookId); ?>" class="book-info-link">
                    <h3 class="card-title"><?php echo htmlspecialchars($title); ?></h3>
                    <p class="card-author"><?php echo htmlspecialchars($author); ?></p>
                    <p class="category-label"><?php echo htmlspecialchars($category); ?></p>

                    <!-- Visual Rating -->
                    <?php 
                    $ratingCount = $book['rating_count'] ?? 0;
                    if ($ratingCount > 0): 
                        $fullStars = floor($rating);
                        $hasHalfStar = ($rating - $fullStars) >= 0.5;
                        $emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0);
                    ?>
                        <div class="rating-row">
                            <div class="stars-mini">
                                <?php for ($i = 0; $i < $fullStars; $i++): ?>
                                    <i class="fas fa-star active"></i>
                                <?php endfor; ?>
                                <?php if ($hasHalfStar): ?>
                                    <i class="fas fa-star-half-alt active"></i>
                                <?php endif; ?>
                                <?php for ($i = 0; $i < $emptyStars; $i++): ?>
                                    <i class="far fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                            <span class="rating-count">(<?php echo $ratingCount; ?>)</span>
                        </div>
                    <?php endif; ?>

                    <?php if ($extraInfoKey && !empty($book[$extraInfoKey])): ?>
                        <div class="extra-info-row" style="margin-top: 8px; font-size: 0.8rem; color: #2C3E50; font-weight: 600; padding: 4px 8px; background: #f8fafc; border-radius: 6px; width: fit-content; border: 1px solid #e2e8f0;">
                            <?php echo htmlspecialchars($extraInfoLabel); ?>: <?php echo htmlspecialchars($book[$extraInfoKey]); ?>
                        </div>
                    <?php endif; ?>
                </a>

                <!-- Owner info (clickable → owner profile) -->
                <a href="/profile/?id=<?php echo htmlspecialchars($ownerId); ?>" class="owner-link-area">
                    <img src="<?php echo htmlspecialchars($ownerAvatar); ?>" 
                         alt="<?php echo htmlspecialchars($ownerName); ?>"
                         class="owner-avatar"
                         onerror="this.src='/assets/images/avatars/default.jpg';">
                    <div class="owner-details">
                        <span class="owner-name"><?php echo htmlspecialchars($ownerName); ?></span>
                        <span class="owner-hall"><?php echo htmlspecialchars($displayHall); ?></span>
                    </div>
                </a>
            </div>
        </div>
        <?php
    }
    echo '</div>';
}

/**
 * Render a skeleton loader for the Book Card List
 *
 * @param int $count Number of skeleton cards to render
 * @param array $options Configuration options
 */
function renderBookCardListSkeleton($count = 3, $options = []) {
    $listId = $options['id'] ?? '';
    $listClass = $options['listClass'] ?? 'book-list';

    // Ensure CSS is included once
    static $skeletonCssIncluded = false;
    if (!$skeletonCssIncluded) {
        ?>
        <style>
            .skeleton-card-list { border: 1px solid var(--gray-200, #e2e8f0); box-shadow: none; pointer-events: none; }
            .skeleton { background: #e2e8f0; }
            .pulse { animation: pulse 1.5s infinite ease-in-out; }
            @keyframes pulse {
                0% { opacity: 1; }
                50% { opacity: 0.5; }
                100% { opacity: 1; }
            }
            [data-theme="dark"] .skeleton { background: #334155; }
            [data-theme="dark"] .skeleton-card-list { border-color: #334155; background: #1e293b; }
            
            /* Add some base layout identical to the real card for skeletons */
            .skeleton-card-list.book-card-list .book-cover-image { background: #e2e8f0; }
            [data-theme="dark"] .skeleton-card-list.book-card-list .book-cover-image { background: #334155; }
        </style>
        <?php
        $skeletonCssIncluded = true;
    }

    echo '<div class="' . htmlspecialchars($listClass) . '" ' . ($listId ? 'id="' . htmlspecialchars($listId) . '"' : '') . '>';
    
    for ($i = 0; $i < $count; $i++) {
        ?>
        <div class="book-card-list skeleton-card-list">
            <!-- Status Sign Skeleton -->
            <div class="status-sign skeleton pulse" style="box-shadow: none;"></div>

            <!-- Cover Skeleton -->
            <div class="cover-link skeleton pulse" style="border-radius: 8px;"></div>

            <!-- Content Skeleton -->
            <div class="card-info-section">
                <div class="book-info-link" style="padding-top: 4px;">
                    <div class="skeleton pulse" style="width: 70%; height: 1.1rem; margin-bottom: 6px; border-radius: 4px;"></div>
                    <div class="skeleton pulse" style="width: 40%; height: 0.85rem; margin-bottom: 8px; border-radius: 4px;"></div>
                    <div class="skeleton pulse" style="width: 30%; height: 0.8rem; margin-bottom: 12px; border-radius: 4px;"></div>
                </div>

                <!-- Owner Skeleton -->
                <div class="owner-link-area" style="border-top-color: transparent;">
                    <div class="skeleton pulse" style="width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;"></div>
                    <div class="owner-details" style="width: 100%;">
                        <div class="skeleton pulse" style="width: 50%; height: 0.8rem; margin-bottom: 4px; border-radius: 4px;"></div>
                        <div class="skeleton pulse" style="width: 30%; height: 0.7rem; border-radius: 4px;"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    echo '</div>';
}

