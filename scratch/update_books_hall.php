<?php
/**
 * Migration Script: Add Hall column to Books table
 * and sync existing data.
 */

require_once __DIR__ . '/../includes/db.php';

try {
    $db = getDB();
    
    // 1. Add hall column if it doesn't exist
    $db->exec("ALTER TABLE books ADD COLUMN hall CHAR(1) AFTER owner_id");
    echo "✅ Added 'hall' column to 'books' table.\n";
    
    // 2. Sync existing books with their owner's hall
    $db->exec("
        UPDATE books b
        JOIN users u ON b.owner_id = u.id
        SET b.hall = u.hall
        WHERE b.hall IS NULL OR b.hall = ''
    ");
    echo "✅ Synchronized existing books with owner halls.\n";
    
    // 3. Add index for faster filtering
    $db->exec("CREATE INDEX idx_books_hall ON books(hall)");
    echo "✅ Added index on 'hall' column.\n";

} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    
    // If column already exists, just try to sync
    if (strpos($e->getMessage(), 'Duplicate column') !== false) {
        try {
            $db->exec("
                UPDATE books b
                JOIN users u ON b.owner_id = u.id
                SET b.hall = u.hall
                WHERE b.hall IS NULL OR b.hall = ''
            ");
            echo "✅ Synchronized existing books (column already existed).\n";
        } catch (PDOException $e2) {
            echo "❌ Sync Error: " . $e2->getMessage() . "\n";
        }
    }
}
