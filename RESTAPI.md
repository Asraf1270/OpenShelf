# REST API Checklist

Use this file to track the REST API work for the mobile app.

Legend:
- `[x]` = API already created
- `[ ]` = API still needed

## 1. Existing APIs

- [x] `GET /api/books` - List books with search, filter, sort, and cursor pagination
- [x] `GET /api/books/suggest` - Book and profile suggestions for search typeahead
- [x] `GET|POST /api/settings` - Settings action endpoint for profile and password updates
- [x] `GET|POST /api/notifications` - List notifications, unread count, and notification actions

## 2. Authentication

- [ ] `POST /api/auth/register` - Create a new user account
- [ ] `POST /api/auth/login` - Log in and create a mobile session/token
- [ ] `POST /api/auth/logout` - Log out the current user
- [ ] `POST /api/auth/forgot-password` - Request password reset
- [ ] `POST /api/auth/reset-password` - Reset password with token/OTP
- [ ] `POST /api/auth/verify-otp` - Verify signup or password reset OTP

## 3. Profile & Account

- [ ] `GET /api/profile/me` - Get the current user profile
- [ ] `GET /api/profile/{id}` - Get a public user profile
- [ ] `PUT /api/profile/me` - Update profile data
- [ ] `POST /api/profile/me/avatar` - Upload or change profile picture
- [ ] `POST /api/profile/me/password` - Change password

## 4. Books

- [x] `GET /api/books` - Get the main book list
- [x] `GET /api/books/suggest` - Search suggestions for books and profiles
- [ ] `GET /api/books/{id}` - Get full book details
- [ ] `POST /api/books` - Create a new book listing
- [ ] `PUT /api/books/{id}` - Update a book listing
- [ ] `DELETE /api/books/{id}` - Delete a book listing
- [ ] `GET /api/books/my` - Get books listed by the current user
- [ ] `GET /api/books/newest` - Get newest books for the home feed
- [ ] `GET /api/books/available` - Get currently available books
- [ ] `GET /api/categories` - Get all book categories

## 5. Borrowing

- [ ] `POST /api/borrow-requests` - Create a borrow request
- [ ] `GET /api/borrow-requests/my` - Get requests made by the current user
- [ ] `GET /api/borrow-requests/incoming` - Get requests received by the current user
- [ ] `POST /api/borrow-requests/{id}/approve` - Approve a borrow request
- [ ] `POST /api/borrow-requests/{id}/reject` - Reject a borrow request
- [ ] `GET /api/borrowed/my` - Get books currently borrowed by the current user
- [ ] `POST /api/return-requests` - Start a return request
- [ ] `POST /api/return-requests/{id}/confirm` - Confirm a return
- [ ] `POST /api/borrow-requests/{id}/cancel` - Cancel a borrow request

## 6. Wishlist

- [ ] `GET /api/wishlist` - Get wishlist items
- [ ] `POST /api/wishlist` - Add a book to wishlist
- [ ] `DELETE /api/wishlist/{id}` - Remove a book from wishlist

## 7. Notifications

- [x] `GET /api/notifications` - Get notifications list
- [x] `GET /api/notifications?action=count` - Get unread notification count
- [x] `POST /api/notifications` with `action=mark_read` - Mark one notification as read
- [x] `POST /api/notifications` with `action=mark_all_read` - Mark all notifications as read
- [x] `POST /api/notifications` with `action=delete` - Delete a notification

## 8. Announcements

- [ ] `GET /api/announcements` - Get announcements list
- [ ] `GET /api/announcements/{id}` - Get announcement detail
- [ ] `POST /api/announcements/{id}/read` - Mark an announcement as read

## 9. Support & Community

- [ ] `GET /api/home` - Get mobile home dashboard data
- [ ] `GET /api/leaderboard` - Get leaderboard data
- [ ] `POST /api/contact-messages` - Submit a contact message
- [ ] `POST /api/reports` - Submit a report
- [ ] `POST /api/support-us` - Submit a support/donation transaction

## 10. App Settings & Metadata

- [x] `GET|POST /api/settings` - App settings payload and account actions
- [ ] `GET /api/app-config` - Get version, theme, maintenance, and feature flags
- [ ] `GET /api/server-status` - Check service health for the mobile app

## 11. Optional Admin APIs

If the mobile app will include an admin mode later, these APIs will also be needed.

- [ ] `GET /api/admin/dashboard` - Admin summary stats
- [ ] `GET /api/admin/users` - Manage users
- [ ] `GET /api/admin/books` - Manage books
- [ ] `GET /api/admin/requests` - Manage borrow requests
- [ ] `GET /api/admin/reports` - Manage reports
- [ ] `GET /api/admin/announcements` - Manage announcements
- [ ] `GET /api/admin/transactions` - Manage support transactions
- [ ] `GET /api/admin/contact-messages` - Manage contact messages

## 12. Tracking Rule

When an API is created, change its checkbox from `[ ]` to `[x]`.

Keep this file updated before starting the mobile app so the remaining backend work is visible at a glance.