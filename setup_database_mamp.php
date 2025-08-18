<?php
// Database setup script for MAMP
echo "<h2>Database Setup for MAMP</h2>";

// MAMP connection parameters
$host = 'localhost';
$port = 8889; // MAMP default port
$username = 'root';
$password = 'root'; // MAMP default password
$database = 'teaching_management';

try {
    // Connect to MySQL server without selecting a database
    echo "<p>Connecting to MySQL server...</p>";
    $conn = new mysqli($host, $username, $password, '', $port);
    
    if ($conn->connect_error) {
        // Try standard port if MAMP port fails
        echo "<p>MAMP port failed, trying standard port...</p>";
        $conn = new mysqli($host, $username, $password, '', 3306);
        
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }
        echo "<p style='color: green;'>✓ Connected using standard port (3306)</p>";
    } else {
        echo "<p style='color: green;'>✓ Connected using MAMP port (8889)</p>";
    }
    
    // Check if database exists
    $result = $conn->query("SHOW DATABASES LIKE '$database'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: orange;'>⚠ Database '$database' already exists</p>";
    } else {
        // Create database
        echo "<p>Creating database '$database'...</p>";
        if ($conn->query("CREATE DATABASE $database")) {
            echo "<p style='color: green;'>✓ Database '$database' created successfully</p>";
        } else {
            throw new Exception("Error creating database: " . $conn->error);
        }
    }
    
    // Select the database
    $conn->select_db($database);
    
    // Create basic tables if they don't exist
    echo "<p>Creating tables...</p>";
    
    // Users table
    $users_table = "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        user_type ENUM('admin', 'teacher', 'student') NOT NULL,
        phone VARCHAR(20),
        address TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($users_table)) {
        echo "<p style='color: green;'>✓ Users table created/verified</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating users table: " . $conn->error . "</p>";
    }
    
    // Courses table
    $courses_table = "CREATE TABLE IF NOT EXISTS courses (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        teacher_id INT,
        price DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($courses_table)) {
        echo "<p style='color: green;'>✓ Courses table created/verified</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating courses table: " . $conn->error . "</p>";
    }
    
    // Enrollments table
    $enrollments_table = "CREATE TABLE IF NOT EXISTS enrollments (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        course_id INT NOT NULL,
        enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
        progress DECIMAL(5,2) DEFAULT 0.00,
        FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_enrollment (student_id, course_id)
    )";
    
    if ($conn->query($enrollments_table)) {
        echo "<p style='color: green;'>✓ Enrollments table created/verified</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating enrollments table: " . $conn->error . "</p>";
    }
    
    // Check if admin user exists
    $admin_check = $conn->query("SELECT id FROM users WHERE user_type = 'admin' LIMIT 1");
    if ($admin_check->num_rows == 0) {
        // Create default admin user
        $admin_password = md5('admin123');
        $admin_sql = "INSERT INTO users (name, email, password, user_type) VALUES ('Admin User', 'admin@example.com', '$admin_password', 'admin')";
        
        if ($conn->query($admin_sql)) {
            echo "<p style='color: green;'>✓ Default admin user created</p>";
            echo "<p><strong>Admin Login:</strong> admin@example.com / admin123</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creating admin user: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Admin user already exists</p>";
    }
    
    $conn->close();
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✓ Setup Complete!</h3>";
    echo "<p>Your database is now ready. You can:</p>";
    echo "<ul>";
    echo "<li><a href='index.php'>Go to the main application</a></li>";
    echo "<li><a href='admin/dashboard.php'>Access admin dashboard</a> (admin@example.com / admin123)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>❌ Setup Failed</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "<h4>Troubleshooting:</h4>";
    echo "<ol>";
    echo "<li>Make sure MAMP is running (Apache & MySQL)</li>";
    echo "<li>Check that MySQL is running on port 8889 (MAMP default)</li>";
    echo "<li>Verify that the username is 'root' and password is 'root'</li>";
    echo "<li><a href='test_db_connection.php'>Run the connection test</a></li>";
    echo "</ol>";
    echo "</div>";
}
?>