# OpenShelf - Community Library Management System

A modern, open-source library management system designed for communities, universities, and book clubs. OpenShelf enables users to share, borrow, and manage books effortlessly with a beautiful, animated interface.

## 🌟 Features

### For Users
- **User Registration & Authentication**: Secure email-based registration with university domain verification
- **Book Catalog**: Browse a comprehensive catalog of shared books with detailed information
- **Book Sharing**: Add your own books to the library and manage your collection
- **Borrowing System**: Request to borrow books from other community members with automated notifications
- **User Profiles**: Create and customize your profile with reading preferences
- **Announcements**: Stay updated with community announcements and library news
- **Notifications**: Real-time notifications for borrow requests, approvals, and returns
- **Feed System**: View activity feed and community interactions
- **Contact & Support**: Built-in contact form and FAQ section

### For Administrators
- **Admin Dashboard**: Comprehensive dashboard with real-time statistics and charts
- **User Management**: Manage user accounts, approve registrations, and suspend users
- **Book Management**: Monitor all books, verify entries, and manage categories
- **Borrow Request Management**: Review and approve book borrow requests
- **Announcements Management**: Create and manage community announcements
- **Activity Logs**: Track all system activities and user actions
- **Backup & Restore**: Automated backup and restore functionality for data safety
- **Reports & Analytics**: Generate reports on library usage and statistics

## 🛠️ Tech Stack

- **Backend**: PHP 7.4+
- **Frontend**: HTML5, CSS3, JavaScript
- **Data Storage**: JSON-based file storage (no database required)
- **Email Service**: PHPMailer with SMTP (Brevo/Sendinblue)
- **Server**: Apache/Nginx with PHP support

## 📋 System Requirements

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Read/write permissions for `/data`, `/uploads`, `/logs`, and `/sessions` directories
- SMTP credentials for email notifications (optional but recommended)
- 100MB+ storage space

## 🚀 Installation

### 1. Download/Clone the Repository
```bash
git clone <repository-url>
cd openshelf_like_final
```

### 2. Configure Environment Variables
Create a `.env` file in the root directory with your SMTP credentials:

```env
SMTP_USERNAME=your-smtp-username@smtp-provider.com
SMTP_PASSWORD=your-smtp-password
```

### 3. Set File Permissions
```bash
chmod 755 data/ uploads/ logs/ sessions/ backups/
chmod 644 data/*.json uploads/book_cover/ uploads/profile/
```

### 4. Update Configuration Files
Edit `config/mail.php` and update:
- SMTP server settings
- Email sender address and name
- Reply-to email address

### 5. Set Base URL
Update the `BASE_URL` constant in registration, login, and other files to match your domain:
```php
define('BASE_URL', 'https://yourdomain.com');
```

### 6. Create First Admin (Optional)
Edit the admin login or use database tools to add the first admin account to `data/admins.json`

### 7. Access the Application
- **User Interface**: `https://yourdomain.com`
- **Admin Panel**: `https://yourdomain.com/admin/`

## 📁 Directory Structure

```
openshelf/
├── admin/                    # Admin panel and management features
│   ├── dashboard/           # Admin dashboard
│   ├── users/               # User management
│   ├── books/               # Book management
│   ├── requests/            # Borrow request management
│   ├── announcements/       # Announcements management
│   ├── reports/             # Reports and exports
│   └── logs/                # Activity logs
├── api/                     # API endpoints for dynamic features
├── assets/                  # Frontend assets
│   ├── css/                 # Stylesheets
│   └── js/                  # JavaScript files
├── config/                  # Configuration files
├── data/                    # JSON data storage (⚠️ Keep backup!)
│   ├── users.json
│   ├── books.json
│   ├── borrow_requests.json
│   ├── announcements.json
│   ├── notifications.json
│   └── categories.json
├── includes/                # Shared PHP include files
│   ├── header.php
│   ├── footer.php
│   └── navbar.php
├── lib/                     # Library classes
│   └── Mailer.php          # Email handling class
├── uploads/                 # User uploads
│   ├── book_cover/         # Book cover images
│   └── profile/            # Profile pictures
├── emails/                  # Email template files
├── logs/                    # System and activity logs
├── backups/                 # Automated backups
└── vendor/                  # Composer dependencies

```

## 🔧 Configuration

### Email Setup
1. Sign up with an email service provider (e.g., Brevo, SendGrid, Gmail)
2. Get your SMTP credentials
3. Update `.env` file with your credentials
4. Verify sender email address with the provider

### User Registration Constraints
By default, the system requires a university email domain. To change this:
1. Edit `register/index.php`
2. Modify the `validateEmail()` function to accept your desired email pattern

### Categories
Edit `data/categories.json` to add or modify book categories

## 📖 Usage Guide

### For Users

1. **Register**: Visit `/register/` and create an account with your email
2. **Browse Books**: View all available books on the home page or `/books/`
3. **Add Books**: Go to `/add-book/` to share your books
4. **Borrow Books**: Click "Borrow" on any book and wait for owner approval
5. **Return Books**: Use `/return-book/` to complete a borrow transaction
6. **View Requests**: Check `/requests/` to see all borrow requests

### For Admins

1. **Login**: Navigate to `/admin/login/` with admin credentials
2. **Dashboard**: View statistics and activity reports
3. **User Management**: Approve users, suspend accounts, view profiles
4. **Book Management**: Verify and manage book listings
5. **Approvals**: Review and approve/reject borrow requests
6. **Generate Reports**: Export data and view analytics
7. **Backup**: Create and restore backups from admin panel

## 🔐 Security Considerations

- ✅ Email-based user verification
- ✅ Session-based authentication
- ✅ Data stored in JSON files (not exposed to web)
- ⚠️ Regularly backup `/data` directory
- ⚠️ Use HTTPS in production
- ⚠️ Keep `.env` and sensitive files out of version control
- ⚠️ Set appropriate file permissions (644 for data, 755 for directories)

## 📧 Email Notifications

The system automatically sends emails for:
- Account registration and approval/rejection
- Book borrow requests and approvals
- Book return reminders
- Overdue notifications
- New announcements
- System notifications

## 🔄 Backup & Restore

### Automatic Backups
- Located in `/backups/` directory
- Backups include all JSON data files
- Access via Admin Panel → Backup & Restore

### Manual Backup
```bash
cp -r data/ data_backup_$(date +%Y%m%d)/
```

### Restore
1. Go to Admin Panel → Backup & Restore
2. Select backup date and click "Restore"

## 🐛 Troubleshooting

### Issues with Emails Not Sending
- Verify SMTP credentials in `.env`
- Check if SMTP provider requires port changes (usually 587 for TLS)
- Verify sender email is confirmed with provider
- Check `/logs/` for error messages

### Registration Not Working
- Verify email validation pattern matches your domain
- Check file permissions on `/data/` directory
- Ensure PHP can write to `/data/` directory

### Books Not Displaying
- Check if `data/books.json` exists and is readable
- Verify JSON syntax is valid
- Check `books/` directory permissions

### Session Issues
- Ensure `/sessions/` directory has write permissions
- Check PHP session configuration
- Clear sessions folder if experiencing login issues

## 📝 Features in Detail

### Book Management
- Add books with title, author, ISBN, category, description
- Upload book cover images
- Track book availability status
- View borrowing history per book
- Categorize books for easy discovery

### Borrow System
- Request to borrow available books
- Owner receives notification of requests
- Admin can approve/reject requests
- Automatic reminders for returns
- View borrow history and favorites

### Admin Features
- Real-time dashboard with animations
- User approval workflow
- Borrow request management
- Content moderation
- Activity logging for audit trails
- Export reports as files

## 🤝 Contributing

Contributions are welcome! Feel free to:
- Report bugs
- Suggest features
- Submit pull requests
- Improve documentation

## 📄 License

This project is open source and available under the MIT License.

## 📞 Support

For issues, questions, or suggestions:
- Email: support@openshelf.free.nf
- Submit feedback via: `/contact/`
- Check FAQs: `/faq.php`

## 🎨 Customization

The system is easily customizable:
- Modify colors in `assets/css/style.css`
- Change branding in `includes/header.php`
- Update email templates in `emails/` directory
- Customize dashboard in `admin/dashboard/index.php`

## 📚 Additional Pages

- **About**: `/about.php` - Information about OpenShelf
- **FAQ**: `/faq.php` - Frequently asked questions
- **Guidelines**: `/guidelines.php` - Community guidelines
- **Privacy Policy**: `/privacy.php` - Data privacy information
- **Terms of Service**: `/terms.php` - Terms and conditions
- **Contact**: `/contact.php` - Contact form

## Future Enhancements

- [ ] Database migration support
- [ ] Advanced search and filtering
- [ ] Rating and review system
- [ ] Wishlist feature
- [ ] User recommendations
- [ ] Mobile application
- [ ] Real-time notifications (WebSockets)
- [ ] Integration with library management standards

---

**OpenShelf** - Making book sharing simple and accessible to everyone! 📚✨
