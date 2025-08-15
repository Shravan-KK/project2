<?php
// Add Test Data for All Tables
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>📊 Add Test Data</h1>";

try {
    require_once 'config/database.php';
    echo "<p>✅ Database connected</p>";
} catch (Exception $e) {
    die("<p>❌ Database failed: " . $e->getMessage() . "</p>");
}

// Create test users if they don't exist
echo "<h2>👥 Creating Test Users</h2>";

$test_users = [
    ['name' => 'Admin User', 'email' => 'admin@test.com', 'password' => md5('admin123'), 'type' => 'admin'],
    ['name' => 'John Teacher', 'email' => 'teacher@test.com', 'password' => md5('teacher123'), 'type' => 'teacher'],
    ['name' => 'Jane Student', 'email' => 'student@test.com', 'password' => md5('student123'), 'type' => 'student'],
    ['name' => 'Bob Student', 'email' => 'student2@test.com', 'password' => md5('student123'), 'type' => 'student']
];

foreach ($test_users as $user) {
    $stmt = $conn->prepare("INSERT IGNORE INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user['name'], $user['email'], $user['password'], $user['type']);
    if ($stmt->execute()) {
        echo "<p>✅ User: {$user['name']} ({$user['type']})</p>";
    }
}

// Create test courses
echo "<h2>📚 Creating Test Courses</h2>";

$test_courses = [
    ['title' => 'Introduction to Programming', 'description' => 'Learn basic programming concepts', 'teacher_id' => 2, 'price' => 99.99],
    ['title' => 'Web Development Basics', 'description' => 'HTML, CSS, and JavaScript fundamentals', 'teacher_id' => 2, 'price' => 149.99],
    ['title' => 'Database Design', 'description' => 'Learn how to design efficient databases', 'teacher_id' => 2, 'price' => 199.99]
];

foreach ($test_courses as $course) {
    $stmt = $conn->prepare("INSERT IGNORE INTO courses (title, description, teacher_id, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssid", $course['title'], $course['description'], $course['teacher_id'], $course['price']);
    if ($stmt->execute()) {
        echo "<p>✅ Course: {$course['title']}</p>";
    }
}

// Create test enrollments
echo "<h2>📝 Creating Test Enrollments</h2>";

$test_enrollments = [
    ['student_id' => 3, 'course_id' => 1],
    ['student_id' => 3, 'course_id' => 2],
    ['student_id' => 4, 'course_id' => 1],
    ['student_id' => 4, 'course_id' => 3]
];

foreach ($test_enrollments as $enrollment) {
    $stmt = $conn->prepare("INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $enrollment['student_id'], $enrollment['course_id']);
    if ($stmt->execute()) {
        echo "<p>✅ Enrollment: Student {$enrollment['student_id']} → Course {$enrollment['course_id']}</p>";
    }
}

// Create test announcements
echo "<h2>📢 Creating Test Announcements</h2>";

$test_announcements = [
    ['title' => 'Welcome to the Platform', 'content' => 'Welcome to our online learning platform! We\'re excited to have you here.', 'target_audience' => 'all', 'created_by' => 1],
    ['title' => 'New Course Available', 'content' => 'Check out our new programming course starting next week.', 'target_audience' => 'students', 'created_by' => 1],
    ['title' => 'Faculty Meeting', 'content' => 'Monthly faculty meeting scheduled for Friday at 2 PM.', 'target_audience' => 'teachers', 'created_by' => 1],
    ['title' => 'System Maintenance', 'content' => 'Scheduled maintenance this Sunday from 2-4 AM.', 'target_audience' => 'all', 'created_by' => 1]
];

foreach ($test_announcements as $announcement) {
    $stmt = $conn->prepare("INSERT IGNORE INTO announcements (title, content, target_audience, created_by) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $announcement['title'], $announcement['content'], $announcement['target_audience'], $announcement['created_by']);
    if ($stmt->execute()) {
        echo "<p>✅ Announcement: {$announcement['title']}</p>";
    }
}

// Create test assignments
echo "<h2>📋 Creating Test Assignments</h2>";

$test_assignments = [
    ['title' => 'Programming Exercise 1', 'description' => 'Write a simple calculator program', 'course_id' => 1, 'due_date' => '2025-02-15'],
    ['title' => 'HTML Project', 'description' => 'Create a personal website using HTML and CSS', 'course_id' => 2, 'due_date' => '2025-02-20'],
    ['title' => 'Database Schema Design', 'description' => 'Design a database schema for an e-commerce system', 'course_id' => 3, 'due_date' => '2025-02-25']
];

foreach ($test_assignments as $assignment) {
    $stmt = $conn->prepare("INSERT IGNORE INTO assignments (title, description, course_id, due_date) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssis", $assignment['title'], $assignment['description'], $assignment['course_id'], $assignment['due_date']);
    if ($stmt->execute()) {
        echo "<p>✅ Assignment: {$assignment['title']}</p>";
    }
}

// Create test classes
echo "<h2>🕐 Creating Test Classes</h2>";

$test_classes = [
    ['course_id' => 1, 'class_date' => '2025-01-15', 'start_time' => '10:00:00', 'end_time' => '11:30:00', 'topic' => 'Introduction to Variables'],
    ['course_id' => 1, 'class_date' => '2025-01-17', 'start_time' => '10:00:00', 'end_time' => '11:30:00', 'topic' => 'Control Structures'],
    ['course_id' => 2, 'class_date' => '2025-01-16', 'start_time' => '14:00:00', 'end_time' => '15:30:00', 'topic' => 'HTML Basics'],
    ['course_id' => 2, 'class_date' => '2025-01-18', 'start_time' => '14:00:00', 'end_time' => '15:30:00', 'topic' => 'CSS Styling']
];

foreach ($test_classes as $class) {
    $stmt = $conn->prepare("INSERT IGNORE INTO classes (course_id, class_date, start_time, end_time, topic) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $class['course_id'], $class['class_date'], $class['start_time'], $class['end_time'], $class['topic']);
    if ($stmt->execute()) {
        echo "<p>✅ Class: {$class['topic']}</p>";
    }
}

// Create test grades
echo "<h2>📈 Creating Test Grades</h2>";

$test_grades = [
    ['student_id' => 3, 'assignment_id' => 1, 'course_id' => 1, 'grade' => 85.5, 'graded_by' => 2],
    ['student_id' => 4, 'assignment_id' => 1, 'course_id' => 1, 'grade' => 92.0, 'graded_by' => 2],
    ['student_id' => 3, 'assignment_id' => 2, 'course_id' => 2, 'grade' => 78.5, 'graded_by' => 2]
];

foreach ($test_grades as $grade) {
    $stmt = $conn->prepare("INSERT IGNORE INTO grades (student_id, assignment_id, course_id, grade, graded_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iiidi", $grade['student_id'], $grade['assignment_id'], $grade['course_id'], $grade['grade'], $grade['graded_by']);
    if ($stmt->execute()) {
        echo "<p>✅ Grade: Student {$grade['student_id']} - {$grade['grade']}%</p>";
    }
}

// Create test certificates
echo "<h2>🏆 Creating Test Certificates</h2>";

$test_certificates = [
    ['student_id' => 3, 'course_id' => 1, 'certificate_number' => 'CERT-2025-001', 'issue_date' => '2025-01-10', 'completion_date' => '2025-01-09', 'grade' => 'A'],
    ['student_id' => 4, 'course_id' => 1, 'certificate_number' => 'CERT-2025-002', 'issue_date' => '2025-01-10', 'completion_date' => '2025-01-09', 'grade' => 'A+']
];

foreach ($test_certificates as $cert) {
    $stmt = $conn->prepare("INSERT IGNORE INTO certificates (student_id, course_id, certificate_number, issue_date, completion_date, grade) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iissss", $cert['student_id'], $cert['course_id'], $cert['certificate_number'], $cert['issue_date'], $cert['completion_date'], $cert['grade']);
    if ($stmt->execute()) {
        echo "<p>✅ Certificate: {$cert['certificate_number']}</p>";
    }
}

echo "<h2>✅ Test Data Creation Complete!</h2>";

echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>📊 Summary of Test Data Added:</h3>";
echo "<ul>";
echo "<li>4 Test Users (admin, teacher, 2 students)</li>";
echo "<li>3 Test Courses</li>";
echo "<li>4 Test Enrollments</li>";
echo "<li>4 Test Announcements</li>";
echo "<li>3 Test Assignments</li>";
echo "<li>4 Test Classes</li>";
echo "<li>3 Test Grades</li>";
echo "<li>2 Test Certificates</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🎯 Test Your Pages Now:</h2>";
echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; margin: 20px 0;'>";

$test_links = [
    'admin/announcements.php' => 'Admin Announcements',
    'student/classes.php' => 'Student Classes',
    'student/assignments.php' => 'Student Assignments',
    'student/announcements.php' => 'Student Announcements',
    'student/certificates.php' => 'Student Certificates',
    'student/grades.php' => 'Student Grades',
    'teacher/students.php' => 'Teacher Students',
    'teacher/grades.php' => 'Teacher Grades',
    'teacher/announcements.php' => 'Teacher Announcements'
];

foreach ($test_links as $url => $name) {
    echo "<a href='$url' target='_blank' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px; text-align: center; display: block;'>$name</a>";
}

echo "</div>";

echo "<p><a href='debug_specific_pages.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>← Back to Debug Tool</a></p>";

$conn->close();
?>