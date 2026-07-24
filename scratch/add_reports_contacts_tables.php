<?php
/**
 * Migration Script: Add reports and contact_messages tables.
 * Run from the browser or CLI to create the new tables safely.
 */

require_once __DIR__ . '/../includes/db.php';

echo "<pre>";
echo "Starting reports and contact_messages table migration...\n";

try {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS reports (
        id VARCHAR(30) NOT NULL,
        user_id VARCHAR(16) DEFAULT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        type VARCHAR(50) NOT NULL DEFAULT 'other',
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        admin_notes TEXT DEFAULT NULL,
        resolved_by VARCHAR(50) DEFAULT NULL,
        resolved_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_reports_status (status),
        KEY idx_reports_type (type),
        KEY idx_reports_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "✅ Created or verified reports table.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "ℹ️ reports table already exists.\n";
    } else {
        echo "❌ Error creating reports table: " . $e->getMessage() . "\n";
    }
}

try {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id VARCHAR(30) NOT NULL,
        user_id VARCHAR(16) DEFAULT NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        status VARCHAR(20) DEFAULT 'unread',
        admin_reply TEXT DEFAULT NULL,
        replied_by VARCHAR(50) DEFAULT NULL,
        replied_at DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_contact_status (status),
        KEY idx_contact_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "✅ Created or verified contact_messages table.\n";

} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'already exists') !== false) {
        echo "ℹ️ contact_messages table already exists.\n";
    } else {
        echo "❌ Error creating contact_messages table: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration finished.\n";
echo "</pre>";
