# 📚 OpenShelf — Community Library Management System

[![Version](https://img.shields.io/badge/version-3.2.0-blue.svg)](https://github.com/Asraf1270/OpenShelf/releases/tag/v3.2.0)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-4479A1.svg)](https://www.mysql.com/)
[![PWA](https://img.shields.io/badge/PWA-Enabled-5A0FC8.svg)](https://web.dev/progressive-web-apps/)

**OpenShelf** is a modern, open-source community library management system designed for universities, halls, and book clubs. It empowers users to share, borrow, and manage books effortlessly through a **premium, glassmorphic interface** built for mobile-first experiences — installable as a Progressive Web App.

---

## 🌟 What is OpenShelf?

OpenShelf transforms the way communities share knowledge. Instead of books collecting dust on personal shelves, members can list them on the platform and lend them to fellow community members. A student can discover a textbook, request it from a peer, and track the entire borrowing lifecycle — all from their phone.

The platform serves two primary audiences:

- **Community Members** — Browse, request, borrow, and return books with full email notifications and in-app alerts.
- **Administrators** — Manage users, books, requests, reports, announcements, and financial transactions from a dedicated admin panel.

---

## ✨ Core Features

### 👤 User Features

| Feature | Description |
|---|---|
| 📖 **Book Discovery** | Infinite scroll catalog with live search, category filters, and sorting |
| 🔍 **Smart Search** | Real-time filtering by title, author, category, and availability |
| ➕ **Add Books** | List personal books with custom cover uploads and full metadata |
| 📬 **Borrow Requests** | Request books with a custom message; owner gets instant email alert |
| 🔄 **Two-Step Returns** | Borrower initiates return; owner confirms or rejects via a secure email link |
| ❤️ **Wishlist** | Wishlist unavailable books; get notified by email the moment one becomes free |
| 🔔 **Notifications** | In-app alerts for borrows, approvals, rejections, returns, and announcements |
| 👤 **User Profiles** | Public profiles showing shared books, borrow history, bio, and contact info |
| ✏️ **Edit Profile** | Update name, department, hall, room number, bio, and profile picture |
| 🔐 **Password Recovery** | Secure multi-step OTP flow via email + phone verification |
| 💳 **Support Us** | Donate via bKash, Nagad, or Rocket with one-click copy & TrxID submission |
| 📱 **PWA Support** | Installable on Android/iOS/Desktop with offline fallback page |
| 📢 **Announcements** | Receive community-wide broadcasts in-app and via email |
| 🌗 **Dark / Light Mode** | Full dark mode support across all pages and components |

### 🛡️ Admin Features

| Feature | Description |
|---|---|
| 📊 **Dashboard** | Real-time stats with interactive charts for books, users, and requests |
| 👥 **User Management** | View, verify, suspend, and delete users with role control |
| 📚 **Book Moderation** | Review, edit, remove, and manage all listed books |
| 📋 **Request Management** | Approve or reject borrow requests; track full lifecycle history |
| 📢 **Announcement Engine** | Broadcast messages with priority levels, scheduling, and email + in-app delivery |
| 🚩 **Reports Management** | Review bug/misconduct reports with status tracking and admin notes |
| 💬 **Contact Messages** | Manage user contact submissions with reply tracking |
| 💰 **Support Transactions** | Approve donation submissions and manage transaction records |
| 🗂️ **Category Management** | Automated "Collect" engine to sync categories with real inventory |
| 📁 **Backups** | One-click full system data export and restore |
| 📈 **CSV Exports** | Export user, book, and borrow history reports |
| 🔒 **Audit Logs** | Full activity log for admin transparency and accountability |

---

## 🛠️ Tech Stack

| Layer | Technology |
|---|---|
| **Backend** | PHP 7.4+ — clean, modular, no framework |
| **Database** | MySQL 5.7+ with PDO prepared statements |
| **Frontend** | HTML5, CSS3 (Custom Properties), Vanilla JavaScript |
| **Styling** | Glassmorphism, HSL color system, fluid micro-animations |
| **Email** | PHPMailer with SMTP (Brevo / SendGrid / Gmail compatible) |
| **Architecture** | Progressive Web App (PWA) with Service Worker |

---

## 📋 Database Schema

The database consists of the following core tables:

| Table | Purpose |
|---|---|
| `users` | Registered community members |
| `books` | All listed books and their metadata |
| `borrow_requests` | Full borrow lifecycle with return confirmation flow |
| `notifications` | In-app notification records per user |
| `announcements` | Admin-created community broadcasts |
| `announcement_read_status` | Per-user read tracking for announcements |
| `categories` | Book categories with live inventory counts |
| `wishlist` | User wishlists for unavailable books |
| `reports` | User-submitted bug/misconduct reports |
| `contact_messages` | User contact form submissions |
| `support_us` | Donation submissions from users |
| `transactions` | Approved financial transaction records |
| `login_otps` | OTP codes for password recovery |
| `remember_tokens` | Persistent login tokens per device |
| `admins` | Admin accounts and credentials |

---

## 📋 System Requirements

- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Server:** Apache / Nginx with PHP support
- **Permissions:** Write access for `/uploads`, `/logs`, `/sessions`, and `/backups`
- **Mail:** SMTP credentials for automated notifications (Brevo recommended)

---

## 🚀 Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/Asraf1270/OpenShelf.git
cd OpenShelf
```

### 2. Configure Environment

```bash
cp .env.example .env
```

Open `.env` and set:

```ini
DB_HOST=127.0.0.1
DB_NAME=openshelf_db
DB_USER=root
DB_PASS=

SMTP_HOST=smtp-relay.brevo.com
SMTP_PORT=587
SMTP_USER=your@email.com
SMTP_PASS=your_smtp_key

APP_NAME=OpenShelf
APP_URL=https://yourdomain.com
ADMIN_EMAIL=admin@yourdomain.com
```

### 3. Create the Database & Import Schema

**Via phpMyAdmin:**
1. Create a database named `openshelf_db` with collation `utf8mb4_unicode_ci`.
2. Import `data/schema.sql` via the **Import** tab.

**Via Command Line:**
```bash
mysql -u root -e "CREATE DATABASE IF NOT EXISTS openshelf_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -u root openshelf_db < data/schema.sql
```

### 4. Set File Permissions

```bash
chmod 755 uploads/ logs/ sessions/ backups/
```

### 5. Launch

Visit your server URL. The admin panel is at `/admin/`.

---

## 📁 Directory Structure

```
openshelf/
├── admin/              # Admin dashboard & management panels
├── api/                # AJAX endpoints (books, feed, settings, etc.)
├── assets/             # CSS, JS, images, fonts, design tokens
├── book/               # Single book detail & wishlist page
├── books/              # Public book catalog with infinite scroll
├── borrow-request/     # Borrow request submission
├── confirm-return/     # Two-step return confirmation handler
├── cron/               # Cron jobs (wishlist notifier, etc.)
├── config/             # DB, mail, and app configuration
├── data/               # schema.sql and migration scripts
├── emails/             # All email notification templates
├── includes/           # Shared PHP components (header, footer, db, helpers)
├── lib/                # Core libraries (Mailer, utilities)
├── my-borrowed/        # User's active borrowed books tracker
├── notifications/      # In-app notification center
├── profile/            # Public user profile page
├── register/           # User registration & email verification
├── login/              # Login with Remember Me & OTP recovery
├── return-book/        # Borrower-initiated book return flow
├── settings/           # User account settings & edit profile
├── support_us/         # Community donation page
├── uploads/            # User-uploaded book covers & profile photos
├── backups/            # Auto-generated system snapshots
└── vendor/             # Composer dependencies (PHPMailer)
```

---

## 🔐 Security Standards

| Protection | Implementation |
|---|---|
| **SQL Injection** | PDO prepared statements for all database queries |
| **XSS** | `htmlspecialchars()` on all user-rendered output |
| **Session Security** | Encrypted sessions with strict cookie settings |
| **Env Protection** | All credentials stored in `.env`, excluded from git |
| **OTP Recovery** | Two-factor (email + phone) password reset with hashed OTPs |
| **Return Confirmation** | Secure token-based two-step book return flow |
| **Domain Registration** | Optional email domain restriction for university deployments |
| **Admin Auth** | Separate admin table with independent credential management |

---

## 📧 Email Notifications

OpenShelf sends automated, HTML-formatted emails for:

- ✅ Welcome / Registration confirmation
- 📬 Borrow request received (to book owner)
- ✅ Borrow request approved (to borrower)
- ❌ Borrow request rejected (to borrower)
- 📦 Book return initiated (to owner for confirmation)
- ✅ Return confirmed (to borrower)
- ❌ Return rejected (to borrower)
- ❤️ Wishlist availability notification
- 📢 Community announcements

All templates are stored in `/emails/` and use the centralized `Mailer` class.

---

## 📱 Progressive Web App (PWA)

OpenShelf is fully installable as a PWA:

- **Service Worker** — Caches static assets for offline access
- **Web App Manifest** — Defines app name, icons, theme color
- **Offline Page** — Custom glassmorphic offline fallback at `/offline.php`
- **Install Prompt** — Native browser install banner support

---

## 🔄 Release History

See [RELEASES.md](RELEASES.md) for full version history.

**Current:** v3.2.0 — Database Integrity & Schema Completion *(July 8, 2026)*

---

## 🤝 Contributing

Contributions are welcome! Whether it's reporting bugs, suggesting features, or submitting pull requests, all community input is valued.

---

## 📄 License

This project is open-source and released under the **MIT License**.

---

## 📞 Support & Community

- **Email:** <support@duopenshelf.top>
- **Bug Reports:** `/report.php`
- **Contact / Feedback:** `/contact.php`
- **FAQ:** `/faq.php`
- **Donate:** `/support_us/`

---

**OpenShelf** — Empowering communities, one shared book at a time. 📚✨
