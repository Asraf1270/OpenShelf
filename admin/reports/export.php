<?php
/**
 * OpenShelf Export Reports
 * Export data as CSV
 */

session_start();

define('DATA_PATH', dirname(__DIR__, 2) . '/data/');

if (!isset($_SESSION['admin_id'])) {
    header('Location: /admin/login/');
    exit;
}

function loadAllUsers() {
    $usersFile = DATA_PATH . 'users.json';
    return file_exists($usersFile) ? json_decode(file_get_contents($usersFile), true) ?? [] : [];
}

function loadAllBooks() {
    $booksFile = DATA_PATH . 'books.json';
    return file_exists($booksFile) ? json_decode(file_get_contents($booksFile), true) ?? [] : [];
}

function loadAllRequests() {
    $requestsFile = DATA_PATH . 'borrow_requests.json';
    return file_exists($requestsFile) ? json_decode(file_get_contents($requestsFile), true) ?? [] : [];
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