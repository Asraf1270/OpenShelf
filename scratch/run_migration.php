<?php
/**
 * Migration Script: Add Hall column to Books table
 * and sync existing data.
 * You can run this by visiting: your-domain.com/scratch/run_migration.php
 */

require_once __DIR__ . '/../includes/db.php';

echo "<pre>";
echo "Starting migration...\n";

try {
    $db = getDB();
    
    // 1. Add hall column if it doesn't exist
    try {
        $db->exec("ALTER TABLE books ADD COLUMN hall CHAR(1) AFTER owner_id");
        echo "✅ Added 'hall' column to 'books' table.\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false) {
            echo "ℹ️ 'hall' column already exists in 'books' table.\n";
        } else {
            throw $e;
        }
    }
    
    // 2. Sync existing books with their owner's hall
    $count = $db->exec("
        UPDATE books b
        JOIN users u ON b.owner_id = u.id
        SET b.hall = u.hall
        WHERE b.hall IS NULL OR b.hall = ''
    ");
    echo "✅ Synchronized $count existing books with owner halls.\n";
    
    // 3. Add index for faster filtering
    try {
        $db->exec("CREATE INDEX idx_books_hall ON books(hall)");
        echo "✅ Added index on 'hall' column.\n";
    } catch (PDOException $e) {
        echo "ℹ️ Index might already exist.\n";
    }

    echo "\nMigration completed successfully!\n";

} catch (PDOException $e) {
    echo "❌ Critical Error: " . $e->getMessage() . "\n";
}
echo "</pre>";
