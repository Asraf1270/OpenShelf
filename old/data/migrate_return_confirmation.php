<?php
/**
 * Migration: Add Return Confirmation System
 * Adds token-based return confirmation columns to borrow_requests table.
 *
 * Run once: php data/migrate_return_confirmation.php
 */

require_once dirname(__DIR__) . '/includes/db.php';

$db = getDB();

$steps = [
    "ALTER TABLE `borrow_requests`
        ADD COLUMN IF NOT EXISTS `return_confirmation_token` varchar(64) DEFAULT NULL COMMENT 'Secure token emailed to owner to confirm physical receipt'",

    "ALTER TABLE `borrow_requests`
        ADD COLUMN IF NOT EXISTS `return_confirmation_status` varchar(20) DEFAULT NULL COMMENT 'pending_owner | confirmed | rejected'",

    "ALTER TABLE `borrow_requests`
        ADD COLUMN IF NOT EXISTS `return_confirmation_sent_at` datetime DEFAULT NULL COMMENT 'When the confirmation email was sent to owner'",

    "ALTER TABLE `borrow_requests`
        ADD COLUMN IF NOT EXISTS `return_confirmed_at` datetime DEFAULT NULL COMMENT 'When the owner clicked confirm'",

    "ALTER TABLE `borrow_requests`
        ADD COLUMN IF NOT EXISTS `return_rejected_at` datetime DEFAULT NULL COMMENT 'When the owner clicked reject'",

    "ALTER TABLE `borrow_requests`
        ADD COLUMN IF NOT EXISTS `return_reject_reason` text DEFAULT NULL COMMENT 'Optional reason the owner rejected the return'",
];

$errors = [];
foreach ($steps as $i => $sql) {
    try {
        $db->exec($sql);
        echo "✅ Step " . ($i + 1) . " OK\n";
    } catch (PDOException $e) {
        // Duplicate column is fine (already migrated)
        if (strpos($e->getMessage(), 'Duplicate column') !== false || strpos($e->getMessage(), 'already exists') !== false) {
            echo "⏭  Step " . ($i + 1) . " already applied, skipping.\n";
        } else {
            echo "❌ Step " . ($i + 1) . " FAILED: " . $e->getMessage() . "\n";
            $errors[] = $e->getMessage();
        }
    }
}

if (empty($errors)) {
    echo "\n✅ Migration completed successfully.\n";
} else {
    echo "\n⚠️  Migration finished with errors:\n";
    foreach ($errors as $err) {
        echo "  - $err\n";
    }
}
