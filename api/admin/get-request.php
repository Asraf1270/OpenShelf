<?php
/**
 * API endpoint to get request data for modals
 */

session_start();
header('Content-Type: application/json');

// Configuration
define('DATA_PATH', dirname(__DIR__, 2) . '/data/');

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$requestId = $_GET['id'] ?? '';

if (empty($requestId)) {
    echo json_encode(['success' => false, 'message' => 'Request ID required']);
    exit;
}

$requestsFile = DATA_PATH . 'borrow_requests.json';

if (!file_exists($requestsFile)) {
    echo json_encode(['success' => false, 'message' => 'Requests not found']);
    exit;
}

$requests = json_decode(file_get_contents($requestsFile), true) ?? [];
$requestData = null;

foreach ($requests as $request) {
    if ($request['id'] === $requestId) {
        $requestData = $request;
        break;
    }
}

if (!$requestData) {
    echo json_encode(['success' => false, 'message' => 'Request not found']);
    exit;
}

echo json_encode([
    'success' => true,
    'request' => $requestData
]);