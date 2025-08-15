<?php
// Enable error display for development
require_once __DIR__ . '/error_display.php';

// Database configuration - Auto-detect environment
if (strpos($_SERVER['SERVER_NAME'], 'localhost') !== false || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false) {
    // Local development environment (MAMP/XAMPP)
    $host = 'localhost';
    $dbname = 'teaching_management';
    $username = 'root';
    $password = 'root'; // Default MAMP password
} else {
    // Production environment
    // You need to update these with your actual hosting provider's database credentials
    $host = 'localhost'; // Usually localhost, but check with your hosting provider
    $dbname = 'shravan_teaching_management'; // Usually prefixed with your username
    $username = 'shravan_dbuser'; // Usually prefixed with your username
    $password = 'your_database_password'; // Your actual database password
    
    // Alternative: Use environment variables (recommended for security)
    // $host = $_ENV['DB_HOST'] ?? 'localhost';
    // $dbname = $_ENV['DB_NAME'] ?? 'teaching_management';
    // $username = $_ENV['DB_USER'] ?? 'root';
    // $password = $_ENV['DB_PASS'] ?? '';
}

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p><strong>Error:</strong> " . $conn->connect_error . "</p>";
    echo "<p><strong>Host:</strong> $host</p>";
    echo "<p><strong>Database:</strong> $dbname</p>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<h3>Common Solutions:</h3>";
    echo "<ol>";
    echo "<li><strong>Check your database credentials:</strong> Make sure the username, password, and database name are correct for your hosting environment.</li>";
    echo "<li><strong>Contact your hosting provider:</strong> They can provide the correct database connection details.</li>";
    echo "<li><strong>Database doesn't exist:</strong> You may need to create the database through your hosting control panel.</li>";
    echo "<li><strong>User permissions:</strong> Make sure the database user has the correct permissions.</li>";
    echo "</ol>";
    echo "<p><a href='config/setup_production_db.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>→ Database Setup Helper</a></p>";
    echo "</div>";
    die();
}

// Set charset to utf8
$conn->set_charset("utf8");
?> 