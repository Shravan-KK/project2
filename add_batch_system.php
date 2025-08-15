<?php
require_once 'config/database.php';

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
    echo "✓ Batches table created successfully<br>";
} else {
    echo "✗ Error creating batches table: " . $conn->error . "<br>";
}

// Create batch_courses table (many-to-many relationship)
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
    echo "✓ Batch courses table created successfully<br>";
} else {
    echo "✗ Error creating batch_courses table: " . $conn->error . "<br>";
}

// Add batch_id to enrollments table
$sql = "ALTER TABLE enrollments ADD COLUMN IF NOT EXISTS batch_id INT(11) AFTER course_id";
if ($conn->query($sql) === TRUE) {
    echo "✓ Added batch_id to enrollments table<br>";
} else {
    echo "✗ Error adding batch_id to enrollments: " . $conn->error . "<br>";
}

// Add foreign key constraint for batch_id in enrollments
$sql = "ALTER TABLE enrollments ADD CONSTRAINT fk_enrollment_batch FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE SET NULL";
if ($conn->query($sql) === TRUE) {
    echo "✓ Added foreign key constraint for batch_id<br>";
} else {
    echo "✗ Error adding foreign key constraint: " . $conn->error . "<br>";
}

// Insert sample batches
$sample_batches = [
    ['name' => 'Web Development Batch 2024', 'description' => 'Complete web development course for beginners', 'start_date' => '2024-01-15', 'end_date' => '2024-06-15', 'max_students' => 25],
    ['name' => 'Python Programming Batch A', 'description' => 'Python programming fundamentals and advanced concepts', 'start_date' => '2024-02-01', 'end_date' => '2024-07-01', 'max_students' => 20],
    ['name' => 'Data Science Batch 2024', 'description' => 'Data science and machine learning course', 'start_date' => '2024-03-01', 'end_date' => '2024-08-01', 'max_students' => 30],
    ['name' => 'Mobile App Development', 'description' => 'iOS and Android app development', 'start_date' => '2024-04-01', 'end_date' => '2024-09-01', 'max_students' => 15]
];

foreach ($sample_batches as $batch) {
    $sql = "INSERT IGNORE INTO batches (name, description, start_date, end_date, max_students) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $batch['name'], $batch['description'], $batch['start_date'], $batch['end_date'], $batch['max_students']);
    if ($stmt->execute()) {
        echo "✓ Sample batch '{$batch['name']}' created<br>";
    } else {
        echo "✗ Error creating sample batch: " . $stmt->error . "<br>";
    }
}

// Get existing courses and batches to create sample batch_courses
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
        $sql = "INSERT IGNORE INTO batch_courses (batch_id, course_id, start_date, end_date) VALUES (?, ?, '2024-01-15', '2024-06-15')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $batch_ids[$i], $course_ids[$i]);
        if ($stmt->execute()) {
            echo "✓ Batch-course assignment created<br>";
        }
    }
}

echo "<br><strong>Batch management system has been successfully implemented!</strong><br>";
echo "<h3>What was added:</h3>";
echo "<ul>";
echo "<li>✓ <strong>batches</strong> table - to store batch information</li>";
echo "<li>✓ <strong>batch_courses</strong> table - to link batches with courses</li>";
echo "<li>✓ <strong>batch_id</strong> field in enrollments table</li>";
echo "<li>✓ Sample batches and batch-course assignments</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Create admin interface for batch management</li>";
echo "<li>Modify enrollment process to include batch selection</li>";
echo "<li>Add batch-specific reporting and analytics</li>";
echo "<li>Create batch assignment functionality for teachers</li>";
echo "</ol>";

echo "<br><a href='admin/dashboard.php' class='bg-blue-500 text-white px-4 py-2 rounded'>Go to Admin Dashboard</a>";
?> 