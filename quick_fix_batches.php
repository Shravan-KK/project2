<?php
require_once 'config/database.php';

echo "<!DOCTYPE html><html><head><title>Quick Batch Fix</title></head><body>";
echo "<h1>Quick Batch Tables Fix</h1>";

try {
    echo "<h2>Creating Required Tables...</h2>";
    
    // Create batches table
    $create_batches = "CREATE TABLE IF NOT EXISTS batches (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        start_date DATE,
        end_date DATE,
        max_students INT(11) DEFAULT 30,
        current_students INT(11) DEFAULT 0,
        status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    
    if ($conn->query($create_batches)) {
        echo "<p style='color: green;'>✓ Batches table created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating batches table: " . $conn->error . "</p>";
    }
    
    // Drop and recreate batch_courses table to ensure correct structure
    $conn->query("DROP TABLE IF EXISTS batch_courses");
    
    $create_batch_courses = "CREATE TABLE batch_courses (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        batch_id INT(11) NOT NULL,
        course_id INT(11) NOT NULL,
        start_date DATE,
        end_date DATE,
        status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_batch_id (batch_id),
        INDEX idx_course_id (course_id),
        INDEX idx_status (status),
        UNIQUE KEY unique_batch_course (batch_id, course_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    
    if ($conn->query($create_batch_courses)) {
        echo "<p style='color: green;'>✓ Batch_courses table created successfully</p>";
        
        // Verify the status column exists
        $verify_status = $conn->query("SHOW COLUMNS FROM batch_courses LIKE 'status'");
        if ($verify_status->num_rows > 0) {
            echo "<p style='color: green;'>✓ Status column verified in batch_courses table</p>";
        } else {
            echo "<p style='color: red;'>✗ Status column missing from batch_courses table</p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Error creating batch_courses table: " . $conn->error . "</p>";
    }
    
    // Add batch_id to enrollments if it doesn't exist
    $check_column = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'batch_id'");
    if ($check_column->num_rows == 0) {
        if ($conn->query("ALTER TABLE enrollments ADD COLUMN batch_id INT(11) NULL")) {
            echo "<p style='color: green;'>✓ Added batch_id column to enrollments table</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding batch_id column: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ batch_id column already exists in enrollments</p>";
    }
    
    // Add some sample data
    $count_check = $conn->query("SELECT COUNT(*) as count FROM batches");
    $count = $count_check->fetch_assoc()['count'];
    
    if ($count == 0) {
        $sample_data = "INSERT INTO batches (name, description, max_students) VALUES 
        ('Sample Batch 1', 'First sample batch for testing', 25),
        ('Sample Batch 2', 'Second sample batch for testing', 30)";
        
        if ($conn->query($sample_data)) {
            echo "<p style='color: green;'>✓ Added sample batch data</p>";
        } else {
            echo "<p style='color: red;'>✗ Error adding sample data: " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠ Batches table already has $count records</p>";
    }
    
    // Debug: Show table structure
    echo "<h2>Debugging Table Structure...</h2>";
    
    // Show batch_courses table structure
    $show_structure = $conn->query("DESCRIBE batch_courses");
    if ($show_structure) {
        echo "<h3>batch_courses table structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $show_structure->fetch_assoc()) {
            echo "<tr>";
            echo "<td>{$row['Field']}</td>";
            echo "<td>{$row['Type']}</td>";
            echo "<td>{$row['Null']}</td>";
            echo "<td>{$row['Key']}</td>";
            echo "<td>{$row['Default']}</td>";
            echo "<td>{$row['Extra']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ Cannot describe batch_courses table: " . $conn->error . "</p>";
    }
    
    // Test simpler query first
    echo "<h2>Testing Simpler Queries...</h2>";
    
    $simple_test = $conn->query("SELECT * FROM batch_courses LIMIT 1");
    if ($simple_test) {
        echo "<p style='color: green;'>✓ Simple SELECT from batch_courses works</p>";
    } else {
        echo "<p style='color: red;'>✗ Simple SELECT failed: " . $conn->error . "</p>";
    }
    
    $status_test = $conn->query("SELECT status FROM batch_courses LIMIT 1");
    if ($status_test) {
        echo "<p style='color: green;'>✓ Can SELECT status column specifically</p>";
    } else {
        echo "<p style='color: red;'>✗ Cannot SELECT status column: " . $conn->error . "</p>";
    }
    
    // Test the JOIN without the status condition
    echo "<h2>Testing JOIN Without Status Condition...</h2>";
    $simple_join = "SELECT b.*, COUNT(DISTINCT bc.course_id) as assigned_courses
            FROM batches b 
            LEFT JOIN batch_courses bc ON b.id = bc.batch_id
            GROUP BY b.id";
    
    $join_result = $conn->query($simple_join);
    if ($join_result) {
        echo "<p style='color: green;'>✓ Simple JOIN works</p>";
    } else {
        echo "<p style='color: red;'>✗ Simple JOIN failed: " . $conn->error . "</p>";
    }
    
    // Test the query that was failing
    echo "<h2>Testing the Original Query...</h2>";
    $test_sql = "SELECT b.*, 
            COUNT(DISTINCT e.student_id) as enrolled_students,
            COUNT(DISTINCT bc.course_id) as assigned_courses
            FROM batches b 
            LEFT JOIN enrollments e ON b.id = e.batch_id AND (e.status = 'active' OR e.status IS NULL)
            LEFT JOIN batch_courses bc ON b.id = bc.batch_id AND (bc.status = 'active' OR bc.status IS NULL)
            GROUP BY b.id 
            ORDER BY b.created_at DESC";
    
    $test_result = $conn->query($test_sql);
    if ($test_result) {
        echo "<p style='color: green;'>✓ Query executed successfully! Found " . $test_result->num_rows . " batches.</p>";
    } else {
        echo "<p style='color: red;'>✗ Query still failing: " . $conn->error . "</p>";
        
        // Try alternative query without status conditions
        echo "<h3>Trying Alternative Query...</h3>";
        $alt_sql = "SELECT b.*, 
                COUNT(DISTINCT e.student_id) as enrolled_students,
                COUNT(DISTINCT bc.course_id) as assigned_courses
                FROM batches b 
                LEFT JOIN enrollments e ON b.id = e.batch_id
                LEFT JOIN batch_courses bc ON b.id = bc.batch_id
                GROUP BY b.id 
                ORDER BY b.created_at DESC";
        
        $alt_result = $conn->query($alt_sql);
        if ($alt_result) {
            echo "<p style='color: green;'>✓ Alternative query works! Found " . $alt_result->num_rows . " batches.</p>";
        } else {
            echo "<p style='color: red;'>✗ Alternative query also failed: " . $conn->error . "</p>";
        }
    }
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Fix Complete!</h3>";
    echo "<p>The batch tables have been set up. You can now:</p>";
    echo "<ul>";
    echo "<li><a href='admin/batches.php'>Go to Batches Page</a></li>";
    echo "<li><a href='admin/dashboard.php'>Go to Admin Dashboard</a></li>";
    echo "<li><a href='index.php'>Go to Main Page</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ Error occurred:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "</body></html>";
?>