# 📚 OpenShelf — Community Library Management System

[![Version](https://img.shields.io/badge/version-4.0.0-blue.svg)](https://github.com/Asraf1270/OpenShelf/releases/tag/v4.0.0)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Laravel](https://img.shields.io/badge/Laravel-13.x-FF2D20.svg)](https://laravel.com/)
[![PHP](https://img.shields.io/badge/PHP-8.3%2B-777bb4.svg)](https://www.php.net/)

**OpenShelf** is a modern, open-source community library management system designed for universities, halls, and book clubs. It empowers users to share, borrow, and manage books effortlessly through a **premium, glassmorphic interface** built for mobile-first experiences.

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
| ➕ **Add Books** | List personal books with custom cover uploads (Cloudflare R2/S3 supported) and full metadata |
| 📬 **Borrow Requests** | Request books with a custom message; owner gets instant email alert |
| 🔄 **Two-Step Returns** | Borrower initiates return; owner confirms or rejects via a secure email link |
| ❤️ **Wishlist** | Wishlist unavailable books; get notified by email the moment one becomes free |
| 🔔 **Notifications** | In-app alerts for borrows, approvals, rejections, returns, and announcements |
| 👤 **User Profiles** | Public profiles showing shared books, borrow history, bio, and contact info |
| ✏️ **Edit Profile** | Update name, department, hall, room number, bio, and profile picture |
| 🔐 **Password Recovery** | Secure password reset flow |
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
| 🔒 **Audit Logs** | Full activity log for admin transparency and accountability |

---

## 🛠️ Tech Stack (v4.0.0 Architecture)

| Layer | Technology |
|---|---|
| **Backend** | Laravel 13.x (PHP 8.3+) — MVC architecture, Eloquent ORM |
| **Database** | MySQL 8.x (managed via Laravel Migrations) |
| **Frontend** | Blade Templates, HTML5, CSS3 (Vanilla), JavaScript |
| **Styling** | Glassmorphism, HSL color system, standalone modular CSS architecture |
| **Storage** | Cloudflare R2 / AWS S3 integration (via Laravel Flysystem) |
| **Email** | Laravel Mailer with SMTP support |

---

## 📋 System Requirements

- **PHP:** 8.3 or higher
- **Composer** for dependency management
- **MySQL:** 8.0 or higher
- **Server:** Apache / Nginx with PHP support
- **Storage:** Local storage or Cloudflare R2/AWS S3 bucket

---

## 🚀 Installation & Setup

### 1. Clone the Repository

```bash
git clone https://github.com/Asraf1270/OpenShelf.git
cd OpenShelf
```

### 2. Install Dependencies

```bash
composer install
```

### 3. Configure Environment

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and set your database and SMTP credentials:

```ini
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=openshelf_db
DB_USERNAME=root
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=smtp-relay.brevo.com
MAIL_PORT=587
MAIL_USERNAME=your@email.com
MAIL_PASSWORD=your_smtp_key
MAIL_FROM_ADDRESS=hello@example.com
```

If using Cloudflare R2 for storage, configure the AWS / S3 environment variables accordingly.

### 4. Run Migrations & Seeders

```bash
php artisan migrate
# Optional: Seed the database with initial categories and admin account
php artisan db:seed
```

### 5. Link Storage

```bash
php artisan storage:link
```

### 6. Launch Development Server

```bash
php artisan serve
```

Visit `http://localhost:8000`. The admin panel is accessible to users with the admin role.

---

## 🔐 Security Standards

- **SQL Injection:** Mitigated via Laravel's Eloquent ORM and Query Builder.
- **XSS Protection:** Automatic output escaping in Blade templates.
- **CSRF Protection:** Laravel's built-in CSRF token verification on all POST/PUT/DELETE requests.
- **File Storage:** Secure object storage (R2/S3) or isolated local storage symlinks.
- **Session Security:** Encrypted sessions via Laravel's session handlers.

---

## 🔄 Release History

See [RELEASE.md](RELEASE.md) for full version history.

**Current:** v4.0.0 — The Laravel Evolution *(July 2026)*

---

## 🤝 Contributing

Contributions are welcome! Whether it's reporting bugs, suggesting features, or submitting pull requests, all community input is valued.

---

## 📄 License

This project is open-source and released under the **MIT License**.

---

**OpenShelf** — Empowering communities, one shared book at a time. 📚✨
