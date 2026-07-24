<?php
session_start();
/**
 * OpenShelf Export Reports
 * Export data as CSV
 */

define('DATA_PATH', dirname(__DIR__, 2) . '/data/');

// Include database connection
require_once dirname(__DIR__, 2) . '/includes/db.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

function loadAllUsers() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM users ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function loadAllBooks() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM books ORDER BY created_at DESC");
    return $stmt->fetchAll();
}

function loadAllRequests() {
    $db = getDB();
    $stmt = $db->query("SELECT * FROM borrow_requests ORDER BY request_date DESC");
    return $stmt->fetchAll();
}

$type = $_GET['type'] ?? 'users';

if ($type === 'users') {
    $data = loadAllUsers();
    $filename = 'users_export_' . date('Y-m-d') . '.csv';
    $headers = ['ID', 'Name', 'Email', 'Department', 'Session', 'Phone', 'Room', 'Status', 'Verified', 'Created At'];
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['name'],
            $row['email'],
            $row['department'],
            $row['session'],
            $row['phone'],
            $row['room_number'],
            $row['status'],
            $row['verified'] ? 'Yes' : 'No',
            $row['created_at']
        ]);
    }
    fclose($output);
    
} elseif ($type === 'books') {
    $data = loadAllBooks();
    $filename = 'books_export_' . date('Y-m-d') . '.csv';
    $headers = ['ID', 'Title', 'Author', 'Category', 'Owner', 'Status', 'Views', 'Times Borrowed', 'Created At'];
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['title'],
            $row['author'],
            $row['category'],
            $row['owner_name'],
            $row['status'],
            $row['views'] ?? 0,
            $row['times_borrowed'] ?? 0,
            $row['created_at']
        ]);
    }
    fclose($output);
    
} elseif ($type === 'requests') {
    $data = loadAllRequests();
    $filename = 'requests_export_' . date('Y-m-d') . '.csv';
    $headers = ['ID', 'Book', 'Borrower', 'Owner', 'Status', 'Request Date', 'Due Date', 'Return Date', 'Duration'];
    
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        fputcsv($output, [
            $row['id'],
            $row['book_title'],
            $row['borrower_name'],
            $row['owner_name'],
            $row['status'],
            $row['request_date'],
            $row['expected_return_date'] ?? '',
            $row['returned_at'] ?? '',
            $row['duration_days'] ?? ''
        ]);
    }
    fclose($output);
}
exit;