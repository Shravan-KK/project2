<?php
// Simple Database Manager for when phpMyAdmin is not accessible
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if this is running on production or local
$is_production = !strpos($_SERVER['SERVER_NAME'], 'localhost');

if ($is_production) {
    // Production database settings (update these with your actual credentials)
    $host = 'localhost';
    $dbname = 'shravan_teaching_management'; // Usually prefixed with your username
    $username = 'shravan_dbuser'; // Usually prefixed with your username  
    $password = 'your_database_password'; // Your actual database password
} else {
    // Local MAMP settings
    $host = 'localhost';
    $dbname = 'teaching_management';
    $username = 'root';
    $password = 'root';
}

echo "<h1>Database Manager</h1>";
echo "<p><strong>Environment:</strong> " . ($is_production ? "Production Server" : "Local Development") . "</p>";

// Try to connect
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 20px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h2>❌ Database Connection Failed</h2>";
    echo "<p><strong>Error:</strong> " . $conn->connect_error . "</p>";
    echo "<p><strong>Host:</strong> $host</p>";
    echo "<p><strong>Database:</strong> $dbname</p>";
    echo "<p><strong>Username:</strong> $username</p>";
    
    if ($is_production) {
        echo "<h3>For Production Server (Hestia CP):</h3>";
        echo "<ol>";
        echo "<li>Check your Hestia Control Panel → Databases</li>";
        echo "<li>Verify database name and user credentials</li>";
        echo "<li>Make sure the database user has proper permissions</li>";
        echo "<li>Try accessing phpMyAdmin at: <code>https://yourdomain.com:8083/phpmyadmin/</code></li>";
        echo "</ol>";
    } else {
        echo "<h3>For Local MAMP:</h3>";
        echo "<ol>";
        echo "<li>Make sure MAMP is running</li>";
        echo "<li>Check MAMP preferences for correct ports</li>";
        echo "<li>Access phpMyAdmin at: <a href='http://localhost:8888/phpMyAdmin/' target='_blank'>http://localhost:8888/phpMyAdmin/</a></li>";
        echo "</ol>";
    }
    echo "</div>";
    die();
}

echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
echo "<h2>✅ Database Connected Successfully!</h2>";
echo "<p><strong>Host:</strong> $host</p>";
echo "<p><strong>Database:</strong> $dbname</p>";
echo "</div>";

// Show tables
echo "<h2>Database Tables:</h2>";
$result = $conn->query("SHOW TABLES");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Table Name</th><th>Row Count</th></tr>";
    while ($row = $result->fetch_array()) {
        $table_name = $row[0];
        $count_result = $conn->query("SELECT COUNT(*) as count FROM `$table_name`");
        $count = $count_result ? $count_result->fetch_assoc()['count'] : 'Error';
        echo "<tr><td>$table_name</td><td>$count</td></tr>";
    }
    echo "</table>";
} else {
    echo "<p>No tables found in database.</p>";
}

// Show users table if it exists
echo "<h2>Users in System:</h2>";
$result = $conn->query("SELECT id, name, email, user_type, created_at FROM users ORDER BY id");
if ($result && $result->num_rows > 0) {
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
    echo "<p>No users found. <a href='convert_to_md5.php'>Create test users</a></p>";
}

$conn->close();

echo "<hr>";
echo "<h2>🔧 Troubleshooting phpMyAdmin Access:</h2>";

if ($is_production) {
    echo "<h3>For Hestia Control Panel:</h3>";
    echo "<ol>";
    echo "<li><strong>SSH into your server and run:</strong></li>";
    echo "<pre>sudo systemctl status apache2
sudo systemctl status mysql
sudo v-list-web-domain admin yourdomain.com</pre>";
    echo "<li><strong>Access phpMyAdmin via:</strong></li>";
    echo "<ul>";
    echo "<li><code>https://yourdomain.com:8083/phpmyadmin/</code></li>";
    echo "<li><code>https://server-ip:8083/phpmyadmin/</code></li>";
    echo "</ul>";
    echo "<li><strong>Check Hestia logs:</strong></li>";
    echo "<pre>sudo tail -f /var/log/hestia/system.log</pre>";
    echo "</ol>";
} else {
    echo "<h3>For MAMP (Local):</h3>";
    echo "<ol>";
    echo "<li><strong>Start MAMP application</strong></li>";
    echo "<li><strong>Click 'Start Servers'</strong></li>";
    echo "<li><strong>Access phpMyAdmin:</strong> <a href='http://localhost:8888/phpMyAdmin/' target='_blank'>http://localhost:8888/phpMyAdmin/</a></li>";
    echo "<li><strong>Or use MAMP WebStart page:</strong> <a href='http://localhost:8888/MAMP/' target='_blank'>http://localhost:8888/MAMP/</a></li>";
    echo "</ol>";
}

echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>← Back to Login</a></p>";
?>