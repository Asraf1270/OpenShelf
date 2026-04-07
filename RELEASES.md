# 🚀 Release Notes: OpenShelf v2.0.0 — The Database Evolution

We are thrilled to announce the release of **OpenShelf v2.0.0**, our most significant architectural update yet. This version marks a major transition from flat JSON file storage to a robust, scalable **MySQL Database**, bringing unprecedented performance, security, and reliability to your community library.

---

### 🗄️ What's New: The Database Integration

The core of v2.0.0 is a complete backend overhaul. By shifting to a relational database, we've laid the groundwork for future-proof growth:

- **MySQL Power:** Replaced all core JSON data handlers (Users, Books, Requests, Notifications) with a high-performance MySQL schema.
- **Enhanced Data Integrity:** Implemented foreign key constraints and ACID transactions to ensure your library data remains consistent and corruption-free.
- **Lightning Speed:** Dramatically reduced response times for large book catalogs and complex search queries through optimized SQL indexing.
- **Industrial-Grade Security:** Improved protection against data collisions and unauthorized file access by centralizing data management in a secure database layer.

---

### 🎨 Refined Administrative Tools

To complement the new database, we've updated the administrator experience:

- **Enhanced Dashboard:** Real-time statistics are now pulled directly from SQL queries, providing instant insights into library activity.
- **Robust Reporting:** Generate and export CSV reports for users, books, and borrow history with improved accuracy.
- **Optimized Backups:** The backup system now captures both the remaining configuration files and a structured export of the database state.
- **Advanced Audit Logs:** System activities are now more thoroughly tracked within the database for better transparency.

---

### 🛠️ Technical Improvements

- **PDO-Powered Backend:** Standardized database interactions using PHP Data Objects (PDO) with prepared statements for top-tier security against SQL injection.
- **Scalable Notification System:** Notifications are now managed more efficiently, supporting larger user bases without file-system overhead.
- **Simplified Configuration:** Centralized database settings in `config/database.php` for easier environment-based deployments.

---

### 📦 How to Upgrade

1. **Backup:** Always backup your existing `/data` directory before proceeding.
2. **Database Setup:** Import the provided `data/schema.sql` into your MySQL server.
3. **Configure:** Update your `.env` file with your new database credentials.
4. **Migrate:** Run the included migration script (if applicable) or start fresh with the new relational structure.

---

**OpenShelf v2.0.0** is more than just an update — it's a new foundation for your community. Happy sharing! 📚✨
