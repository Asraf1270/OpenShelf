@props([
    'books' => [],
    'id' => '',
    'gridClass' => 'book-grid',
    'showOwner' => true,
    'extraInfoKey' => null,
    'extraInfoLabel' => '',
    'skeleton' => false,
    'count' => 4,
])

<style>
    .book-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.25rem; width: 100%; max-width: 1100px; margin: 0 auto; padding: 0.5rem; }
    .book-card { background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; display: flex; flex-direction: column; transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1); height: 100%; }
    .book-card:hover { transform: translateY(-6px); box-shadow: 0 16px 36px rgba(0,0,0,0.1); }
    .book-card .cover-link { display: block; width: 100%; aspect-ratio: 2 / 3; overflow: hidden; position: relative; background: #f8fafc; }
    .book-cover-container { width: 100%; height: 100%; overflow: hidden; position: relative; }
    .book-cover-container img { width: 100%; height: 100%; object-fit: cover; display: block; transition: transform 0.3s ease; }
    .book-card:hover .book-cover-container img { transform: scale(1.05); }
    .book-badge { position: absolute; top: 0.75rem; right: 0.75rem; padding: 0.25rem 0.55rem; border-radius: 6px; color: #fff; font-size: 0.72rem; font-weight: 700; text-transform: capitalize; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
    .badge-available { background: #10b981; }
    .badge-borrowed { background: #ef4444; }
    .badge-reserved { background: #3b82f6; }
    .book-info { padding: 1rem; display: flex; flex-direction: column; gap: 0.5rem; flex: 1; }
    .book-info-link { text-decoration: none; color: inherit; display: flex; flex-direction: column; gap: 0.4rem; flex: 1; }
    .book-category-tag { font-size: 0.75rem; color: #4C9F8A; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; }
    .book-title { font-size: 1.05rem; font-weight: 700; margin: 0; color: #1f2937; line-height: 1.3; transition: color 0.2s ease; }
    .book-info-link:hover .book-title { color: #4C9F8A; }
    .book-author { color: #4b5563; font-size: 0.88rem; margin: 0; }
    .book-rating { display: flex; align-items: center; gap: 0.3rem; margin-top: 0.25rem; font-size: 0.8rem; }
    .book-rating .stars { display: flex; gap: 2px; color: #e2e8f0; }
    .book-rating .stars .fas, .book-rating .stars .fas.fa-star-half-alt { color: #f59e0b; }
    .book-rating .rating-val { font-weight: 700; color: #1f2937; margin-left: 0.25rem; }
    .book-rating .rating-count { color: #6b7280; font-weight: 400; font-size: 0.75rem; }
    .book-extra-info { font-size: 0.8rem; color: #4b5563; margin: 0.25rem 0 0 0; }
    .book-footer { margin-top: auto; padding-top: 0.75rem; border-top: 1px solid #f3f4f6; }
    .owner-link-area { display: flex; align-items: center; gap: 0.5rem; text-decoration: none; color: inherit; width: fit-content; }
    .owner-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; background: #f3f4f6; }
    .owner-name { font-size: 0.82rem; font-weight: 600; color: #4b5563; transition: color 0.2s ease; }
    .owner-details { display: flex; flex-direction: column; gap: 0.12rem; min-width: 0; }
    .owner-hall { font-size: 0.72rem; color: #6b7280; line-height: 1.2; }
    .owner-link-area:hover .owner-name { color: #4C9F8A; }

    /* Skeleton */
    .skeleton-card { border: 1px solid #e2e8f0; box-shadow: none; pointer-events: none; }
    .skeleton { background: #e2e8f0; }
    .skeleton-cover { aspect-ratio: 2 / 3; width: 100%; }
    .pulse { animation: pulse 1.5s infinite ease-in-out; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }

    /* Dark mode */
    [data-theme="dark"] .book-card { background: #1e293b; border-color: #334155; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
    [data-theme="dark"] .book-card:hover { box-shadow: 0 16px 36px rgba(0,0,0,0.4); }
    [data-theme="dark"] .book-title { color: #f8fafc; }
    [data-theme="dark"] .book-author { color: #94a3b8; }
    [data-theme="dark"] .book-category-tag { color: #4C9F8A; }
    [data-theme="dark"] .book-rating .stars { color: #475569; }
    [data-theme="dark"] .book-rating .rating-val { color: #cbd5e1; }
    [data-theme="dark"] .book-rating .rating-count { color: #64748b; }
    [data-theme="dark"] .owner-name { color: #cbd5e1; }
    [data-theme="dark"] .owner-hall { color: #64748b; }
    [data-theme="dark"] .book-footer { border-color: #334155; }
    [data-theme="dark"] .skeleton { background: #334155; }
    [data-theme="dark"] .skeleton-card { border-color: #334155; }
    [data-theme="dark"] .book-cover-container { background: #0f172a; }
</style>

<div class="{{ $gridClass }}" @if($id) id="{{ $id }}" @endif>
    @if ($skeleton)
        @for ($i = 0; $i < $count; $i++)
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
            @php($createdAt = $book['created_at'] ?? '')
            @php($rating = (float) ($book['rating'] ?? 0))
            @php($ratingCount = (int) ($book['rating_count'] ?? 0))
            @php($fullStars = floor($rating))
            @php($hasHalfStar = ($rating - $fullStars) >= 0.5)
            @php($emptyStars = 5 - $fullStars - ($hasHalfStar ? 1 : 0))
            @php($coverSrc = $book['cover_url'] ?? $fallbackCover)
            @php($avatarSrc = $book['owner_avatar_url'] ?? $fallbackAvatar)
            <div class="book-card" data-title="{{ strtolower($title) }}" data-author="{{ strtolower($author) }}" data-date="{{ $createdAt }}">
                <a href="/book/?id={{ $bookId }}" class="cover-link">
                    <div class="book-cover-container">
                        <img src="{{ $coverSrc }}" alt="{{ $title }}" loading="lazy" onerror="this.onerror=null; this.src='{{ $fallbackCover }}'">
                        <span class="book-badge badge-{{ $status }}">{{ ucfirst($status) }}</span>
                    </div>
                </a>
                <div class="book-info">
                    <a href="/book/?id={{ $bookId }}" class="book-info-link">
                        <div class="book-category-tag">{{ $category }}</div>
                        <h3 class="book-title">{{ $title }}</h3>
                        <p class="book-author">By {{ $author }}</p>
                        @if ($ratingCount > 0)
                            <div class="book-rating">
                                <div class="stars">
                                    @for ($i = 0; $i < $fullStars; $i++)
                                        <i class="fas fa-star"></i>
                                    @endfor
                                    @if ($hasHalfStar)
                                        <i class="fas fa-star-half-alt"></i>
                                    @endif
                                    @for ($i = 0; $i < $emptyStars; $i++)
                                        <i class="far fa-star"></i>
                                    @endfor
                                </div>
                                <span class="rating-val">{{ number_format($rating, 1) }}</span>
                                <span class="rating-count">({{ $ratingCount }})</span>
                            </div>
                        @endif
                        @if ($extraInfoKey && ! empty($book[$extraInfoKey]))
                            <p class="book-extra-info">{{ $extraInfoLabel }}: <strong>{{ $book[$extraInfoKey] }}</strong></p>
                        @endif
                    </a>
                    @if ($showOwner)
                        <div class="book-footer">
                            <a href="/profile/?id={{ $book['owner_id'] ?? '' }}" class="owner-link-area">
                                <img src="{{ $avatarSrc }}" alt="{{ $book['owner_name'] ?? 'Owner' }}" class="owner-avatar" onerror="this.onerror=null; this.src='{{ $fallbackAvatar }}'">
                                <div class="owner-details">
                                    <span class="owner-name">{{ $book['owner_name'] ?? 'Owner' }}</span>
                                    @php($ownerHall = $book['display_hall'] ?? ($book['owner_hall'] ?? 'N/A'))
                                    @if (! empty($ownerHall) && $ownerHall !== 'N/A')
                                        <span class="owner-hall">{{ $ownerHall }}</span>
                                    @endif
                                </div>
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
