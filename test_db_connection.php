<?php
// Simple database connection test for MAMP
echo "<h2>Database Connection Test</h2>";

// Try different MAMP configurations
$hosts = ['localhost', 'localhost:8889', '127.0.0.1', '127.0.0.1:8889'];
$ports = [3306, 8889];
$usernames = ['root'];
$passwords = ['', 'root'];
$database = 'teaching_management';

echo "<h3>Testing different configurations...</h3>";

foreach ($hosts as $host) {
    foreach ($usernames as $username) {
        foreach ($passwords as $password) {
            echo "<p>Testing: Host: $host, User: $username, Pass: " . ($password ? '***' : 'empty') . "</p>";
            
            try {
                $conn = new mysqli($host, $username, $password);
                
                if ($conn->connect_error) {
                    echo "<span style='color: red;'>✗ Connection failed: " . $conn->connect_error . "</span><br>";
                } else {
                    echo "<span style='color: green;'>✓ Connection successful!</span><br>";
                    
                    // Check if database exists
                    $result = $conn->query("SHOW DATABASES LIKE '$database'");
                    if ($result && $result->num_rows > 0) {
                        echo "<span style='color: green;'>✓ Database '$database' exists!</span><br>";
                        
                        // Try to connect to the database
                        $conn->select_db($database);
                        if ($conn->error) {
                            echo "<span style='color: red;'>✗ Cannot select database: " . $conn->error . "</span><br>";
                        } else {
                            echo "<span style='color: green;'>✓ Successfully connected to database '$database'!</span><br>";
                            echo "<p style='background: #d4edda; padding: 10px; border-radius: 5px;'>";
                            echo "<strong>SUCCESS!</strong><br>";
                            echo "Host: $host<br>";
                            echo "Username: $username<br>";
                            echo "Password: " . ($password ? 'root' : 'empty') . "<br>";
                            echo "Database: $database<br>";
                            echo "</p>";
                            $conn->close();
                            exit();
                        }
                    } else {
                        echo "<span style='color: orange;'>⚠ Database '$database' does not exist</span><br>";
                        
                        // Show available databases
                        $result = $conn->query("SHOW DATABASES");
                        if ($result) {
                            echo "<strong>Available databases:</strong><br>";
                            while ($row = $result->fetch_array()) {
                                echo "- " . $row[0] . "<br>";
                            }
                        }
                    }
                }
                $conn->close();
            } catch (Exception $e) {
                echo "<span style='color: red;'>✗ Exception: " . $e->getMessage() . "</span><br>";
            }
            echo "<br>";
        }
    }
}

echo "<h3>Manual Database Creation</h3>";
echo "<p>If no working configuration was found, you may need to:</p>";
echo "<ol>";
echo "<li>Open MAMP and start Apache & MySQL</li>";
echo "<li>Go to phpMyAdmin (usually http://localhost:8888/phpMyAdmin/)</li>";
echo "<li>Create a database named 'teaching_management'</li>";
echo "<li>Run the database setup script</li>";
echo "</ol>";
?>