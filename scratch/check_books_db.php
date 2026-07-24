<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $db = getDB();
    $stmt = $db->query("DESCRIBE books");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Columns in 'books' table:\n";
    foreach ($columns as $column) {
        echo "- $column\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
