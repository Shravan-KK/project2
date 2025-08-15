<?php
// Enable error display for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database configuration
$host = 'localhost';
$dbname = 'teaching_management';
$username = 'root';
$password = 'root';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

echo "<h2>Database Connection Status</h2>";
echo "<p>✅ Connected to database: $dbname</p>";

// Check if users table exists
$result = $conn->query("SHOW TABLES LIKE 'users'");
if ($result->num_rows > 0) {
    echo "<p>✅ Users table exists</p>";
    
    // Count total users
    $result = $conn->query("SELECT COUNT(*) as total FROM users");
    $total = $result->fetch_assoc()['total'];
    echo "<p>📊 Total users in database: $total</p>";
    
    // Show all users
    $result = $conn->query("SELECT id, name, email, user_type, created_at FROM users ORDER BY id");
    if ($result->num_rows > 0) {
        echo "<h3>Existing Users:</h3>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Created</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['id'] . "</td>";
            echo "<td>" . $row['name'] . "</td>";
            echo "<td>" . $row['email'] . "</td>";
            echo "<td>" . $row['user_type'] . "</td>";
            echo "<td>" . $row['created_at'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>❌ No users found in the database</p>";
    }
} else {
    echo "<p>❌ Users table does not exist</p>";
}

// Check if we can create a test user
echo "<h3>Testing Password Hashing:</h3>";
$test_password = 'admin123';
$hashed_password = password_hash($test_password, PASSWORD_DEFAULT);
echo "<p>Test password: $test_password</p>";
echo "<p>Hashed password: $hashed_password</p>";

// Test password verification
$is_valid = password_verify($test_password, $hashed_password);
echo "<p>Password verification test: " . ($is_valid ? "✅ PASS" : "❌ FAIL") . "</p>";

$conn->close();

echo "<hr>";
echo "<h3>Next Steps:</h3>";
if ($total == 0) {
    echo "<p>🔧 <strong>No users found!</strong> You need to run the database setup script.</p>";
    echo "<p><a href='setup_database.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>→ Run Database Setup</a></p>";
} else {
    echo "<p>✅ Users exist in database. Try logging in with the credentials shown above.</p>";
    echo "<p><a href='index.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>→ Go to Login Page</a></p>";
}
?> 