<?php
// Create Sample Student Accounts
header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head><title>Create Sample Student Accounts</title></head>";
echo "<body style='font-family: Arial, sans-serif; margin: 20px;'>";

echo "<h1>👨‍🎓 Creating Sample Student Accounts</h1>";
echo "<p>Adding student accounts for testing the student interface</p>";

echo "<style>
.success-box { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb; }
.error-box { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb; }
.info-box { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #b3d9ff; }
.account-box { background: #f8f9fa; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff; }
.demo-btn { display: inline-block; background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 5px; font-weight: bold; }
table { border-collapse: collapse; width: 100%; margin: 10px 0; }
th, td { border: 1px solid #ddd; padding: 12px; text-align: left; }
th { background-color: #f2f2f2; }
</style>";

// Suppress warnings for this setup script
error_reporting(E_ERROR);

try {
    // Connect to database
    $host = 'localhost';
    $dbname = 'teaching_management';
    $username = 'root';
    $password = 'root'; // MAMP default

    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<div class='success-box'>";
    echo "✅ Connected to database successfully";
    echo "</div>";

    // Sample student accounts
    $students = [
        ['name' => 'Alice Johnson', 'email' => 'alice.johnson@student.com', 'phone' => '+1-555-0101'],
        ['name' => 'Bob Smith', 'email' => 'bob.smith@student.com', 'phone' => '+1-555-0102'],
        ['name' => 'Carol Davis', 'email' => 'carol.davis@student.com', 'phone' => '+1-555-0103'],
        ['name' => 'David Wilson', 'email' => 'david.wilson@student.com', 'phone' => '+1-555-0104'],
        ['name' => 'Eva Brown', 'email' => 'eva.brown@student.com', 'phone' => '+1-555-0105'],
        ['name' => 'Frank Miller', 'email' => 'frank.miller@student.com', 'phone' => '+1-555-0106'],
        ['name' => 'Grace Lee', 'email' => 'grace.lee@student.com', 'phone' => '+1-555-0107'],
        ['name' => 'Henry Chen', 'email' => 'henry.chen@student.com', 'phone' => '+1-555-0108'],
        ['name' => 'Ivy Rodriguez', 'email' => 'ivy.rodriguez@student.com', 'phone' => '+1-555-0109'],
        ['name' => 'Jack Thompson', 'email' => 'jack.thompson@student.com', 'phone' => '+1-555-0110']
    ];

    echo "<h2>👥 Creating Student Accounts</h2>";
    echo "<div class='info-box'>";
    echo "<p><strong>Password for all students:</strong> student123</p>";
    echo "<p><strong>User Type:</strong> student</p>";
    echo "<p><strong>Status:</strong> active</p>";
    echo "</div>";

    $created_count = 0;
    $existing_count = 0;

    foreach ($students as $student) {
        // Check if student already exists
        $check = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check);
        $check_stmt->bind_param("s", $student['email']);
        $check_stmt->execute();
        $exists = $check_stmt->get_result()->fetch_assoc();
        
        if (!$exists) {
            $password_hash = md5('student123'); // Using MD5 as requested
            $insert = "INSERT INTO users (name, email, password, user_type, status, phone, created_at) VALUES (?, ?, ?, 'student', 'active', ?, NOW())";
            $stmt = $conn->prepare($insert);
            $stmt->bind_param("ssss", $student['name'], $student['email'], $password_hash, $student['phone']);
            
            if ($stmt->execute()) {
                echo "<div style='background: #d4edda; color: #155724; padding: 5px; margin: 2px 0; border-radius: 3px;'>";
                echo "✅ Created: " . $student['name'] . " (" . $student['email'] . ")";
                echo "</div>";
                $created_count++;
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 5px; margin: 2px 0; border-radius: 3px;'>";
                echo "❌ Failed to create: " . $student['name'];
                echo "</div>";
            }
        } else {
            echo "<div style='background: #fff3cd; color: #856404; padding: 5px; margin: 2px 0; border-radius: 3px;'>";
            echo "⚠️ Already exists: " . $student['name'] . " (" . $student['email'] . ")";
            echo "</div>";
            $existing_count++;
        }
    }

    echo "<h2>📊 Account Summary</h2>";
    echo "<div class='success-box'>";
    echo "<p><strong>✅ Created:</strong> $created_count new student accounts</p>";
    echo "<p><strong>⚠️ Existing:</strong> $existing_count accounts already existed</p>";
    echo "<p><strong>📝 Total Available:</strong> " . ($created_count + $existing_count) . " student accounts</p>";
    echo "</div>";

    echo "<h2>🔑 Student Login Credentials</h2>";
    echo "<div class='account-box'>";
    echo "<h3>📋 Complete List of Student Accounts</h3>";
    echo "<table>";
    echo "<tr><th>Name</th><th>Email</th><th>Password</th><th>Phone</th></tr>";
    
    foreach ($students as $student) {
        echo "<tr>";
        echo "<td><strong>" . $student['name'] . "</strong></td>";
        echo "<td>" . $student['email'] . "</td>";
        echo "<td><code>student123</code></td>";
        echo "<td>" . $student['phone'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "</div>";

    echo "<h2>🎯 Quick Test Accounts</h2>";
    echo "<div class='info-box'>";
    echo "<h3>⚡ Recommended for Quick Testing:</h3>";
    echo "<p><strong>Primary Test Account:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> alice.johnson@student.com</li>";
    echo "<li><strong>Password:</strong> student123</li>";
    echo "<li><strong>Name:</strong> Alice Johnson</li>";
    echo "</ul>";

    echo "<p><strong>Secondary Test Account:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> bob.smith@student.com</li>";
    echo "<li><strong>Password:</strong> student123</li>";
    echo "<li><strong>Name:</strong> Bob Smith</li>";
    echo "</ul>";

    echo "<p><strong>Additional Test Account:</strong></p>";
    echo "<ul>";
    echo "<li><strong>Email:</strong> carol.davis@student.com</li>";
    echo "<li><strong>Password:</strong> student123</li>";
    echo "<li><strong>Name:</strong> Carol Davis</li>";
    echo "</ul>";
    echo "</div>";

    // Add some sample enrollments for testing
    echo "<h2>📚 Creating Sample Enrollments</h2>";
    echo "<div class='info-box'>";
    echo "<p>Adding some course enrollments for testing the student interface...</p>";
    echo "</div>";

    // Get some student IDs and course IDs for sample enrollments
    $student_ids = [];
    $student_result = $conn->query("SELECT id FROM users WHERE user_type = 'student' LIMIT 3");
    while ($row = $student_result->fetch_assoc()) {
        $student_ids[] = $row['id'];
    }

    $course_ids = [];
    $course_result = $conn->query("SELECT id FROM courses LIMIT 3");
    while ($row = $course_result->fetch_assoc()) {
        $course_ids[] = $row['id'];
    }

    if (!empty($student_ids) && !empty($course_ids)) {
        // Create some sample enrollments
        $enrollments_created = 0;
        for ($i = 0; $i < min(count($student_ids), count($course_ids)); $i++) {
            $student_id = $student_ids[$i];
            $course_id = $course_ids[$i];
            
            // Check if enrollment already exists
            $check_enrollment = "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?";
            $check_stmt = $conn->prepare($check_enrollment);
            $check_stmt->bind_param("ii", $student_id, $course_id);
            $check_stmt->execute();
            $enrollment_exists = $check_stmt->get_result()->fetch_assoc();
            
            if (!$enrollment_exists) {
                $progress = rand(10, 95); // Random progress between 10-95%
                $insert_enrollment = "INSERT INTO enrollments (student_id, course_id, status, progress, enrollment_date) VALUES (?, ?, 'active', ?, NOW())";
                $enroll_stmt = $conn->prepare($insert_enrollment);
                $enroll_stmt->bind_param("iii", $student_id, $course_id, $progress);
                
                if ($enroll_stmt->execute()) {
                    $enrollments_created++;
                }
            }
        }
        
        if ($enrollments_created > 0) {
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
            echo "✅ Created $enrollments_created sample enrollments for testing";
            echo "</div>";
        }
    }

    echo "<h2>🚀 Test the Student Interface</h2>";
    echo "<div class='success-box'>";
    echo "<h3>🎯 Ready to Test!</h3>";
    echo "<p>Use any of the student accounts to test the student interface:</p>";
    
    echo "<a href='index.php' target='_blank' class='demo-btn'>🔐 Login Page</a>";
    echo "<a href='student/dashboard.php' target='_blank' class='demo-btn'>📊 Student Dashboard</a>";
    echo "<a href='student/courses.php' target='_blank' class='demo-btn'>📚 Student Courses</a>";

    echo "<h4>📖 Testing Steps:</h4>";
    echo "<ol>";
    echo "<li>Go to the login page</li>";
    echo "<li>Use any student email and password 'student123'</li>";
    echo "<li>Explore the student interface features</li>";
    echo "<li>Test courses, assignments, grades, etc.</li>";
    echo "</ol>";

    echo "<h4>🔍 Student Interface Features to Test:</h4>";
    echo "<ul>";
    echo "<li>📊 <strong>Dashboard:</strong> Overview and statistics</li>";
    echo "<li>📚 <strong>Courses:</strong> Enrolled courses and progress</li>";
    echo "<li>📝 <strong>Assignments:</strong> Submit and view assignments</li>";
    echo "<li>🎯 <strong>Grades:</strong> View assignment and quiz grades</li>";
    echo "<li>📢 <strong>Announcements:</strong> Course announcements</li>";
    echo "<li>💬 <strong>Messages:</strong> Communication with instructors</li>";
    echo "<li>🏆 <strong>Certificates:</strong> Course completion certificates</li>";
    echo "</ul>";
    echo "</div>";

    // Database statistics
    echo "<h2>📈 Database Statistics</h2>";
    echo "<div class='info-box'>";
    $stats = [
        'students' => $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'student'")->fetch_assoc()['count'],
        'teachers' => $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'teacher'")->fetch_assoc()['count'],
        'admins' => $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'admin'")->fetch_assoc()['count'],
        'courses' => $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'],
        'enrollments' => $conn->query("SELECT COUNT(*) as count FROM enrollments")->fetch_assoc()['count'],
        'batches' => $conn->query("SELECT COUNT(*) as count FROM batches")->fetch_assoc()['count']
    ];

    echo "<table>";
    echo "<tr><th>Item</th><th>Count</th></tr>";
    foreach ($stats as $item => $count) {
        echo "<tr><td>" . ucfirst($item) . "</td><td><strong>$count</strong></td></tr>";
    }
    echo "</table>";
    echo "</div>";

    $conn->close();

} catch (Exception $e) {
    echo "<div class='error-box'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}

echo "</body></html>";
?>