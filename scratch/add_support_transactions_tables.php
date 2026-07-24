<?php
/**
 * Migration Script: Add support_us and transactions tables.
 * Run from the browser or CLI to create the new tables safely.
 */

require_once __DIR__ . '/../includes/db.php';

echo "<pre>";
echo "Starting support and transaction table migration...\n";

try {
    $db = getDB();

    $db->exec("CREATE TABLE IF NOT EXISTS support_us (
        id VARCHAR(30) NOT NULL,
        user_id VARCHAR(16) NOT NULL,
        user_name VARCHAR(255) DEFAULT NULL,
        user_email VARCHAR(255) DEFAULT NULL,
        user_phone VARCHAR(20) DEFAULT NULL,
        user_department VARCHAR(255) DEFAULT NULL,
        user_session VARCHAR(50) DEFAULT NULL,
        user_room VARCHAR(50) DEFAULT NULL,
        provider VARCHAR(50) NOT NULL,
        account_number VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        transaction_id VARCHAR(100) NOT NULL,
        status VARCHAR(20) DEFAULT 'pending',
        invoice_number VARCHAR(50) DEFAULT NULL,
        approved_at DATETIME DEFAULT NULL,
        approved_by VARCHAR(50) DEFAULT NULL,
        submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "✅ Created or verified support_us table.\n";

    $db->exec("ALTER TABLE support_us ADD CONSTRAINT fk_support_us_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;");
    echo "✅ Added foreign key support_us.user_id -> users.id.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo "ℹ️ support_us table or foreign key already exists.\n";
    } else {
        echo "❌ Error creating support_us table: " . $e->getMessage() . "\n";
    }
}

try {
    $db->exec("CREATE TABLE IF NOT EXISTS transactions (
        id VARCHAR(30) NOT NULL,
        support_us_id VARCHAR(30) DEFAULT NULL,
        user_id VARCHAR(16) NOT NULL,
        user_name VARCHAR(255) DEFAULT NULL,
        user_email VARCHAR(255) DEFAULT NULL,
        provider VARCHAR(50) DEFAULT NULL,
        account_number VARCHAR(50) DEFAULT NULL,
        amount DECIMAL(10,2) NOT NULL,
        transaction_id VARCHAR(100) DEFAULT NULL,
        invoice_number VARCHAR(50) NOT NULL,
        status VARCHAR(20) DEFAULT 'completed',
        created_by VARCHAR(50) DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY support_us_transaction (support_us_id),
        KEY user_id (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
    echo "✅ Created or verified transactions table.\n";

    $db->exec("ALTER TABLE transactions ADD CONSTRAINT fk_transaction_support FOREIGN KEY (support_us_id) REFERENCES support_us(id) ON DELETE SET NULL;");
    echo "✅ Added foreign key transactions.support_us_id -> support_us.id.\n";
    $db->exec("ALTER TABLE transactions ADD CONSTRAINT fk_transaction_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE;");
    echo "✅ Added foreign key transactions.user_id -> users.id.\n";
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate key name') !== false || strpos($e->getMessage(), 'already exists') !== false) {
        echo "ℹ️ transactions table or foreign key already exists.\n";
    } else {
        echo "❌ Error creating transactions table: " . $e->getMessage() . "\n";
    }
}

echo "\nMigration finished.\n";
echo "</pre>";
