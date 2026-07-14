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
    .book-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1rem; width: 100%; max-width: 1100px; margin: 0 auto; padding: 0.5rem; }
    .book-card { background: #fff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.06); border: 1px solid #f0f0f0; display: flex; flex-direction: column; }
    .book-cover-container { position: relative; aspect-ratio: 2 / 3; overflow: hidden; background: #f8fafc; }
    .book-cover-container img { width: 100%; height: 100%; object-fit: cover; display: block; }
    .book-badge { position: absolute; top: 0.75rem; left: 0.75rem; padding: 0.35rem 0.6rem; border-radius: 999px; color: #fff; font-size: 0.75rem; font-weight: 700; text-transform: capitalize; }
    .badge-available { background: #4ade80; }
    .badge-borrowed { background: #f87171; }
    .badge-reserved { background: #60a5fa; }
    .book-info { padding: 1rem; display: flex; flex-direction: column; gap: 0.4rem; flex: 1; }
    .book-category-tag { font-size: 0.75rem; color: #2C3E50; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; }
    .book-title { font-size: 1rem; font-weight: 700; margin: 0; }
    .book-title a { color: #111827; text-decoration: none; }
    .book-author { color: #6b7280; font-size: 0.9rem; }
    .book-rating { display: flex; align-items: center; gap: 0.2rem; margin-top: 0.25rem; font-size: 0.8rem; color: #f59e0b; }
    .book-footer { margin-top: auto; padding-top: 0.75rem; }
    .owner-info { display: flex; align-items: center; gap: 0.5rem; }
    .owner-avatar { width: 28px; height: 28px; border-radius: 50%; object-fit: cover; }
    .owner-name { font-size: 0.85rem; font-weight: 600; color: #374151; }
    .book-extra-info { font-size: 0.8rem; color: #4b5563; }
    .skeleton-card { border: 1px solid #e2e8f0; box-shadow: none; pointer-events: none; }
    .skeleton { background: #e2e8f0; }
    .pulse { animation: pulse 1.5s infinite ease-in-out; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
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
            <div class="book-card" data-title="{{ strtolower($title) }}" data-author="{{ strtolower($author) }}" data-date="{{ $createdAt }}">
                <div class="book-cover-container">
                    <img src="{{ $book['cover_url'] ?? asset('images/default-book-cover.jpg') }}" alt="{{ $title }}" loading="lazy">
                    <span class="book-badge badge-{{ $status }}">{{ ucfirst($status) }}</span>
                </div>
                <div class="book-info">
                    <div class="book-category-tag">{{ $category }}</div>
                    <h3 class="book-title"><a href="/book/?id={{ $bookId }}">{{ $title }}</a></h3>
                    <p class="book-author">By {{ $author }}</p>
                    @if ($ratingCount > 0)
                        <div class="book-rating">
                            <div class="stars" style="display: flex; gap: 1px;">
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
                            <span style="font-weight: 700; margin-left: 0.25rem;">{{ number_format($rating, 1) }}</span>
                            <span style="color: var(--gray-500); font-weight: 400; font-size: 0.75rem;">({{ $ratingCount }})</span>
                        </div>
                    @endif
                    @if ($extraInfoKey && ! empty($book[$extraInfoKey]))
                        <p class="book-extra-info">{{ $extraInfoLabel }}: <strong>{{ $book[$extraInfoKey] }}</strong></p>
                    @endif
                    @if ($showOwner)
                        <div class="book-footer">
                            <div class="owner-info">
                                <img src="{{ $book['owner_avatar_url'] ?? asset('images/avatars/default.jpg') }}" alt="{{ $book['owner_name'] ?? 'Owner' }}" class="owner-avatar">
                                <span class="owner-name">{{ $book['owner_name'] ?? 'Owner' }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endforeach
    @endif
</div>
