<?php
/**
 * Global Helpers for OpenShelf
 */

/**
 * Fetch a user from the database by ID
 * Returns a standardized array that mimics the JSON structure for backward compatibility
 */
function getUserById($userId) {
    if (empty($userId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) return null;
    
    // For backward compatibility with JSON-based code
    return [
        'id' => $user['id'],
        'personal_info' => [
            'name' => $user['name'],
            'email' => $user['email'],
            'department' => $user['department'],
            'session' => $user['session'],
            'phone' => $user['phone'],
            'room_number' => $user['room_number'],
            'hall' => $user['hall']
        ],
        'account_info' => [
            'verified' => (bool)$user['verified'],
            'role' => $user['role'],
            'created_at' => $user['created_at'],
            'status' => $user['status']
        ]
    ];
}

/**
 * Fetch a book from the database by ID
 */
function getBookById($bookId) {
    if (empty($bookId)) return null;
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$bookId]);
    return $stmt->fetch() ?: null;
}

/**
 * Get Hall Name by ID
 */
function getHallName($hallId) {
    $halls = [
        '1' => 'Amar Ekushey Hall',
        '2' => 'Dr. Muhammad Shahidullah Hall',
        '3' => 'Fazlul Huq Muslim Hall'
    ];
    return $halls[$hallId] ?? 'N/A';
}
