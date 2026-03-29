/**
 * OpenShelf Book Interactions JavaScript
 * Handles AJAX requests for reviews, comments, and likes
 */

class BookInteractions {
    constructor(bookId, currentUserId) {
        this.bookId = bookId;
        this.currentUserId = currentUserId;
        this.apiUrl = '/api/book-interactions.php';
        this.csrfToken = this.getCsrfToken();
        
        // Initialize event listeners
        this.initEventListeners();
    }
    
    /**
     * Get CSRF token from meta tag or generate one
     */
    getCsrfToken() {
        const metaTag = document.querySelector('meta[name="csrf-token"]');
        return metaTag ? metaTag.getAttribute('content') : '';
    }
    
    /**
     * Initialize all event listeners
     */
    initEventListeners() {
        // Review form submission
        const reviewForm = document.getElementById('reviewForm');
        if (reviewForm) {
            reviewForm.addEventListener('submit', (e) => this.submitReview(e));
        }
        
        // Comment form submission
        const commentForm = document.getElementById('commentForm');
        if (commentForm) {
            commentForm.addEventListener('submit', (e) => this.submitComment(e));
        }
        
        // Rating stars
        const ratingStars = document.querySelectorAll('.rating-star');
        ratingStars.forEach(star => {
            star.addEventListener('click', (e) => this.setRating(e));
            star.addEventListener('mouseover', (e) => this.previewRating(e));
            star.addEventListener('mouseout', () => this.resetRatingPreview());
        });
        
        // Like buttons for reviews
        document.querySelectorAll('.like-review').forEach(btn => {
            btn.addEventListener('click', (e) => this.toggleReviewLike(e));
        });
        
        // Like buttons for comments
        document.querySelectorAll('.like-comment').forEach(btn => {
            btn.addEventListener('click', (e) => this.toggleCommentLike(e));
        });
        
        // Reply buttons for comments
        document.querySelectorAll('.reply-comment').forEach(btn => {
            btn.addEventListener('click', (e) => this.showReplyForm(e));
        });
        
        // Delete buttons
        document.querySelectorAll('.delete-review, .delete-comment').forEach(btn => {
            btn.addEventListener('click', (e) => this.deleteItem(e));
        });
        
        // Load more reviews
        const loadMoreReviews = document.getElementById('loadMoreReviews');
        if (loadMoreReviews) {
            loadMoreReviews.addEventListener('click', () => this.loadMoreReviews());
        }
        
        // Load more comments
        const loadMoreComments = document.getElementById('loadMoreComments');
        if (loadMoreComments) {
            loadMoreComments.addEventListener('click', () => this.loadMoreComments());
        }
    }
    
    /**
     * Submit a new review
     */
    async submitReview(event) {
        event.preventDefault();
        
        const form = event.target;
        const rating = document.getElementById('rating').value;
        const reviewText = document.getElementById('reviewText').value;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Validate
        if (!rating || rating === '0') {
            this.showNotification('Please select a rating', 'error');
            return;
        }
        
        if (!reviewText || reviewText.trim().length < 10) {
            this.showNotification('Review must be at least 10 characters', 'error');
            return;
        }
        
        // Disable button and show loading
        this.setButtonLoading(submitBtn, true);
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    action: 'add_review',
                    book_id: this.bookId,
                    rating: parseInt(rating),
                    review_text: reviewText.trim()
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Add new review to the list
                this.addReviewToDOM(data.review);
                
                // Reset form
                form.reset();
                this.resetRatingStars();
                
                // Show success message
                this.showNotification('Review posted successfully!', 'success');
                
                // Update review count
                this.updateReviewCount('increment');
            } else {
                this.showNotification(data.message || 'Failed to post review', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Network error. Please try again.', 'error');
        } finally {
            this.setButtonLoading(submitBtn, false);
        }
    }
    
    /**
     * Submit a new comment
     */
    async submitComment(event) {
        event.preventDefault();
        
        const form = event.target;
        const commentText = document.getElementById('commentText').value;
        const parentId = document.getElementById('parentCommentId')?.value || null;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        // Validate
        if (!commentText || commentText.trim().length < 2) {
            this.showNotification('Comment must be at least 2 characters', 'error');
            return;
        }
        
        // Disable button and show loading
        this.setButtonLoading(submitBtn, true);
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    action: 'add_comment',
                    book_id: this.bookId,
                    comment_text: commentText.trim(),
                    parent_id: parentId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (parentId) {
                    // Add reply to specific comment
                    this.addReplyToDOM(data.comment, parentId);
                    
                    // Hide reply form
                    document.getElementById(`replyForm-${parentId}`)?.remove();
                } else {
                    // Add new comment to the list
                    this.addCommentToDOM(data.comment);
                }
                
                // Reset form
                form.reset();
                document.getElementById('commentText').value = '';
                
                // Show success message
                this.showNotification('Comment posted successfully!', 'success');
                
                // Update comment count
                this.updateCommentCount('increment');
            } else {
                this.showNotification(data.message || 'Failed to post comment', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Network error. Please try again.', 'error');
        } finally {
            this.setButtonLoading(submitBtn, false);
        }
    }
    
    /**
     * Toggle like on a review
     */
    async toggleReviewLike(event) {
        const btn = event.currentTarget;
        const reviewId = btn.dataset.reviewId;
        const likeCount = btn.querySelector('.like-count');
        const icon = btn.querySelector('i');
        
        // Disable button temporarily
        btn.disabled = true;
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    action: 'like_review',
                    book_id: this.bookId,
                    review_id: reviewId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update like count
                if (likeCount) {
                    likeCount.textContent = data.likes;
                }
                
                // Toggle active class
                btn.classList.toggle('active');
                
                // Toggle icon
                if (btn.classList.contains('active')) {
                    icon.className = 'fas fa-heart';
                } else {
                    icon.className = 'far fa-heart';
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Failed to like review', 'error');
        } finally {
            btn.disabled = false;
        }
    }
    
    /**
     * Toggle like on a comment
     */
    async toggleCommentLike(event) {
        const btn = event.currentTarget;
        const commentId = btn.dataset.commentId;
        const likeCount = btn.querySelector('.like-count');
        const icon = btn.querySelector('i');
        
        btn.disabled = true;
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    action: 'like_comment',
                    book_id: this.bookId,
                    comment_id: commentId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                if (likeCount) {
                    likeCount.textContent = data.likes;
                }
                
                btn.classList.toggle('active');
                
                if (btn.classList.contains('active')) {
                    icon.className = 'fas fa-heart';
                } else {
                    icon.className = 'far fa-heart';
                }
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Failed to like comment', 'error');
        } finally {
            btn.disabled = false;
        }
    }
    
    /**
     * Show reply form for a comment
     */
    showReplyForm(event) {
        const btn = event.currentTarget;
        const commentId = btn.dataset.commentId;
        const commentElement = document.getElementById(`comment-${commentId}`);
        
        // Check if reply form already exists
        if (document.getElementById(`replyForm-${commentId}`)) {
            return;
        }
        
        // Create reply form
        const replyForm = document.createElement('div');
        replyForm.id = `replyForm-${commentId}`;
        replyForm.className = 'reply-form';
        replyForm.innerHTML = `
            <form onsubmit="return false;">
                <input type="hidden" id="parentCommentId" value="${commentId}">
                <textarea class="comment-input" placeholder="Write your reply..." rows="2" required></textarea>
                <div class="reply-form-actions">
                    <button type="button" class="btn-cancel" onclick="this.closest('.reply-form').remove()">Cancel</button>
                    <button type="submit" class="btn-submit" onclick="window.bookInteractions.submitReply(event, '${commentId}')">
                        <i class="fas fa-paper-plane"></i> Reply
                    </button>
                </div>
            </form>
        `;
        
        // Insert after the comment
        commentElement.parentNode.insertBefore(replyForm, commentElement.nextSibling);
        
        // Focus the textarea
        replyForm.querySelector('textarea').focus();
    }
    
    /**
     * Submit a reply to a comment
     */
    async submitReply(event, parentId) {
        event.preventDefault();
        
        const form = event.target.closest('form');
        const commentText = form.querySelector('textarea').value;
        const submitBtn = form.querySelector('button[type="submit"]');
        
        if (!commentText || commentText.trim().length < 2) {
            this.showNotification('Reply must be at least 2 characters', 'error');
            return;
        }
        
        this.setButtonLoading(submitBtn, true);
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    action: 'add_comment',
                    book_id: this.bookId,
                    comment_text: commentText.trim(),
                    parent_id: parentId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.addReplyToDOM(data.comment, parentId);
                form.closest('.reply-form').remove();
                this.showNotification('Reply posted successfully!', 'success');
                this.updateCommentCount('increment');
            } else {
                this.showNotification(data.message || 'Failed to post reply', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Network error. Please try again.', 'error');
        } finally {
            this.setButtonLoading(submitBtn, false);
        }
    }
    
    /**
     * Delete a review or comment
     */
    async deleteItem(event) {
        const btn = event.currentTarget;
        const itemType = btn.classList.contains('delete-review') ? 'review' : 'comment';
        const itemId = btn.dataset.id;
        
        if (!confirm(`Are you sure you want to delete this ${itemType}?`)) {
            return;
        }
        
        btn.disabled = true;
        
        try {
            const response = await fetch(this.apiUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': this.csrfToken
                },
                body: JSON.stringify({
                    action: `delete_${itemType}`,
                    book_id: this.bookId,
                    [`${itemType}_id`]: itemId
                })
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Remove from DOM
                const element = document.getElementById(`${itemType}-${itemId}`);
                if (element) {
                    element.remove();
                    this.showNotification(`${itemType} deleted successfully`, 'success');
                    
                    if (itemType === 'review') {
                        this.updateReviewCount('decrement');
                    } else {
                        this.updateCommentCount('decrement');
                    }
                }
            } else {
                this.showNotification(data.message || `Failed to delete ${itemType}`, 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Network error. Please try again.', 'error');
        } finally {
            btn.disabled = false;
        }
    }
    
    /**
     * Load more reviews (pagination)
     */
    async loadMoreReviews() {
        const btn = document.getElementById('loadMoreReviews');
        const page = parseInt(btn.dataset.page) || 1;
        const container = document.getElementById('reviewsList');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        
        try {
            const response = await fetch(`${this.apiUrl}?action=get_reviews&book_id=${this.bookId}&page=${page}`);
            const data = await response.json();
            
            if (data.success && data.reviews.length > 0) {
                data.reviews.forEach(review => {
                    this.addReviewToDOM(review, false);
                });
                
                btn.dataset.page = page + 1;
                
                if (!data.has_more) {
                    btn.style.display = 'none';
                }
            } else {
                btn.style.display = 'none';
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Failed to load more reviews', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Load More Reviews';
        }
    }
    
    /**
     * Load more comments (pagination)
     */
    async loadMoreComments() {
        const btn = document.getElementById('loadMoreComments');
        const page = parseInt(btn.dataset.page) || 1;
        const container = document.getElementById('commentsList');
        
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        
        try {
            const response = await fetch(`${this.apiUrl}?action=get_comments&book_id=${this.bookId}&page=${page}`);
            const data = await response.json();
            
            if (data.success && data.comments.length > 0) {
                data.comments.forEach(comment => {
                    this.addCommentToDOM(comment, false);
                });
                
                btn.dataset.page = page + 1;
                
                if (!data.has_more) {
                    btn.style.display = 'none';
                }
            } else {
                btn.style.display = 'none';
            }
        } catch (error) {
            console.error('Error:', error);
            this.showNotification('Failed to load more comments', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = 'Load More Comments';
        }
    }
    
    /**
     * Add a new review to the DOM
     */
    addReviewToDOM(review, prepend = true) {
        const container = document.getElementById('reviewsList');
        if (!container) return;
        
        const reviewHtml = this.createReviewHTML(review);
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = reviewHtml;
        const reviewElement = tempDiv.firstElementChild;
        
        if (prepend) {
            container.insertBefore(reviewElement, container.firstChild);
        } else {
            container.appendChild(reviewElement);
        }
        
        // Update average rating if needed
        this.updateAverageRating(review.rating);
    }
    
    /**
     * Add a new comment to the DOM
     */
    addCommentToDOM(comment, prepend = true) {
        const container = document.getElementById('commentsList');
        if (!container) return;
        
        const commentHtml = this.createCommentHTML(comment);
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = commentHtml;
        const commentElement = tempDiv.firstElementChild;
        
        if (prepend) {
            container.insertBefore(commentElement, container.firstChild);
        } else {
            container.appendChild(commentElement);
        }
    }
    
    /**
     * Add a reply to a comment
     */
    addReplyToDOM(reply, parentId) {
        const parentComment = document.getElementById(`comment-${parentId}`);
        if (!parentComment) return;
        
        const repliesContainer = parentComment.querySelector('.comment-replies') || 
            this.createRepliesContainer(parentComment);
        
        const replyHtml = this.createCommentHTML(reply, true);
        const tempDiv = document.createElement('div');
        tempDiv.innerHTML = replyHtml;
        const replyElement = tempDiv.firstElementChild;
        
        repliesContainer.appendChild(replyElement);
    }
    
    /**
     * Create replies container for a comment
     */
    createRepliesContainer(commentElement) {
        const repliesContainer = document.createElement('div');
        repliesContainer.className = 'comment-replies';
        commentElement.appendChild(repliesContainer);
        return repliesContainer;
    }
    
    /**
     * Generate HTML for a review
     */
    createReviewHTML(review) {
        const userLiked = review.user_liked ? 'active' : '';
        const stars = this.getStarHTML(review.rating);
        
        return `
            <div class="review-card" id="review-${review.id}">
                <div class="review-header">
                    <img src="${review.user_avatar}" alt="${review.user_name}" class="reviewer-avatar">
                    <div class="reviewer-info">
                        <h4>${review.user_name}</h4>
                        <div class="review-meta">
                            <span class="review-rating">${stars}</span>
                            <span class="review-date">${review.time_ago}</span>
                        </div>
                    </div>
                </div>
                <div class="review-text">
                    ${review.review_text}
                </div>
                <div class="review-footer">
                    <button class="review-action like-review ${userLiked}" 
                            data-review-id="${review.id}"
                            onclick="window.bookInteractions.toggleReviewLike(event)">
                        <i class="${userLiked ? 'fas' : 'far'} fa-heart"></i>
                        <span class="like-count">${review.likes || 0}</span>
                    </button>
                    ${review.can_delete ? `
                        <button class="review-action delete-review" 
                                data-id="${review.id}"
                                onclick="window.bookInteractions.deleteItem(event)">
                            <i class="far fa-trash-alt"></i> Delete
                        </button>
                    ` : ''}
                </div>
            </div>
        `;
    }
    
    /**
     * Generate HTML for a comment
     */
    createCommentHTML(comment, isReply = false) {
        const userLiked = comment.user_liked ? 'active' : '';
        const indentClass = isReply ? 'reply' : '';
        
        return `
            <div class="comment-item ${indentClass}" id="comment-${comment.id}">
                <div class="comment-avatar">
                    <img src="${comment.user_avatar}" alt="${comment.user_name}">
                </div>
                <div class="comment-content">
                    <div class="comment-header">
                        <span class="comment-author">${comment.user_name}</span>
                        <span class="comment-time">${comment.time_ago}</span>
                    </div>
                    <div class="comment-text">
                        ${comment.comment_text}
                    </div>
                    <div class="comment-actions">
                        <button class="comment-action like-comment ${userLiked}" 
                                data-comment-id="${comment.id}"
                                onclick="window.bookInteractions.toggleCommentLike(event)">
                            <i class="${userLiked ? 'fas' : 'far'} fa-heart"></i>
                            <span class="like-count">${comment.likes || 0}</span>
                        </button>
                        <button class="comment-action reply-comment" 
                                data-comment-id="${comment.id}"
                                onclick="window.bookInteractions.showReplyForm(event)">
                            <i class="far fa-comment"></i> Reply
                        </button>
                        ${comment.can_delete ? `
                            <button class="comment-action delete-comment" 
                                    data-id="${comment.id}"
                                    onclick="window.bookInteractions.deleteItem(event)">
                                <i class="far fa-trash-alt"></i> Delete
                            </button>
                        ` : ''}
                    </div>
                </div>
            </div>
        `;
    }
    
    /**
     * Get HTML for rating stars
     */
    getStarHTML(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                stars += '<i class="fas fa-star"></i>';
            } else {
                stars += '<i class="far fa-star"></i>';
            }
        }
        return stars;
    }
    
    /**
     * Set rating value and update stars
     */
    setRating(event) {
        const star = event.currentTarget;
        const rating = parseInt(star.dataset.rating);
        document.getElementById('rating').value = rating;
        
        this.updateRatingStars(rating);
    }
    
    /**
     * Preview rating on hover
     */
    previewRating(event) {
        const star = event.currentTarget;
        const rating = parseInt(star.dataset.rating);
        this.updateRatingStars(rating, true);
    }
    
    /**
     * Reset rating preview
     */
    resetRatingPreview() {
        const currentRating = parseInt(document.getElementById('rating').value) || 0;
        this.updateRatingStars(currentRating);
    }
    
    /**
     * Update rating stars display
     */
    updateRatingStars(rating, isPreview = false) {
        const stars = document.querySelectorAll('.rating-star');
        stars.forEach((star, index) => {
            const starRating = index + 1;
            if (starRating <= rating) {
                star.className = `rating-star ${isPreview ? 'preview' : ''} fas fa-star`;
            } else {
                star.className = `rating-star ${isPreview ? 'preview' : ''} far fa-star`;
            }
        });
    }
    
    /**
     * Reset rating stars to empty
     */
    resetRatingStars() {
        document.getElementById('rating').value = '0';
        const stars = document.querySelectorAll('.rating-star');
        stars.forEach(star => {
            star.className = 'rating-star far fa-star';
        });
    }
    
    /**
     * Update average rating display
     */
    updateAverageRating(newRating) {
        const avgElement = document.getElementById('averageRating');
        const countElement = document.getElementById('reviewCount');
        
        if (avgElement && countElement) {
            const currentAvg = parseFloat(avgElement.textContent) || 0;
            const currentCount = parseInt(countElement.textContent) || 0;
            
            const newAvg = ((currentAvg * currentCount) + newRating) / (currentCount + 1);
            avgElement.textContent = newAvg.toFixed(1);
            countElement.textContent = currentCount + 1;
        }
    }
    
    /**
     * Update review count
     */
    updateReviewCount(action) {
        const countElement = document.getElementById('reviewCount');
        if (countElement) {
            let count = parseInt(countElement.textContent) || 0;
            count = action === 'increment' ? count + 1 : Math.max(0, count - 1);
            countElement.textContent = count;
        }
    }
    
    /**
     * Update comment count
     */
    updateCommentCount(action) {
        const countElement = document.getElementById('commentCount');
        if (countElement) {
            let count = parseInt(countElement.textContent) || 0;
            count = action === 'increment' ? count + 1 : Math.max(0, count - 1);
            countElement.textContent = count;
        }
    }
    
    /**
     * Set button loading state
     */
    setButtonLoading(button, isLoading) {
        if (isLoading) {
            button.disabled = true;
            const originalText = button.innerHTML;
            button.dataset.originalText = originalText;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
        } else {
            button.disabled = false;
            if (button.dataset.originalText) {
                button.innerHTML = button.dataset.originalText;
            }
        }
    }
    
    /**
     * Show notification message
     */
    showNotification(message, type = 'info') {
        // Check if notification container exists
        let container = document.getElementById('notificationContainer');
        if (!container) {
            container = document.createElement('div');
            container.id = 'notificationContainer';
            container.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                z-index: 9999;
            `;
            document.body.appendChild(container);
        }
        
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.style.cssText = `
            background: ${type === 'success' ? '#2dce89' : type === 'error' ? '#f5365c' : '#667eea'};
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease;
            cursor: pointer;
        `;
        
        notification.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
            <span>${message}</span>
        `;
        
        container.appendChild(notification);
        
        // Remove after 3 seconds
        setTimeout(() => {
            notification.style.animation = 'slideOut 0.3s ease';
            setTimeout(() => {
                notification.remove();
                if (container.children.length === 0) {
                    container.remove();
                }
            }, 300);
        }, 3000);
        
        // Click to dismiss
        notification.addEventListener('click', () => {
            notification.remove();
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const bookId = document.body.dataset.bookId;
    const currentUserId = document.body.dataset.userId;
    
    if (bookId) {
        window.bookInteractions = new BookInteractions(bookId, currentUserId);
    }
});