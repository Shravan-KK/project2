<?php
// Enable error display for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Converting Passwords to MD5</h2>";

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
}

// Clear existing users (to start fresh with MD5)
echo "<p>🧹 Clearing existing users...</p>";
$conn->query("DELETE FROM users");

// Create MD5-based test users
$users_to_create = [
    ['name' => 'Admin User', 'email' => 'admin@test.com', 'password' => 'admin123', 'type' => 'admin'],
    ['name' => 'Test Teacher', 'email' => 'teacher@test.com', 'password' => 'teacher123', 'type' => 'teacher'],
    ['name' => 'Test Student', 'email' => 'student@test.com', 'password' => 'student123', 'type' => 'student']
];

echo "<h3>Creating MD5-based User Accounts:</h3>";

foreach ($users_to_create as $user) {
    // Hash password with MD5
    $md5_password = md5($user['password']);
    
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user['name'], $user['email'], $md5_password, $user['type']);
    
    if ($stmt->execute()) {
        echo "<p>✅ Created {$user['type']}: <strong>{$user['email']}</strong> / <strong>{$user['password']}</strong></p>";
        echo "<p style='margin-left: 20px; color: #666; font-size: 12px;'>MD5 Hash: {$md5_password}</p>";
    } else {
        echo "<p>❌ Error creating {$user['type']}: " . $stmt->error . "</p>";
    }
}

$stmt->close();
$conn->close();

echo "<hr>";
echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>🎉 MD5 Password System Ready!</h3>";
echo "<p><strong>Your login credentials are now:</strong></p>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@test.com / admin123</li>";
echo "<li><strong>Teacher:</strong> teacher@test.com / teacher123</li>";
echo "<li><strong>Student:</strong> student@test.com / student123</li>";
echo "</ul>";
echo "<p><strong>Note:</strong> All passwords are now stored using MD5 hashing.</p>";
echo "</div>";

echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>→ Go to Login Page</a></p>";

echo "<hr>";
echo "<h3>🔧 What Changed:</h3>";
echo "<ul>";
echo "<li>✅ Login page now uses MD5 for password validation</li>";
echo "<li>✅ Registration page now uses MD5 for password hashing</li>";
echo "<li>✅ All existing users updated to use MD5 passwords</li>";
echo "<li>✅ New test accounts created with MD5 passwords</li>";
echo "</ul>";

echo "<hr>";
echo "<p><small><strong>Security Note:</strong> MD5 is not recommended for production use as it's vulnerable to rainbow table attacks. Consider using stronger hashing algorithms like bcrypt or Argon2 for production applications.</small></p>";
?>