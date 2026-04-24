<?php
/**
 * OpenShelf Books API - Cursor Based Pagination
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/search_helper.php';

// Get parameters
$cursor_date = ($_GET['cursor_date'] ?? null) === 'null' ? null : ($_GET['cursor_date'] ?? null);
$cursor_id = ($_GET['cursor_id'] ?? null) === 'null' ? null : ($_GET['cursor_id'] ?? null);
$limit = isset($_GET['limit']) ? min(100, intval($_GET['limit'])) : 25;
$search = $_GET['search'] ?? '';
$selectedCategories = isset($_GET['categories']) ? (array)$_GET['categories'] : [];
$availability = $_GET['availability'] ?? '';

try {
    $db = getDB();
    
    list($where, $params) = prepareBookQuery($search, $selectedCategories, $availability, 'b');

    // Cursor pagination logic (using unique placeholders for date)
    if ($cursor_date && $cursor_id) {
        $where[] = "(b.created_at < :c_date1 OR (b.created_at = :c_date2 AND b.id < :c_id))";
        $params[':c_date1'] = $cursor_date;
        $params[':c_date2'] = $cursor_date;
        $params[':c_id'] = $cursor_id;
    }

    $sql = "
        SELECT b.*, u.name as owner_name, u.profile_pic as owner_avatar
        FROM books b 
        LEFT JOIN users u ON b.owner_id = u.id 
        WHERE " . implode(' AND ', $where) . "
        ORDER BY b.created_at DESC, b.id DESC
        LIMIT :limit
    ";

    $stmt = $db->prepare($sql);
    foreach ($params as $key => $val) {
        $stmt->bindValue($key, $val);
    }
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Suggest related books if results are few and it's the first page
    if (!empty($search) && count($books) < 4 && !$cursor_date) {
        $excludeIds = array_column($books, 'id');
        $related = getRelatedBooksForSearch($db, $search, $excludeIds, 8 - count($books));
        $books = array_merge($books, $related);
    }

    // Format output
    foreach ($books as &$book) {
        $book['owner_avatar'] = !empty($book['owner_avatar']) && $book['owner_avatar'] !== 'default-avatar.jpg'
            ? '/uploads/profile/' . ltrim($book['owner_avatar'], '/')
            : '/assets/images/avatars/default.jpg';
        
        $book['cover_image'] = !empty($book['cover_image']) 
            ? '/uploads/book_cover/' . ltrim($book['cover_image'], '/') 
            : '/assets/images/default-book-cover.jpg';
    }

    $lastBook = !empty($books) ? end($books) : null;
    $next_cursor_date = $lastBook ? $lastBook['created_at'] : null;
    $next_cursor_id = $lastBook ? $lastBook['id'] : null;

    echo json_encode([
        'success' => true,
        'data' => $books,
        'cursor' => [
            'date' => $next_cursor_date,
            'id' => $next_cursor_id
        ],
        'has_more' => count($books) === $limit
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
