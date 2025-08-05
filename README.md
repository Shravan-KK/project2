# Teaching Management System

A comprehensive online teaching management system built with PHP, MySQL, HTML, and Tailwind CSS. This system allows educational institutions to manage courses, students, teachers, and learning activities online.

## Features

### 🎓 Core Features
- **User Management**: Admin, Teacher, and Student roles with different permissions
- **Course Management**: Create, edit, and manage courses with detailed information 
- **Student Enrollment**: Easy course enrollment system for students
- **Progress Tracking**: Monitor student progress through courses
- **Assignment System**: Create and submit assignments with grading
- **Messaging System**: Communication between users
- **Payment Tracking**: Basic payment and revenue tracking

### 👨‍💼 Admin Features
- Dashboard with comprehensive statistics
- User management (add, edit, delete users)
- Course oversight and management
- Enrollment monitoring
- Revenue reports
- System-wide analytics

### 👨‍🏫 Teacher Features
- Personal dashboard with course overview
- Course creation and management
- Student progress monitoring
- Assignment creation and grading
- Communication with students

### 👨‍🎓 Student Features
- Course browsing and enrollment
- Learning progress tracking
- Assignment submission
- Grade viewing
- Course completion certificates

## Technology Stack

- **Backend**: PHP 7.4+
- **Database**: MySQL 5.7+
- **Frontend**: HTML5, Tailwind CSS
- **Icons**: Font Awesome
- **Server**: Apache/Nginx (MAMP/XAMPP compatible)

## Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- MAMP, XAMPP, or similar local development environment

### Setup Instructions

1. **Clone or Download the Project**
   ```bash
   # If using git
   git clone <repository-url>
   # Or download and extract the ZIP file
   ```

2. **Database Setup**
   - Start your local server (MAMP/XAMPP)
   - Open your web browser and navigate to: `http://localhost/project2/setup_database.php`
   - This will automatically create the database and all required tables
   - You should see success messages for each table creation

3. **Database Configuration**
   - Edit `config/database.php` if your database credentials are different:
   ```php
   $host = 'localhost';
   $dbname = 'teaching_management';
   $username = 'root';  // Your MySQL username
   $password = 'root';  // Your MySQL password (default for MAMP)
   ```

4. **Access the System**
   - Navigate to: `http://localhost/project2/`
   - You'll see the login page

### Default Login Credentials

The system comes with pre-configured accounts:

#### Admin Account
- **Email**: admin@tms.com
- **Password**: admin123

#### Teacher Account
- **Email**: teacher@tms.com
- **Password**: teacher123

#### Student Account
- **Email**: student@tms.com
- **Password**: student123

## System Structure

```
project2/
├── index.php                 # Main login page
├── register.php              # User registration
├── courses.php               # Public courses listing
├── logout.php                # Logout functionality
├── setup_database.php        # Database setup script
├── README.md                 # This file
├── config/
│   └── database.php          # Database configuration
├── includes/
│   └── functions.php         # Helper functions
├── admin/                    # Admin panel files
│   ├── dashboard.php
│   ├── users.php
│   ├── courses.php
│   └── ...
├── teacher/                  # Teacher panel files
│   ├── dashboard.php
│   ├── courses.php
│   ├── students.php
│   └── ...
├── student/                  # Student panel files
│   ├── dashboard.php
│   ├── courses.php
│   ├── assignments.php
│   └── ...
└── uploads/                  # File uploads directory
```

## Database Schema

The system includes the following main tables:

- **users**: User accounts and profiles
- **courses**: Course information and details
- **enrollments**: Student course enrollments
- **lessons**: Course lessons and content
- **assignments**: Course assignments
- **submissions**: Student assignment submissions
- **messages**: User messaging system
- **payments**: Payment tracking

## Usage Guide

### For Administrators
1. Login with admin credentials
2. Access the admin dashboard
3. Manage users, courses, and system settings
4. Monitor enrollments and revenue
5. Generate reports

### For Teachers
1. Login with teacher credentials (or register as a teacher)
2. Create and manage courses
3. Add lessons and assignments
4. Monitor student progress
5. Grade assignments

### For Students
1. Register as a student or login with student credentials
2. Browse available courses
3. Enroll in courses
4. Access course content and assignments
5. Track your progress

## Security Features

- Password hashing using PHP's `password_hash()`
- SQL injection prevention with prepared statements
- Session-based authentication
- Role-based access control
- Input sanitization and validation

## Customization

### Styling
- The system uses Tailwind CSS for styling
- Colors and themes can be customized by modifying Tailwind classes
- Icons are from Font Awesome and can be changed

### Functionality
- Add new features by extending the existing structure
- Modify database schema as needed
- Add new user roles if required

## Troubleshooting

### Common Issues

1. **Database Connection Error**
   - Check your database credentials in `config/database.php`
   - Ensure MySQL service is running
   - Verify database name exists

2. **Page Not Found**
   - Ensure your web server is running
   - Check file permissions
   - Verify URL paths are correct

3. **Login Issues**
   - Clear browser cookies and cache
   - Verify login credentials
   - Check if database tables exist

### Support
If you encounter any issues:
1. Check the error logs in your web server
2. Verify all prerequisites are met
3. Ensure proper file permissions

## Future Enhancements

Potential improvements for the system:
- Video streaming integration
- Live chat functionality
- Advanced analytics and reporting
- Mobile app development
- Payment gateway integration
- Email notifications
- Certificate generation
- Discussion forums

## License

This project is open source and available under the MIT License.

## Contributing

Feel free to contribute to this project by:
- Reporting bugs
- Suggesting new features
- Submitting pull requests
- Improving documentation

---

**Note**: This is a basic teaching management system. For production use, consider implementing additional security measures, backup systems, and scalability features. 