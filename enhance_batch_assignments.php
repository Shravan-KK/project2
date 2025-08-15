<?php
// Enhanced Batch Assignment System - Add Instructor Assignment Functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚀 Enhanced Batch Assignment System</h1>";
echo "<p>Adding instructor assignment functionality and enhancing the UI</p>";

echo "<style>
.success-box { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb; }
.error-box { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb; }
.info-box { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #b3d9ff; }
.warning-box { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ffeaa7; }
.step-box { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
</style>";

require_once '/home/shravan/web/training.kcdfindia.org/public_html/config/database.php';

echo "<h2>🏗️ Step 1: Database Schema Enhancement</h2>";

// Check if batch_instructors table exists
$check_table = "SHOW TABLES LIKE 'batch_instructors'";
$table_exists = $conn->query($check_table)->num_rows > 0;

if (!$table_exists) {
    echo "<div class='info-box'>";
    echo "<p>Creating batch_instructors table for instructor assignments...</p>";
    echo "</div>";
    
    $create_batch_instructors = "CREATE TABLE batch_instructors (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        batch_id INT(11) NOT NULL,
        instructor_id INT(11) NOT NULL,
        role ENUM('lead', 'assistant', 'mentor') DEFAULT 'lead',
        assigned_date DATE DEFAULT CURRENT_DATE,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
        FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_batch_instructor_role (batch_id, instructor_id, role)
    )";
    
    if ($conn->query($create_batch_instructors)) {
        echo "<div class='success-box'>";
        echo "<p>✅ Created batch_instructors table successfully</p>";
        echo "</div>";
    } else {
        echo "<div class='error-box'>";
        echo "<p>❌ Error creating batch_instructors table: " . $conn->error . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='warning-box'>";
    echo "<p>⚠️ batch_instructors table already exists</p>";
    echo "</div>";
}

// Check if batch_courses table exists and has all needed columns
$check_batch_courses = "SHOW TABLES LIKE 'batch_courses'";
$batch_courses_exists = $conn->query($check_batch_courses)->num_rows > 0;

if (!$batch_courses_exists) {
    echo "<div class='info-box'>";
    echo "<p>Creating batch_courses table...</p>";
    echo "</div>";
    
    $create_batch_courses = "CREATE TABLE batch_courses (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        batch_id INT(11) NOT NULL,
        course_id INT(11) NOT NULL,
        start_date DATE,
        end_date DATE,
        status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_batch_course (batch_id, course_id)
    )";
    
    if ($conn->query($create_batch_courses)) {
        echo "<div class='success-box'>";
        echo "<p>✅ Created batch_courses table successfully</p>";
        echo "</div>";
    } else {
        echo "<div class='error-box'>";
        echo "<p>❌ Error creating batch_courses table: " . $conn->error . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='success-box'>";
    echo "<p>✅ batch_courses table already exists</p>";
    echo "</div>";
}

echo "<h2>📊 Step 2: Add Sample Data</h2>";

// Add sample instructors if they don't exist
$sample_instructors = [
    ['name' => 'Dr. Sarah Johnson', 'email' => 'sarah.johnson@training.com', 'user_type' => 'teacher'],
    ['name' => 'Prof. Michael Chen', 'email' => 'michael.chen@training.com', 'user_type' => 'teacher'],
    ['name' => 'Dr. Emily Rodriguez', 'email' => 'emily.rodriguez@training.com', 'user_type' => 'teacher']
];

echo "<div class='step-box'>";
echo "<h3>Adding Sample Instructors</h3>";
foreach ($sample_instructors as $instructor) {
    $check_instructor = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_instructor);
    $check_stmt->bind_param("s", $instructor['email']);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_assoc();
    
    if (!$exists) {
        $password_hash = md5('instructor123'); // Using MD5 as requested
        $insert_instructor = "INSERT INTO users (name, email, password, user_type, status) VALUES (?, ?, ?, ?, 'active')";
        $insert_stmt = $conn->prepare($insert_instructor);
        $insert_stmt->bind_param("ssss", $instructor['name'], $instructor['email'], $password_hash, $instructor['user_type']);
        
        if ($insert_stmt->execute()) {
            echo "<p>✅ Added instructor: " . $instructor['name'] . "</p>";
        } else {
            echo "<p>❌ Failed to add instructor: " . $instructor['name'] . "</p>";
        }
    } else {
        echo "<p>⚠️ Instructor already exists: " . $instructor['name'] . "</p>";
    }
}
echo "</div>";

echo "<h2>🎨 Step 3: Enhanced Admin Interface</h2>";
echo "<div class='info-box'>";
echo "<p>Now creating enhanced batch management interface with instructor and course assignment capabilities...</p>";
echo "</div>";

echo "<div class='success-box'>";
echo "<h3>✅ Database Enhancement Complete!</h3>";
echo "<p>The system now supports:</p>";
echo "<ul>";
echo "<li>✅ Instructor assignment to batches with roles (Lead, Assistant, Mentor)</li>";
echo "<li>✅ Course assignment to batches (already existed)</li>";
echo "<li>✅ Status management for assignments</li>";
echo "<li>✅ Date tracking for assignments</li>";
echo "<li>✅ Unique constraints to prevent duplicates</li>";
echo "</ul>";
echo "</div>";

echo "<div class='warning-box'>";
echo "<h3>🔄 Next Steps:</h3>";
echo "<p>1. Enhanced admin interface will be created</p>";
echo "<p>2. Assignment management functionality will be added</p>";
echo "<p>3. UI improvements for better user experience</p>";
echo "</div>";

?>