# 📦 OpenShelf — Release History

## 🚀 v3.1.0 — Polish, Performance, and Stability

**Release Date:** June 30, 2026

A polished release focused on faster discovery, improved responsiveness, and stability across both public and admin experiences.

### Highlights

- ✨ **UI Refinements** — Polished book discovery, filter controls, and navigation for a smoother experience.
- ⚡ **Performance Improvements** — Faster page load and infinite scroll responsiveness throughout the catalog.
- 🛠️ **Stability Fixes** — Resolved issues across borrow/request workflows, admin reporting, and mobile layouts.
- 🔒 **PWA & Offline Strengthening** — Improved fallback messaging and reliability for installable usage.
- 📈 **Quality Enhancements** — Updated visual polish and bug fixes across core user-facing flows.

📄 Full details: [v3.1.0 Release Notes](v3.1.0_release_notes.md)

---

## 🚀 v3.0.0 — Design Revamp & Enhanced User Experience

**Release Date:** May 21, 2026

A major milestone release introducing completely redesigned core layouts, a unified global color scheme powered by CSS variables, and significant new features including Book Filtering and a dedicated Settings page.

### Highlights

- 🎨 **Complete Design Revamp** — Reimagined Profile, Footer, Book, and Header layouts.
- ⚙️ **New Features** — Introduced Book Filtering and a dedicated Settings page.
- 📱 **Floating Navigation** — Implemented a modern floating bottom navigation bar.
- 💅 **Unified Color Scheme** — Refactored UI to use centralized CSS variables for consistent theming.
- 🐛 **UI Polish** — Resolved the "jumping" problem in the plus button and updated global colors.

📄 Full details: [v3.0.0 Release Notes](v3.0.0_release_notes.md)

---

## 🚀 v2.9.1 — The "Unified Public Experience"

**Release Date:** May 11, 2026

This release completes the platform-wide visual modernization by unifying the design language across all public-facing pages. Every public touchpoint now reflects our modern **Deep Indigo & Muted Teal** identity with full dark mode support and responsive refinement.

### Highlights

- 🎭 **Public Page Renaissance** — Complete modernization of `about.php`, `contact.php`, `faq.php`, `privacy.php`, `terms.php`, `guidelines.php`, and `report.php`.
- 💳 **Premium Support Portal** — High-fidelity redesign of the `Support Us` page with optimized payment UI and brand-aligned styling.
- 🔐 **Reimagined Security UI** — Overhauled the `Forget Password` flow with a sleek, multi-step interface and premium aesthetics.
- 📡 **Resilient Offline Experience** — Redesigned `Offline` fallback page with glassmorphic UI and animated connectivity indicators.

📄 Full details: [v2.9.1 Release Notes](v2.9.1_release_notes.md)

---


## 🚀 v2.9.0 — The "Admin Renaissance" & Intelligent Discovery

**Release Date:** May 10, 2026

A transformative update that brings the entire **Administrative Suite** into the modern era. This release harmonizes the management interface with our premium brand identity, introduces global dark mode for administrators, and implements intelligent automation for library categorization.

### Highlights

- 🎨 **Admin Suite Renaissance** — Complete visual modernization of the entire administrative suite with our premium brand identity.
- 🌗 **Global Admin Dark Mode** — Full, native dark mode support for all administrative surfaces and tools.
- 🧠 **Intelligent Category Sync** — Reimagined Category Management with an automated "Collect" engine for real-time inventory synchronization.
- 📱 **Responsive Admin Architecture** — Mobile-first refactor of all admin data tables, management grids, and interactive charts.

📄 Full details: [v2.9.0 Release Notes](v2.9.0_release_notes.md)

---


## 🚀 v2.8.0 — The "Mobile-First" Design Evolution

**Release Date:** May 9, 2026

A visual and functional transformation focused on delivering a high-fidelity, "native app" experience for web users. Drawing inspiration from modern mobile interfaces, this update introduces fluid navigation patterns, elegant glassmorphism, and a completely reimagined discovery layout.

### Highlights

- 📱 **Smart Header** — Complete redesign of the global header featuring "Smart Scroll" (auto-hide on scroll down, instant reveal on scroll up) and high-end glassmorphism.
- 🏷️ **Integrated Category Bar** — Horizontal scrolling category "chips" with a persistent sticky state that glides to the top of the viewport for a seamless, app-like discovery experience.
- 🔍 **Slim Search Interface** — Redesigned the search bar to be slim and modern, featuring an integrated magnifying glass icon and brand-coordinated button.
- 📚 **Modern Book Card Component** — Total overhaul of the book card with 20px radius, soft shadows, and floating pill-shaped availability badges with `backdrop-filter` blur.
- 🎨 **Premium Site Footer** — Reimagined the footer with bold headings, vibrant gradient social links, and refined scannability.

📄 Full details: [v2.8.0 Release Notes](v2.8.0_release_notes.md)

---

## 🚀 v2.7.0 — The Notification & UX Evolution

**Release Date:** April 29, 2026

A major architectural and user experience milestone delivering a standardized notification engine, streamlined registration workflow, and enterprise-grade template stability.

### Highlights

- 📧 **Unified Mailer Engine** — Centralized all system communications through a powerful new `Mailer` class, providing a consistent and premium design for every user interaction.
- ⚡ **Streamlined Onboarding** — Removed the mandatory Admin Approval flow; users can now register and start using the platform immediately after email verification.
- 🛡️ **Template Robustness Audit** — Comprehensive refactoring of all email templates to include safe variable fallbacks and `isset()` checks, eliminating 500 errors and runtime notices.
- 🎨 **Visual Consistency** — All system emails have been updated to follow our modern, clean design language with responsive layouts and improved readability.
- 🔧 **Admin Workflow Fixes** — Aligned admin controllers with the new mailer system to ensure consistent data delivery for borrow approvals and rejections.

📄 Full details: [v2.7.0 Release Notes](v2.7.0_release_notes.md)

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
