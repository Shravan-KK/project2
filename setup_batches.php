<?php
require_once 'config/database.php';

echo "<h2>Setting up Batch Management System</h2>";
echo "<div style='font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; background: #f9f9f9; border-radius: 8px;'>";

// Check if batches table exists
$check_table = $conn->query("SHOW TABLES LIKE 'batches'");
if ($check_table->num_rows > 0) {
    echo "<p style='color: green;'>✓ Batches table already exists</p>";
} else {
    // Create batches table
    $sql = "CREATE TABLE IF NOT EXISTS batches (
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

    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✓ Batches table created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating batches table: " . $conn->error . "</p>";
    }
}

// Check if batch_courses table exists
$check_batch_courses = $conn->query("SHOW TABLES LIKE 'batch_courses'");
if ($check_batch_courses->num_rows > 0) {
    echo "<p style='color: green;'>✓ Batch courses table already exists</p>";
} else {
    // Create batch_courses table
    $sql = "CREATE TABLE IF NOT EXISTS batch_courses (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        batch_id INT(11),
        course_id INT(11),
        start_date DATE,
        end_date DATE,
        status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
        UNIQUE KEY unique_batch_course (batch_id, course_id)
    )";

    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✓ Batch courses table created successfully</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating batch_courses table: " . $conn->error . "</p>";
    }
}

// Check if batch_id column exists in enrollments
$check_column = $conn->query("SHOW COLUMNS FROM enrollments LIKE 'batch_id'");
if ($check_column->num_rows > 0) {
    echo "<p style='color: green;'>✓ Batch ID column already exists in enrollments table</p>";
} else {
    // Add batch_id to enrollments table
    $sql = "ALTER TABLE enrollments ADD COLUMN batch_id INT(11) AFTER course_id";
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color: green;'>✓ Added batch_id to enrollments table</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding batch_id to enrollments: " . $conn->error . "</p>";
    }
}

// Insert sample batches
$sample_batches = [
    ['name' => 'Web Development Batch 2024', 'description' => 'Complete web development course for beginners', 'start_date' => '2024-01-15', 'end_date' => '2024-06-15', 'max_students' => 25],
    ['name' => 'Python Programming Batch A', 'description' => 'Python programming fundamentals and advanced concepts', 'start_date' => '2024-02-01', 'end_date' => '2024-07-01', 'max_students' => 20],
    ['name' => 'Data Science Batch 2024', 'description' => 'Data science and machine learning course', 'start_date' => '2024-03-01', 'end_date' => '2024-08-01', 'max_students' => 30],
    ['name' => 'Mobile App Development', 'description' => 'iOS and Android app development', 'start_date' => '2024-04-01', 'end_date' => '2024-09-01', 'max_students' => 15]
];

foreach ($sample_batches as $batch) {
    $check_existing = $conn->prepare("SELECT id FROM batches WHERE name = ?");
    $check_existing->bind_param("s", $batch['name']);
    $check_existing->execute();
    
    if ($check_existing->get_result()->num_rows == 0) {
        $sql = "INSERT INTO batches (name, description, start_date, end_date, max_students) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssi", $batch['name'], $batch['description'], $batch['start_date'], $batch['end_date'], $batch['max_students']);
        if ($stmt->execute()) {
            echo "<p style='color: green;'>✓ Sample batch '{$batch['name']}' created</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creating sample batch: " . $stmt->error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ Sample batch '{$batch['name']}' already exists</p>";
    }
}

// Create sample batch-course assignments
$courses = $conn->query("SELECT id FROM courses LIMIT 3");
$batches = $conn->query("SELECT id FROM batches LIMIT 3");

if ($courses && $batches) {
    $course_ids = [];
    $batch_ids = [];
    
    while ($course = $courses->fetch_assoc()) {
        $course_ids[] = $course['id'];
    }
    
    while ($batch = $batches->fetch_assoc()) {
        $batch_ids[] = $batch['id'];
    }
    
    // Create sample batch-course assignments
    for ($i = 0; $i < min(count($course_ids), count($batch_ids)); $i++) {
        $check_existing = $conn->prepare("SELECT id FROM batch_courses WHERE batch_id = ? AND course_id = ?");
        $check_existing->bind_param("ii", $batch_ids[$i], $course_ids[$i]);
        $check_existing->execute();
        
        if ($check_existing->get_result()->num_rows == 0) {
            $sql = "INSERT INTO batch_courses (batch_id, course_id, start_date, end_date) VALUES (?, ?, '2024-01-15', '2024-06-15')";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $batch_ids[$i], $course_ids[$i]);
            if ($stmt->execute()) {
                echo "<p style='color: green;'>✓ Batch-course assignment created</p>";
            } else {
                echo "<p style='color: red;'>✗ Error creating batch-course assignment: " . $stmt->error . "</p>";
            }
        } else {
            echo "<p style='color: blue;'>ℹ Batch-course assignment already exists</p>";
        }
    }
}

echo "<br><div style='background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; border-radius: 4px; margin-top: 20px;'>";
echo "<strong>✅ Batch Management System Setup Complete!</strong><br>";
echo "You can now access the batch management system from the admin panel.";
echo "</div>";

echo "<div style='margin-top: 20px;'>";
echo "<a href='admin/batches.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; margin-right: 10px;'>Go to Batch Management</a>";
echo "<a href='admin/dashboard.php' style='background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>Go to Admin Dashboard</a>";
echo "</div>";

echo "</div>";
?> 