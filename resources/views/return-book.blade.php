@extends('layouts.app')

@section('content')
<main>
        <div class="container">
            <div class="return-container">
                <!-- Page Header -->
                <div class="return-page-header">
                    <div style="margin-bottom: var(--space-sm);">
                        <a href="/requests" class="btn btn-outline btn-sm">
                            <i class="fas fa-arrow-left"></i> Back to Requests
                        </a>
                    </div>
                    <div class="return-page-band"></div>
                    <h1>
                        <span class="return-header-icon"><i class="fas fa-undo-alt"></i></span>
                        Return Confirmation
                    </h1>
                    <p>Provide final details to complete the lending transaction</p>
                </div>
                
                <!-- Error Alert -->
                @if ($error)
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-circle"></i>
                        {{ $error }}
                    </div>
                @endif

                <!-- Progress Flow Timeline Tracker -->
                <div class="return-timeline">
                    <div class="timeline-step completed">
                        <span class="timeline-step-num"><i class="fas fa-check"></i></span> Borrowed
                    </div>
                    <div class="timeline-line active"></div>
                    <div class="timeline-step active">
                        <span class="timeline-step-num">2</span> File Return
                    </div>
                    <div class="timeline-line"></div>
                    <div class="timeline-step">
                        <span class="timeline-step-num">3</span> Owner Confirms
                    </div>
                    <div class="timeline-line"></div>
                    <div class="timeline-step">
                        <span class="timeline-step-num">4</span> Complete
                    </div>
                </div>

                <div class="return-content-layout">
                    <!-- Left Sidebar details -->
                    <div class="return-sidebar">
                        
                        <!-- Book Details Card -->
                        <div class="return-card book-preview-card">
                            <div class="book-cover-container">
                                <img src="{{ $coverImage }}" alt="{{ $borrowRequest->book_title }}">
                            </div>
                            <div class="book-details">
                                <h3>{{ $borrowRequest->book_title }}</h3>
                                <p class="book-author">by {{ $borrowRequest->book_author }}</p>
                                <span class="book-badge-category">
                                    <i class="fas fa-bookmark"></i> {{ $book->category ?? 'Category' }}
                                </span>
                            </div>

                            <ul class="meta-list">
                                <li class="meta-item">
                                    <i class="fas fa-user-circle"></i>
                                    <span class="meta-label">Borrower:</span> 
                                    <span style="margin-left:auto;">{{ $borrowRequest->borrower_name }}</span>
                                </li>
                                <li class="meta-item">
                                    <i class="fas fa-user-tie"></i>
                                    <span class="meta-label">Owner:</span> 
                                    <span style="margin-left:auto;">{{ $borrowRequest->owner_name }}</span>
                                </li>
                                @if ($borrowRequest->expected_return_date)
                                    <li class="meta-item {{ $isPastDue ? 'overdue' : '' }}">
                                        <i class="far fa-calendar-alt"></i>
                                        <span class="meta-label">Due Date:</span>
                                        <span style="margin-left:auto;">{{ $borrowRequest->expected_return_date->format('M j, Y') }}</span>
                                        @if ($isPastDue)
                                            <span class="badge-overdue">{{ $overdueDays }}d Overdue</span>
                                        @endif
                                    </li>
                                @endif
                            </ul>
                        </div>
                        
                        <!-- Guidelines Instruction Card -->
                        <div class="return-card">
                            <div class="guide-title">
                                <i class="fas fa-shield-alt"></i>
                                Return Guidelines
                            </div>
                            <ul class="guide-list">
                                <li class="guide-item">
                                    <i class="fas fa-circle-check"></i>
                                    <span>Check all book pages for personal belongings or notes.</span>
                                </li>
                                <li class="guide-item">
                                    <i class="fas fa-circle-check"></i>
                                    <span>Assess condition honestly to support the book sharing community.</span>
                                </li>
                                <li class="guide-item">
                                    <i class="fas fa-circle-check"></i>
                                    <span>Leave a rating of your experience reading this book.</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Right Main Form Card -->
                    <div class="form-card">
                        <form method="POST" id="returnForm" action="{{ route('return-book', ['id' => $borrowRequest->id]) }}">
                            @csrf
                            
                            <!-- Book Condition Section -->
                            <div class="form-group">
                                <div class="form-section-title">
                                    <i class="fas fa-check-double"></i> Book Condition
                                </div>
                                
                                <div class="condition-grid">
                                    <!-- Same condition card -->
                                    <label class="condition-card same-active">
                                        <input type="radio" name="condition" value="same" checked onchange="toggleConditionState()">
                                        <div class="condition-header">
                                            <span>Good / Intact</span>
                                            <i class="fas fa-circle-check"></i>
                                        </div>
                                        <p>Same condition as borrowed, no new structural damage or marks.</p>
                                    </label>
                                    
                                    <!-- Damaged condition card -->
                                    <label class="condition-card damaged-active">
                                        <input type="radio" name="condition" value="damaged" onchange="toggleConditionState()">
                                        <div class="condition-header">
                                            <span>Has Damage</span>
                                            <i class="fas fa-exclamation-triangle"></i>
                                        </div>
                                        <p>New wears, tears, marks, torn pages, or water damage.</p>
                                    </label>
                                </div>

                                <!-- Slide-down Damage Description -->
                                <div id="damageField" class="damage-details">
                                    <label class="form-label" style="font-size: var(--font-size-sm);">
                                        Describe the damage <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="damage_description" class="form-input" rows="3"
                                              placeholder="Please details the location and severity of the damage..."></textarea>
                                </div>
                            </div>
                            
                            <!-- Premium Rating Stars Section -->
                            <div class="form-group">
                                <div class="form-section-title">
                                    <i class="fas fa-star-half-alt"></i> Rate this book
                                </div>
                                <div class="rating-wrapper">
                                    <div class="stars-container" id="ratingStars">
                                        <i class="far fa-star" data-rating="1"></i>
                                        <i class="far fa-star" data-rating="2"></i>
                                        <i class="far fa-star" data-rating="3"></i>
                                        <i class="far fa-star" data-rating="4"></i>
                                        <i class="far fa-star" data-rating="5"></i>
                                    </div>
                                    <div class="rating-label-feedback" id="ratingFeedbackText">
                                        Select rating
                                    </div>
                                </div>
                                <input type="hidden" name="rating" id="ratingValue" value="0">
                            </div>
                            
                            <!-- Additional Notes Section -->
                            <div class="form-group">
                                <div class="form-section-title">
                                    <i class="fas fa-comment-alt"></i> Notes & Feedback
                                </div>
                                <div class="textarea-container">
                                    <textarea name="notes" class="form-input" rows="3" maxlength="300"
                                              placeholder="Write additional comments about the lending experience or return process..."
                                              oninput="updateCharCount(this)"></textarea>
                                    <div class="char-counter" id="notesCounter">0 / 300 characters</div>
                                </div>
                            </div>
                            
                            <!-- Info banner: owner confirmation step -->
                            <div style="background:rgba(245,158,11,0.08);border:1px solid rgba(245,158,11,0.25);border-radius:var(--radius-md);padding:var(--space-md);margin-bottom:var(--space-md);display:flex;gap:var(--space-sm);align-items:flex-start;">
                                <i class="fas fa-info-circle" style="color:#f59e0b;margin-top:2px;flex-shrink:0;"></i>
                                <div style="font-size:var(--font-size-sm);color:var(--text-secondary);">
                                    <strong style="color:var(--text-primary);">Two-step return process</strong><br>
                                    Submitting this form will notify the <strong>book owner</strong> by email.
                                    The book will be marked <em>Available</em> only after the owner confirms physical receipt.
                                </div>
                            </div>

                            <!-- Form Buttons -->
                            <div class="form-actions">
                                <button type="submit" class="btn" id="submitReturnBtn">
                                    <i class="fas fa-paper-plane"></i> Submit Return Request
                                </button>
                                <a href="/requests" class="btn btn-cancel">
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    @endsection

@push('scripts')
<script>

        // Toggle damage field visibility and card design updates
        function toggleConditionState() {
            const damagedRadio = document.querySelector('input[name="condition"][value="damaged"]');
            const damageField = document.getElementById('damageField');
            const damageInput = document.querySelector('textarea[name="damage_description"]');
            
            if (damagedRadio && damagedRadio.checked) {
                damageField.classList.add('show');
                if (damageInput) damageInput.required = true;
            } else {
                damageField.classList.remove('show');
                if (damageInput) {
                    damageInput.required = false;
                    damageInput.value = '';
                }
            }
        }
        
        // Character counter for notes input
        function updateCharCount(el) {
            const count = el.value.length;
            const max = el.getAttribute('maxlength') || 300;
            const counter = document.getElementById('notesCounter');
            if (counter) {
                counter.textContent = `${count} / ${max} characters`;
            }
        }
        
        // Rating stars premium click & hover feedback
        (function() {
            const stars = document.querySelectorAll('#ratingStars i');
            const ratingInput = document.getElementById('ratingValue');
            const feedbackText = document.getElementById('ratingFeedbackText');
            
            const ratingLabels = {
                0: 'Select rating',
                1: 'Poor - Did not like it',
                2: 'Fair - Could be better',
                3: 'Good - Enjoyable read',
                4: 'Very Good - Recommended',
                5: 'Excellent - Absolutely loved it!'
            };
            
            function renderStars(rating) {
                stars.forEach((star, index) => {
                    if (index < rating) {
                        star.className = 'fas fa-star active';
                    } else {
                        star.className = 'far fa-star';
                    }
                });
                if (feedbackText) {
                    feedbackText.textContent = ratingLabels[rating] || ratingLabels[0];
                    if (rating > 0) {
                        feedbackText.style.color = '#f59e0b';
                    } else {
                        feedbackText.style.color = '';
                    }
                }
            }
            
            stars.forEach(star => {
                star.addEventListener('click', function() {
                    const rating = parseInt(this.dataset.rating);
                    ratingInput.value = rating;
                    renderStars(rating);
                });
                
                star.addEventListener('mouseenter', function() {
                    const rating = parseInt(this.dataset.rating);
                    renderStars(rating);
                });
                
                star.addEventListener('mouseleave', function() {
                    const currentRating = parseInt(ratingInput.value || 0);
                    renderStars(currentRating);
                });
            });
            
            // Trigger initial state
            renderStars(0);
        })();
        
        // Validation shaker on submit
        document.getElementById('returnForm').addEventListener('submit', function(e) {
            const condition = document.querySelector('input[name="condition"]:checked');
            
            if (!condition) {
                e.preventDefault();
                alert('Please select the book condition status.');
                return false;
            }
            
            if (condition.value === 'damaged') {
                const damageDesc = document.querySelector('textarea[name="damage_description"]').value.trim();
                if (!damageDesc) {
                    e.preventDefault();
                    alert('Please provide a brief description of the damage.');
                    return false;
                }
            }
            return true;
        });
        
        // Initial call
        toggleConditionState();
    
</script>
@endpush
