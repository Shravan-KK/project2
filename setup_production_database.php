<?php
/**
 * Production Database Setup Script
 * Compatible with older MySQL versions (5.7+)
 * 
 * This script creates all necessary tables with compatible collations
 * Run this on your production server after setting up database credentials
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Database Setup</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin:10px 0;} .error{background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin:10px 0;} .info{background:#d1ecf1;color:#0c5460;padding:10px;border-radius:5px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>🚀 Teaching Management System - Database Setup</h1>";

// Check MySQL version
$version_result = $conn->query("SELECT VERSION() as version");
$mysql_version = $version_result->fetch_assoc()['version'];
echo "<div class='info'><strong>MySQL Version:</strong> $mysql_version</div>";

// Determine collation based on MySQL version
$collation = "utf8mb4_unicode_ci"; // Compatible with MySQL 5.7+
if (version_compare($mysql_version, '8.0', '>=')) {
    $collation = "utf8mb4_0900_ai_ci"; // Use newer collation for MySQL 8.0+
}

echo "<div class='info'><strong>Using Collation:</strong> $collation</div>";

$tables_created = 0;
$errors = 0;

// Array of table creation queries
$table_queries = [
    "users" => "CREATE TABLE IF NOT EXISTS `users` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `email` varchar(100) NOT NULL,
        `password` varchar(255) NOT NULL,
        `user_type` enum('admin','teacher','student') NOT NULL,
        `phone` varchar(20) DEFAULT NULL,
        `address` text,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `email` (`email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "courses" => "CREATE TABLE IF NOT EXISTS `courses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(200) NOT NULL,
        `description` text,
        `teacher_id` int(11) DEFAULT NULL,
        `category` varchar(100) DEFAULT NULL,
        `price` decimal(10,2) DEFAULT '0.00',
        `duration` varchar(50) DEFAULT NULL,
        `level` enum('beginner','intermediate','advanced') DEFAULT 'beginner',
        `status` enum('active','inactive') DEFAULT 'active',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `teacher_id` (`teacher_id`),
        CONSTRAINT `courses_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "enrollments" => "CREATE TABLE IF NOT EXISTS `enrollments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `course_id` int(11) NOT NULL,
        `batch_id` int(11) DEFAULT NULL,
        `enrollment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `status` enum('active','completed','dropped') DEFAULT 'active',
        `progress` int(3) DEFAULT '0',
        PRIMARY KEY (`id`),
        KEY `student_id` (`student_id`),
        KEY `course_id` (`course_id`),
        KEY `batch_id` (`batch_id`),
        CONSTRAINT `enrollments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `enrollments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "lessons" => "CREATE TABLE IF NOT EXISTS `lessons` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
        `title` varchar(200) NOT NULL,
        `description` text,
        `content` text NOT NULL,
        `video_url` varchar(500) DEFAULT NULL,
        `image_url` varchar(500) DEFAULT NULL,
        `attachment_url` varchar(500) DEFAULT NULL,
        `videos` text,
        `images` text,
        `lesson_order` int(11) DEFAULT '0',
        `duration` varchar(50) DEFAULT NULL,
        `is_free` tinyint(1) DEFAULT '0',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `course_id` (`course_id`),
        CONSTRAINT `lessons_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "assignments" => "CREATE TABLE IF NOT EXISTS `assignments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `course_id` int(11) NOT NULL,
        `title` varchar(200) NOT NULL,
        `description` text NOT NULL,
        `due_date` datetime NOT NULL,
        `points` int(11) DEFAULT '100',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `course_id` (`course_id`),
        CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "submissions" => "CREATE TABLE IF NOT EXISTS `submissions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `assignment_id` int(11) NOT NULL,
        `student_id` int(11) NOT NULL,
        `content` text,
        `file_path` varchar(500) DEFAULT NULL,
        `submitted_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `grade` decimal(5,2) DEFAULT NULL,
        `feedback` text,
        PRIMARY KEY (`id`),
        KEY `assignment_id` (`assignment_id`),
        KEY `student_id` (`student_id`),
        CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
        CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "announcements" => "CREATE TABLE IF NOT EXISTS `announcements` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `title` varchar(200) NOT NULL,
        `content` text NOT NULL,
        `target_audience` enum('students','teachers','both') NOT NULL,
        `course_id` int(11) DEFAULT NULL,
        `is_active` tinyint(1) DEFAULT '1',
        `created_by` int(11) DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `course_id` (`course_id`),
        KEY `created_by` (`created_by`),
        CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
        CONSTRAINT `announcements_ibfk_2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "messages" => "CREATE TABLE IF NOT EXISTS `messages` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `sender_id` int(11) NOT NULL,
        `receiver_id` int(11) NOT NULL,
        `subject` varchar(200) NOT NULL,
        `message` text NOT NULL,
        `is_read` tinyint(1) DEFAULT '0',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `sender_id` (`sender_id`),
        KEY `receiver_id` (`receiver_id`),
        CONSTRAINT `messages_ibfk_1` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `messages_ibfk_2` FOREIGN KEY (`receiver_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "payments" => "CREATE TABLE IF NOT EXISTS `payments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `student_id` int(11) NOT NULL,
        `course_id` int(11) NOT NULL,
        `amount` decimal(10,2) NOT NULL,
        `payment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `payment_method` varchar(50) DEFAULT NULL,
        `status` enum('pending','completed','failed') DEFAULT 'pending',
        `transaction_id` varchar(100) DEFAULT NULL,
        PRIMARY KEY (`id`),
        KEY `student_id` (`student_id`),
        KEY `course_id` (`course_id`),
        CONSTRAINT `payments_ibfk_1` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `payments_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "batches" => "CREATE TABLE IF NOT EXISTS `batches` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `description` text,
        `start_date` date DEFAULT NULL,
        `end_date` date DEFAULT NULL,
        `max_students` int(11) DEFAULT '30',
        `current_students` int(11) DEFAULT '0',
        `status` enum('active','inactive','completed') DEFAULT 'active',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "batch_courses" => "CREATE TABLE IF NOT EXISTS `batch_courses` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `batch_id` int(11) NOT NULL,
        `course_id` int(11) NOT NULL,
        `start_date` date DEFAULT NULL,
        `end_date` date DEFAULT NULL,
        `status` enum('active','inactive','completed') DEFAULT 'active',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_batch_course` (`batch_id`,`course_id`),
        KEY `batch_id` (`batch_id`),
        KEY `course_id` (`course_id`),
        CONSTRAINT `batch_courses_ibfk_1` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE CASCADE,
        CONSTRAINT `batch_courses_ibfk_2` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "classes" => "CREATE TABLE IF NOT EXISTS `classes` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(100) NOT NULL,
        `description` text,
        `course_id` int(11) NOT NULL,
        `instructor_id` int(11) NOT NULL,
        `start_date` date DEFAULT NULL,
        `end_date` date DEFAULT NULL,
        `schedule` varchar(200) DEFAULT NULL,
        `max_students` int(11) DEFAULT '30',
        `current_students` int(11) DEFAULT '0',
        `status` enum('active','inactive','completed','cancelled') DEFAULT 'active',
        `meeting_link` varchar(500) DEFAULT NULL,
        `meeting_platform` varchar(50) DEFAULT NULL,
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `course_id` (`course_id`),
        KEY `instructor_id` (`instructor_id`),
        CONSTRAINT `classes_ibfk_1` FOREIGN KEY (`course_id`) REFERENCES `courses` (`id`) ON DELETE CASCADE,
        CONSTRAINT `classes_ibfk_2` FOREIGN KEY (`instructor_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "class_enrollments" => "CREATE TABLE IF NOT EXISTS `class_enrollments` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `class_id` int(11) NOT NULL,
        `student_id` int(11) NOT NULL,
        `enrollment_date` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        `status` enum('active','completed','dropped') DEFAULT 'active',
        `attendance_count` int(11) DEFAULT '0',
        `progress` int(3) DEFAULT '0',
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_class_enrollment` (`class_id`,`student_id`),
        KEY `class_id` (`class_id`),
        KEY `student_id` (`student_id`),
        CONSTRAINT `class_enrollments_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE,
        CONSTRAINT `class_enrollments_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "class_sessions" => "CREATE TABLE IF NOT EXISTS `class_sessions` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `class_id` int(11) NOT NULL,
        `session_number` int(11) NOT NULL,
        `title` varchar(200) DEFAULT NULL,
        `description` text,
        `session_date` date DEFAULT NULL,
        `start_time` time DEFAULT NULL,
        `end_time` time DEFAULT NULL,
        `meeting_link` varchar(500) DEFAULT NULL,
        `recording_url` varchar(500) DEFAULT NULL,
        `notes` text,
        `status` enum('scheduled','completed','cancelled') DEFAULT 'scheduled',
        `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `class_id` (`class_id`),
        CONSTRAINT `class_sessions_ibfk_1` FOREIGN KEY (`class_id`) REFERENCES `classes` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation",

    "class_attendance" => "CREATE TABLE IF NOT EXISTS `class_attendance` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `session_id` int(11) NOT NULL,
        `student_id` int(11) NOT NULL,
        `status` enum('present','absent','late','excused') DEFAULT 'present',
        `notes` text,
        `marked_by` int(11) DEFAULT NULL,
        `marked_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `unique_attendance` (`session_id`,`student_id`),
        KEY `session_id` (`session_id`),
        KEY `student_id` (`student_id`),
        KEY `marked_by` (`marked_by`),
        CONSTRAINT `class_attendance_ibfk_1` FOREIGN KEY (`session_id`) REFERENCES `class_sessions` (`id`) ON DELETE CASCADE,
        CONSTRAINT `class_attendance_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
        CONSTRAINT `class_attendance_ibfk_3` FOREIGN KEY (`marked_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=$collation"
];

echo "<h2>Creating Tables...</h2>";

// Create tables
foreach ($table_queries as $table_name => $query) {
    echo "<div class='info'>Creating table: <strong>$table_name</strong></div>";
    
    if ($conn->query($query) === TRUE) {
        echo "<div class='success'>✅ Table '$table_name' created successfully</div>";
        $tables_created++;
    } else {
        echo "<div class='error'>❌ Error creating table '$table_name': " . $conn->error . "</div>";
        $errors++;
    }
}

// Add foreign key constraints for batches (if not already present)
echo "<h2>Adding Additional Constraints...</h2>";

$additional_constraints = [
    "ALTER TABLE `enrollments` ADD CONSTRAINT `fk_enrollment_batch` FOREIGN KEY (`batch_id`) REFERENCES `batches` (`id`) ON DELETE SET NULL"
];

foreach ($additional_constraints as $constraint) {
    $result = $conn->query($constraint);
    if ($result) {
        echo "<div class='success'>✅ Constraint added successfully</div>";
    } else {
        // Don't show error if constraint already exists
        if (strpos($conn->error, 'Duplicate key') === false && strpos($conn->error, 'already exists') === false) {
            echo "<div class='info'>ℹ️ Constraint may already exist: " . $conn->error . "</div>";
        }
    }
}

// Insert default users
echo "<h2>Creating Default Users...</h2>";

$default_users = [
    ['Admin User', 'admin@tms.com', password_hash('admin123', PASSWORD_DEFAULT), 'admin'],
    ['John Teacher', 'teacher@tms.com', password_hash('teacher123', PASSWORD_DEFAULT), 'teacher'],
    ['Jane Student', 'student@tms.com', password_hash('student123', PASSWORD_DEFAULT), 'student']
];

foreach ($default_users as $user) {
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $user[1]);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows > 0;
    
    if (!$exists) {
        $sql = "INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssss", $user[0], $user[1], $user[2], $user[3]);
        
        if ($stmt->execute()) {
            echo "<div class='success'>✅ Created {$user[3]}: {$user[1]}</div>";
        } else {
            echo "<div class='error'>❌ Error creating {$user[3]}: " . $stmt->error . "</div>";
        }
    } else {
        echo "<div class='info'>ℹ️ User {$user[1]} already exists</div>";
    }
}

echo "<h2>🎉 Setup Complete!</h2>";
echo "<div class='success'>";
echo "<strong>Summary:</strong><br>";
echo "• Tables created: $tables_created<br>";
echo "• Errors: $errors<br>";
echo "• MySQL Version: $mysql_version<br>";
echo "• Collation used: $collation<br>";
echo "</div>";

echo "<h3>Default Login Credentials:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@tms.com / admin123</li>";
echo "<li><strong>Teacher:</strong> teacher@tms.com / teacher123</li>";
echo "<li><strong>Student:</strong> student@tms.com / student123</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Delete this setup file for security: <code>setup_production_database.php</code></li>";
echo "<li>Update your login credentials through the admin panel</li>";
echo "<li>Start using your Teaching Management System!</li>";
echo "</ol>";

echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>🚀 Go to Application</a></p>";

echo "</body></html>";
?>