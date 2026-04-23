# 📚 OpenShelf — Community Library Management System

[![Version](https://img.shields.io/badge/version-2.3.0-blue.svg)](https://github.com/Asraf1270/OpenShelf/releases/tag/v2.3.0)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1.svg)](https://www.mysql.com/)

**OpenShelf** is a modern, open-source library management system designed for communities, universities, and book clubs. It empowers users to share, borrow, and manage books effortlessly through a **premium, glassmorphic interface** that feels alive and responsive.

---

## ✨ What's New in v2.3.0

**OpenShelf v2.3.0** delivers a streamlined administrative experience and enhanced system stability:

- 🛡️ **Simplified Admin Authentication:** Replaced OTP-based admin login with a robust, standard email and password authentication system.
- 🔑 **Admin Profile Management:** Added a functional "Change Password" feature directly within the admin profile section for improved account security.
- 💾 **Reliable Backup System:** Audited and verified the integrity of the full backup and restore processes to ensure flawless data preservation.
- 🎨 **CSS Compatibility:** Fixed background-clip CSS property warnings to ensure strict cross-browser compliance.

---

## 🌟 Key Features

### 👤 For Users
- **Secure Registration:** Email-based authentication with university domain verification.
- **Glassmorphic Catalog:** Browse books through an elegant, interactive grid layout.
- **Easy Sharing:** Add your own books with custom cover uploads in seconds.
- **Smart Requests:** Request books directly with automated email alerts to the owner.
- **My Borrowed Books:** Track active borrows with visual due-date progress and one-click returns.
- **Password Recovery:** Secure "Forget Password" flow with email + phone verification and OTP codes.
- **Personalized Profiles:** A beautiful split-screen UI to manage your shared books and reading history.
- **Real-time Notifications:** Stay updated with in-app alerts for borrows, approvals, and community news.
- 🌙 **Native Dark Mode:** Toggle between light and dark themes with a persistent interface that remembers your preference.
- 📱 **Installable PWA:** Add OpenShelf to your mobile or desktop home screen for a standalone app experience.

### 🛡️ For Administrators
- **Dynamic Dashboard:** Real-time statistics with interactive charts and system health monitoring.
- **Mobile-First Admin Panel:** Fully responsive management interface optimized for smartphones and tablets.
- **Full Moderation:** Manage users, verify book entries, and oversee borrow requests.
- **Announcement Engine:** Broadcast community-wide updates with premium styling, scheduling, and delivery via email and in-app alerts.
- **Audit Logs:** Track every system activity for complete transparency and security.
- **One-Click Backups:** Automated data safety tools to keep your library's information secure.
- 📊 **Advanced Reports:** Export comprehensive CSV reports for users, books, and borrow history.

---

## 🛠️ Tech Stack
- **Backend:** PHP 7.4+ (Clean, modular architecture)
- **Database:** MySQL 5.7+ (Scalable relational storage)
- **Frontend:** Modern HTML5, CSS3 (Custom properties/variables), Vanilla JavaScript
- **Styling:** Premium Glassmorphism, HSL color system, fluid animations
- **Communication:** PHPMailer with SMTP integration (Brevo/SendGrid/Gmail)
- **Architecture:** Progressive Web App (PWA) with Service Worker support

---

## 📋 System Requirements
- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Server:** Apache/Nginx (with PHP support)
- **Permissions:** Read/write access for `/data`, `/uploads`, `/logs`, `/sessions`, and `/backups`
- **Mail:** SMTP credentials for automated notifications

---

## 🚀 Installation & Setup

### 1. Clone the Repository
```bash
git clone https://github.com/Asraf1270/OpenShelf.git
cd OpenShelf
```

### 2. Configure Environment
Create a `.env` file in the root directory by copying the example:
```bash
cp .env.example .env
```
Open `.env` and fill in your details:
- **Database Settings:** DB Host, DB Name, DB User, DB Password.
- **SMTP Settings:** Host, Port, Secure, Username, Password.
- **Email Settings:** From Address, From Name, Reply-To, and Admin Email.
- **App Settings:** App Name, URL, and Debug mode.

### 3. Database Migration
Import the provided schema into your MySQL server:
```bash
mysql -u your_username -p your_database < data/schema.sql
```

### 4. Set File Permissions
Ensure the web server can write to the following directories:
```bash
chmod 755 data/ uploads/ logs/ sessions/ backups/
chmod 644 data/*.json uploads/book_cover/ uploads/profile/
```

### 5. Launch
Navigate to your server's URL. For the admin panel, visit `/admin/`.

---

## 📁 Directory Structure
```
openshelf/
├── admin/            # Comprehensive management dashboard
├── api/              # Dynamic endpoints for frontend interactions
├── assets/           # Premium CSS, JS, design tokens, and branding assets
├── config/           # Centralized configuration (Database, Mail, App)
├── data/             # SQL schema and configuration files
├── emails/           # Email notification templates
├── includes/         # Shared UI components and database singleton
├── lib/              # Core libraries (Mailer, utilities)
├── my-borrowed/      # User's active borrowed books tracker
├── uploads/          # User-uploaded covers and profile media
├── backups/          # Automatically generated system snapshots
└── vendor/           # Composer dependencies
```

---

## 🧪 Security Standards
- ✅ **SQL Injection Protection:** High-security PDO prepared statements for all database queries.
- ✅ **Domain-Locked Registration:** Prevent unauthorized access by restricting email domains.
- ✅ **Encrypted Sessions:** Secure user state management.
- ✅ **Environment Protection:** Sensitive database credentials and passwords kept in `.env`.
- ✅ **OTP-Based Recovery:** Two-factor password reset prevents unauthorized account takeover.
- ✅ **Data Separation:** Core media uploads isolated from critical system files.

---

## 🤝 Contributing
Contributions are welcome! Whether it's reporting bugs, suggesting features, or submitting pull requests, we value community input. Check out our contributing guidelines for more details.

---

## 📄 License
This project is open-source and released under the **MIT License**.

---

## 📞 Support & Community
- **Email:** support@openshelf.free.nf
- **Feedback:** Use the built-in `/contact.php` form
- **FAQ:** Check `/faq.php` for common questions

---
**OpenShelf** — Empowering communities, one shared book at a time. 📚✨
