# 📦 OpenShelf — Release History

---

## 🚀 v4.0.0 — The Laravel Evolution

**Release Date:** July 24, 2026

The most transformative update in OpenShelf's history. Version 4.0.0 represents a complete architectural overhaul, migrating the entire application from raw PHP to the robust, elegant Laravel framework. This evolution guarantees enterprise-grade security, extreme maintainability, and unlocks new scaling capabilities, marking the final stage of our framework conversion.

### Highlights

- 🏗️ **Full Laravel MVC Migration** — Replaced all legacy flat-file PHP scripts with structured Controllers, Models, and Blade Views.
- 🗄️ **Eloquent ORM & Migrations** — Database interactions are now fully powered by Eloquent ORM, with schema management entirely handled by Laravel Migrations, replacing manual `schema.sql` imports.
- ☁️ **Cloudflare R2 Storage Integration** — Transitioned image asset handling (book covers and profile avatars) from basic local storage to a robust, host-agnostic system supporting Cloudflare R2 Object Storage (via Flysystem).
- 🎨 **Blade Component Architecture** — Frontend views have been modularized into reusable Blade components (e.g., `book-card-list`, `minimal-top-bar`), drastically reducing code duplication and improving UI consistency.
- ⚡ **Standalone Production-Ready CSS** — Deprecated Vite dependency for CSS building, transitioning to a streamlined, browser-compatible `public/css/app.css` architecture for faster deployments and reliable asset loading.
- 🛡️ **Enhanced Security & Routing** — Benefiting from Laravel's built-in CSRF protection, secure routing, route middleware, and robust session handlers.

---

## 🚀 v3.2.0 — Database Integrity & Schema Completion

**Release Date:** July 8, 2026

A database-focused stability release that audits and completes the master schema, ensuring every table and column that exists in production is properly represented in `data/schema.sql` for clean fresh deployments.

### Highlights

- 🗄️ **Schema Audit & Sync** — Analysed all `scratch/` migration scripts against the live schema and patched every discrepancy.
- 🆕 **`support_us` & `transactions` Tables** — Two financial management tables previously only created via migration scripts are now part of the master schema.
- 🔑 **`idx_return_token` Index** — Dedicated index on `borrow_requests.return_confirmation_token` for fast two-step return lookups.
- 📋 **`wishlist` Table Alignment** — Schema updated to match migration script and application usage.

---

## 🚀 v3.1.0 — Polish, Performance, and Stability

**Release Date:** June 30, 2026

A polished release focused on faster discovery, improved responsiveness, and stability across both public and admin experiences.

### Highlights

- ✨ **UI Refinements** — Polished book discovery, filter controls, and navigation for a smoother experience.
- ⚡ **Performance Improvements** — Faster page load and infinite scroll responsiveness throughout the catalog.
- 🛠️ **Stability Fixes** — Resolved issues across borrow/request workflows, admin reporting, and mobile layouts.
- 🔒 **PWA & Offline Strengthening** — Improved fallback messaging and reliability for installable usage.
- 📈 **Quality Enhancements** — Updated visual polish and bug fixes across core user-facing flows.

---

## 🚀 v3.0.5 — SEO & Dynamic Metadata System

**Release Date:** July 7, 2026

Implemented a dynamic SEO and metadata management system in the global header for better search engine indexing and social sharing.

### Highlights

- 🔍 **Dynamic Title Tags** — Auto-generated, page-specific `<title>` based on context (book name, section, etc.).
- 🧾 **Meta Descriptions** — Context-aware meta descriptions per page.
- 🔗 **Canonical URLs** — Prevents duplicate content indexing.
- 📣 **Open Graph / Twitter Cards** — Social sharing previews for books and pages.
- 🏗️ **Schema.org JSON-LD** — Structured data for Google rich results.

---

## 🚀 v3.0.4 — Book Return Rejection Workflow

**Release Date:** July 6, 2026

Finalised the two-step return confirmation system with a full rejection path, allowing owners to reject returns and revert books back to a borrowable state.

### Highlights

- ❌ **Return Rejection Flow** — Owner can reject a return, reverting status to `approved` and notifying the borrower.
- 📧 **Return Rejection Email** — Dedicated email template informing the borrower of rejection with reason.
- 🎴 **Book Detail Card UI** — Borrower request actions wrapped in a polished card layout with availability status indicators.

---

## 🚀 v3.0.3 — Book Wishlist System

**Release Date:** July 5, 2026

Implemented a full wishlist feature so users can queue for unavailable books and receive automatic availability notifications.

### Highlights

- ❤️ **Wishlist Toggle** — Heart-icon button on unavailable books to add/remove from wishlist.
- 🔔 **Availability Notifier** — Cron job (`cron/wishlist_notify.php`) sends email to the first user in queue when a book becomes available.
- 📧 **Wishlist Email Template** — Dedicated `emails/wishlist_available.php` notification.
- 🗄️ **`wishlist` Table** — New database table with FIFO ordering and `notified` flag.

---

## 🚀 v3.0.2 — Notification Database Migration

**Release Date:** July 4, 2026

Completed the full migration of the notification system from legacy JSON files to a centralised MySQL database.

### Highlights

- 📋 **DB-Driven Notifications** — All notification `INSERT` operations moved to the database across all controllers.
- 🔄 **Controller Refactoring** — Updated `book/index.php`, `admin/requests/index.php`, `requests/index.php`, `return-book/index.php`, and `admin/announcements/index.php`.
- 🗑️ **Legacy JSON Elimination** — Removed all reliance on the `users/` JSON directory for notifications.

---

## 🚀 v3.0.1 — Reporting & Contact Management

**Release Date:** July 4, 2026

Migrated `report.php` and `contact.php` from JSON file storage to proper MySQL tables, with dedicated admin management pages.

### Highlights

- 🚩 **`reports` Table** — Persistent bug/misconduct reports with status, admin notes, and resolution tracking.
- 💬 **`contact_messages` Table** — Contact form submissions with reply tracking and read status.
- 🖥️ **Admin Reports Page** — `admin/reports-management.php` with filter, update, and view UI.
- 🖥️ **Admin Contact Page** — `admin/contact-messages.php` with reply management.

---

## 🚀 v3.0.0 — Design Revamp & Enhanced User Experience

**Release Date:** May 21, 2026

A major milestone release introducing completely redesigned core layouts, a unified global color scheme powered by CSS variables, and significant new features.

---

## 🚀 v2.9.1 — The "Unified Public Experience"

**Release Date:** May 11, 2026

Unified the design language across all public-facing pages.

---

## 🚀 v2.9.0 — The "Admin Renaissance" & Intelligent Discovery

**Release Date:** May 10, 2026

A transformative update bringing the entire admin suite into the modern era.

---

## 🚀 v2.8.0 — The "Mobile-First" Design Evolution

**Release Date:** May 9, 2026

A visual and functional transformation focused on delivering a native-app-like experience.

---

## 🚀 v2.7.0 — The Notification & UX Evolution

**Release Date:** April 29, 2026

Major architectural milestone delivering a standardized notification engine and streamlined registration.

---

## 🚀 v2.6.0 — Smart Recommendations & UI Refinements

**Release Date:** April 24, 2026

Enhanced book discovery with smart recommendations and a cleaner interface.

---

## 🚀 v2.5.0 — Infinite Discovery & Community Support

**Release Date:** April 24, 2026

Seamless infinite scroll book discovery and a new community Support Us page.

---

## 🚀 v2.4.0 — Aesthetic Overhaul & Community Safety

**Release Date:** April 24, 2026

Reimagined landing page, reporting system, and comprehensive page modernization.

---

## 🚀 v2.3.0 — Enhanced Security & Admin Workflow

**Release Date:** April 23, 2026

Streamlined admin authentication, backup reliability, and cross-browser compatibility.

---

## 🚀 v2.2.0 — The Complete Experience

**Release Date:** April 23, 2026

New user features, modernized admin panel, and fully restored email notifications.

---

## 🚀 v2.1.0 — Feature Expansion

**Release Date:** April 9, 2026

Introduced Forget Password and My Borrowed Books features with critical admin bug fixes.

---

## 🚀 v2.0.0 — The Database Evolution

**Release Date:** April 7, 2026

The most significant architectural update — migrated from JSON flat-file storage to a robust MySQL backend.

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
