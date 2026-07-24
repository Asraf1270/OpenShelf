<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $db = getDB();
    
    // Add columns to books table
    $sql = "ALTER TABLE books 
            ADD COLUMN rating DECIMAL(3,2) DEFAULT 0.00 AFTER comments,
            ADD COLUMN rating_count INT DEFAULT 0 AFTER rating";
    
    $db->exec($sql);
    echo "Database table 'books' updated successfully.\n";

    // Migrate existing ratings
    $stmt = $db->query("SELECT id, reviews FROM books");
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($books as $book) {
        $reviews = json_decode($book['reviews'] ?? '[]', true);
        $count = count($reviews);
        $avg = 0;
        
        if ($count > 0) {
            $total = 0;
            foreach ($reviews as $review) {
                $total += $review['rating'] ?? 0;
            }
            $avg = round($total / $count, 2);
        }
        
        $updateStmt = $db->prepare("UPDATE books SET rating = ?, rating_count = ? WHERE id = ?");
        $updateStmt->execute([$avg, $count, $book['id']]);
    }
    echo "Existing ratings migrated successfully.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Columns already exist.\n";
    } else {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
