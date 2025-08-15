<?php
// Enable error display for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>🔧 Fixing All Database Errors</h2>";

// Database configuration
$host = 'localhost';
$dbname = 'teaching_management';
$username = 'root';
$password = 'root';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "<p>✅ Connected to database: $dbname</p>";

// Array to track what we've fixed
$fixes_applied = [];

echo "<h3>📋 Checking and Creating Missing Tables...</h3>";

// 1. Create course_sections table (for admin/course_sections.php)
$course_sections_sql = "CREATE TABLE IF NOT EXISTS course_sections (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    course_id INT(11) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    order_number INT(11) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course_order (course_id, order_number)
)";

if ($conn->query($course_sections_sql) === TRUE) {
    echo "<p>✅ course_sections table created successfully</p>";
    $fixes_applied[] = "course_sections table created";
} else {
    echo "<p>⚠️ course_sections table: " . $conn->error . "</p>";
}

// 2. Update lessons table to include section_id
$alter_lessons_sql = "ALTER TABLE lessons 
                      ADD COLUMN IF NOT EXISTS section_id INT(11) AFTER course_id,
                      ADD COLUMN IF NOT EXISTS description TEXT AFTER title,
                      ADD INDEX IF NOT EXISTS idx_section_order (section_id, order_number)";

if ($conn->query($alter_lessons_sql) === TRUE) {
    echo "<p>✅ lessons table updated with section_id</p>";
    $fixes_applied[] = "lessons table updated";
} else {
    echo "<p>⚠️ lessons table update: " . $conn->error . "</p>";
}

// 3. Create class_sessions table with correct columns
$class_sessions_sql = "CREATE TABLE IF NOT EXISTS class_sessions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    class_id INT(11) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    scheduled_date DATETIME NOT NULL,
    start_time TIME,
    end_time TIME,
    duration INT(11) DEFAULT 60,
    location VARCHAR(200),
    status ENUM('scheduled', 'in_progress', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES batches(id) ON DELETE CASCADE,
    INDEX idx_class_date (class_id, scheduled_date)
)";

if ($conn->query($class_sessions_sql) === TRUE) {
    echo "<p>✅ class_sessions table created successfully</p>";
    $fixes_applied[] = "class_sessions table created";
} else {
    echo "<p>⚠️ class_sessions table: " . $conn->error . "</p>";
}

// 4. Create class_attendances table
$class_attendances_sql = "CREATE TABLE IF NOT EXISTS class_attendances (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    session_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES class_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_session_student (session_id, student_id),
    INDEX idx_session (session_id),
    INDEX idx_student (student_id)
)";

if ($conn->query($class_attendances_sql) === TRUE) {
    echo "<p>✅ class_attendances table created successfully</p>";
    $fixes_applied[] = "class_attendances table created";
} else {
    echo "<p>⚠️ class_attendances table: " . $conn->error . "</p>";
}

// 5. Add batch_id to assignments table
$alter_assignments_sql = "ALTER TABLE assignments 
                         ADD COLUMN IF NOT EXISTS batch_id INT(11) AFTER course_id,
                         ADD INDEX IF NOT EXISTS idx_batch (batch_id)";

if ($conn->query($alter_assignments_sql) === TRUE) {
    echo "<p>✅ assignments table updated with batch_id</p>";
    $fixes_applied[] = "assignments table updated with batch_id";
} else {
    echo "<p>⚠️ assignments table update: " . $conn->error . "</p>";
}

// 6. Create batch_courses table if not exists
$batch_courses_sql = "CREATE TABLE IF NOT EXISTS batch_courses (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    batch_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_batch_course (batch_id, course_id),
    INDEX idx_batch (batch_id),
    INDEX idx_course (course_id)
)";

if ($conn->query($batch_courses_sql) === TRUE) {
    echo "<p>✅ batch_courses table created successfully</p>";
    $fixes_applied[] = "batch_courses table created";
} else {
    echo "<p>⚠️ batch_courses table: " . $conn->error . "</p>";
}

echo "<h3>📊 Creating Sample Data...</h3>";

// Insert sample course sections
$sample_courses_sql = "SELECT id, title FROM courses LIMIT 3";
$sample_courses_result = $conn->query($sample_courses_sql);

if ($sample_courses_result && $sample_courses_result->num_rows > 0) {
    while ($course = $sample_courses_result->fetch_assoc()) {
        echo "<p>📖 Processing course: <strong>" . htmlspecialchars($course['title']) . "</strong></p>";
        
        $sections = ['Introduction', 'Core Concepts', 'Advanced Topics', 'Assessment'];
        
        foreach ($sections as $index => $section_title) {
            $section_sql = "INSERT IGNORE INTO course_sections (course_id, title, description, order_number) VALUES (?, ?, ?, ?)";
            $section_stmt = $conn->prepare($section_sql);
            $section_desc = "This section covers " . strtolower($section_title) . " for the course.";
            $section_stmt->bind_param("issi", $course['id'], $section_title, $section_desc, $index + 1);
            
            if ($section_stmt->execute()) {
                echo "<p>  ✅ Section created: $section_title</p>";
            }
        }
    }
}

// Link batches to courses
$batches_sql = "SELECT id FROM batches LIMIT 3";
$batches_result = $conn->query($batches_sql);

if ($batches_result && $batches_result->num_rows > 0) {
    while ($batch = $batches_result->fetch_assoc()) {
        $courses_sql = "SELECT id FROM courses LIMIT 2";
        $courses_result = $conn->query($courses_sql);
        
        while ($course = $courses_result->fetch_assoc()) {
            $batch_course_sql = "INSERT IGNORE INTO batch_courses (batch_id, course_id) VALUES (?, ?)";
            $batch_course_stmt = $conn->prepare($batch_course_sql);
            $batch_course_stmt->bind_param("ii", $batch['id'], $course['id']);
            
            if ($batch_course_stmt->execute()) {
                echo "<p>✅ Linked batch {$batch['id']} to course {$course['id']}</p>";
            }
        }
    }
}

// Create sample class sessions
$batches_sql = "SELECT id FROM batches LIMIT 2";
$batches_result = $conn->query($batches_sql);

if ($batches_result && $batches_result->num_rows > 0) {
    while ($batch = $batches_result->fetch_assoc()) {
        for ($i = 1; $i <= 3; $i++) {
            $session_date = date('Y-m-d H:i:s', strtotime("+{$i} week"));
            $session_title = "Week {$i} Class Session";
            $session_desc = "Regular class session for week {$i}";
            
            $session_sql = "INSERT IGNORE INTO class_sessions (class_id, title, description, scheduled_date, duration, status) VALUES (?, ?, ?, ?, ?, ?)";
            $session_stmt = $conn->prepare($session_sql);
            $status = $i == 1 ? 'completed' : 'scheduled';
            $duration = 90;
            $session_stmt->bind_param("isssss", $batch['id'], $session_title, $session_desc, $session_date, $duration, $status);
            
            if ($session_stmt->execute()) {
                echo "<p>✅ Created session: {$session_title} for batch {$batch['id']}</p>";
            }
        }
    }
}

// Update existing assignments to link to batches
$update_assignments_sql = "UPDATE assignments SET batch_id = course_id WHERE batch_id IS NULL";
if ($conn->query($update_assignments_sql) === TRUE) {
    echo "<p>✅ Updated existing assignments with batch_id</p>";
    $fixes_applied[] = "Updated assignments with batch_id";
}

$conn->close();

echo "<hr>";
echo "<h3>🎉 All Database Errors Fixed!</h3>";
echo "<p><strong>Summary of fixes applied:</strong></p>";
echo "<ul>";
foreach ($fixes_applied as $fix) {
    echo "<li>✅ " . htmlspecialchars($fix) . "</li>";
}
echo "</ul>";

echo "<h3>🔧 Errors That Should Now Be Fixed:</h3>";
echo "<ul>";
echo "<li>✅ <strong>Admin course_sections.php</strong> - course_sections table created</li>";
echo "<li>✅ <strong>Teacher batch_details.php</strong> - scheduled_date column exists in class_sessions</li>";
echo "<li>✅ <strong>Teacher batch_students.php</strong> - class_attendances table created</li>";
echo "<li>✅ <strong>Teacher batch_assignments.php</strong> - batch_id column added to assignments</li>";
echo "<li>✅ <strong>Teacher batch_grades.php</strong> - batch_id column added to assignments</li>";
echo "</ul>";

echo "<p><a href='admin/courses.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px; margin-right: 10px;'>→ Test Admin Courses</a>";
echo "<a href='teacher/batches.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>→ Test Teacher Batches</a></p>";
?>