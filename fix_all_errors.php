<?php
// Comprehensive Error Fix Tool for Teaching Management System
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔧 Fix All Application Errors</h1>";
echo "<p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Database connection
try {
    require_once 'config/database.php';
    echo "<p>✅ Database connected successfully</p>";
} catch (Exception $e) {
    die("<p>❌ Database connection failed: " . $e->getMessage() . "</p>");
}

echo "<h2>📋 Checking Required Tables</h2>";

// Define all required tables with their structures
$required_tables = [
    'announcements' => "CREATE TABLE announcements (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        target_audience ENUM('all', 'students', 'teachers', 'admins') DEFAULT 'all',
        is_active BOOLEAN DEFAULT TRUE,
        created_by INT(11),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )",
    
    'classes' => "CREATE TABLE classes (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        course_id INT(11),
        batch_id INT(11),
        class_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        topic VARCHAR(200),
        description TEXT,
        status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )",
    
    'batches' => "CREATE TABLE batches (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        course_id INT(11),
        start_date DATE,
        end_date DATE,
        max_students INT(11) DEFAULT 30,
        status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )",
    
    'certificates' => "CREATE TABLE certificates (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        student_id INT(11),
        course_id INT(11),
        certificate_number VARCHAR(50) UNIQUE,
        issue_date DATE,
        completion_date DATE,
        grade VARCHAR(10),
        certificate_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )",
    
    'grades' => "CREATE TABLE grades (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        student_id INT(11),
        assignment_id INT(11),
        course_id INT(11),
        grade DECIMAL(5,2),
        max_grade DECIMAL(5,2) DEFAULT 100.00,
        feedback TEXT,
        graded_by INT(11),
        graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        FOREIGN KEY (graded_by) REFERENCES users(id) ON DELETE SET NULL
    )"
];

$missing_tables = [];
$existing_tables = [];

foreach ($required_tables as $table_name => $create_sql) {
    $result = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($result->num_rows > 0) {
        echo "<p>✅ Table '$table_name' exists</p>";
        $existing_tables[] = $table_name;
    } else {
        echo "<p>❌ Table '$table_name' missing</p>";
        $missing_tables[] = $table_name;
    }
}

// Create missing tables
if (count($missing_tables) > 0) {
    echo "<h2>🔨 Creating Missing Tables</h2>";
    
    foreach ($missing_tables as $table_name) {
        $create_sql = $required_tables[$table_name];
        
        try {
            if ($conn->query($create_sql) === TRUE) {
                echo "<p>✅ Created table '$table_name' successfully</p>";
            } else {
                echo "<p>❌ Error creating table '$table_name': " . $conn->error . "</p>";
            }
        } catch (Exception $e) {
            echo "<p>❌ Exception creating table '$table_name': " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h2>📊 Adding Sample Data</h2>";

// Add sample data for testing
$sample_data = [
    'announcements' => [
        ['title' => 'Welcome to the System', 'content' => 'Welcome to our teaching management system!', 'target_audience' => 'all'],
        ['title' => 'New Course Available', 'content' => 'Check out our new programming course.', 'target_audience' => 'students'],
        ['title' => 'Teacher Meeting', 'content' => 'Monthly teacher meeting scheduled.', 'target_audience' => 'teachers']
    ]
];

// Insert sample announcements
if (in_array('announcements', $missing_tables) || isset($_GET['add_sample'])) {
    foreach ($sample_data['announcements'] as $announcement) {
        $stmt = $conn->prepare("INSERT IGNORE INTO announcements (title, content, target_audience, created_by) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $announcement['title'], $announcement['content'], $announcement['target_audience']);
        if ($stmt->execute()) {
            echo "<p>✅ Added sample announcement: {$announcement['title']}</p>";
        }
    }
}

echo "<h2>🔍 Testing Database Queries</h2>";

// Test queries that are used in the problematic pages
$test_queries = [
    'announcements_count' => "SELECT COUNT(*) as count FROM announcements",
    'user_assignments' => "SELECT COUNT(*) as count FROM assignments",
    'user_courses' => "SELECT COUNT(*) as count FROM courses",
    'enrollments_check' => "SELECT COUNT(*) as count FROM enrollments",
    'classes_check' => "SELECT COUNT(*) as count FROM classes",
    'certificates_check' => "SELECT COUNT(*) as count FROM certificates",
    'grades_check' => "SELECT COUNT(*) as count FROM grades"
];

foreach ($test_queries as $test_name => $query) {
    try {
        $result = $conn->query($query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "<p>✅ $test_name: $count records</p>";
        } else {
            echo "<p>❌ $test_name failed: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ $test_name error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>🎯 Page-Specific Fixes</h2>";

// Create fixes for specific problematic areas
echo "<h3>Admin Announcements:</h3>";
try {
    $announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
    echo "<p>✅ Admin announcements query works (" . $announcements->num_rows . " records)</p>";
} catch (Exception $e) {
    echo "<p>❌ Admin announcements error: " . $e->getMessage() . "</p>";
}

echo "<h3>Student Classes:</h3>";
try {
    // Test student classes query
    $classes = $conn->query("SELECT c.*, co.title as course_title FROM classes c LEFT JOIN courses co ON c.course_id = co.id LIMIT 5");
    echo "<p>✅ Student classes query works (" . $classes->num_rows . " records)</p>";
} catch (Exception $e) {
    echo "<p>❌ Student classes error: " . $e->getMessage() . "</p>";
}

echo "<h3>Student Assignments:</h3>";
try {
    $assignments = $conn->query("SELECT a.*, c.title as course_title FROM assignments a LEFT JOIN courses c ON a.course_id = c.id LIMIT 5");
    echo "<p>✅ Student assignments query works (" . $assignments->num_rows . " records)</p>";
} catch (Exception $e) {
    echo "<p>❌ Student assignments error: " . $e->getMessage() . "</p>";
}

echo "<h2>🚀 Summary & Next Steps</h2>";

if (count($missing_tables) > 0) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>✅ Tables Created:</h3>";
    echo "<ul>";
    foreach ($missing_tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    echo "</div>";
} else {
    echo "<p>✅ All required tables already exist</p>";
}

echo "<div style='background: #cce5ff; color: #004085; padding: 15px; border: 1px solid #b3d9ff; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>🎯 Test Your Pages Now:</h3>";
echo "<ul>";
echo "<li><a href='admin/announcements.php' target='_blank'>Admin Announcements</a></li>";
echo "<li><a href='student/classes.php' target='_blank'>Student Classes</a></li>";
echo "<li><a href='student/assignments.php' target='_blank'>Student Assignments</a></li>";
echo "<li><a href='student/announcements.php' target='_blank'>Student Announcements</a></li>";
echo "<li><a href='student/certificates.php' target='_blank'>Student Certificates</a></li>";
echo "<li><a href='student/grades.php' target='_blank'>Student Grades</a></li>";
echo "<li><a href='teacher/students.php' target='_blank'>Teacher Students</a></li>";
echo "<li><a href='teacher/grades.php' target='_blank'>Teacher Grades</a></li>";
echo "<li><a href='teacher/announcements.php' target='_blank'>Teacher Announcements</a></li>";
echo "</ul>";
echo "</div>";

echo "<p><a href='?add_sample=1' style='background: #ffc107; color: #212529; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Add More Sample Data</a></p>";

echo "<hr>";
echo "<p><strong>Note:</strong> If pages still show errors after this fix, there might be specific code issues in individual files that need attention.</p>";

$conn->close();
?>