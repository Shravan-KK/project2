<?php
require_once 'config/database.php';

echo "<h2>Setting up Class Management System</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 8px;'>";

// Create classes table
$sql = "CREATE TABLE IF NOT EXISTS classes (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    course_id INT(11) NOT NULL,
    instructor_id INT(11) NOT NULL,
    start_date DATE,
    end_date DATE,
    schedule VARCHAR(200),
    max_students INT(11) DEFAULT 30,
    current_students INT(11) DEFAULT 0,
    status ENUM('active', 'inactive', 'completed', 'cancelled') DEFAULT 'active',
    meeting_link VARCHAR(500),
    meeting_platform VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (instructor_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Classes table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating classes table: " . $conn->error . "</p>";
}

// Create class_enrollments table
$sql = "CREATE TABLE IF NOT EXISTS class_enrollments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    class_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    attendance_count INT(11) DEFAULT 0,
    progress INT(3) DEFAULT 0,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_class_enrollment (class_id, student_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Class enrollments table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating class_enrollments table: " . $conn->error . "</p>";
}

// Create class_sessions table for tracking individual class sessions
$sql = "CREATE TABLE IF NOT EXISTS class_sessions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    class_id INT(11) NOT NULL,
    session_number INT(11) NOT NULL,
    title VARCHAR(200),
    description TEXT,
    session_date DATE,
    start_time TIME,
    end_time TIME,
    meeting_link VARCHAR(500),
    recording_url VARCHAR(500),
    notes TEXT,
    status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Class sessions table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating class_sessions table: " . $conn->error . "</p>";
}

// Create class_attendance table
$sql = "CREATE TABLE IF NOT EXISTS class_attendance (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    session_id INT(11) NOT NULL,
    student_id INT(11) NOT NULL,
    status ENUM('present', 'absent', 'late', 'excused') DEFAULT 'present',
    notes TEXT,
    marked_by INT(11),
    marked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES class_sessions(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (marked_by) REFERENCES users(id) ON DELETE SET NULL,
    UNIQUE KEY unique_attendance (session_id, student_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "<p style='color: green;'>✓ Class attendance table created successfully</p>";
} else {
    echo "<p style='color: red;'>✗ Error creating class_attendance table: " . $conn->error . "</p>";
}

// Insert sample classes
$sample_classes = [
    [
        'name' => 'Web Development Fundamentals - Morning Class',
        'description' => 'Learn HTML, CSS, and JavaScript basics in this interactive morning session',
        'course_id' => 1,
        'instructor_id' => 2, // teacher@tms.com
        'start_date' => '2024-01-15',
        'end_date' => '2024-03-15',
        'schedule' => 'Monday, Wednesday, Friday 9:00 AM - 11:00 AM',
        'max_students' => 20,
        'meeting_platform' => 'Zoom'
    ],
    [
        'name' => 'Python Programming - Evening Class',
        'description' => 'Comprehensive Python programming course for working professionals',
        'course_id' => 1,
        'instructor_id' => 2,
        'start_date' => '2024-01-20',
        'end_date' => '2024-04-20',
        'schedule' => 'Tuesday, Thursday 6:00 PM - 8:00 PM',
        'max_students' => 25,
        'meeting_platform' => 'Google Meet'
    ],
    [
        'name' => 'Data Science Essentials - Weekend Class',
        'description' => 'Weekend intensive course covering data analysis and visualization',
        'course_id' => 1,
        'instructor_id' => 2,
        'start_date' => '2024-02-01',
        'end_date' => '2024-05-01',
        'schedule' => 'Saturday 10:00 AM - 2:00 PM',
        'max_students' => 15,
        'meeting_platform' => 'Microsoft Teams'
    ]
];

foreach ($sample_classes as $class) {
    $sql = "INSERT IGNORE INTO classes (name, description, course_id, instructor_id, start_date, end_date, schedule, max_students, meeting_platform) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssiississ", $class['name'], $class['description'], $class['course_id'], $class['instructor_id'], $class['start_date'], $class['end_date'], $class['schedule'], $class['max_students'], $class['meeting_platform']);
    if ($stmt->execute()) {
        echo "<p style='color: green;'>✓ Sample class '{$class['name']}' created</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating sample class: " . $stmt->error . "</p>";
    }
}

// Create sample class sessions
$classes = $conn->query("SELECT id, name FROM classes LIMIT 3");
if ($classes) {
    while ($class = $classes->fetch_assoc()) {
        // Create 5 sample sessions for each class
        for ($i = 1; $i <= 5; $i++) {
            $session_date = date('Y-m-d', strtotime("+$i weeks"));
            $sql = "INSERT IGNORE INTO class_sessions (class_id, session_number, title, session_date, start_time, end_time, status) VALUES (?, ?, ?, ?, ?, ?, 'scheduled')";
            $stmt = $conn->prepare($sql);
            $start_time = '09:00:00';
            $end_time = '11:00:00';
            $title = "Session $i - " . substr($class['name'], 0, 30);
            $stmt->bind_param("iissss", $class['id'], $i, $title, $session_date, $start_time, $end_time);
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✓ Created session $i for class '{$class['name']}'</p>";
            }
        }
    }
}

echo "<br><div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
echo "<strong>✅ Class Management System Setup Complete!</strong><br>";
echo "The system now supports instructor-led classes with the following features:<br>";
echo "• Classes as subdivisions of courses<br>";
echo "• Multiple classes per instructor<br>";
echo "• Class sessions and attendance tracking<br>";
echo "• Student enrollment in specific classes<br>";
echo "• Meeting platform integration";
echo "</div>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='teacher/classes.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Go to Teacher Classes</a>";
echo "<a href='admin/classes.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Go to Admin Classes</a>";
echo "<a href='student/classes.php' style='background: #ffc107; color: black; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Student Classes</a>";
echo "</div>";

echo "</div>";
?> 