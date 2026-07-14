<?php
/**
 * OpenShelf Migration: Wishlist Table
 *
 * Creates the `wishlist` table so logged-in users can add unavailable
 * books to their wishlist and receive an email when the book becomes
 * available again.
 *
 * Usage: php data/migrate_wishlist_table.php
 */

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $db = getDB();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "Creating wishlist table...\n";

$sql = "
CREATE TABLE IF NOT EXISTS `wishlist` (
  `id`         int(11)      NOT NULL AUTO_INCREMENT,
  `user_id`    varchar(16)  NOT NULL,
  `book_id`    varchar(50)  NOT NULL,
  `book_title` varchar(255) DEFAULT NULL,
  `notified`   tinyint(1)   DEFAULT 0 COMMENT '1 = email already sent for this availability window',
  `created_at` datetime     DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user_book` (`user_id`, `book_id`),
  KEY `book_id` (`book_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $db->exec($sql);
    echo "✓ Table 'wishlist' created successfully (or already exists).\n";
} catch (PDOException $e) {
    die("Error creating 'wishlist' table: " . $e->getMessage() . "\n");
}

echo "\nMigration complete!\n";
