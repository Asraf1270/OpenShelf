<?php
/**
 * OpenShelf Migration Script: Notifications, Bio & Remember Tokens
 * 
 * Migrates personal user data from individual JSON files in the users/ directory
 * to the MySQL database tables.
 * 
 * Usage: php data/migrate_notifications_and_tokens.php
 */

require_once dirname(__DIR__) . '/includes/db.php';

try {
    $db = getDB();
} catch (Exception $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

echo "Starting Notifications & Remember Tokens Migration...\n";

// 1. Ensure schema updates
try {
    // Add bio column to users table
    $db->exec("ALTER TABLE `users` ADD COLUMN `bio` TEXT DEFAULT NULL AFTER `profile_pic`");
    echo "✓ Added 'bio' column to 'users' table (or it already existed).\n";
} catch (PDOException $e) {
    // Ignore error if column already exists (SQLState 42S21 / Error Code 1060)
    if ($e->getCode() == '42S21' || strpos($e->getMessage(), 'Duplicate column') !== false) {
        echo "✓ 'bio' column already exists in 'users' table.\n";
    } else {
        echo "⚠ Warning while adding 'bio' column: " . $e->getMessage() . "\n";
    }
}

// Create notifications table
$createNotificationsTable = "
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` varchar(50) NOT NULL,
  `user_id` varchar(16) NOT NULL,
  `type` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `link` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `read_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `expires_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_notifications_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $db->exec($createNotificationsTable);
    echo "✓ Table 'notifications' created successfully or already exists.\n";
} catch (PDOException $e) {
    die("Error creating 'notifications' table: " . $e->getMessage() . "\n");
}

// Create remember_tokens table
$createRememberTokensTable = "
CREATE TABLE IF NOT EXISTS `remember_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(16) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expiry` int(11) NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `user_agent` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_remember_tokens_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
";

try {
    $db->exec($createRememberTokensTable);
    echo "✓ Table 'remember_tokens' created successfully or already exists.\n";
} catch (PDOException $e) {
    die("Error creating 'remember_tokens' table: " . $e->getMessage() . "\n");
}

// 2. Scan users directory for JSON files
$usersDir = dirname(__DIR__) . '/users';
if (!file_exists($usersDir)) {
    die("Error: 'users/' directory does not exist.\n");
}

$files = glob($usersDir . '/*.json');
echo "Found " . count($files) . " user JSON files to process.\n";

$usersUpdated = 0;
$notificationsMigrated = 0;
$tokensMigrated = 0;

$updateBioStmt = $db->prepare("UPDATE `users` SET `bio` = ? WHERE `id` = ?");
$insertNotifStmt = $db->prepare("
    INSERT IGNORE INTO `notifications` 
    (id, user_id, type, title, message, link, is_read, read_at, created_at, expires_at) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$insertTokenStmt = $db->prepare("
    INSERT INTO `remember_tokens` 
    (user_id, token, expiry, created_at, user_agent, ip_address) 
    VALUES (?, ?, ?, ?, ?, ?)
");

foreach ($files as $file) {
    $userId = pathinfo($file, PATHINFO_FILENAME);
    $data = json_decode(file_get_contents($file), true);
    
    if (!$data) {
        echo "⚠ Warning: Failed to parse JSON for user ID: $userId\n";
        continue;
    }
    
    // Check if user exists in the database
    $userCheck = $db->prepare("SELECT 1 FROM `users` WHERE `id` = ?");
    $userCheck->execute([$userId]);
    if (!$userCheck->fetch()) {
        echo "⚠ Warning: User $userId does not exist in 'users' table. Skipping.\n";
        continue;
    }
    
    // Migrate Bio
    $bio = $data['personal_info']['bio'] ?? null;
    if ($bio !== null) {
        $updateBioStmt->execute([$bio, $userId]);
    }
    
    // Migrate Notifications
    $notifications = $data['notifications'] ?? [];
    foreach ($notifications as $n) {
        if (empty($n['id'])) continue;
        
        $isRead = isset($n['is_read']) ? ($n['is_read'] ? 1 : 0) : 0;
        $readAt = $n['read_at'] ?? null;
        if (!$readAt && $isRead) {
            $readAt = date('Y-m-d H:i:s');
        }
        
        $createdAt = $n['created_at'] ?? date('Y-m-d H:i:s');
        $expiresAt = $n['expires_at'] ?? null;
        
        $insertNotifStmt->execute([
            $n['id'],
            $userId,
            $n['type'] ?? 'info',
            $n['title'] ?? '',
            $n['message'] ?? '',
            $n['link'] ?? null,
            $isRead,
            $readAt,
            $createdAt,
            $expiresAt
        ]);
        $notificationsMigrated++;
    }
    
    // Migrate Remember Tokens
    $tokens = $data['remember_tokens'] ?? [];
    foreach ($tokens as $t) {
        if (empty($t['token'])) continue;
        
        $createdAt = $t['created_at'] ?? date('Y-m-d H:i:s');
        
        $insertTokenStmt->execute([
            $userId,
            $t['token'],
            $t['expiry'],
            $createdAt,
            $t['user_agent'] ?? null,
            $t['ip_address'] ?? null
        ]);
        $tokensMigrated++;
    }
    
    $usersUpdated++;
}

echo "\nMigration Completed!\n";
echo "- Users updated: $usersUpdated\n";
echo "- Notifications migrated: $notificationsMigrated\n";
echo "- Remember tokens migrated: $tokensMigrated\n";
echo "Note: The files in the 'users/' directory are preserved for backup. Once verified, you can delete them.\n";
