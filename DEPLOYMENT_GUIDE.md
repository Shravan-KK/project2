# 🚀 Teaching Management System - Deployment Guide

This guide will help you deploy the Teaching Management System to your production server.

## 📋 Prerequisites

- Web hosting with PHP 7.4+ and MySQL 5.7+
- FTP/SFTP access to your server
- Database access through cPanel/hosting control panel

## 🔧 Step 1: Update Database Configuration

1. **Edit `config/database.php`** with your production database credentials:

```php
// Production environment
$host = 'localhost'; // Usually localhost
$dbname = 'your_actual_database_name'; // e.g., shravan_teaching_management
$username = 'your_actual_username'; // e.g., shravan_dbuser
$password = 'your_actual_password'; // Your database password
```

## 📁 Step 2: Upload Files

Upload all files to your server using FTP/SFTP:

```
/public_html/
├── admin/
├── config/
├── includes/
├── student/
├── teacher/
├── index.php
├── setup_production_database.php
└── ... (all other files)
```

## 🗄️ Step 3: Setup Database

### Option A: Use the Automated Setup (Recommended)

1. Visit: `https://yoursite.com/setup_production_database.php`
2. This will automatically:
   - Detect your MySQL version
   - Use compatible collation
   - Create all tables with proper relationships
   - Insert default users

### Option B: Manual SQL Import

If Option A doesn't work, import the SQL file manually:

1. Access your hosting control panel (cPanel, etc.)
2. Go to phpMyAdmin
3. Create a new database (if not already created)
4. Import the file: `database_export_compatible.sql`

## 🔍 Step 4: Test Database Connection

Visit: `https://yoursite.com/config/setup_production_db.php`

This will test your database connection and show any issues.

## 🔐 Step 5: Default Login Credentials

Once setup is complete, you can login with:

- **Admin:** admin@tms.com / admin123
- **Teacher:** teacher@tms.com / teacher123  
- **Student:** student@tms.com / student123

**⚠️ Important:** Change these passwords immediately after first login!

## 🧹 Step 6: Security Cleanup

After successful deployment:

1. **Delete setup files:**
   - `setup_production_database.php`
   - `config/setup_production_db.php`
   - `test_errors.php`
   - `DEPLOYMENT_GUIDE.md`

2. **Disable error display** in production by commenting out this line in `config/database.php`:
   ```php
   // require_once __DIR__ . '/error_display.php';
   ```

## ❗ Troubleshooting Common Issues

### Database Connection Failed
- Check database credentials in `config/database.php`
- Ensure database exists and user has proper permissions
- Contact your hosting provider for correct connection details

### Unknown Collation Error
- Your MySQL version doesn't support `utf8mb4_0900_ai_ci`
- Use the automated setup script which detects your MySQL version
- Or manually change collation to `utf8mb4_unicode_ci` in SQL files

### Permission Denied Errors
- Check file permissions (644 for files, 755 for directories)
- Ensure the web server can read all files

### Blank Pages
- Check error logs in your hosting control panel
- Temporarily enable error display to see specific issues

## 📞 Getting Help

If you encounter issues:

1. Check the error display in your browser (now enabled)
2. Review your hosting provider's error logs
3. Contact your hosting provider for database connectivity issues
4. Ensure your hosting meets the minimum requirements

## 🎉 Success!

Once deployed successfully:

1. Visit your site: `https://yoursite.com`
2. Login with admin credentials
3. Change default passwords
4. Start adding your courses and users!

---

**Note:** This system automatically detects local vs production environments, so the same codebase works for both development and production.