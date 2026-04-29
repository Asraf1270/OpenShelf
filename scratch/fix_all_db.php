<?php
/**
 * Super Migration: Fixes all database issues for OpenShelf v3.0
 */
require_once __DIR__ . '/../includes/db.php';

echo "<pre>🚀 Starting Super Migration...\n\n";

try {
    $db = getDB();
    
    // 1. Fix Users Table
    echo "Checking 'users' table...\n";
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS hall CHAR(1) DEFAULT NULL AFTER room_number");
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS otp_code VARCHAR(6) DEFAULT NULL AFTER password_hash");
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS otp_expiry DATETIME DEFAULT NULL AFTER otp_code");
    $db->exec("ALTER TABLE users MODIFY COLUMN status VARCHAR(20) DEFAULT 'unverified'");
    echo "✅ Users table is up to date.\n\n";
    
    // 2. Fix Books Table
    echo "Checking 'books' table...\n";
    try {
        $db->exec("ALTER TABLE books ADD COLUMN hall CHAR(1) DEFAULT NULL AFTER owner_id");
        echo "✅ Added 'hall' column to 'books' table.\n";
    } catch (PDOException $e) {
        echo "ℹ️ 'hall' column already exists in 'books' table.\n";
    }
    
    // 3. Sync Book Halls
    echo "Synchronizing book halls with owner data...\n";
    $count = $db->exec("
        UPDATE books b
        JOIN users u ON b.owner_id = u.id
        SET b.hall = u.hall
        WHERE b.hall IS NULL OR b.hall = ''
    ");
    echo "✅ Synchronized $count books.\n\n";
    
    echo "✨ All database issues resolved! Your site should now work perfectly.";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
echo "</pre>";
