<?php
/**
 * OpenShelf Book Interactions API
 * Handles AJAX requests for reviews, comments, and likes
 */

session_start();
header('Content-Type: application/json');

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_PATH', dirname(__DIR__) . '/books/');
define('USERS_PATH', dirname(__DIR__) . '/users/');

// Check if user is logged in for write operations
$isLoggedIn = isset($_SESSION['user_id']);
$currentUserId = $_SESSION['user_id'] ?? null;
$currentUserName = $_SESSION['user_name'] ?? null;

/**
 * Generate a unique ID
 */
function generateId($prefix = '') {
    return $prefix . uniqid() . bin2hex(random_bytes(4));
}

/**
 * Load book data
 */
function loadBookData($bookId) {
    $bookFile = BOOKS_PATH . $bookId . '.json';
    if (!file_exists($bookFile)) {
        return null;
    }
    return json_decode(file_get_contents($bookFile), true);
}

/**
 * Save book data
 */
function saveBookData($bookId, $bookData) {
    $bookFile = BOOKS_PATH . $bookId . '.json';
    return file_put_contents(
        $bookFile,
        json_encode($bookData, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
    );
}

/**
 * Load user data
 */
function loadUserData($userId) {
    $userFile = USERS_PATH . $userId . '.json';
    if (!file_exists($userFile)) {
        return null;
    }
    return json_decode(file_get_contents($userFile), true);
}

/**
 * Format time ago
 */
function timeAgo($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return date('M j, Y', $time);
    }
}

/**
 * Get user avatar URL
 */
function getUserAvatar($userId) {
    $userData = loadUserData($userId);
    $avatar = $userData['personal_info']['profile_pic'] ?? 'default-avatar.jpg';
    return '/uploads/profile/' . $avatar;
}

/**
 * Check if user can delete an item
 */
function canDelete($itemUserId) {
    global $currentUserId;
    return $currentUserId && $currentUserId === $itemUserId;
}

// Get request data
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

// Handle GET requests (reading data)
if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    $bookId = $_GET['book_id'] ?? '';
    
    if (!$bookId) {
        echo json_encode(['success' => false, 'message' => 'Book ID required']);
        exit;
    }
    
    $bookData = loadBookData($bookId);
    if (!$bookData) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }
    
    if ($action === 'get_reviews') {
        $page = intval($_GET['page'] ?? 1);
        $perPage = 5;
        $reviews = $bookData['reviews'] ?? [];
        
        // Sort by date (newest first)
        usort($reviews, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        $total = count($reviews);
        $offset = ($page - 1) * $perPage;
        $paginatedReviews = array_slice($reviews, $offset, $perPage);
        
        // Format reviews for output
        $formattedReviews = [];
        foreach ($paginatedReviews as $review) {
            $formattedReviews[] = [
                'id' => $review['id'],
                'user_id' => $review['user_id'],
                'user_name' => $review['user_name'],
                'user_avatar' => getUserAvatar($review['user_id']),
                'rating' => $review['rating'],
                'review_text' => $review['review_text'],
                'likes' => count($review['likes'] ?? []),
                'user_liked' => $currentUserId ? in_array($currentUserId, $review['likes'] ?? []) : false,
                'time_ago' => timeAgo($review['created_at']),
                'can_delete' => canDelete($review['user_id'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'reviews' => $formattedReviews,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total
        ]);
        exit;
        
    } elseif ($action === 'get_comments') {
        $page = intval($_GET['page'] ?? 1);
        $perPage = 10;
        $comments = $bookData['comments'] ?? [];
        
        // Sort by date (newest first)
        usort($comments, function($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });
        
        $total = count($comments);
        $offset = ($page - 1) * $perPage;
        $paginatedComments = array_slice($comments, $offset, $perPage);
        
        // Format comments for output
        $formattedComments = [];
        foreach ($paginatedComments as $comment) {
            $formattedComments[] = [
                'id' => $comment['id'],
                'user_id' => $comment['user_id'],
                'user_name' => $comment['user_name'],
                'user_avatar' => getUserAvatar($comment['user_id']),
                'comment_text' => $comment['comment_text'],
                'parent_id' => $comment['parent_id'] ?? null,
                'likes' => count($comment['likes'] ?? []),
                'user_liked' => $currentUserId ? in_array($currentUserId, $comment['likes'] ?? []) : false,
                'time_ago' => timeAgo($comment['created_at']),
                'can_delete' => canDelete($comment['user_id'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'comments' => $formattedComments,
            'has_more' => ($offset + $perPage) < $total,
            'total' => $total
        ]);
        exit;
    }
}

// Handle POST requests (writing data)
if ($method === 'POST') {
    
    // Check authentication for write operations
    if (!$isLoggedIn) {
        echo json_encode(['success' => false, 'message' => 'Please login to continue']);
        exit;
    }
    
    $action = $input['action'] ?? '';
    $bookId = $input['book_id'] ?? '';
    
    if (!$bookId) {
        echo json_encode(['success' => false, 'message' => 'Book ID required']);
        exit;
    }
    
    $bookData = loadBookData($bookId);
    if (!$bookData) {
        echo json_encode(['success' => false, 'message' => 'Book not found']);
        exit;
    }
    
    // Initialize arrays if they don't exist
    if (!isset($bookData['reviews'])) {
        $bookData['reviews'] = [];
    }
    if (!isset($bookData['comments'])) {
        $bookData['comments'] = [];
    }
    
    // Handle different actions
    switch ($action) {
        
        case 'add_review':
            $rating = intval($input['rating'] ?? 0);
            $reviewText = trim($input['review_text'] ?? '');
            
            // Validate
            if ($rating < 1 || $rating > 5) {
                echo json_encode(['success' => false, 'message' => 'Invalid rating']);
                exit;
            }
            
            if (strlen($reviewText) < 10) {
                echo json_encode(['success' => false, 'message' => 'Review must be at least 10 characters']);
                exit;
            }
            
            // Check if user already reviewed
            foreach ($bookData['reviews'] as $review) {
                if ($review['user_id'] === $currentUserId) {
                    echo json_encode(['success' => false, 'message' => 'You have already reviewed this book']);
                    exit;
                }
            }
            
            // Create new review
            $reviewId = generateId('rev_');
            $newReview = [
                'id' => $reviewId,
                'user_id' => $currentUserId,
                'user_name' => $currentUserName,
                'rating' => $rating,
                'review_text' => $reviewText,
                'likes' => [],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $bookData['reviews'][] = $newReview;
            
            if (saveBookData($bookId, $bookData)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Review added successfully',
                    'review' => [
                        'id' => $reviewId,
                        'user_id' => $currentUserId,
                        'user_name' => $currentUserName,
                        'user_avatar' => getUserAvatar($currentUserId),
                        'rating' => $rating,
                        'review_text' => $reviewText,
                        'likes' => 0,
                        'user_liked' => false,
                        'time_ago' => 'just now',
                        'can_delete' => true
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save review']);
            }
            break;
            
        case 'add_comment':
            $commentText = trim($input['comment_text'] ?? '');
            $parentId = $input['parent_id'] ?? null;
            
            // Validate
            if (strlen($commentText) < 2) {
                echo json_encode(['success' => false, 'message' => 'Comment must be at least 2 characters']);
                exit;
            }
            
            // Create new comment
            $commentId = generateId('com_');
            $newComment = [
                'id' => $commentId,
                'user_id' => $currentUserId,
                'user_name' => $currentUserName,
                'comment_text' => $commentText,
                'parent_id' => $parentId,
                'likes' => [],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $bookData['comments'][] = $newComment;
            
            if (saveBookData($bookId, $bookData)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Comment added successfully',
                    'comment' => [
                        'id' => $commentId,
                        'user_id' => $currentUserId,
                        'user_name' => $currentUserName,
                        'user_avatar' => getUserAvatar($currentUserId),
                        'comment_text' => $commentText,
                        'parent_id' => $parentId,
                        'likes' => 0,
                        'user_liked' => false,
                        'time_ago' => 'just now',
                        'can_delete' => true
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to save comment']);
            }
            break;
            
        case 'like_review':
            $reviewId = $input['review_id'] ?? '';
            
            if (!$reviewId) {
                echo json_encode(['success' => false, 'message' => 'Review ID required']);
                exit;
            }
            
            $reviewFound = false;
            foreach ($bookData['reviews'] as &$review) {
                if ($review['id'] === $reviewId) {
                    if (!isset($review['likes'])) {
                        $review['likes'] = [];
                    }
                    
                    if (in_array($currentUserId, $review['likes'])) {
                        // Unlike
                        $review['likes'] = array_diff($review['likes'], [$currentUserId]);
                        $liked = false;
                    } else {
                        // Like
                        $review['likes'][] = $currentUserId;
                        $liked = true;
                    }
                    
                    $reviewFound = true;
                    $likeCount = count($review['likes']);
                    break;
                }
            }
            
            if ($reviewFound && saveBookData($bookId, $bookData)) {
                echo json_encode([
                    'success' => true,
                    'likes' => $likeCount,
                    'liked' => $liked
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update like']);
            }
            break;
            
        case 'like_comment':
            $commentId = $input['comment_id'] ?? '';
            
            if (!$commentId) {
                echo json_encode(['success' => false, 'message' => 'Comment ID required']);
                exit;
            }
            
            $commentFound = false;
            foreach ($bookData['comments'] as &$comment) {
                if ($comment['id'] === $commentId) {
                    if (!isset($comment['likes'])) {
                        $comment['likes'] = [];
                    }
                    
                    if (in_array($currentUserId, $comment['likes'])) {
                        // Unlike
                        $comment['likes'] = array_diff($comment['likes'], [$currentUserId]);
                        $liked = false;
                    } else {
                        // Like
                        $comment['likes'][] = $currentUserId;
                        $liked = true;
                    }
                    
                    $commentFound = true;
                    $likeCount = count($comment['likes']);
                    break;
                }
            }
            
            if ($commentFound && saveBookData($bookId, $bookData)) {
                echo json_encode([
                    'success' => true,
                    'likes' => $likeCount,
                    'liked' => $liked
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update like']);
            }
            break;
            
        case 'delete_review':
            $reviewId = $input['review_id'] ?? '';
            
            if (!$reviewId) {
                echo json_encode(['success' => false, 'message' => 'Review ID required']);
                exit;
            }
            
            $reviewIndex = -1;
            foreach ($bookData['reviews'] as $index => $review) {
                if ($review['id'] === $reviewId) {
                    if ($review['user_id'] !== $currentUserId) {
                        echo json_encode(['success' => false, 'message' => 'You can only delete your own reviews']);
                        exit;
                    }
                    $reviewIndex = $index;
                    break;
                }
            }
            
            if ($reviewIndex >= 0) {
                array_splice($bookData['reviews'], $reviewIndex, 1);
                
                if (saveBookData($bookId, $bookData)) {
                    echo json_encode(['success' => true, 'message' => 'Review deleted']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete review']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Review not found']);
            }
            break;
            
        case 'delete_comment':
            $commentId = $input['comment_id'] ?? '';
            
            if (!$commentId) {
                echo json_encode(['success' => false, 'message' => 'Comment ID required']);
                exit;
            }
            
            $commentIndex = -1;
            foreach ($bookData['comments'] as $index => $comment) {
                if ($comment['id'] === $commentId) {
                    if ($comment['user_id'] !== $currentUserId) {
                        echo json_encode(['success' => false, 'message' => 'You can only delete your own comments']);
                        exit;
                    }
                    $commentIndex = $index;
                    break;
                }
            }
            
            if ($commentIndex >= 0) {
                array_splice($bookData['comments'], $commentIndex, 1);
                
                if (saveBookData($bookId, $bookData)) {
                    echo json_encode(['success' => true, 'message' => 'Comment deleted']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to delete comment']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Comment not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
    exit;
}

// Invalid request method
echo json_encode(['success' => false, 'message' => 'Invalid request method']);