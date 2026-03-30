# 📚 OpenShelf — Community Library Management System

[![Version](https://img.shields.io/badge/version-1.0.1-blue.svg)](https://github.com/Asraf1270/OpenShelf_V1.0.1)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)](https://www.php.net/)

**OpenShelf** is a modern, open-source library management system designed for communities, universities, and book clubs. It empowers users to share, borrow, and manage books effortlessly through a **premium, glassmorphic interface** that feels alive and responsive.

---

## ✨ What's New in v1.0.1

We've completely overhauled the platform to provide a state-of-the-art experience:
- 🎨 **Premium UI/UX:** A total redesign using modern glassmorphism, fluid animations, and a rich HSL-based color palette.
- 📱 **Mobile-First Excellence:** Every feature is now optimized for mobile devices, ensuring a seamless experience on any screen size.
- 📧 **Automated Borrow Notifications:** Integrated email system to alert owners instantly when someone wants to borrow their book.
- 🔍 **Multi-Category Search:** Find your next read faster with advanced filtering across multiple genres simultaneously.
- ⚡ **Performance Boost:** Smarter JSON data handling for lightning-fast book discovery and user interactions.

---

## 🌟 Key Features

### 👤 For Users
- **Secure Registration:** Email-based authentication with university domain verification.
- **Glassmorphic Catalog:** Browse books through an elegant, interactive grid layout.
- **Easy Sharing:** Add your own books with custom cover uploads in seconds.
- **Smart Requests:** Request books directly with automated email alerts to the owner.
- **Personalized Profiles:** A beautiful split-screen UI to manage your shared books and reading history.
- **Real-time Notifications:** Stay updated with in-app alerts for borrows, approvals, and community news.

### 🛡️ For Administrators
- **Dynamic Dashboard:** Real-time statistics with interactive charts and system health monitoring.
- **Full Moderation:** Manage users, verify book entries, and oversee borrow requests.
- **Announcement Engine:** Broadcast community-wide updates with premium styling.
- **Audit Logs:** Track every system activity for complete transparency and security.
- **One-Click Backups:** Automated data safety tools to keep your library's information secure.

---

## 🛠️ Tech Stack
- **Backend:** PHP 7.4+ (Clean, modular architecture)
- **Frontend:** Modern HTML5, CSS3 (Custom properties/variables), Vanilla JavaScript
- **Styling:** Premium Glassmorphism, HSL color system, fluid animations
- **Storage:** High-performance JSON-based file system (No SQL database required!)
- **Communication:** PHPMailer with SMTP integration (Brevo/SendGrid/Gmail)

---

## 📋 System Requirements
- **PHP:** 7.4 or higher
- **Server:** Apache/Nginx (with PHP support)
- **Permissions:** Read/write access for `/data`, `/uploads`, `/logs`, and `/sessions`
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
- **SMTP Settings:** Host, Port, Secure, Username, Password.
- **Email Settings:** From Address, From Name, Reply-To, and Admin Email.
- **App Settings:** App Name, URL, and Debug mode.

### 3. Set File Permissions
Ensure the web server can write to the following directories:
```bash
chmod 755 data/ uploads/ logs/ sessions/ backups/
chmod 644 data/*.json uploads/book_cover/ uploads/profile/
```

### 4. Configuration
- Edit `config/mail.php` to update SMTP host, sender name, and reply-to address.
- Update the `BASE_URL` in its respective configuration files to match your domain (e.g., `https://yourdomain.com`).

### 5. Launch
Navigate to your server's URL. For the admin panel, visit `/admin/`.

---

## 📁 Directory Structure
```
openshelf/
├── admin/            # Comprehensive management dashboard
├── api/              # Dynamic endpoints for frontend interactions
├── assets/           # Premium CSS, JS, and design tokens
├── data/             # Optimized JSON storage (⚠️ Keep this secure/backed up)
├── includes/         # Shared UI components (header, footer, nav)
├── uploads/          # User-uploaded covers and profile media
├── backups/          # Automatically generated system snapshots
└── vendor/           # Composer dependencies
```

---

## 🧪 Security Standards
- ✅ **Domain-Locked Registration:** Prevent unauthorized access by restricting email domains.
- ✅ **Encrypted Sessions:** Secure user state management.
- ✅ **Data Isolation:** JSON storage is structured to be inaccessible from direct web requests.
- ✅ **Environment Protection:** Sensitive API keys and passwords are kept out of version control via `.env`.

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
