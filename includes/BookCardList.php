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
                align-items: center; /* Center cover and content vertically */
                background: #ffffff;
                border-radius: 16px;
                padding: 12px; 
                position: relative;
                box-shadow: 0 4px 15px rgba(0,0,0,0.06);
                transition: transform 0.2s ease, box-shadow 0.2s ease;
                border: 1px solid #f0f0f0;
                text-decoration: none;
                color: inherit;
            }

            .book-card-list:hover {
                transform: translateY(-2px);
                box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            }

            /* LEFT: Book Cover */
            .book-card-list .card-cover-section {
                flex: 0 0 80px; /* Slightly smaller cover */
                height: 110px;
                margin-right: 12px;
            }

            .book-card-list .book-cover-image {
                width: 100%;
                height: 100%;
                object-fit: cover;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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
                -webkit-line-clamp: 2;
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
            .book-card-list .card-owner {
                display: flex;
                align-items: center;
                gap: 8px;
                margin-top: 8px;
                padding-top: 6px;
                border-top: 1px solid #f5f5f5;
            }

            .book-card-list .owner-avatar {
                width: 28px;
                height: 28px;
                border-radius: 50%;
                object-fit: cover;
                background: #eee;
            }

            .book-card-list .owner-details { display: flex; flex-direction: column; line-height: 1.1; }
            .book-card-list .owner-name { font-size: 0.8rem; font-weight: 700; color: #333; text-decoration: none; }
            .book-card-list .owner-hall { font-size: 0.7rem; color: #888; font-weight: 500; }

            /* Dark Mode */
            [data-theme="dark"] .book-card-list { background: #1e293b; border-color: #334155; }
            [data-theme="dark"] .card-title { color: #f8fafc; }
            [data-theme="dark"] .card-author { color: #94a3b8; }
            [data-theme="dark"] .category-label { color: #64748b; }
            [data-theme="dark"] .rating-value { color: #cbd5e1; }
            [data-theme="dark"] .owner-name { color: #f8fafc; }
            [data-theme="dark"] .owner-hall { color: #64748b; }
            [data-theme="dark"] .card-owner { border-top-color: #334155; }
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

            <!-- LEFT: Cover -->
            <div class="card-cover-section">
                <img src="<?php echo htmlspecialchars($coverImage); ?>" 
                     alt="<?php echo htmlspecialchars($title); ?>"
                     class="book-cover-image"
                     onerror="this.src='/assets/images/default-book-cover.jpg';">
            </div>

            <!-- RIGHT: Content -->
            <div class="card-info-section">
                <h3 class="card-title">
                    <a href="/book/?id=<?php echo htmlspecialchars($bookId); ?>" style="text-decoration: none; color: inherit;">
                        <?php echo htmlspecialchars($title); ?>
                    </a>
                </h3>
                <p class="card-author"><?php echo htmlspecialchars($author); ?></p>
                <p class="category-label"><?php echo htmlspecialchars($category); ?></p>
                
                <!-- Visual Rating -->
                <div class="rating-row">
                    <div class="stars-mini">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star <?php echo ($i <= round($rating)) ? 'active' : ''; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <?php if ($rating > 0): ?>
                        <span class="rating-value"><?php echo number_format($rating, 1); ?></span>
                    <?php endif; ?>
                </div>
                
                <!-- Owner Info -->
                <div class="card-owner">
                    <img src="<?php echo htmlspecialchars($ownerAvatar); ?>" 
                         alt="<?php echo htmlspecialchars($ownerName); ?>"
                         class="owner-avatar"
                         onerror="this.src='/assets/images/avatars/default.jpg';">
                    <div class="owner-details">
                        <a href="/profile/?id=<?php echo htmlspecialchars($ownerId); ?>" class="owner-name">
                            <?php echo htmlspecialchars($ownerName); ?>
                        </a>
                        <span class="owner-hall"><?php echo htmlspecialchars($displayHall); ?></span>
                    </div>
                </div>

                <?php if ($extraInfoKey && !empty($book[$extraInfoKey])): ?>
                    <div class="extra-info-row" style="margin-top: 8px; font-size: 0.8rem; color: #2C3E50; font-weight: 600; padding: 4px 8px; background: #f8fafc; border-radius: 6px; width: fit-content; border: 1px solid #e2e8f0;">
                        <?php echo htmlspecialchars($extraInfoLabel); ?>: <?php echo htmlspecialchars($book[$extraInfoKey]); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }
    echo '</div>';
}
