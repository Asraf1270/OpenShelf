-- OpenShelf Advanced Query Optimization - Database Indexing
-- Adding indexes to frequently searched and filtered columns

START TRANSACTION;

-- Indexing for 'books' table
-- status: heavily used in public discovery and admin filters
-- category: used for filtering
-- created_at: used for sorting (newest first)
-- title and author: used for search (FULLTEXT for better performance)
ALTER TABLE `books` ADD INDEX `idx_status` (`status`);
ALTER TABLE `books` ADD INDEX `idx_category` (`category`);
ALTER TABLE `books` ADD INDEX `idx_created_at` (`created_at`);
ALTER TABLE `books` ADD FULLTEXT `idx_fulltext_title_author` (`title`, `author`);

-- Indexing for 'users' table
-- status and role: used for admin filters
-- created_at: used for sorting
ALTER TABLE `users` ADD INDEX `idx_status` (`status`);
ALTER TABLE `users` ADD INDEX `idx_role` (`role`);
ALTER TABLE `users` ADD INDEX `idx_created_at` (`created_at`);
ALTER TABLE `users` ADD FULLTEXT `idx_fulltext_name_email` (`name`, `email`);

-- Indexing for 'borrow_requests' table
-- status: used for filtering
-- request_date: used for sorting
ALTER TABLE `borrow_requests` ADD INDEX `idx_status` (`status`);
ALTER TABLE `borrow_requests` ADD INDEX `idx_request_date` (`request_date`);

COMMIT;
