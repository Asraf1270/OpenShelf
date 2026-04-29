<?php
require_once __DIR__ . '/includes/db.php';
$db = getDB();
$stmt = $db->query("SELECT id, title, owner_id, owner_name FROM books LIMIT 10");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Books in DB:\n";
foreach ($books as $book) {
    echo "ID: {$book['id']} | Title: {$book['title']} | Owner ID: {$book['owner_id']} | Owner Name: {$book['owner_name']}\n";
    
    $stmtUser = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmtUser->execute([$book['owner_id']]);
    $user = $stmtUser->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        echo "  -> Found User in DB: {$user['name']} ({$user['id']})\n";
    } else {
        echo "  -> !!! User NOT found in DB for ID: {$book['owner_id']}\n";
    }
}
