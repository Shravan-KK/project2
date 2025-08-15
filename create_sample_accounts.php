<?php
// Enable error display for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Sample Accounts</h2>";

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

// Check if users table exists, if not create it
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows == 0) {
    echo "<p>📋 Users table doesn't exist. Creating it...</p>";
    
    $sql = "CREATE TABLE users (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        user_type ENUM('admin', 'teacher', 'student') NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($sql) === TRUE) {
        echo "<p>✅ Users table created successfully</p>";
    } else {
        die("❌ Error creating users table: " . $conn->error);
    }
} else {
    echo "<p>✅ Users table already exists</p>";
}

// Sample teacher accounts
$teachers = [
    ['name' => 'Alex', 'email' => 'alex@tms.com', 'password' => 'alex123'],
    ['name' => 'Sam', 'email' => 'sam@tms.com', 'password' => 'sam123'],
    ['name' => 'Kim', 'email' => 'kim@tms.com', 'password' => 'kim123'],
    ['name' => 'Jay', 'email' => 'jay@tms.com', 'password' => 'jay123'],
    ['name' => 'Lee', 'email' => 'lee@tms.com', 'password' => 'lee123'],
    ['name' => 'Pat', 'email' => 'pat@tms.com', 'password' => 'pat123'],
    ['name' => 'Ray', 'email' => 'ray@tms.com', 'password' => 'ray123'],
    ['name' => 'Zoe', 'email' => 'zoe@tms.com', 'password' => 'zoe123'],
    ['name' => 'Max', 'email' => 'max@tms.com', 'password' => 'max123'],
    ['name' => 'Ava', 'email' => 'ava@tms.com', 'password' => 'ava123']
];

// Sample student accounts
$students = [
    ['name' => 'Tom', 'email' => 'tom@tms.com', 'password' => 'tom123'],
    ['name' => 'Eva', 'email' => 'eva@tms.com', 'password' => 'eva123'],
    ['name' => 'Dan', 'email' => 'dan@tms.com', 'password' => 'dan123'],
    ['name' => 'Mia', 'email' => 'mia@tms.com', 'password' => 'mia123'],
    ['name' => 'Ben', 'email' => 'ben@tms.com', 'password' => 'ben123'],
    ['name' => 'Lia', 'email' => 'lia@tms.com', 'password' => 'lia123'],
    ['name' => 'Jake', 'email' => 'jake@tms.com', 'password' => 'jake123'],
    ['name' => 'Nina', 'email' => 'nina@tms.com', 'password' => 'nina123'],
    ['name' => 'Cole', 'email' => 'cole@tms.com', 'password' => 'cole123'],
    ['name' => 'Ruby', 'email' => 'ruby@tms.com', 'password' => 'ruby123']
];

echo "<h3>👨‍🏫 Creating Teacher Accounts...</h3>";

$teachers_created = 0;
foreach ($teachers as $teacher) {
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $teacher['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Hash the password
        $hashed_password = password_hash($teacher['password'], PASSWORD_DEFAULT);
        
        // Insert the teacher
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, 'teacher')");
        $stmt->bind_param("sss", $teacher['name'], $teacher['email'], $hashed_password);
        
        if ($stmt->execute()) {
            echo "<p>✅ Teacher created: <strong>{$teacher['name']}</strong> - {$teacher['email']} / {$teacher['password']}</p>";
            $teachers_created++;
        } else {
            echo "<p>❌ Failed to create teacher: {$teacher['name']}</p>";
        }
    } else {
        echo "<p>⚠️ Teacher already exists: {$teacher['email']}</p>";
    }
}

echo "<h3>👨‍🎓 Creating Student Accounts...</h3>";

$students_created = 0;
foreach ($students as $student) {
    // Check if user already exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $student['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        // Hash the password
        $hashed_password = password_hash($student['password'], PASSWORD_DEFAULT);
        
        // Insert the student
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, 'student')");
        $stmt->bind_param("sss", $student['name'], $student['email'], $hashed_password);
        
        if ($stmt->execute()) {
            echo "<p>✅ Student created: <strong>{$student['name']}</strong> - {$student['email']} / {$student['password']}</p>";
            $students_created++;
        } else {
            echo "<p>❌ Failed to create student: {$student['name']}</p>";
        }
    } else {
        echo "<p>⚠️ Student already exists: {$student['email']}</p>";
    }
}

$stmt->close();
$conn->close();

echo "<hr>";
echo "<h3>🎉 Account Creation Summary</h3>";
echo "<p><strong>Teachers created:</strong> $teachers_created</p>";
echo "<p><strong>Students created:</strong> $students_created</p>";

echo "<hr>";
echo "<h3>📋 All Sample Account Credentials</h3>";

echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";

echo "<div>";
echo "<h4>👨‍🏫 Teacher Accounts:</h4>";
echo "<div style='background: #f0f9ff; padding: 15px; border-radius: 8px;'>";
foreach ($teachers as $teacher) {
    echo "<p><strong>{$teacher['name']}:</strong> {$teacher['email']} / {$teacher['password']}</p>";
}
echo "</div>";
echo "</div>";

echo "<div>";
echo "<h4>👨‍🎓 Student Accounts:</h4>";
echo "<div style='background: #f0fdf4; padding: 15px; border-radius: 8px;'>";
foreach ($students as $student) {
    echo "<p><strong>{$student['name']}:</strong> {$student['email']} / {$student['password']}</p>";
}
echo "</div>";
echo "</div>";

echo "</div>";

echo "<hr>";
echo "<h3>🚀 Ready to Test!</h3>";
echo "<p>You can now use any of these accounts to login and test the system.</p>";
echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>→ Go to Login Page</a></p>";

echo "<hr>";
echo "<p><small>Note: All passwords follow the pattern: name123 (e.g., alex123, tom123)</small></p>";
?> 