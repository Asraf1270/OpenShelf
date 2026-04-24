<?php
/**
 * OpenShelf Books API - Cursor Based Pagination
 */

session_start();
header('Content-Type: application/json');

require_once __DIR__ . '/../includes/db.php';

// Get parameters
$cursor_date = ($_GET['cursor_date'] ?? null) === 'null' ? null : ($_GET['cursor_date'] ?? null);
$cursor_id = ($_GET['cursor_id'] ?? null) === 'null' ? null : ($_GET['cursor_id'] ?? null);
$limit = isset($_GET['limit']) ? min(100, intval($_GET['limit'])) : 12;
$search = $_GET['search'] ?? '';
$selectedCategories = isset($_GET['categories']) ? (array)$_GET['categories'] : [];
$availability = $_GET['availability'] ?? '';

try {
    $db = getDB();
    
    $where = ["1=1"];
    $params = [];

    // Filter by status
    if (!empty($availability)) {
        $where[] = "b.status = :availability";
        $params[':availability'] = $availability;
    }

    // Filter by categories
    if (!empty($selectedCategories)) {
        $catPlaceholders = [];
        foreach ($selectedCategories as $i => $cat) {
            $key = ":cat$i";
            $catPlaceholders[] = $key;
            $params[$key] = $cat;
        }
        $where[] = "b.category IN (" . implode(',', $catPlaceholders) . ")";
    }

    // Filter by search (using unique placeholders for each occurrence)
    if (!empty($search)) {
        $where[] = "(b.title LIKE :search1 OR b.author LIKE :search2 OR b.publisher LIKE :search3)";
        $params[':search1'] = "%$search%";
        $params[':search2'] = "%$search%";
        $params[':search3'] = "%$search%";
    }

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
