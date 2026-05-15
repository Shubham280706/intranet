# WorkSpace Intranet 🏢

A modern, full-featured employee intranet platform built with PHP, MySQL, and vanilla JavaScript. Featuring real-time task management, employee messaging, announcements, and profile management with photo uploads.

![Version](https://img.shields.io/badge/version-1.0.0-blue.svg)
![PHP](https://img.shields.io/badge/PHP-8.2+-green.svg)
![MySQL](https://img.shields.io/badge/MySQL-5.7+-blue.svg)
![License](https://img.shields.io/badge/license-MIT-green.svg)

## ✨ Features

### 🔐 Authentication & Security
- Secure login with bcrypt password hashing
- Session management with timeout
- Role-based access control (Admin/Employee)
- CSRF protection and XSS prevention
- Secure PDO database queries

### 📋 Task Management
- Create, assign, and track tasks
- Priority levels (High, Medium, Low)
- Status tracking (Pending, In Progress, Completed)
- Due date management
- Task filtering and sorting
- Admin dashboard with task statistics

### 💬 Messaging
- Direct employee messaging
- Real-time message notifications
- Mark messages as read
- Message history
- Unread message counters
- Notification badges

### 📢 Announcements
- Create and post company announcements
- Pin important announcements
- Category-based organization
- Announcement history
- Admin-only posting (configurable)

### 👤 Profile Management
- User profiles with detailed information
- **Profile photo upload** (JPG, PNG, GIF, WebP)
- Circular avatar display across the app
- Department management
- Role assignment
- Password management

### 🔔 Real-time Notifications
- 5-second polling for new messages and tasks
- Popup notifications with animations
- Sidebar badge updates
- Auto-dismiss notifications
- Click-to-navigate functionality

### 👥 Employee Directory
- View all employees
- Search and filter employees
- Online status indicators
- Department information
- Admin controls for user management
- Bulk operations support

### 📊 Dashboard
- Personalized dashboard for each role
- Statistics cards (tasks, messages, employees)
- Recent tasks display
- Latest announcements
- Online users list
- Quick access to key features

## 🚀 Quick Start

### Prerequisites
- PHP 8.2 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite
- XAMPP (recommended for local development)

### Installation

1. **Clone the repository**
```bash
git clone https://github.com/Shubham280706/intranet.git
cd intranet
```

2. **Set up the database**
```bash
# Create MySQL database
mysql -u root -p < database.sql

# Or manually in phpMyAdmin:
# - Create database: intranet_db
# - Import database.sql
```

3. **Configure the application**
```bash
# Copy config template (if needed)
cp config.php.example config.php

# Edit config.php with your database credentials:
# DB_HOST: localhost (or your server)
# DB_USER: root
# DB_PASS: your_password
# DB_NAME: intranet_db
```

4. **Set up file permissions**
```bash
# Make uploads directory writable
chmod 777 uploads/avatars
```

5. **Access the application**
```
http://localhost/intranet/
```

### Default Test Accounts

The database comes with sample accounts:

| Email | Password | Role |
|-------|----------|------|
| admin@company.com | admin123 | Admin |
| employee@company.com | emp123 | Employee |

⚠️ **Change these passwords in production!**

## 📁 Project Structure

```
intranet/
├── index.php              # Login page
├── dashboard.php          # Main dashboard
├── tasks.php              # Task management
├── chat.php               # Messaging
├── announcements.php      # Announcements
├── employees.php          # Employee directory
├── profile.php            # User profile
├── logout.php             # Logout handler
├── config.php             # Database configuration
├── database.sql           # Database schema
│
├── api/                   # API endpoints
│   ├── auth.php          # Authentication
│   ├── tasks.php         # Task operations
│   ├── chat.php          # Messaging operations
│   ├── employees.php     # Employee & photo upload
│   ├── announcements.php # Announcement operations
│   ├── notifications.php # Notification operations
│   └── poll.php          # Real-time notifications
│
├── includes/              # Reusable components
│   ├── auth_check.php    # Authentication check
│   ├── avatar.php        # Avatar display helper
│   ├── header.php        # Header component
│   └── sidebar.php       # Sidebar navigation
│
├── assets/                # Static assets
│   ├── css/
│   │   └── style.css     # Main stylesheet
│   └── js/
│       └── app.js        # Shared JavaScript utilities
│
└── uploads/
    └── avatars/          # User profile photos
```

## 🛠️ Technology Stack

**Backend:**
- PHP 8.2+
- MySQL 5.7+
- PDO (Database abstraction)

**Frontend:**
- HTML5
- CSS3 (Custom design system)
- Vanilla JavaScript (No frameworks)
- Fetch API for AJAX

**Features:**
- RESTful API architecture
- Session-based authentication
- Real-time polling
- Responsive design
- Accessibility compliant

## 📝 API Documentation

### Authentication
- `POST /api/auth.php` - Login, user creation, management

### Tasks
- `GET /api/tasks.php?action=get_all` - Fetch all tasks
- `POST /api/tasks.php` - Create/update tasks
- `DELETE /api/tasks.php` - Delete tasks

### Messaging
- `GET /api/chat.php?action=users` - Get message list
- `GET /api/chat.php?action=messages&with=ID` - Fetch conversation
- `POST /api/chat.php` - Send message

### Notifications
- `GET /api/poll.php` - Poll for new notifications
- `GET /api/notifications.php` - Get notification list
- `POST /api/notifications.php` - Mark as read

### Profile Photos
- `POST /api/employees.php?action=upload_photo` - Upload profile photo
- Accepted formats: JPG, PNG, GIF, WebP
- Max file size: 2MB

## 🔒 Security Features

✅ **Password Security**
- Bcrypt hashing (cost: 12)
- Password strength validation (min 6 chars)
- Secure session handling

✅ **Input Validation**
- PDO prepared statements
- HTML entity escaping
- MIME type validation for file uploads

✅ **HTTP Security Headers**
- X-Frame-Options: SAMEORIGIN
- X-Content-Type-Options: nosniff
- X-XSS-Protection enabled
- Content-Security-Policy configured

✅ **Session Security**
- HTTPOnly cookies
- SameSite=Strict
- Session timeout (1 hour)
- Session regeneration on login

## 📸 Profile Photo Upload

### Upload Features
- Drag & drop or file picker
- Instant preview
- Automatic resize and optimization
- Circular avatar display
- Supported formats: JPG, PNG, GIF, WebP
- File size limit: 2MB

### Upload Process
1. Click "Change Photo" on profile page
2. Select an image from your computer
3. Preview appears instantly
4. Auto-uploads and updates across the app
5. Photo appears in:
   - Sidebar avatar
   - Top header
   - Chat conversations
   - Employee directory
   - Dashboard online users

## 🎨 Design System

The app uses a custom design system inspired by Zoho and Notion:

**Colors:**
- Primary: #2563EB (Blue)
- Success: #059669 (Green)
- Warning: #D97706 (Orange)
- Danger: #DC2626 (Red)

**Typography:**
- Font: Inter (system fallback)
- Responsive sizing
- Accessible contrast ratios

**Components:**
- Cards with shadows
- Badges for status
- Modal dialogs
- Toast notifications
- Loading spinners
- Empty states

## 🚦 Real-time Features

### Notification System
- Polls `/api/poll.php` every 5 seconds
- Shows popup notifications for:
  - New messages (💬 green border)
  - New tasks (📋 blue border)
- Auto-dismisses after 5 seconds
- Updates sidebar badges in real-time

### Online Status
- Updates when user logs in/out
- Shows online users on dashboard
- Online indicator dots on avatars

## 📱 Responsive Design

- Mobile-first approach
- Tablet optimized
- Desktop enhanced
- Touch-friendly interactions
- Hamburger menu on mobile

## 🔧 Configuration

Edit `config.php` to customize:

```php
// Database
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'intranet_db');
define('DB_USER', 'root');
define('DB_PASS', 'root123');

// Application
define('APP_NAME', 'WorkSpace Intranet');
define('APP_URL', 'http://192.168.1.100/intranet');
define('SESSION_TIMEOUT', 3600); // 1 hour

// Security
define('MAX_UPLOAD_SIZE', 2 * 1024 * 1024); // 2MB
```

## 🐛 Troubleshooting

### Photos not uploading?
1. Check upload directory permissions: `chmod 777 uploads/avatars`
2. Verify PHP file_uploads is enabled
3. Check max_upload_size in php.ini

### Database connection failed?
1. Ensure MySQL is running
2. Verify credentials in config.php
3. Check database exists: `mysql -u root -p intranet_db`

### Pages not loading?
1. Enable error reporting in config.php for development
2. Check Apache error logs
3. Verify .htaccess for URL rewriting

## 📊 Database Schema

**Key Tables:**
- `users` - Employee accounts and profiles
- `tasks` - Task management
- `messages` - Direct messaging
- `announcements` - Company announcements
- `notifications` - Notification history

See `database.sql` for complete schema.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit changes (`git commit -m 'Add amazing feature'`)
4. Push to branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👨‍💻 Author

**Shubham Shah**
- GitHub: [@Shubham280706](https://github.com/Shubham280706)

## 🙏 Acknowledgments

- Built with PHP, MySQL, and vanilla JavaScript
- Inspired by modern intranet and project management platforms
- Uses Font Awesome-style icons via custom SVGs
- Google Fonts (Inter) for typography

## 📞 Support

For issues and feature requests, please:
1. Check existing issues on GitHub
2. Create a new issue with detailed information
3. Include error messages and screenshots
4. Specify your environment (OS, PHP version, MySQL version)

## 🗺️ Roadmap

Future enhancements:
- [ ] Email notifications
- [ ] File sharing/attachments
- [ ] Advanced reporting
- [ ] Mobile app
- [ ] Dark mode
- [ ] Multi-language support
- [ ] Two-factor authentication
- [ ] API documentation (Swagger/OpenAPI)

---

**Made with ❤️ for team collaboration**
