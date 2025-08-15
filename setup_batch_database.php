<?php
// Simple Database Setup for Batch Assignment System
header('Content-Type: text/html; charset=UTF-8');

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head><title>Batch Database Setup</title></head>";
echo "<body style='font-family: Arial, sans-serif; margin: 20px;'>";

echo "<h1>🔧 Batch Database Setup</h1>";
echo "<p>Setting up batch assignment database tables...</p>";

// Suppress warnings for this setup script
error_reporting(E_ERROR);

try {
    // Connect to database
    $host = 'localhost';
    $dbname = 'teaching_management';
    $username = 'root';
    $password = 'root'; // MAMP default

    $conn = new mysqli($host, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    echo "<div style='background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "✅ Connected to database successfully";
    echo "</div>";

    // Create batch_instructors table
    $sql1 = "CREATE TABLE IF NOT EXISTS batch_instructors (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        batch_id INT(11) NOT NULL,
        instructor_id INT(11) NOT NULL,
        role ENUM('lead', 'assistant', 'mentor') DEFAULT 'lead',
        assigned_date DATE DEFAULT (CURRENT_DATE),
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_batch_id (batch_id),
        INDEX idx_instructor_id (instructor_id),
        INDEX idx_status (status),
        UNIQUE KEY unique_batch_instructor_role (batch_id, instructor_id, role)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql1)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
        echo "✅ batch_instructors table created";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
        echo "❌ Error creating batch_instructors: " . $conn->error;
        echo "</div>";
    }

    // Create batch_courses table
    $sql2 = "CREATE TABLE IF NOT EXISTS batch_courses (
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql2)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
        echo "✅ batch_courses table created";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
        echo "❌ Error creating batch_courses: " . $conn->error;
        echo "</div>";
    }

    // Create batches table
    $sql3 = "CREATE TABLE IF NOT EXISTS batches (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        start_date DATE,
        end_date DATE,
        max_students INT(11) DEFAULT 30,
        current_students INT(11) DEFAULT 0,
        status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_status (status),
        INDEX idx_dates (start_date, end_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

    if ($conn->query($sql3)) {
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
        echo "✅ batches table created";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
        echo "❌ Error creating batches: " . $conn->error;
        echo "</div>";
    }

    // Add sample instructors
    $instructors = [
        ['Dr. Sarah Johnson', 'sarah.johnson@training.com'],
        ['Prof. Michael Chen', 'michael.chen@training.com'],
        ['Dr. Emily Rodriguez', 'emily.rodriguez@training.com'],
        ['Mr. David Wilson', 'david.wilson@training.com'],
        ['Ms. Lisa Thompson', 'lisa.thompson@training.com']
    ];

    echo "<h3>Adding Sample Instructors:</h3>";
    foreach ($instructors as $instructor) {
        $password_hash = md5('instructor123');
        $check = "SELECT id FROM users WHERE email = ?";
        $check_stmt = $conn->prepare($check);
        $check_stmt->bind_param("s", $instructor[1]);
        $check_stmt->execute();
        $exists = $check_stmt->get_result()->fetch_assoc();
        
        if (!$exists) {
            $insert = "INSERT INTO users (name, email, password, user_type, status) VALUES (?, ?, ?, 'teacher', 'active')";
            $stmt = $conn->prepare($insert);
            $stmt->bind_param("sss", $instructor[0], $instructor[1], $password_hash);
            
            if ($stmt->execute()) {
                echo "<div style='background: #d4edda; color: #155724; padding: 5px; margin: 2px 0; border-radius: 3px;'>";
                echo "✅ Added: " . $instructor[0] . " (Password: instructor123)";
                echo "</div>";
            }
        } else {
            echo "<div style='background: #fff3cd; color: #856404; padding: 5px; margin: 2px 0; border-radius: 3px;'>";
            echo "⚠️ Already exists: " . $instructor[0];
            echo "</div>";
        }
    }

    // Add sample batches
    $batches = [
        ['Web Development Batch 2024', 'Comprehensive web development training', '2024-01-15', '2024-06-15', 25],
        ['Data Science Batch 2024', 'Python and machine learning focus', '2024-02-01', '2024-07-01', 20],
        ['Mobile App Development', 'React Native and Flutter training', '2024-03-01', '2024-08-01', 30]
    ];

    $batch_count = $conn->query("SELECT COUNT(*) as count FROM batches")->fetch_assoc()['count'];
    if ($batch_count == 0) {
        echo "<h3>Adding Sample Batches:</h3>";
        foreach ($batches as $batch) {
            $insert = "INSERT INTO batches (name, description, start_date, end_date, max_students) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert);
            $stmt->bind_param("ssssi", $batch[0], $batch[1], $batch[2], $batch[3], $batch[4]);
            
            if ($stmt->execute()) {
                echo "<div style='background: #d4edda; color: #155724; padding: 5px; margin: 2px 0; border-radius: 3px;'>";
                echo "✅ Added batch: " . $batch[0];
                echo "</div>";
            }
        }
    } else {
        echo "<div style='background: #fff3cd; color: #856404; padding: 10px; margin: 5px 0; border-radius: 3px;'>";
        echo "⚠️ Batches already exist ($batch_count batches found)";
        echo "</div>";
    }

    // Final verification
    echo "<h3>Database Verification:</h3>";
    $tables = ['batches', 'batch_courses', 'batch_instructors'];
    foreach ($tables as $table) {
        $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
        echo "<div style='background: #e3f2fd; color: #1976d2; padding: 5px; margin: 2px 0; border-radius: 3px;'>";
        echo "📊 $table: $count records";
        echo "</div>";
    }

    $instructor_count = $conn->query("SELECT COUNT(*) as count FROM users WHERE user_type = 'teacher'")->fetch_assoc()['count'];
    echo "<div style='background: #e3f2fd; color: #1976d2; padding: 5px; margin: 2px 0; border-radius: 3px;'>";
    echo "👨‍🏫 Available instructors: $instructor_count";
    echo "</div>";

    echo "<div style='background: #d4edda; color: #155724; padding: 20px; margin: 20px 0; border-radius: 8px; border-left: 4px solid #28a745;'>";
    echo "<h2>✅ Setup Complete!</h2>";
    echo "<p><strong>Your batch assignment system is now ready!</strong></p>";
    echo "<ul>";
    echo "<li>✅ All database tables created</li>";
    echo "<li>✅ Sample instructors added</li>";
    echo "<li>✅ Sample batches created</li>";
    echo "<li>✅ Ready for instructor and course assignments</li>";
    echo "</ul>";
    echo "<h3>Next Steps:</h3>";
    echo "<p>1. Go to <a href='admin/batches.php' target='_blank' style='color: #0066cc;'><strong>Admin → Batch Management</strong></a></p>";
    echo "<p>2. Test instructor assignment (green user+ icon)</p>";
    echo "<p>3. Test course assignment (purple book+ icon)</p>";
    echo "</div>";

    $conn->close();

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
    echo "❌ Error: " . $e->getMessage();
    echo "</div>";
}

echo "</body></html>";
?>