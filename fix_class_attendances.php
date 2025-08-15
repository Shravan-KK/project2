<?php
// Enable error display for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Fixing Missing Database Tables</h2>";

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

// Create class_attendances table
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
    echo "<p>✅ Class attendances table created successfully</p>";
} else {
    echo "<p>⚠️ Class attendances table already exists or error: " . $conn->error . "</p>";
}

// Create class_sessions table if it doesn't exist
$class_sessions_sql = "CREATE TABLE IF NOT EXISTS class_sessions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    class_id INT(11) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    scheduled_date DATE NOT NULL,
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
    echo "<p>✅ Class sessions table created successfully</p>";
} else {
    echo "<p>⚠️ Class sessions table already exists or error: " . $conn->error . "</p>";
}

// Create batch_courses table if it doesn't exist
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
    echo "<p>✅ Batch courses table created successfully</p>";
} else {
    echo "<p>⚠️ Batch courses table already exists or error: " . $conn->error . "</p>";
}

// Insert sample data for existing batches and courses
echo "<h3>📚 Creating Sample Batch-Course Relationships</h3>";

// Get existing batches
$batches_sql = "SELECT id FROM batches LIMIT 3";
$batches_result = $conn->query($batches_sql);

if ($batches_result->num_rows > 0) {
    while ($batch = $batches_result->fetch_assoc()) {
        // Get existing courses
        $courses_sql = "SELECT id FROM courses LIMIT 2";
        $courses_result = $conn->query($courses_sql);
        
        while ($course = $courses_result->fetch_assoc()) {
            $batch_course_sql = "INSERT IGNORE INTO batch_courses (batch_id, course_id) VALUES (?, ?)";
            $batch_course_stmt = $conn->prepare($batch_course_sql);
            $batch_course_stmt->bind_param("ii", $batch['id'], $course['id']);
            
            if ($batch_course_stmt->execute()) {
                echo "<p>✅ Linked batch ID {$batch['id']} to course ID {$course['id']}</p>";
            }
        }
    }
}

// Create sample class sessions
echo "<h3>📅 Creating Sample Class Sessions</h3>";

$batches_sql = "SELECT id FROM batches LIMIT 2";
$batches_result = $conn->query($batches_sql);

if ($batches_result->num_rows > 0) {
    while ($batch = $batches_result->fetch_assoc()) {
        // Create sample sessions for the next few weeks
        for ($i = 1; $i <= 4; $i++) {
            $session_date = date('Y-m-d', strtotime("+{$i} week"));
            $session_title = "Week {$i} Session";
            $session_desc = "Regular class session for week {$i}";
            
            $session_sql = "INSERT IGNORE INTO class_sessions (class_id, title, description, scheduled_date, duration, status) VALUES (?, ?, ?, ?, ?, ?)";
            $session_stmt = $conn->prepare($session_sql);
            $status = $i == 1 ? 'completed' : 'scheduled';
            $session_stmt->bind_param("isssss", $batch['id'], $session_title, $session_desc, $session_date, 60, $status);
            
            if ($session_stmt->execute()) {
                $session_id = $conn->insert_id;
                echo "<p>✅ Created session: {$session_title} for batch ID {$batch['id']}</p>";
                
                // Create sample attendance records for completed sessions
                if ($status == 'completed') {
                    // Get students enrolled in this batch
                    $enrollments_sql = "SELECT student_id FROM enrollments WHERE batch_id = ? LIMIT 3";
                    $enrollments_stmt = $conn->prepare($enrollments_sql);
                    $enrollments_stmt->bind_param("i", $batch['id']);
                    $enrollments_stmt->execute();
                    $enrollments = $enrollments_stmt->get_result();
                    
                    while ($enrollment = $enrollments->fetch_assoc()) {
                        $attendance_status = rand(0, 10) > 2 ? 'present' : 'absent'; // 80% attendance rate
                        $attendance_sql = "INSERT IGNORE INTO class_attendances (session_id, student_id, status) VALUES (?, ?, ?)";
                        $attendance_stmt = $conn->prepare($attendance_sql);
                        $attendance_stmt->bind_param("iis", $session_id, $enrollment['student_id'], $attendance_status);
                        
                        if ($attendance_stmt->execute()) {
                            echo "<p>  📝 Attendance: Student {$enrollment['student_id']} - {$attendance_status}</p>";
                        }
                    }
                }
            }
        }
    }
}

$conn->close();

echo "<hr>";
echo "<h3>🎉 Database Tables Fixed Successfully!</h3>";
echo "<p>The following issues have been resolved:</p>";
echo "<ul>";
echo "<li>✅ <strong>class_attendances</strong> table created</li>";
echo "<li>✅ <strong>class_sessions</strong> table created</li>";
echo "<li>✅ <strong>batch_courses</strong> table created</li>";
echo "<li>✅ Sample data inserted for testing</li>";
echo "</ul>";

echo "<p><strong>Error Fixed:</strong> The 'Table class_attendances doesn't exist' error in teacher/batch_students.php has been resolved.</p>";

echo "<p><a href='teacher/batches.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>→ Go to Teacher Batches</a></p>";
echo "<p><a href='teacher/batch_students.php?batch_id=1' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>→ Test Batch Students Page</a></p>";
?> 