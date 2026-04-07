# OpenShelf Database Migration Guide

We have successfully finished patching the bugs related to the new database integration! Here is how to complete the setup on your end, connect the database, and run `data/schema.sql`.

## 1. Start MySQL
Ensure that your MySQL service is running within XAMPP. You can start it from the XAMPP Control Panel.

## 2. Configure Database Connection
To connect your application to the new MySQL database, create an environment config file:
1. Make a copy of `.env.example` and name it `.env` in the root folder `/opt/lampp/htdocs/OpenShelf/`.
2. Open the `.env` file and set the correct database credentials:
```ini
DB_HOST=127.0.0.1
DB_NAME=openshelf_db
DB_PASS=
```
*(If your default root MySQL user in XAMPP does not have a password, you can leave it empty.)*

## 3. Create the Database & Run Data Schema
To run `data/schema.sql` and set up the tables:

- **Using phpMyAdmin (Recommended):**
  1. Go to `http://localhost/phpmyadmin` in your browser.
  2. Create a new database named `openshelf_db` with collation `utf8mb4_unicode_ci`.
  3. Select the `openshelf_db` database, click on the **Import** tab.
  4. Choose the file `/opt/lampp/htdocs/OpenShelf/data/schema.sql` and click **Import**.

- **Using Command Line:**
  You can run these commands from your terminal:
```bash
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS openshelf_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
/opt/lampp/bin/mysql -u root openshelf_db < /opt/lampp/htdocs/OpenShelf/data/schema.sql
```

## 4. Migrate Old Data (Required)
I noticed you have the `data/migrate.php` script! This will port all your JSON data into MySQL.
Once the `schema.sql` is imported into your database, open your terminal and run:
```bash
php /opt/lampp/htdocs/OpenShelf/data/migrate.php
```

---

## 🔎 Summary of Bugs Fixed:
I went through the codebase to find any bugs related to the Database upgrade and fixed the following:

- **`data/migrate.php`**: Fixed path resolution issues (`__DIR__` combined with `/data/` was causing incorrect directory lookup for the JSON files).
- **`admin/announcements/index.php`**: Fixed a database query mismatch where the code inserted values correctly into `sent_via_email` and `sent_via_notification` which did not exist as columns in the new schema (they are now stored as a single `sent_via` JSON field). The stats field had a similar issue. They are now properly JSON encoded before insert!
- **`return-book/index.php`**: This entire functionality was missed during the database migration and it was still reading from & updating `books.json` and `borrow_requests.json`. This was completely rewritten to execute MySQL PDO Statements on the tables to match `add-book` and the rest of the site.
- **`api/feed.php`**: Rewritten to pull feeds and activities from MySQL instead of JSON files.

Everything should be smooth sailing now. Reach out if you notice any other issues while launching v2!
