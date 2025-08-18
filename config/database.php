<?php
// Enable error display for development
require_once __DIR__ . '/error_display.php';

// Database configuration - Auto-detect environment
if (strpos($_SERVER['SERVER_NAME'], 'localhost') !== false || strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false) {
    // Local development environment (MAMP/XAMPP)
    // Try MAMP default port first, then standard port
    $host = 'localhost';
    $port = 8889; // MAMP default port
    $dbname = 'teaching_management';
    $username = 'root';
    $password = 'root'; // Default MAMP password
} else {
    // Production environment
    // You need to update these with your actual hosting provider's database credentials
    $host = 'localhost'; // Usually localhost, but check with your hosting provider
    $port = 3306; // Standard MySQL port for production
    $dbname = 'shravan_teaching_management'; // Usually prefixed with your username
    $username = 'shravan_dbuser'; // Usually prefixed with your username
    $password = 'your_database_password'; // Your actual database password
    
    // Alternative: Use environment variables (recommended for security)
    // $host = $_ENV['DB_HOST'] ?? 'localhost';
    // $dbname = $_ENV['DB_NAME'] ?? 'teaching_management';
    // $username = $_ENV['DB_USER'] ?? 'root';
    // $password = $_ENV['DB_PASS'] ?? '';
}

// Create connection with port specification
try {
    $conn = new mysqli($host, $username, $password, $dbname, $port);
} catch (Exception $e) {
    // If MAMP port fails, try standard port
    if (isset($port) && $port == 8889) {
        try {
            $conn = new mysqli($host, $username, $password, $dbname, 3306);
        } catch (Exception $e2) {
            // Try without port specification
            $conn = new mysqli($host, $username, $password, $dbname);
        }
    } else {
        $conn = new mysqli($host, $username, $password, $dbname);
    }
}

// Check connection
if ($conn->connect_error) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px; font-family: Arial, sans-serif;'>";
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p><strong>Error:</strong> " . $conn->connect_error . "</p>";
    echo "<p><strong>Host:</strong> $host</p>";
    echo "<p><strong>Database:</strong> $dbname</p>";
    echo "<p><strong>Username:</strong> $username</p>";
    echo "<p><strong>Port:</strong> " . (isset($port) ? $port : 'default') . "</p>";
    echo "<h3>MAMP Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li><strong>Start MAMP:</strong> Open MAMP application and click 'Start Servers'</li>";
    echo "<li><strong>Check MySQL Status:</strong> Ensure MySQL light is green in MAMP</li>";
    echo "<li><strong>Verify Port:</strong> MAMP uses port 8889 by default for MySQL</li>";
    echo "<li><strong>Check phpMyAdmin:</strong> Try accessing http://localhost:8888/phpMyAdmin/</li>";
    echo "</ol>";
    echo "<h3>Quick Fixes:</h3>";
    echo "<p><a href='mamp_troubleshooting.php' style='background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🔧 MAMP Troubleshooting Guide</a>";
    echo "<a href='test_db_connection.php' style='background: #ffc107; color: black; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin-right: 10px;'>🧪 Test Connection</a>";
    echo "<a href='setup_database_mamp.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>⚡ Setup Database</a></p>";
    echo "</div>";
    die();
}

// Set charset to utf8
$conn->set_charset("utf8");
?> 