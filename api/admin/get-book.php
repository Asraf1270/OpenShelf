<?php
/**
 * API endpoint to get book data for editing
 */

session_start();
header('Content-Type: application/json');

// Configuration
define('DATA_PATH', dirname(__DIR__, 2) . '/data/');
define('BOOKS_PATH', dirname(__DIR__, 2) . '/books/');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$bookId = $_GET['id'] ?? '';

if (empty($bookId)) {
    echo json_encode(['success' => false, 'message' => 'Book ID required']);
    exit;
}

$bookFile = BOOKS_PATH . $bookId . '.json';

if (!file_exists($bookFile)) {
    echo json_encode(['success' => false, 'message' => 'Book not found']);
    exit;
}

$bookData = json_decode(file_get_contents($bookFile), true);

echo json_encode([
    'success' => true,
    'book' => $bookData
]);