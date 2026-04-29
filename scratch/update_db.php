<?php
require_once __DIR__ . '/../includes/db.php';

try {
    $db = getDB();
    
    echo "Starting database migration...\n";
    
    // Add hall column
    echo "Adding 'hall' column to 'users' table...\n";
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS hall CHAR(1) DEFAULT NULL AFTER room_number");
    
    // Add otp_code column
    echo "Adding 'otp_code' column to 'users' table...\n";
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS otp_code VARCHAR(6) DEFAULT NULL AFTER password_hash");
    
    // Add otp_expiry column
    echo "Adding 'otp_expiry' column to 'users' table...\n";
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS otp_expiry DATETIME DEFAULT NULL AFTER otp_code");
    
    // Change default status to 'unverified'
    echo "Updating default status for new users...\n";
    $db->exec("ALTER TABLE users MODIFY COLUMN status VARCHAR(20) DEFAULT 'unverified'");
    
    echo "Migration completed successfully!\n";
    
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}
