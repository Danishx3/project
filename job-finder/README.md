# Part-Time Job Finder - PHP & MySQL

A comprehensive job finder platform with separate dashboards for Users, Agents, and Admin.

## 🚀 Quick Start

### Prerequisites
- XAMPP (PHP 7.4+, MySQL 5.7+)
- Web browser

### Installation Steps

1. **Start XAMPP**
   - Start Apache and MySQL services

2. **Create Database**
   - Open phpMyAdmin: `http://localhost/phpmyadmin`
   - Create a new database named `job_finder`
   - Import the schema: `database/schema.sql`

3. **Configure Database**
   - Edit `config/database.php` if needed (default credentials: root/no password)

4. **Access Application**
   - Open browser: `http://localhost/job-finder`

### Default Login Credentials

**Admin Account:**
- Email: `admin@jobfinder.com`
- Password: `Admin@123`

## 📁 Project Structure

```
job-finder/
├── config/              # Configuration files
├── includes/            # Common includes (header, footer, functions)
├── auth/                # Authentication pages
├── user/                # User dashboard pages
├── agent/               # Agent dashboard pages
├── admin/               # Admin dashboard pages
├── api/                 # REST API endpoints
├── assets/              # CSS, JS, images
├── cron/                # Cron jobs for reminders
├── database/            # Database schema
└── uploads/             # User uploads (resumes)
```

## ✨ Features

### User Features
- ✅ Registration & Login
- ✅ Browse & Search Jobs
- ✅ Advanced Filters (category, location, salary, type)
- ✅ Apply for Jobs
- ✅ Set Reminders
- ✅ Track Application Status
- ✅ Profile Management

### Agent Features
- ✅ Post New Jobs
- ✅ Manage Job Listings
- ✅ Verify Applications
- ✅ View Applicants
- ✅ Track Statistics

### Admin Features
- ✅ System Overview Dashboard
- ✅ Manage Users & Agents
- ✅ Manage All Jobs
- ✅ Monitor Applications
- ✅ Activity Logging
- ✅ Role Management
- ✅ Analytics

## 🔐 Security Features

- Password hashing with `password_hash()`
- Session-based authentication
- Role-based access control (RBAC)
- SQL injection prevention (prepared statements)
- XSS protection
- CSRF token protection
- Input sanitization

## 🎨 Design

- Modern dark theme with glassmorphism
- Fully responsive (mobile, tablet, desktop)
- Bootstrap 5 framework
- Font Awesome icons
- Smooth animations and transitions
- Premium UI/UX

## 📧 Email Configuration

To enable email notifications:

1. Edit `config/constants.php`
2. Update SMTP settings:
   ```php
   define('SMTP_HOST', 'smtp.gmail.com');
   define('SMTP_PORT', 587);
   define('SMTP_USER', 'your-email@gmail.com');
   define('SMTP_PASS', 'your-app-password');
   ```

## ⏰ Setting Up Cron Jobs

For reminder notifications, set up a cron job:

```bash
# Run every hour
0 * * * * php /path/to/job-finder/cron/check_reminders.php
```

## 🛠️ Technologies Used

- **Backend:** PHP 7.4+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript
- **Framework:** Bootstrap 5
- **Icons:** Font Awesome 6
- **Fonts:** Google Fonts (Inter)

## 📱 Browser Support

- Chrome (latest)
- Firefox (latest)
- Safari (latest)
- Edge (latest)

## 🐛 Troubleshooting

### Database Connection Error
- Check XAMPP MySQL is running
- Verify database credentials in `config/database.php`
- Ensure database `job_finder` exists

### Login Issues
- Clear browser cache and cookies
- Check if user exists in database
- Verify password is correct

### File Upload Issues
- Check `uploads/resumes` folder exists
- Verify folder permissions (755)
- Check PHP `upload_max_filesize` setting

## 📝 License

This project is for educational purposes.

## 👨‍💻 Support

For issues or questions, contact the development team.

---

**Version:** 1.0.0  
**Last Updated:** February 2026
