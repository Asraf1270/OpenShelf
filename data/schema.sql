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
  `password_hash` varchar(255) NOT NULL,
  `verified` tinyint(1) DEFAULT 0,
  `role` varchar(20) DEFAULT 'user',
  `profile_pic` varchar(255) DEFAULT 'default-avatar.jpg',
  `status` varchar(20) DEFAULT 'active',
  `rejection_reason` text DEFAULT NULL,
  `verified_by` varchar(50) DEFAULT NULL,
  `verified_at` datetime DEFAULT NULL,
  `last_login` datetime DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  UNIQUE KEY `phone` (`phone`)
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
  `status_updated_by` varchar(50) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
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
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `book_id` (`book_id`),
  KEY `borrower_id` (`borrower_id`),
  KEY `owner_id` (`owner_id`),
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

COMMIT;
