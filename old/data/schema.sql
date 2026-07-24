-- OpenShelf Database Schema v2.0
-- Created for migration from JSON to MySQL

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------
-- Table structure for table `users`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `users` (
  `id` varchar(16) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `department` varchar(255) DEFAULT NULL,
  `session` varchar(50) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `room_number` varchar(50) DEFAULT NULL,
  `hall` char(1) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `otp_code` varchar(6) DEFAULT NULL,
  `otp_expiry` datetime DEFAULT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `role` varchar(20) DEFAULT 'user',
  `profile_pic` varchar(255) DEFAULT 'default-avatar.jpg',
  `bio` text DEFAULT NULL,
  `status` varchar(20) DEFAULT 'unverified',
  `rejection_reason` text DEFAULT NULL,
  `verified_by` varchar(50) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`),
  KEY `idx_status` (`status`),
  KEY `idx_role` (`role`),
  KEY `idx_created_at` (`created_at`),
  FULLTEXT KEY `idx_fulltext_name_email` (`name`, `email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `books`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `books` (
  `id` varchar(10) NOT NULL,
  `title` varchar(255) NOT NULL,
  `author` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `category` varchar(100) DEFAULT NULL,
  `condition` varchar(50) DEFAULT NULL,
  `cover_image` varchar(255) DEFAULT NULL,
  `owner_id` varchar(16) NOT NULL,
  `hall` char(1) DEFAULT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'available',
  `views` int(11) DEFAULT 0,
  `times_borrowed` int(11) DEFAULT 0,
  `isbn` varchar(20) DEFAULT NULL,
  `publication_year` varchar(10) DEFAULT NULL,
  `publisher` varchar(255) DEFAULT NULL,
  `pages` varchar(20) DEFAULT NULL,
  `language` varchar(50) DEFAULT 'English',
  `tags` text DEFAULT NULL, -- JSON string
  `reviews` longtext DEFAULT NULL, -- JSON string
  `comments` longtext DEFAULT NULL, -- JSON string
  `rating` decimal(3,2) DEFAULT 0.00,
  `rating_count` int(11) DEFAULT 0,
  `status_updated_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  KEY `idx_books_hall` (`hall`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_created_at` (`created_at`),
  FULLTEXT KEY `idx_fulltext_title_author` (`title`, `author`),
  CONSTRAINT `fk_book_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `borrow_requests`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `borrow_requests` (
  `id` varchar(30) NOT NULL,
  `book_id` varchar(10) NOT NULL,
  `book_title` varchar(255) DEFAULT NULL,
  `book_author` varchar(255) DEFAULT NULL,
  `book_cover` varchar(255) DEFAULT NULL,
  `owner_id` varchar(16) NOT NULL,
  `owner_name` varchar(255) DEFAULT NULL,
  `owner_email` varchar(255) DEFAULT NULL,
  `borrower_id` varchar(16) NOT NULL,
  `borrower_name` varchar(255) DEFAULT NULL,
  `borrower_email` varchar(255) DEFAULT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `request_date` datetime DEFAULT CURRENT_TIMESTAMP,
  `expected_return_date` datetime DEFAULT NULL,
  `duration_days` int(11) DEFAULT NULL,
  `message` text DEFAULT NULL,
  `history` longtext DEFAULT NULL, -- JSON string
  `approved_at` datetime DEFAULT NULL,
  `approved_by` varchar(50) DEFAULT NULL,
  `rejected_at` datetime DEFAULT NULL,
  `rejected_by` varchar(50) DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `actual_return_date` datetime DEFAULT NULL,
  `return_condition` varchar(50) DEFAULT NULL,
  `returned_by` varchar(50) DEFAULT NULL,
  `returned_by_name` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT 0,
  -- Return confirmation (two-step return flow)
  `return_confirmation_token` varchar(64) DEFAULT NULL COMMENT 'Secure token emailed to owner',
  `return_confirmation_status` varchar(20) DEFAULT NULL COMMENT 'pending_owner | confirmed | rejected',
  `return_confirmation_sent_at` datetime DEFAULT NULL,
  `return_confirmed_at` datetime DEFAULT NULL,
  `return_rejected_at` datetime DEFAULT NULL,
  `return_reject_reason` text DEFAULT NULL,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `borrower_id` (`borrower_id`),
  KEY `owner_id` (`owner_id`),
  KEY `idx_status` (`status`),
  KEY `idx_request_date` (`request_date`),
  KEY `idx_return_token` (`return_confirmation_token`),
  CONSTRAINT `fk_request_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_request_borrower` FOREIGN KEY (`borrower_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_request_owner` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `announcements`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `announcements` (
  `id` varchar(50) NOT NULL,
  `title` varchar(255) NOT NULL,
  `content` text NOT NULL,
  `priority` varchar(20) DEFAULT 'info',
  `target` varchar(50) DEFAULT 'all',
  `created_by` varchar(50) DEFAULT NULL,
  `created_by_name` varchar(255) DEFAULT NULL,
  `scheduled_for` datetime DEFAULT NULL,
  `expires_at` datetime DEFAULT NULL,
  `sent_via` text DEFAULT NULL, -- JSON string
  `stats` text DEFAULT NULL, -- JSON string
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `announcement_read_status`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `announcement_read_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `announcement_id` varchar(50) NOT NULL,
  `user_id` varchar(16) NOT NULL,
  `read_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `announce_user` (`announcement_id`,`user_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_read_announcement` FOREIGN KEY (`announcement_id`) REFERENCES `announcements` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_read_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `categories`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `book_count` int(11) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `admins`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `admins` (
  `id` varchar(50) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `role` varchar(20) DEFAULT 'admin',
  `status` varchar(20) DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `login_otps`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `login_otps` (
  `id` varchar(50) NOT NULL,
  `email` varchar(255) NOT NULL,
  `otp_hash` varchar(255) NOT NULL,
  `attempts` int(11) DEFAULT 0,
  `verified` tinyint(1) DEFAULT 0,
  `expires_at` datetime NOT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `reports`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `reports` (
  `id` varchar(30) NOT NULL,
  `user_id` varchar(16) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `type` varchar(50) NOT NULL DEFAULT 'other',
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `admin_notes` text DEFAULT NULL,
  `resolved_by` varchar(50) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reports_status` (`status`),
  KEY `idx_reports_type` (`type`),
  KEY `idx_reports_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `contact_messages`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` varchar(30) NOT NULL,
  `user_id` varchar(16) DEFAULT NULL,
  `name` varchar(255) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `status` varchar(20) DEFAULT 'unread',
  `admin_reply` text DEFAULT NULL,
  `replied_by` varchar(50) DEFAULT NULL,
  `replied_at` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_contact_status` (`status`),
  KEY `idx_contact_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `notifications`
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Table structure for table `remember_tokens`
-- --------------------------------------------------------

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

-- --------------------------------------------------------
-- Table structure for table `support_us`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `support_us` (
  `id` varchar(30) NOT NULL,
  `user_id` varchar(16) NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `user_phone` varchar(20) DEFAULT NULL,
  `user_department` varchar(255) DEFAULT NULL,
  `user_session` varchar(50) DEFAULT NULL,
  `user_room` varchar(50) DEFAULT NULL,
  `provider` varchar(50) NOT NULL,
  `account_number` varchar(50) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(100) NOT NULL,
  `status` varchar(20) DEFAULT 'pending',
  `invoice_number` varchar(50) DEFAULT NULL,
  `approved_at` datetime DEFAULT NULL,
  `approved_by` varchar(50) DEFAULT NULL,
  `submitted_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_support_us_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for table `transactions`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `transactions` (
  `id` varchar(30) NOT NULL,
  `support_us_id` varchar(30) DEFAULT NULL,
  `user_id` varchar(16) NOT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `provider` varchar(50) DEFAULT NULL,
  `account_number` varchar(50) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `transaction_id` varchar(100) DEFAULT NULL,
  `invoice_number` varchar(50) NOT NULL,
  `status` varchar(20) DEFAULT 'completed',
  `created_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `support_us_transaction` (`support_us_id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `fk_transaction_support` FOREIGN KEY (`support_us_id`) REFERENCES `support_us` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_transaction_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;

-- --------------------------------------------------------
-- Table structure for table `wishlist`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `wishlist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(16) NOT NULL,
  `book_id` varchar(10) NOT NULL,
  `book_title` varchar(255) DEFAULT NULL,
  `notified` tinyint(1) DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `user_book` (`user_id`,`book_id`),
  KEY `book_id` (`book_id`),
  CONSTRAINT `fk_wishlist_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_wishlist_book` FOREIGN KEY (`book_id`) REFERENCES `books` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
