<?php
/**
 * Production Database Setup Helper
 * 
 * This file helps you configure your database for production.
 * Follow these steps:
 * 
 * 1. Contact your hosting provider to get:
 *    - Database host (usually 'localhost')
 *    - Database name (usually prefixed with your username)
 *    - Database username (usually prefixed with your username)
 *    - Database password
 * 
 * 2. Update the values in config/database.php
 * 
 * 3. Run this file to test the connection: http://yoursite.com/config/setup_production_db.php
 */

echo "<h1>Production Database Configuration Helper</h1>";

// Get current server information
echo "<h2>Server Information:</h2>";
echo "<p><strong>Server Name:</strong> " . $_SERVER['SERVER_NAME'] . "</p>";
echo "<p><strong>Document Root:</strong> " . $_SERVER['DOCUMENT_ROOT'] . "</p>";
echo "<p><strong>Script Path:</strong> " . __FILE__ . "</p>";

// Common hosting provider database patterns
echo "<h2>Common Database Naming Patterns:</h2>";
echo "<ul>";
echo "<li><strong>cPanel/WHM:</strong> username_databasename</li>";
echo "<li><strong>Plesk:</strong> username_databasename</li>";
echo "<li><strong>DirectAdmin:</strong> username_databasename</li>";
echo "<li><strong>Custom:</strong> Contact your hosting provider</li>";
echo "</ul>";

// Test database connection with current settings
echo "<h2>Testing Current Database Configuration:</h2>";

try {
    // Include the database config to test
    include_once __DIR__ . '/database.php';
    
    if ($conn) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
        echo "<strong>✅ SUCCESS:</strong> Database connection established!<br>";
        echo "<strong>Host:</strong> $host<br>";
        echo "<strong>Database:</strong> $dbname<br>";
        echo "<strong>Username:</strong> $username<br>";
        echo "</div>";
        
        // Test if we can query the database
        $result = $conn->query("SHOW TABLES");
        if ($result) {
            echo "<p><strong>Tables in database:</strong></p>";
            echo "<ul>";
            while ($row = $result->fetch_array()) {
                echo "<li>" . $row[0] . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p><strong>Note:</strong> Connected to database but no tables found. You may need to run setup_database.php</p>";
        }
    }
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "<strong>❌ ERROR:</strong> " . $e->getMessage() . "<br>";
    echo "</div>";
    
    echo "<h3>To fix this:</h3>";
    echo "<ol>";
    echo "<li>Contact your hosting provider to get the correct database credentials</li>";
    echo "<li>Update the production section in <code>config/database.php</code> with the correct values:</li>";
    echo "</ol>";
    
    echo "<pre style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
    echo "// Production environment\n";
    echo "\$host = 'localhost'; // or your DB host\n";
    echo "\$dbname = 'your_actual_database_name';\n";
    echo "\$username = 'your_actual_username';\n";
    echo "\$password = 'your_actual_password';\n";
    echo "</pre>";
}

echo "<h2>Next Steps:</h2>";
echo "<ol>";
echo "<li>If connection failed, update the database credentials in <code>config/database.php</code></li>";
echo "<li>If connection succeeded but no tables, run <code>setup_database.php</code> to create tables</li>";
echo "<li>Delete this file (<code>config/setup_production_db.php</code>) after setup for security</li>";
echo "</ol>";

echo "<p><a href='../setup_database.php'>→ Run Database Setup</a></p>";
echo "<p><a href='../index.php'>→ Go to Main Site</a></p>";
?>