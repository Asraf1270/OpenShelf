<?php
/**
 * OpenShelf Feed API
 * Returns new activities for AJAX polling
 */

session_start();
header('Content-Type: application/json');

// Configuration
define('DATA_PATH', dirname(__DIR__) . '/data/');
define('BOOKS_PATH', dirname(__DIR__) . '/books/');
define('USERS_PATH', dirname(__DIR__) . '/users/');

require_once dirname(__DIR__) . '/includes/db.php';

/**
 * Load all books data
 */
function loadAllBooks() {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM books ORDER BY created_at DESC LIMIT 50");
    $stmt->execute();
    $books = $stmt->fetchAll();
    foreach ($books as &$book) {
        $book['reviews'] = json_decode($book['reviews'] ?? '[]', true);
    }
    return $books;
}

/**
 * Load all borrow requests
 */
function loadAllRequests() {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM borrow_requests ORDER BY updated_at DESC LIMIT 50");
    $stmt->execute();
    return $stmt->fetchAll();
}

/**
 * Get user avatar
 */
function getUserAvatar($userId) {
    $userFile = USERS_PATH . $userId . '.json';
    if (file_exists($userFile)) {
        $userData = json_decode(file_get_contents($userFile), true);
        return $userData['personal_info']['profile_pic'] ?? 'default-avatar.jpg';
    }
    return 'default-avatar.jpg';
}

/**
 * Format time ago
 */
function formatTimeAgo($timestamp) {
    $time = strtotime($timestamp);
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
 * Generate activities from data
 */
function generateActivities($books, $requests, $since = null, $filter = 'all', $limit = 10) {
    $activities = [];
    
    // Book added activities
    foreach ($books as $book) {
        if (!$since || strtotime($book['created_at']) > strtotime($since)) {
            if ($filter === 'all' || $filter === 'book_added') {
                $activities[] = [
                    'id' => 'book_' . $book['id'] . '_' . strtotime($book['created_at']),
                    'type' => 'book_added',
                    'user_id' => $book['owner_id'],
                    'user_name' => $book['owner_name'] ?? 'Unknown',
                    'user_avatar' => getUserAvatar($book['owner_id']),
                    'book_id' => $book['id'],
                    'book_title' => $book['title'],
                    'book_author' => $book['author'],
                    'book_cover' => $book['cover_image'] ?? null,
                    'timestamp' => $book['created_at'],
                    'time_ago' => formatTimeAgo($book['created_at']),
                    'data' => [
                        'category' => $book['category'] ?? 'Uncategorized'
                    ]
                ];
            }
        }
    }
    
    // Borrow request activities (approved only)
    foreach ($requests as $request) {
        if ($request['status'] === 'approved' || $request['status'] === 'returned') {
            $activityType = $request['status'] === 'approved' ? 'book_borrowed' : 'book_returned';
            $timestamp = $request[$request['status'] . '_at'] ?? $request['updated_at'];
            
            if (!$since || strtotime($timestamp) > strtotime($since)) {
                if ($filter === 'all' || $filter === $activityType) {
                    $activities[] = [
                        'id' => $activityType . '_' . $request['id'] . '_' . strtotime($timestamp),
                        'type' => $activityType,
                        'user_id' => $request['borrower_id'],
                        'user_name' => $request['borrower_name'],
                        'user_avatar' => getUserAvatar($request['borrower_id']),
                        'book_id' => $request['book_id'],
                        'book_title' => $request['book_title'],
                        'book_author' => $request['book_author'] ?? 'Unknown',
                        'book_cover' => $request['book_cover'] ?? null,
                        'owner_id' => $request['owner_id'],
                        'owner_name' => $request['owner_name'],
                        'timestamp' => $timestamp,
                        'time_ago' => formatTimeAgo($timestamp),
                        'data' => [
                            'duration' => $request['duration_days'] ?? null
                        ]
                    ];
                }
            }
        }
    }
    
    // Review activities
    foreach ($books as $book) {
        if (!empty($book['reviews'])) {
            foreach ($book['reviews'] as $review) {
                if (!$since || strtotime($review['created_at']) > strtotime($since)) {
                    if ($filter === 'all' || $filter === 'book_reviewed') {
                        $activities[] = [
                            'id' => 'review_' . $review['id'] . '_' . strtotime($review['created_at']),
                            'type' => 'book_reviewed',
                            'user_id' => $review['user_id'],
                            'user_name' => $review['user_name'],
                            'user_avatar' => getUserAvatar($review['user_id']),
                            'book_id' => $book['id'],
                            'book_title' => $book['title'],
                            'book_author' => $book['author'],
                            'book_cover' => $book['cover_image'] ?? null,
                            'timestamp' => $review['created_at'],
                            'time_ago' => formatTimeAgo($review['created_at']),
                            'data' => [
                                'rating' => $review['rating'],
                                'review_text' => $review['review_text']
                            ]
                        ];
                    }
                }
            }
        }
    }
    
    // Sort by timestamp (newest first)
    usort($activities, function($a, $b) {
        return strtotime($b['timestamp']) - strtotime($a['timestamp']);
    });
    
    // Return limited number
    return array_slice($activities, 0, $limit);
}

// Get parameters
$since = $_GET['since'] ?? null;
$before = $_GET['before'] ?? null;
$filter = $_GET['filter'] ?? 'all';
$limit = intval($_GET['limit'] ?? 10);

// Load data
$books = loadAllBooks();
$requests = loadAllRequests();

if ($since) {
    // Get new activities since timestamp
    $activities = generateActivities($books, $requests, $since, $filter, $limit);
    
    // Get latest timestamp
    $latestTimestamp = !empty($activities) ? $activities[0]['timestamp'] : $since;
    
    echo json_encode([
        'success' => true,
        'activities' => $activities,
        'latest_timestamp' => $latestTimestamp,
        'count' => count($activities)
    ]);
    
} elseif ($before) {
    // Get older activities for pagination
    // This would need a more complex implementation with proper pagination
    // For now, return empty array
    echo json_encode([
        'success' => true,
        'activities' => [],
        'has_more' => false
    ]);
    
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Missing parameters'
    ]);
}