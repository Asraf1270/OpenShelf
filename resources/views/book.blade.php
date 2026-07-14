@extends('layouts.app')

@section('content')
<div class="book-detail">

            
            @if ($borrowMessage)
                <div class="alert alert-success" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius-md);">{{ $borrowMessage }}</div>
            @endif
            @if ($borrowError)
                <div class="alert alert-danger" style="margin-bottom: 2rem; padding: 1rem; border-radius: var(--radius-md);">{{ $borrowError }}</div>
            @endif
            
            <!-- Immersive Layout Centered Content Wrapper -->
            <div class="book-content-wrapper">
                
                <!-- Centered Flat Book Cover with Minimal Status Badge -->
                <div class="detail-cover-container">
                    <div class="detail-cover-wrapper">
                        <img src="{{ $coverImage }}" 
                             alt="{{ $book->title }}"
                             class="detail-cover-flat"
                             onerror="this.src='{{ asset('images/default-book-cover.jpg') }}'">
                        
                        <!-- Minimal Availability Badge staying on top of the book cover -->
                        <span class="status-badge-minimal {{ $book->status }}">
                            <i class="fas fa-circle"></i>
                            {{ ucfirst($book->status) }}
                        </span>
                    </div>
                </div>
                
                <!-- Title & Author -->
                <div class="book-header-section">
                    <h1 class="detail-book-title">{{ $book->title }}</h1>
                    <div class="detail-book-author">by {{ $book->author }}</div>
                </div>
                
                <!-- Meta Grid -->
                <div class="meta-grid">
                    <div class="meta-item">
                        <div class="meta-icon"><i class="fas fa-tag"></i></div>
                        <span class="meta-label">Category</span>
                        <span class="meta-value">{{ $book->category ?? 'General' }}</span>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="fas fa-star"></i></div>
                        <span class="meta-label">Rating</span>
                        <span class="meta-value">{{ $avgRating }} <span style="font-weight:400;opacity:0.6;font-size:0.8rem">({{ $ratingCount }})</span></span>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="fas fa-calendar"></i></div>
                        <span class="meta-label">Added</span>
                        <span class="meta-value">{{ $book->created_at?->format('M j, Y') }}</span>
                    </div>
                    <div class="meta-item">
                        <div class="meta-icon"><i class="fas fa-eye"></i></div>
                        <span class="meta-label">Views</span>
                        <span class="meta-value">{{ number_format($book->views ?? 0) }}</span>
                    </div>
                </div>

                <!-- 1. Owner Profile Section -->
                <div class="owner-section">
                    <a href="/profile?id={{ $book->owner_id }}" class="detail-owner-card">
                        <div class="detail-owner-avatar-container">
                            <img src="{{ $owner?->profile_image_url ?? asset('images/avatars/default.jpg') }}" 
                                 class="detail-owner-avatar-large" 
                                 alt="{{ $owner?->name ?? 'Owner' }}"
                                 onerror="this.src='{{ asset('images/avatars/default.jpg') }}'">
                        </div>
                        <div style="flex:1">
                            <div class="detail-owner-name">{{ $owner?->name ?? 'Unknown Owner' }}</div>
                            <div class="detail-owner-details">
                                <span><i class="fas fa-door-open"></i> {{ $owner?->room_number ?? 'N/A' }}</span>
                                <span><i class="fas fa-building"></i> {{ $owner?->department ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div style="color: var(--primary); opacity: 0.5;">
                            <i class="fas fa-chevron-right"></i>
                        </div>
                    </a>
                </div>

                <!-- 2. Action Card -->
                <div class="action-section borrow-card">
                    <div class="borrow-card-header">
                        @if ($isOwner)
                            <span class="card-status-label"><i class="fas fa-user-cog"></i> Listing Owner</span>
                        @elseif ($book->status === 'available')
                            <span class="card-status-label available"><i class="fas fa-check-circle"></i> Available to Borrow</span>
                        @else
                            <span class="card-status-label unavailable"><i class="fas fa-info-circle"></i> Currently Unavailable</span>
                        @endif
                    </div>
                    <div class="action-group">
                        @if ($isOwner)
                            <a href="{{ route('books.edit', ['id' => $book->id]) }}" class="btn btn-primary">
                                <i class="fas fa-edit"></i> Edit Listing
                            </a>
                            <button onclick="shareBook()" class="btn btn-outline">
                                <i class="fas fa-share-alt"></i> Share Listing
                            </button>
                        @elseif ($canBorrow)
                            <button onclick="showBorrowModal()" class="btn btn-primary">
                                <i class="fas fa-handshake"></i> Request to Borrow
                            </button>
                            @if ($whatsappLink)
                                <a href="{{ $whatsappLink }}" target="_blank" class="btn btn-whatsapp">
                                    <i class="fab fa-whatsapp"></i> Chat with Owner
                                </a>
                            @endif
                        @elseif ($hasRequested)
                            <button class="btn btn-secondary" disabled style="background:#f1f5f9; color:#94a3b8; border:1px solid #e2e8f0; cursor: not-allowed; flex:1;">
                                <i class="fas fa-clock"></i> Request Pending
                            </button>
                            <a href="/requests/" class="btn btn-outline">Manage Requests</a>
                        @elseif (!$isLoggedIn)
                            <a href="/login?redirect=/book?id={{ $book->id }}" class="btn btn-primary">
                                Join to Borrow
                            </a>
                        @else
                            <button class="btn btn-secondary" disabled style="cursor: not-allowed; flex:1;">
                                <i class="fas fa-lock"></i> Currently Unavailable
                            </button>
                        @endif

                        @if ($isLoggedIn && !$isOwner && $isUnavailable)
                            <!-- Wishlist toggle button (only shown when book is unavailable) -->
                            <button
                                id="wishlistBtn"
                                class="btn-wishlist {{ $isWishlisted ? 'wishlisted' : '' }}"
                                onclick="toggleWishlist()"
                                title="{{ $isWishlisted ? 'Remove from wishlist' : 'Add to wishlist' }}"
                                aria-label="Wishlist"
                            >
                                <span class="wishlist-tooltip">
                                    {{ $isWishlisted ? 'Remove from wishlist' : 'Notify me when available' }}
                                </span>
                                <i class="{{ $isWishlisted ? 'fas' : 'far' }} fa-heart"></i>
                                @if ($wishlistCount > 0)
                                    <span class="wishlist-count" id="wishlistCount">{{ $wishlistCount }}</span>
                                @else
                                    <span class="wishlist-count" id="wishlistCount" style="display:none">0</span>
                                @endif
                            </button>
                        @endif
                    </div>
                </div>

                <!-- 3. The Tab-Container -->
                <div class="tabs-container">
                    <div class="tabs">
                        <button class="tab active" data-tab="description" onclick="switchTab('description')">Description</button>
                        <button class="tab" data-tab="details" onclick="switchTab('details')">Details</button>
                        <button class="tab" data-tab="reviews" onclick="switchTab('reviews')">Reviews <span style="font-size:0.85rem;opacity:0.6">({{ $reviews->count() }})</span></button>
                        <button class="tab" data-tab="comments" onclick="switchTab('comments')">Comments <span style="font-size:0.85rem;opacity:0.6">({{ $comments->count() }})</span></button>
                        <button class="tab" data-tab="history" onclick="switchTab('history')">History</button>
                    </div>
                    
                    <!-- Description -->
                    <div id="description-tab" class="tab-content active">
                        <p style="font-size:1.05rem;line-height:1.75;color:var(--text-main);opacity:0.95;white-space:pre-line">
                            {!! nl2br(e($book->description ?? 'No description available.')) !!}
                        </p>
                    </div>
                    
                    <!-- Details -->
                    <div id="details-tab" class="tab-content">
                        <div class="detail-grid">
                            <div class="detail-item"><label>ISBN</label><span>{{ $book->isbn ?? 'N/A' }}</span></div>
                            <div class="detail-item"><label>Publisher</label><span>{{ $book->publisher ?? 'N/A' }}</span></div>
                            <div class="detail-item"><label>Year</label><span>{{ $book->publication_year ?? 'N/A' }}</span></div>
                            <div class="detail-item"><label>Pages</label><span>{{ $book->pages ?? 'N/A' }}</span></div>
                            <div class="detail-item"><label>Language</label><span>{{ $book->language ?? 'English' }}</span></div>
                            <div class="detail-item"><label>Condition</label><span>{{ $book->condition ?? 'Good' }}</span></div>
                        </div>
                    </div>
                    
                    <!-- Reviews -->
                    <div id="reviews-tab" class="tab-content">
                        @if ($isLoggedIn && !$isOwner)
                            <div class="form-dark">
                                <h4 style="margin-bottom:1rem;font-weight:700">Write a Review</h4>
                                <div class="rating-stars" id="ratingStarsInput" style="margin-bottom:1.5rem">
                                    <i class="far fa-star" data-rating="1"></i>
                                    <i class="far fa-star" data-rating="2"></i>
                                    <i class="far fa-star" data-rating="3"></i>
                                    <i class="far fa-star" data-rating="4"></i>
                                    <i class="far fa-star" data-rating="5"></i>
                                </div>
                                <textarea id="reviewText" class="form-control" rows="4" placeholder="What did you think of the book?"></textarea>
                                <button onclick="submitReview()" class="btn btn-primary" style="margin-top:1.5rem;max-width:220px">Submit Review</button>
                            </div>
                        @endif
                        
                        @if ($reviews->isEmpty())
                            <div class="empty-state"><i class="far fa-star"></i><p>No reviews yet. Be the first to share your thoughts!</p></div>
                        @else
                        @foreach ($reviews as $review)
                            @php($reviewer = $participants->get($review['user_id'] ?? ''))
                            <div class="entry-card">
                                <img src="{{ $reviewer?->profile_image_url ?? asset('images/avatars/default.jpg') }}" class="entry-avatar">
                                <div class="entry-content">
                                    <div class="entry-header">
                                        <div>
                                            <div class="entry-name">{{ $review['user_name'] ?? 'User' }}</div>
                                            <div class="rating-display">
                                                @for ($i = 1; $i <= 5; $i++)
                                                    <i class="{{ $i <= ($review['rating'] ?? 0) ? 'fas fa-star' : 'far fa-star' }}"></i>
                                                @endfor
                                            </div>
                                        </div>
                                        <div class="entry-date">{{ $formatDate($review['created_at'] ?? null) }}</div>
                                    </div>
                                    <p class="entry-text">{!! nl2br(e($review['review_text'] ?? '')) !!}</p>
                                </div>
                            </div>
                        @endforeach
                        @endif
                    </div>
                    
                    <!-- Comments -->
                    <div id="comments-tab" class="tab-content">
                        @if ($isLoggedIn)
                            <div class="form-dark">
                                <h4 style="margin-bottom:1rem;font-weight:700">Add a Comment</h4>
                                <textarea id="commentText" class="form-control" rows="3" placeholder="Ask a question or share a thought..."></textarea>
                                <button onclick="submitComment()" class="btn btn-primary" style="margin-top:1.5rem;max-width:180px">Post Comment</button>
                            </div>
                        @endif
                        
                        @if ($comments->isEmpty())
                            <div class="empty-state"><i class="far fa-comments"></i><p>No comments yet. Start the conversation!</p></div>
                        @else
                        @foreach ($comments as $comment)
                            @php($commenter = $participants->get($comment['user_id'] ?? ''))
                            @php($userLiked = $isLoggedIn && in_array($currentUserId, $comment['likes'] ?? []))
                            <div class="entry-card">
                                <img src="{{ $commenter?->profile_image_url ?? asset('images/avatars/default.jpg') }}" class="entry-avatar">
                                <div class="entry-content">
                                    <div class="entry-header">
                                        <span class="entry-name">{{ $comment['user_name'] ?? 'User' }}</span>
                                        <span class="entry-date">{{ $formatDate($comment['created_at'] ?? null) }}</span>
                                    </div>
                                    <p class="entry-text" style="margin-bottom:1rem">{!! nl2br(e($comment['comment_text'] ?? '')) !!}</p>
                                    <button onclick="likeComment('{{ $comment['id'] ?? '' }}', this)" class="like-btn {{ $userLiked ? 'active' : '' }}" style="background:var(--bg);padding:0.5rem 1rem;border-radius:10px;display:inline-flex;align-items:center;gap:0.5rem;border:none;cursor:pointer;transition:all 0.2s">
                                        <i class="fas fa-heart"></i> <span class="like-count" style="font-weight:600">{{ count($comment['likes'] ?? []) }}</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                        @endif
                    </div>
                    
                    <!-- History -->
                    <div id="history-tab" class="tab-content">
                        @if ($borrowRequests->isEmpty())
                            <div class="empty-state"><i class="fas fa-history"></i><p>No borrow history yet.</p></div>
                        @else
                        @foreach ($borrowRequests as $borrowRequest)
                            @php
                                $statusColor = $borrowRequest->status === 'approved'
                                    ? '#10b981'
                                    : ($borrowRequest->status === 'pending' ? '#f59e0b' : '#ef4444');
                                $statusGlow = $borrowRequest->status === 'approved'
                                    ? 'rgba(16,185,129,0.4)'
                                    : ($borrowRequest->status === 'pending' ? 'rgba(245,158,11,0.4)' : 'rgba(239,68,68,0.4)');
                            @endphp
                            <div class="entry-card">
                                <div style="width:10px;height:10px;border-radius:50%;margin-top:0.6rem;background:{{ $statusColor }};box-shadow: 0 0 10px {{ $statusGlow }}"></div>
                                <div class="entry-content">
                                    <div class="entry-header">
                                        <span class="entry-name">{{ $borrowRequest->borrower_name }}</span>
                                        <span class="entry-date">{{ $borrowRequest->request_date?->format('M j, Y') }}</span>
                                    </div>
                                    <div style="display:flex;align-items:center;gap:0.75rem">
                                        <span class="status-badge {{ $borrowRequest->status }}" style="position:static;font-size:0.65rem;padding:0.4rem 0.8rem;border-radius:8px">
                                            {{ strtoupper($borrowRequest->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>
    <!-- Borrow Modal -->
    <div id="borrowModal" class="modal">
        <div class="modal-card">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:2rem">
                <h3 style="margin:0;font-size:1.6rem;font-weight:800;letter-spacing:-0.5px">Request to Borrow</h3>
                <button onclick="closeModal('borrowModal')" style="background:var(--bg);border:none;width:36px;height:36px;border-radius:10px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:1.2rem">&times;</button>
            </div>
            <form method="POST" action="{{ route('book.show', ['id' => $book->id]) }}">
                @csrf
                <input type="hidden" name="action" value="borrow">
                <div style="margin-bottom:1.5rem">
                    <label style="display:block;margin-bottom:0.75rem;font-weight:600;font-size:0.9rem;color:var(--text-muted)">BORROW DURATION</label>
                    <select name="duration" class="form-control duration-select">
                        <option value="7">7 days</option>
                        <option value="14" selected>14 days</option>
                        <option value="21">21 days</option>
                        <option value="30">30 days</option>
                    </select>
                </div>
                <div style="margin-bottom:2.5rem">
                    <label style="display:block;margin-bottom:0.75rem;font-weight:600;font-size:0.9rem;color:var(--text-muted)">MESSAGE TO OWNER <span style="font-weight:400;opacity:0.6">(OPTIONAL)</span></label>
                    <textarea name="message" class="form-control" rows="4" placeholder="Hi! I'd love to read this book..."></textarea>
                </div>
                <div style="display:flex;gap:1rem">
                    <button type="button" onclick="closeModal('borrowModal')" class="btn btn-outline" style="flex:1">Cancel</button>
                    <button type="submit" class="btn btn-primary" style="flex:2">Send Request</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Related Books Section -->
    @if ($relatedBooks->isNotEmpty())
    <div class="book-detail">
        <div class="related-section">
            <h2 class="related-title">
                <i class="fas fa-layer-group"></i>
                Related Books
            </h2>
            <div class="hide-on-mobile">
                <x-book-card-grid :books="$relatedBooks" />
            </div>
            <div class="show-on-mobile">
                <x-book-card-list :books="$relatedBooks" />
            </div>
        </div>
    </div>
    @endif

    
@endsection

@push('scripts')
<script>
        const bookActionUrl = @json(route('book.show', ['id' => $book->id]));
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

        function postBookAction(params) {
            params.set('_token', csrfToken);

            return fetch(bookActionUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: params,
            });
        }

        // Tab switching
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelector(`.tab[data-tab="${tab}"]`)?.classList.add('active');
            
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            document.getElementById(tab + '-tab').classList.add('active');
        }
        
        // Modal
        function showBorrowModal() { document.getElementById('borrowModal').classList.add('active'); }
        function closeModal(id) { document.getElementById(id).classList.remove('active'); }
        
        // Rating stars
        let currentRating = 0;
        document.addEventListener('DOMContentLoaded', () => {
            const stars = document.querySelectorAll('#ratingStarsInput i');
            stars.forEach(star => {
                star.addEventListener('click', function () {
                    currentRating = parseInt(this.dataset.rating);
                    stars.forEach((s, index) => {
                        s.className = (index + 1 <= currentRating) ? 'fas fa-star' : 'far fa-star';
                    });
                });
                star.addEventListener('mouseover', function () {
                    const hoverRating = parseInt(this.dataset.rating);
                    stars.forEach((s, index) => {
                        s.className = (index + 1 <= hoverRating) ? 'fas fa-star' : 'far fa-star';
                    });
                });
                star.addEventListener('mouseleave', function () {
                    stars.forEach((s, index) => {
                        s.className = (index + 1 <= currentRating) ? 'fas fa-star' : 'far fa-star';
                    });
                });
            });
        });
        
        // Submit review
        function submitReview() {
            if (currentRating === 0) { alert('Please select a rating'); return; }
            const reviewText = document.getElementById('reviewText').value.trim();
            if (reviewText.length < 10) { alert('Review must be at least 10 characters'); return; }
            postBookAction(new URLSearchParams({ ajax_action: 'add_review', rating: currentRating, review_text: reviewText })).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else alert(data.message || 'Failed to submit review');
            }).catch(() => alert('Network error'));
        }
        
        // Submit comment
        function submitComment() {
            const commentText = document.getElementById('commentText').value.trim();
            if (commentText.length < 2) { alert('Comment must be at least 2 characters'); return; }
            postBookAction(new URLSearchParams({ ajax_action: 'add_comment', comment_text: commentText })).then(r => r.json()).then(data => {
                if (data.success) location.reload();
                else alert(data.message || 'Failed to post comment');
            }).catch(() => alert('Network error'));
        }
        
        // Like comment
        function likeComment(commentId, btn) {
            postBookAction(new URLSearchParams({ ajax_action: 'like_comment', comment_id: commentId })).then(r => r.json()).then(data => {
                if (data.success) {
                    const countEl = btn.querySelector('.like-count');
                    if (countEl) countEl.textContent = data.likes;
                    if (data.liked) btn.classList.add('active');
                    else btn.classList.remove('active');
                }
            }).catch(e => console.error(e));
        }
        
        // ── Wishlist toggle ─────────────────────────────────────────
        function toggleWishlist() {
            const btn   = document.getElementById('wishlistBtn');
            const icon  = btn ? btn.querySelector('i') : null;
            const badge = document.getElementById('wishlistCount');
            if (!btn || !icon) return;

            // Optimistic UI
            const wasWishlisted = btn.classList.contains('wishlisted');
            btn.classList.toggle('wishlisted', !wasWishlisted);
            icon.className = wasWishlisted ? 'far fa-heart' : 'fas fa-heart';

            postBookAction(new URLSearchParams({ ajax_action: 'toggle_wishlist' }))
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    // Revert optimistic update
                    btn.classList.toggle('wishlisted', wasWishlisted);
                    icon.className = wasWishlisted ? 'fas fa-heart' : 'far fa-heart';
                    alert(data.message || 'Failed to update wishlist');
                    return;
                }
                // Update badge count
                if (badge) {
                    badge.textContent = data.count;
                    badge.style.display = data.count > 0 ? 'inline-block' : 'none';
                }
                // Update tooltip text
                const tip = btn.querySelector('.wishlist-tooltip');
                if (tip) tip.textContent = data.added ? 'Remove from wishlist' : 'Notify me when available';
                btn.title = data.added ? 'Remove from wishlist' : 'Add to wishlist';
            })
            .catch(() => {
                // Revert
                btn.classList.toggle('wishlisted', wasWishlisted);
                icon.className = wasWishlisted ? 'fas fa-heart' : 'far fa-heart';
            });
        }

        // Share
        function shareBook() {
            if (navigator.share) {
                navigator.share({ title: @json($book->title), text: 'Check out this amazing book on OpenShelf!', url: window.location.href });
            } else {
                navigator.clipboard.writeText(window.location.href).then(() => alert('Link copied to clipboard!'));
            }
        }
        
        // Close modal on outside click
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('borrowModal');
            if (e.target === modal) closeModal('borrowModal');
        });
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('borrowModal');
                if (modal && modal.classList.contains('active')) closeModal('borrowModal');
            }
        });
    
</script>
@endpush
