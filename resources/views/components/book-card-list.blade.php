@props([
    'books' => [],
    'id' => '',
    'listClass' => 'book-list',
    'showOwner' => true,
    'extraInfoKey' => null,
    'extraInfoLabel' => '',
    'skeleton' => false,
    'count' => 3,
])

<style>
    .book-list { display: flex; flex-direction: column; gap: 0.75rem; width: 100%; max-width: 800px; margin: 0 auto; padding: 0.5rem; }
    .book-card-list { display: flex; flex-direction: row; align-items: center; background: #ffffff; border-radius: 16px; padding: 12px; position: relative; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #f0f0f0; color: inherit; transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .book-card-list:hover { transform: translateY(-2px); box-shadow: 0 8px 25px rgba(0,0,0,0.1); }
    .book-card-list .cover-link { flex: 0 0 80px; height: 110px; margin-right: 12px; display: block; text-decoration: none; flex-shrink: 0; }
    .book-card-list .book-cover-image { width: 80px; height: 110px; object-fit: cover; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); display: block; }
    .book-card-list .card-info-section { flex: 1; display: flex; flex-direction: column; min-width: 0; }
    .book-card-list .book-info-link { display: flex; flex-direction: column; text-decoration: none; color: inherit; flex: 1; min-width: 0; }
    .book-card-list .book-info-link:hover .card-title { color: #4C9F8A; }
    .book-card-list .card-title { font-size: 1.05rem; font-weight: 700; color: #1a1a1a; margin: 0 0 2px 0; line-height: 1.2; word-wrap: break-word; }
    .book-card-list .card-author { font-size: 0.85rem; color: #666; margin: 0 0 4px 0; }
    .book-card-list .category-label { font-size: 0.8rem; color: #888; font-weight: 500; margin-bottom: 4px; }
    .book-card-list .rating-row { display: flex; align-items: center; gap: 6px; margin-bottom: auto; }
    .book-card-list .stars-mini { display: flex; gap: 2px; color: #e2e8f0; font-size: 0.7rem; }
    .book-card-list .stars-mini i.active { color: #f59e0b; }
    .book-card-list .rating-value { font-size: 0.75rem; font-weight: 700; color: #444; }
    .book-card-list .rating-count { font-size: 0.7rem; color: #888; font-weight: 500; }
    .book-card-list .owner-link-area { display: flex; align-items: center; gap: 8px; margin-top: 4px; padding: 4px 4px; border-top: 1px solid #f5f5f5; text-decoration: none; color: inherit; border-radius: 8px; transition: background 0.15s ease; }
    .book-card-list .owner-link-area:hover { background: rgba(0,0,0,0.03); }
    .book-card-list .owner-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; background: #eee; flex-shrink: 0; }
    .book-card-list .owner-details { display: flex; flex-direction: column; line-height: 1.1; }
    .book-card-list .owner-name { font-size: 0.8rem; font-weight: 700; color: #333; }
    .book-card-list .owner-hall { font-size: 0.7rem; color: #888; font-weight: 500; }
    .book-card-list .status-sign { position: absolute; top: 12px; right: 12px; width: 10px; height: 10px; border-radius: 50%; box-shadow: 0 0 0 3px #fff; }
    .book-card-list .status-available { background: #4ade80; }
    .book-card-list .status-borrowed { background: #f87171; }
    .book-card-list .status-reserved { background: #60a5fa; }
    /* Skeleton */
    .skeleton-card-list { border: 1px solid #e2e8f0; box-shadow: none; pointer-events: none; }
    .skeleton { background: #e2e8f0; }
    .pulse { animation: pulse 1.5s infinite ease-in-out; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    /* Dark mode */
    [data-theme="dark"] .book-card-list { background: #1e293b; border-color: #334155; }
    [data-theme="dark"] .book-card-list .card-title { color: #f8fafc; }
    [data-theme="dark"] .book-card-list .card-author { color: #94a3b8; }
    [data-theme="dark"] .book-card-list .category-label { color: #64748b; }
    [data-theme="dark"] .book-card-list .rating-value { color: #cbd5e1; }
    [data-theme="dark"] .book-card-list .rating-count { color: #64748b; }
    [data-theme="dark"] .book-card-list .owner-name { color: #f8fafc; }
    [data-theme="dark"] .book-card-list .owner-hall { color: #64748b; }
    [data-theme="dark"] .book-card-list .owner-link-area { border-top-color: #334155; }
    [data-theme="dark"] .book-card-list .owner-link-area:hover { background: rgba(255,255,255,0.05); }
    [data-theme="dark"] .book-card-list .status-sign { box-shadow: 0 0 0 3px #1e293b; }
    [data-theme="dark"] .skeleton { background: #334155; }
    [data-theme="dark"] .skeleton-card-list { border-color: #334155; background: #1e293b; }
</style>

<div class="{{ $listClass }}" @if($id) id="{{ $id }}" @endif>
    @if ($skeleton)
        @for ($i = 0; $i < $count; $i++)
            <div class="book-card-list skeleton-card-list">
                <div class="status-sign skeleton pulse" style="box-shadow: none;"></div>
                <div class="cover-link skeleton pulse" style="border-radius: 8px;"></div>
                <div class="card-info-section">
                    <div class="book-info-link" style="padding-top: 4px;">
                        <div class="skeleton pulse" style="width: 70%; height: 1.1rem; margin-bottom: 6px; border-radius: 4px;"></div>
                        <div class="skeleton pulse" style="width: 40%; height: 0.85rem; margin-bottom: 8px; border-radius: 4px;"></div>
                        <div class="skeleton pulse" style="width: 30%; height: 0.8rem; margin-bottom: 12px; border-radius: 4px;"></div>
                    </div>
                    <div class="owner-link-area" style="border-top-color: transparent;">
                        <div class="skeleton pulse" style="width: 28px; height: 28px; border-radius: 50%; flex-shrink: 0;"></div>
                        <div class="owner-details" style="width: 100%;">
                            <div class="skeleton pulse" style="width: 50%; height: 0.8rem; margin-bottom: 4px; border-radius: 4px;"></div>
                            <div class="skeleton pulse" style="width: 30%; height: 0.7rem; border-radius: 4px;"></div>
                        </div>
                    </div>
                </div>
            </div>
        @endfor
    @else
        @php($fallbackCover = asset('images/default-book-cover.jpg'))
        @php($fallbackAvatar = asset('images/avatars/default.jpg'))
        @foreach ($books as $book)
            @php($bookId = $book['id'] ?? ($book['book_id'] ?? ''))
            @php($title = $book['title'] ?? 'Untitled')
            @php($author = $book['author'] ?? 'Unknown Author')
            @php($category = $book['category'] ?? 'General')
            @php($status = $book['status'] ?? 'available')
            @php($rating = (float) ($book['rating'] ?? 0))
            @php($ratingCount = (int) ($book['rating_count'] ?? 0))
            @php($fullStars = floor($rating))
            @php($hasHalfStar = ($rating - $fullStars) >= 0.5)
            @php($emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0))
            <div class="book-card-list" data-book-id="{{ $bookId }}">
                <div class="status-sign status-{{ $status }}" title="{{ ucfirst($status) }}"></div>
                <a href="/book/?id={{ $bookId }}" class="cover-link">
                    <img src="{{ $book['cover_url'] ?? $fallbackCover }}" alt="{{ $title }}" class="book-cover-image" onerror="this.onerror=null; this.src='{{ $fallbackCover }}'">
                </a>
                <div class="card-info-section">
                    <a href="/book/?id={{ $bookId }}" class="book-info-link">
                        <h3 class="card-title">{{ $title }}</h3>
                        <p class="card-author">{{ $author }}</p>
                        <p class="category-label">{{ $category }}</p>
                        @if ($ratingCount > 0)
                            <div class="rating-row">
                                <div class="stars-mini">
                                    @for ($i = 0; $i < $fullStars; $i++)
                                        <i class="fas fa-star active"></i>
                                    @endfor
                                    @if ($hasHalfStar)
                                        <i class="fas fa-star-half-alt active"></i>
                                    @endif
                                    @for ($i = 0; $i < $emptyStars; $i++)
                                        <i class="far fa-star"></i>
                                    @endfor
                                </div>
                                <span class="rating-value">{{ number_format($rating, 1) }}</span>
                                <span class="rating-count">({{ $ratingCount }})</span>
                            </div>
                        @endif
                        @if ($extraInfoKey && ! empty($book[$extraInfoKey]))
                            <div class="extra-info-row" style="margin-top: 8px; font-size: 0.8rem; color: #2C3E50; font-weight: 600; padding: 4px 8px; background: #f8fafc; border-radius: 6px; width: fit-content; border: 1px solid #e2e8f0;">
                                {{ $extraInfoLabel }}: {{ $book[$extraInfoKey] }}
                            </div>
                        @endif
                    </a>
                    @if ($showOwner)
                        <a href="/profile/?id={{ $book['owner_id'] ?? '' }}" class="owner-link-area">
                            <img src="{{ $book['owner_avatar_url'] ?? $fallbackAvatar }}" alt="{{ $book['owner_name'] ?? 'Owner' }}" class="owner-avatar" onerror="this.onerror=null; this.src='{{ $fallbackAvatar }}'">
                            <div class="owner-details">
                                <span class="owner-name">{{ $book['owner_name'] ?? 'Owner' }}</span>
                                <span class="owner-hall">{{ $book['display_hall'] ?? 'N/A' }}</span>
                            </div>
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
