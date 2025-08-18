<?php
require_once 'config/database.php';

echo "<h2>Fixing Batch Tables</h2>";

try {
    // Create batches table if it doesn't exist
    $create_batches = "CREATE TABLE IF NOT EXISTS batches (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        start_date DATE,
        end_date DATE,
        max_students INT(11) DEFAULT 30,
        current_students INT(11) DEFAULT 0,
        status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_dates (start_date, end_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($create_batches)) {
        echo "<p style='color: green;'>✓ Batches table created/verified</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating batches table: " . $conn->error . "</p>";
    }

    // Create batch_courses table
    $create_batch_courses = "CREATE TABLE IF NOT EXISTS batch_courses (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        batch_id INT(11) NOT NULL,
        course_id INT(11) NOT NULL,
        start_date DATE,
        end_date DATE,
        status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_batch_id (batch_id),
        INDEX idx_course_id (course_id),
        INDEX idx_status (status),
        UNIQUE KEY unique_batch_course (batch_id, course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($create_batch_courses)) {
        echo "<p style='color: green;'>✓ Batch_courses table created/verified</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating batch_courses table: " . $conn->error . "</p>";
    }

    // Create batch_instructors table
    $create_batch_instructors = "CREATE TABLE IF NOT EXISTS batch_instructors (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        batch_id INT(11) NOT NULL,
        instructor_id INT(11) NOT NULL,
        role ENUM('lead', 'assistant', 'mentor') DEFAULT 'lead',
        assigned_date DATE DEFAULT (CURRENT_DATE),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_batch_id (batch_id),
        INDEX idx_instructor_id (instructor_id),
        INDEX idx_status (status),
        UNIQUE KEY unique_batch_instructor_role (batch_id, instructor_id, role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($create_batch_instructors)) {
        echo "<p style='color: green;'>✓ Batch_instructors table created/verified</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating batch_instructors table: " . $conn->error . "</p>";
    }

    // Check if enrollments table has batch_id column
    $check_enrollments = "SHOW COLUMNS FROM enrollments LIKE 'batch_id'";
    $result = $conn->query($check_enrollments);
    
    if ($result->num_rows == 0) {
        // Add batch_id column to enrollments table
        $add_batch_id = "ALTER TABLE enrollments ADD COLUMN batch_id INT(11) NULL AFTER course_id";
        if ($conn->query($add_batch_id)) {
            echo "<p style='color: green;'>✓ Added batch_id column to enrollments table</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding batch_id column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ batch_id column already exists in enrollments table</p>";
    }

    // Check if users table has status column
    $check_users_status = "SHOW COLUMNS FROM users LIKE 'status'";
    $result = $conn->query($check_users_status);
    
    if ($result->num_rows == 0) {
        // Add status column to users table
        $add_user_status = "ALTER TABLE users ADD COLUMN status ENUM('active', 'inactive') DEFAULT 'active' AFTER address";
        if ($conn->query($add_user_status)) {
            echo "<p style='color: green;'>✓ Added status column to users table</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding status column to users: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ status column already exists in users table</p>";
    }

    // Add some sample batches if none exist
    $count_batches = "SELECT COUNT(*) as count FROM batches";
    $result = $conn->query($count_batches);
    $row = $result->fetch_assoc();
    
    if ($row['count'] == 0) {
        $sample_batches = "INSERT INTO batches (name, description, start_date, end_date, max_students) VALUES 
        ('Web Development Batch 2024', 'Comprehensive web development training', '2024-01-15', '2024-06-15', 25),
        ('Data Science Batch 2024', 'Python and machine learning focus', '2024-02-01', '2024-07-01', 20),
        ('Mobile App Development', 'React Native and Flutter training', '2024-03-01', '2024-08-01', 30)";
        
        if ($conn->query($sample_batches)) {
            echo "<p style='color: green;'>✓ Added sample batches</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding sample batches: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Batches already exist ({$row['count']} found)</p>";
    }

    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✓ Batch Tables Setup Complete!</h3>";
    echo "<p>All required tables have been created. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='admin/batches.php'>Access the Batches page</a></li>";
    echo "<li><a href='admin/dashboard.php'>Go to Admin Dashboard</a></li>";
    echo "</ul>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Error</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

$conn->close();
?>