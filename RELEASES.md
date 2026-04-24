# 📦 OpenShelf — Release History

---

## 🚀 v2.6.0 — Smart Recommendations & UI Refinements
**Release Date:** April 24, 2026

An intelligent update that enhances book discovery with smart recommendations and refines the core user interface for a cleaner, more responsive experience.

### Highlights
- 🧠 **Intelligent Related Books** — Added a multi-layered recommendation system that suggests relevant books based on category, owner, and publication data when search results are limited.
- 📱 **Mobile-First Related Books** — The related books section on the individual book page now features a responsive two-column grid layout specifically optimized for mobile devices.
- 📚 **Expanded Pagination Limit** — Increased the infinite scroll batch size from 12 to 25 books, making catalog browsing significantly faster and more seamless.
- 🎨 **Sleeker Books Page UI** — Removed the vertical gap between the header and search bar for a cleaner, more cohesive aesthetic.

📄 Full details: [v2.6.0 Release Notes](v2.6.0_release_notes.md)

---

## 🚀 v2.5.0 — Infinite Discovery & Community Support
**Release Date:** April 24, 2026

A refined experience update delivering seamless infinite scroll book discovery, a new community Support Us page, and smarter navigation across desktop and mobile.

### Highlights
- ♾️ **Infinite Scroll Pagination** — Books load automatically as you scroll using a cursor-based API and `IntersectionObserver`, with full search + filter integration.
- 💳 **Support Us Page** — Dedicated payment cards for bKash, Nagad, and Rocket with one-click number copy and TrxID submission.
- 🗂️ **Split Book Header** — Search bar and category filter separated; only the category row stays sticky for a cleaner scroll experience.
- 📱 **Mobile Nav Overhaul** — Auth-aware menu items, section labels, and a prominent Support Us CTA in the mobile navigation.
- 🐛 **API Bug Fix** — Resolved `SQLSTATE[HY093]` PDO parameter binding error causing 500 errors during filtered infinite scroll.

📄 Full details: [v2.5.0 Release Notes](v2.5.0_release_notes.md)

---

## 🚀 v2.4.0 — Aesthetic Overhaul & Community Safety
**Release Date:** April 24, 2026

A major visual and functional update introducing a reimagined landing page, a dedicated reporting system, and a comprehensive modernization of all informational pages with full dark mode support.

### Highlights
- 🎨 **Reimagined Landing Page** — Complete overhaul of the homepage with a focus on community mission and mobile-first aesthetics.
- 🚩 **Integrated Reporting System** — New `report.php` page for users to safely report bugs, misconduct, or content issues.
- 🌗 **Global Info Modernization** — Full visual refresh of `About`, `Contact`, `FAQ`, `Privacy`, and `Terms` pages with native dark/light mode support.
- 📚 **Community Growth Policy** — Introduced a new term requiring users to share at least 2 books within 30 days.

📄 Full details: [v2.4.0 Release Notes](v2.4.0_release_notes.md)

---

## 🚀 v2.3.0 — Enhanced Security & Admin Workflow
**Release Date:** April 23, 2026

A focused update streamlining the administrative experience, fortifying backup reliability, and resolving cross-browser compatibility issues.

### Highlights
- 🛡️ **Simplified Admin Authentication** — Replaced OTP-based admin login with a standard, secure email and password flow.
- 🔑 **Admin Password Management** — Added a seamless "Change Password" feature to the admin profile.
- 💾 **Reliable Backups Audited** — Verified full system dump and restoration integrity for flawless data preservation.
- 🎨 **CSS Compatibility Fixed** — Resolved `background-clip` warnings for broad cross-browser standards compliance.

📄 Full details: [v2.3.0 Release Notes](v2.3.0_release_notes.md)

---

## 🚀 v2.2.0 — The Complete Experience
**Release Date:** April 23, 2026

A comprehensive update delivering powerful new user features, a modernized admin panel, and a fully restored email notification system.

### Highlights
- 📖 **My Borrowed Books** — Track active borrows with visual due-date progress and one-click returns.
- 🔑 **Forget Password** — Secure, two-factor (email + phone) password recovery with OTP verification.
- 🎨 **Admin Panel Modernization** — Mobile-first redesign with card-based layouts and full dark mode compatibility.
- 📧 **Email Notifications Restored** — End-to-end SMTP notifications for borrow requests, approvals, rejections, and returns.
- 📢 **Announcements Navigation** — Quick-access links added to desktop and mobile menus.
- 🐛 **Bug Fixes** — Resolved 500 errors on Announcements and Admin Books pages, fixed add-book DB connection, login Remember Me, and return-book workflow.

📄 Full details: [v2.2.0 Release Notes](v2.2.0_release_notes.md)

---

## 🚀 v2.1.0 — Feature Expansion
**Release Date:** April 9, 2026

Introduced the Forget Password and My Borrowed Books features, along with critical bug fixes for announcements and admin book management.

---

## 🚀 v2.0.0 — The Database Evolution
**Release Date:** April 7, 2026

The most significant architectural update — migrated from JSON flat-file storage to a robust **MySQL Database** backend.

### Highlights
- 🗄️ **Full Database Integration** — MySQL-powered data management replacing all JSON handlers.
- ⚡ **Lightning-Fast Performance** — Optimized SQL indexing for large catalogs.
- 🧪 **Industrial-Grade Security** — PDO prepared statements for all database interactions.
- 📊 **Real-time Admin Dashboard** — SQL-powered statistics and reporting.
- 🔄 **Data Integrity** — Foreign key constraints and ACID transactions.
- 💾 **Hybrid Backup System** — Config files + structured database export.

📄 Full details: [v2.0.0 Release Notes — The Database Evolution](RELEASES.md#-v200--the-database-evolution)

---

## 🚀 v1.5.0 — Global Dark Mode
Implemented a persistent dark mode toggle with comprehensive CSS overrides across the entire platform.

---

## 🚀 v1.4.0 — Progressive Web App
Upgraded OpenShelf to a fully installable PWA with Service Worker support and offline capabilities.

---

## 🚀 v1.3.0 — Notification Overhaul
Refactored the notification system with per-user storage, sorted by creation date, and limited to 25 recent items.

---

## 🚀 v1.2.0 — Email Templates
Standardized email templates with responsive, table-based layouts for cross-client compatibility.

---

## 🚀 v1.1.0 — UI Modernization
Major UI overhaul with mobile-first enhancements, dark glassmorphism login/register pages, and critical bug fixes.

---

## 🚀 v1.0.0 — Initial Release
The original OpenShelf platform with community book sharing, user profiles, admin dashboard, and basic library management.

---

**OpenShelf** — Empowering communities, one shared book at a time. 📚✨
