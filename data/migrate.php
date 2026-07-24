<?php
/**
 * OpenShelf Migration Script
 * 
 * Migrates data from JSON files to MySQL database tables.
 * 
 * Usage: php migrate_json_to_mysql.php
 */

require_once dirname(__DIR__) . '/includes/db.php';

// Check if tables exist by trying to describe one
try {
    $db = getDB();
    $db->query("SELECT 1 FROM users LIMIT 1");
} catch (Exception $e) {
    die("Database tables not found. Please run data/schema.sql first.\n");
}

echo "Starting migration...\n";

// 1. Migrate Users
echo "Migrating users...\n";
$usersData = json_decode(file_get_contents(__DIR__ . '/users.json'), true);
if ($usersData) {
    $stmt = $db->prepare("INSERT IGNORE INTO users (id, name, email, department, session, phone, room_number, password_hash, verified, role, profile_pic, created_at, updated_at, last_login, status, rejection_reason, verified_by, verified_at) 
                          VALUES (:id, :name, :email, :department, :session, :phone, :room_number, :password_hash, :verified, :role, :profile_pic, :created_at, :updated_at, :last_login, :status, :rejection_reason, :verified_by, :verified_at)");
    
    foreach ($usersData as $user) {
        $stmt->execute([
            ':id' => $user['id'],
            ':name' => $user['name'],
            ':email' => $user['email'],
            ':department' => $user['department'] ?? null,
            ':session' => $user['session'] ?? null,
            ':phone' => $user['phone'],
            ':room_number' => $user['room_number'] ?? null,
            ':password_hash' => $user['password_hash'],
            ':verified' => isset($user['verified']) ? ($user['verified'] ? 1 : 0) : 0,
            ':role' => $user['role'] ?? 'user',
            ':profile_pic' => $user['profile_pic'] ?? 'default-avatar.jpg',
            ':created_at' => $user['created_at'] ?? date('Y-m-d H:i:s'),
            ':updated_at' => $user['updated_at'] ?? date('Y-m-d H:i:s'),
            ':last_login' => $user['last_login'] ?? null,
            ':status' => $user['status'] ?? 'active',
            ':rejection_reason' => $user['rejection_reason'] ?? '',
            ':verified_by' => $user['verified_by'] ?? null,
            ':verified_at' => $user['verified_at'] ?? null,
        ]);
    }
}

// 2. Migrate Books
echo "Migrating books...\n";
$booksData = json_decode(file_get_contents(__DIR__ . '/books.json'), true);
if ($booksData) {
    $stmt = $db->prepare("INSERT IGNORE INTO books (id, title, author, description, category, `condition`, cover_image, owner_id, owner_name, status, created_at, updated_at, views, times_borrowed, isbn, publication_year, publisher, pages, language, tags, reviews, comments, status_updated_by) 
                          VALUES (:id, :title, :author, :description, :category, :condition, :cover_image, :owner_id, :owner_name, :status, :created_at, :updated_at, :views, :times_borrowed, :isbn, :publication_year, :publisher, :pages, :language, :tags, :reviews, :comments, :status_updated_by)");
    
    foreach ($booksData as $book) {
        $stmt->execute([
            ':id' => $book['id'],
            ':title' => $book['title'],
            ':author' => $book['author'],
            ':description' => $book['description'] ?? null,
            ':category' => $book['category'] ?? null,
            ':condition' => $book['condition'] ?? null,
            ':cover_image' => $book['cover_image'] ?? null,
            ':owner_id' => $book['owner_id'],
            ':owner_name' => $book['owner_name'] ?? null,
            ':status' => $book['status'] ?? 'available',
            ':created_at' => $book['created_at'] ?? date('Y-m-d H:i:s'),
            ':updated_at' => $book['updated_at'] ?? date('Y-m-d H:i:s'),
            ':views' => $book['views'] ?? 0,
            ':times_borrowed' => $book['times_borrowed'] ?? 0,
            ':isbn' => $book['isbn'] ?? null,
            ':publication_year' => $book['publication_year'] ?? null,
            ':publisher' => $book['publisher'] ?? null,
            ':pages' => $book['pages'] ?? null,
            ':language' => $book['language'] ?? 'English',
            ':tags' => json_encode($book['tags'] ?? []),
            ':reviews' => json_encode($book['reviews'] ?? []),
            ':comments' => json_encode($book['comments'] ?? []),
            ':status_updated_by' => $book['status_updated_by'] ?? null,
        ]);
    }
}

// 3. Migrate Borrow Requests
echo "Migrating borrow requests...\n";
$requestsData = json_decode(file_get_contents(__DIR__ . '/borrow_requests.json'), true);
if ($requestsData) {
    $stmt = $db->prepare("INSERT IGNORE INTO borrow_requests (id, book_id, book_title, book_author, book_cover, owner_id, owner_name, owner_email, borrower_id, borrower_name, borrower_email, status, request_date, expected_return_date, duration_days, message, history, updated_at, approved_at, approved_by, rejected_at, rejected_by, rejection_reason, notes, returned_at, actual_return_date, return_condition, returned_by, returned_by_name, rating) 
                          VALUES (:id, :book_id, :book_title, :book_author, :book_cover, :owner_id, :owner_name, :owner_email, :borrower_id, :borrower_name, :borrower_email, :status, :request_date, :expected_return_date, :duration_days, :message, :history, :updated_at, :approved_at, :approved_by, :rejected_at, :rejected_by, :rejection_reason, :notes, :returned_at, :actual_return_date, :return_condition, :returned_by, :returned_by_name, :rating)");
    
    foreach ($requestsData as $req) {
        $stmt->execute([
            ':id' => $req['id'],
            ':book_id' => $req['book_id'],
            ':book_title' => $req['book_title'] ?? null,
            ':book_author' => $req['book_author'] ?? null,
            ':book_cover' => $req['book_cover'] ?? null,
            ':owner_id' => $req['owner_id'],
            ':owner_name' => $req['owner_name'] ?? null,
            ':owner_email' => $req['owner_email'] ?? null,
            ':borrower_id' => $req['borrower_id'],
            ':borrower_name' => $req['borrower_name'] ?? null,
            ':borrower_email' => $req['borrower_email'] ?? null,
            ':status' => $req['status'] ?? 'pending',
            ':request_date' => $req['request_date'] ?? date('Y-m-d H:i:s'),
            ':expected_return_date' => $req['expected_return_date'] ?? null,
            ':duration_days' => $req['duration_days'] ?? null,
            ':message' => $req['message'] ?? null,
            ':history' => json_encode($req['history'] ?? []),
            ':updated_at' => $req['updated_at'] ?? date('Y-m-d H:i:s'),
            ':approved_at' => $req['approved_at'] ?? null,
            ':approved_by' => $req['approved_by'] ?? null,
            ':rejected_at' => $req['rejected_at'] ?? null,
            ':rejected_by' => $req['rejected_by'] ?? null,
            ':rejection_reason' => $req['rejection_reason'] ?? null,
            ':notes' => $req['notes'] ?? null,
            ':returned_at' => $req['returned_at'] ?? null,
            ':actual_return_date' => $req['actual_return_date'] ?? null,
            ':return_condition' => $req['return_condition'] ?? null,
            ':returned_by' => $req['returned_by'] ?? null,
            ':returned_by_name' => $req['returned_by_name'] ?? null,
            ':rating' => $req['rating'] ?? 0,
        ]);
    }
}

// 4. Migrate Announcements
echo "Migrating announcements...\n";
$announcementsFile = json_decode(file_get_contents(__DIR__ . '/announcements.json'), true);
if ($announcementsFile) {
    if (isset($announcementsFile['announcements'])) {
         $stmt = $db->prepare("INSERT IGNORE INTO announcements (id, title, content, priority, target, created_by, created_by_name, scheduled_for, expires_at, sent_via, stats, created_at) 
                              VALUES (:id, :title, :content, :priority, :target, :created_by, :created_by_name, :scheduled_for, :expires_at, :sent_via, :stats, :created_at)");
        
        foreach ($announcementsFile['announcements'] as $ann) {
            $stmt->execute([
                ':id' => $ann['id'],
                ':title' => $ann['title'],
                ':content' => $ann['content'],
                ':priority' => $ann['priority'] ?? 'info',
                ':target' => $ann['target'] ?? 'all',
                ':created_by' => $ann['created_by'] ?? null,
                ':created_by_name' => $ann['created_by_name'] ?? null,
                ':scheduled_for' => $ann['scheduled_for'] ?? null,
                ':expires_at' => $ann['expires_at'] ?? null,
                ':sent_via' => json_encode($ann['sent_via'] ?? []),
                ':stats' => json_encode($ann['stats'] ?? []),
                ':created_at' => $ann['created_at'] ?? date('Y-m-d H:i:s'),
            ]);
        }
    }
    
    if (isset($announcementsFile['user_read_status'])) {
        $stmt = $db->prepare("INSERT IGNORE INTO announcement_read_status (announcement_id, user_id, read_at) 
                              VALUES (:announcement_id, :user_id, :read_at)");
        
        foreach ($announcementsFile['user_read_status'] as $status) {
            $stmt->execute([
                ':announcement_id' => $status['announcement_id'],
                ':user_id' => $status['user_id'],
                ':read_at' => $status['read_at'] ?? date('Y-m-d H:i:s'),
            ]);
        }
    }
}

// 5. Migrate Categories
echo "Migrating categories...\n";
$categoriesData = json_decode(file_get_contents(__DIR__ . '/categories.json'), true);
if ($categoriesData) {
    $stmt = $db->prepare("INSERT IGNORE INTO categories (id, name, book_count) VALUES (:id, :name, :book_count)");
    foreach ($categoriesData as $cat) {
        $stmt->execute([
            ':id' => $cat['id'],
            ':name' => $cat['name'],
            ':book_count' => $cat['count'] ?? 0
        ]);
    }
}

// 6. Migrate Admins
echo "Migrating admins...\n";
$adminsData = json_decode(file_get_contents(__DIR__ . '/admins.json'), true);
if ($adminsData) {
    $stmt = $db->prepare("INSERT IGNORE INTO admins (id, username, password_hash, name, email, role, last_login, created_at) 
                          VALUES (:id, :username, :password_hash, :name, :email, :role, :last_login, :created_at)");
    foreach ($adminsData as $admin) {
        $stmt->execute([
            ':id' => $admin['id'],
            ':username' => $admin['username'],
            ':password_hash' => $admin['password_hash'],
            ':name' => $admin['name'],
            ':email' => $admin['email'],
            ':role' => $admin['role'] ?? 'admin',
            ':last_login' => $admin['last_login'] ?? null,
            ':created_at' => $admin['created_at'] ?? date('Y-m-d H:i:s')
        ]);
    }
}

echo "Migration completed successfully!\n";
