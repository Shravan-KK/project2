<?php
// Enable error display for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Test User Account</h2>";

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

// Create a test admin user
$test_name = 'Test Admin';
$test_email = 'admin@test.com';
$test_password = 'admin123';
$test_user_type = 'admin';

// Hash the password
$hashed_password = password_hash($test_password, PASSWORD_DEFAULT);

// Check if user already exists
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
$stmt->bind_param("s", $test_email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo "<p>⚠️ User with email $test_email already exists</p>";
} else {
    // Insert the test user
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $test_name, $test_email, $hashed_password, $test_user_type);
    
    if ($stmt->execute()) {
        echo "<p>✅ Test user created successfully!</p>";
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 15px 0;'>";
        echo "<h3>🎉 Your Test Account is Ready!</h3>";
        echo "<p><strong>Email:</strong> $test_email</p>";
        echo "<p><strong>Password:</strong> $test_password</p>";
        echo "<p><strong>User Type:</strong> $test_user_type</p>";
        echo "</div>";
    } else {
        echo "<p>❌ Error creating user: " . $stmt->error . "</p>";
    }
}

// Also create a test teacher and student for variety
$users_to_create = [
    ['name' => 'Test Teacher', 'email' => 'teacher@test.com', 'password' => 'teacher123', 'type' => 'teacher'],
    ['name' => 'Test Student', 'email' => 'student@test.com', 'password' => 'student123', 'type' => 'student']
];

foreach ($users_to_create as $user) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $user['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        $hashed_pwd = password_hash($user['password'], PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user['name'], $user['email'], $hashed_pwd, $user['type']);
        
        if ($stmt->execute()) {
            echo "<p>✅ {$user['type']} user created: {$user['email']} / {$user['password']}</p>";
        }
    }
}

$stmt->close();
$conn->close();

echo "<hr>";
echo "<h3>🚀 Ready to Login!</h3>";
echo "<p>You can now use any of these accounts to login:</p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@test.com / admin123</li>";
echo "<li><strong>Teacher:</strong> teacher@test.com / teacher123</li>";
echo "<li><strong>Student:</strong> student@test.com / student123</li>";
echo "</ul>";

echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>→ Go to Login Page</a></p>";

echo "<hr>";
echo "<p><small>Note: These are test accounts. For production use, change the passwords after first login.</small></p>";
?> 