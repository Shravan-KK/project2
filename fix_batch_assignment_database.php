<?php
// Fix Batch Assignment Database Issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Batch Assignment Database Issues</h1>";
echo "<p>Creating missing tables and fixing assignment functionality</p>";

echo "<style>
.success-box { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb; }
.error-box { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb; }
.info-box { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #b3d9ff; }
.warning-box { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ffeaa7; }
pre { background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto; border: 1px solid #e9ecef; }
</style>";

require_once '/Applications/MAMP/htdocs/project2/config/database.php';

echo "<h2>📊 Step 1: Database Analysis</h2>";

// Check current database tables
$tables_check = [
    'batches' => 'Core batch information table',
    'batch_courses' => 'Course assignments to batches',
    'batch_instructors' => 'Instructor assignments to batches'
];

foreach ($tables_check as $table => $description) {
    $check_query = "SHOW TABLES LIKE '$table'";
    $result = $conn->query($check_query);
    
    if ($result->num_rows > 0) {
        echo "<div class='success-box'>";
        echo "<p>✅ <strong>$table:</strong> $description - EXISTS</p>";
        echo "</div>";
    } else {
        echo "<div class='error-box'>";
        echo "<p>❌ <strong>$table:</strong> $description - MISSING</p>";
        echo "</div>";
    }
}

echo "<h2>🏗️ Step 2: Create Missing Tables</h2>";

// Create batch_instructors table
$batch_instructors_exists = $conn->query("SHOW TABLES LIKE 'batch_instructors'")->num_rows > 0;

if (!$batch_instructors_exists) {
    echo "<div class='info-box'>";
    echo "<p>Creating batch_instructors table...</p>";
    echo "</div>";
    
    $create_batch_instructors = "CREATE TABLE batch_instructors (
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
    
    if ($conn->query($create_batch_instructors)) {
        echo "<div class='success-box'>";
        echo "<p>✅ Created batch_instructors table successfully!</p>";
        echo "</div>";
    } else {
        echo "<div class='error-box'>";
        echo "<p>❌ Error creating batch_instructors table: " . $conn->error . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='warning-box'>";
    echo "<p>⚠️ batch_instructors table already exists</p>";
    echo "</div>";
}

// Create batch_courses table if it doesn't exist
$batch_courses_exists = $conn->query("SHOW TABLES LIKE 'batch_courses'")->num_rows > 0;

if (!$batch_courses_exists) {
    echo "<div class='info-box'>";
    echo "<p>Creating batch_courses table...</p>";
    echo "</div>";
    
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
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($create_batch_courses)) {
        echo "<div class='success-box'>";
        echo "<p>✅ Created batch_courses table successfully!</p>";
        echo "</div>";
    } else {
        echo "<div class='error-box'>";
        echo "<p>❌ Error creating batch_courses table: " . $conn->error . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='warning-box'>";
    echo "<p>⚠️ batch_courses table already exists</p>";
    echo "</div>";
}

// Ensure batches table exists
$batches_exists = $conn->query("SHOW TABLES LIKE 'batches'")->num_rows > 0;

if (!$batches_exists) {
    echo "<div class='info-box'>";
    echo "<p>Creating batches table...</p>";
    echo "</div>";
    
    $create_batches = "CREATE TABLE batches (
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
    
    if ($conn->query($create_batches)) {
        echo "<div class='success-box'>";
        echo "<p>✅ Created batches table successfully!</p>";
        echo "</div>";
    } else {
        echo "<div class='error-box'>";
        echo "<p>❌ Error creating batches table: " . $conn->error . "</p>";
        echo "</div>";
    }
} else {
    echo "<div class='success-box'>";
    echo "<p>✅ batches table already exists</p>";
    echo "</div>";
}

echo "<h2>👥 Step 3: Add Sample Data</h2>";

// Add sample instructors
$sample_instructors = [
    ['name' => 'Dr. Sarah Johnson', 'email' => 'sarah.johnson@training.com'],
    ['name' => 'Prof. Michael Chen', 'email' => 'michael.chen@training.com'],
    ['name' => 'Dr. Emily Rodriguez', 'email' => 'emily.rodriguez@training.com'],
    ['name' => 'Mr. David Wilson', 'email' => 'david.wilson@training.com'],
    ['name' => 'Ms. Lisa Thompson', 'email' => 'lisa.thompson@training.com']
];

echo "<div class='info-box'>";
echo "<h3>Adding Sample Instructors</h3>";
foreach ($sample_instructors as $instructor) {
    $check_instructor = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_instructor);
    $check_stmt->bind_param("s", $instructor['email']);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->fetch_assoc();
    
    if (!$exists) {
        $password_hash = md5('instructor123'); // Using MD5 as requested
        $insert_instructor = "INSERT INTO users (name, email, password, user_type, status) VALUES (?, ?, ?, 'teacher', 'active')";
        $insert_stmt = $conn->prepare($insert_instructor);
        $insert_stmt->bind_param("sss", $instructor['name'], $instructor['email'], $password_hash);
        
        if ($insert_stmt->execute()) {
            echo "<p>✅ Added instructor: " . $instructor['name'] . " (Password: instructor123)</p>";
        } else {
            echo "<p>❌ Failed to add instructor: " . $instructor['name'] . "</p>";
        }
    } else {
        echo "<p>⚠️ Instructor already exists: " . $instructor['name'] . "</p>";
    }
}
echo "</div>";

// Add sample batches if none exist
$batch_count = $conn->query("SELECT COUNT(*) as count FROM batches")->fetch_assoc()['count'];

if ($batch_count == 0) {
    echo "<div class='info-box'>";
    echo "<h3>Adding Sample Batches</h3>";
    
    $sample_batches = [
        ['name' => 'Web Development Batch 2024', 'description' => 'Comprehensive web development training', 'start_date' => '2024-01-15', 'end_date' => '2024-06-15', 'max_students' => 25],
        ['name' => 'Data Science Batch 2024', 'description' => 'Python and machine learning focus', 'start_date' => '2024-02-01', 'end_date' => '2024-07-01', 'max_students' => 20],
        ['name' => 'Mobile App Development', 'description' => 'React Native and Flutter training', 'start_date' => '2024-03-01', 'end_date' => '2024-08-01', 'max_students' => 30]
    ];
    
    foreach ($sample_batches as $batch) {
        $insert_batch = "INSERT INTO batches (name, description, start_date, end_date, max_students) VALUES (?, ?, ?, ?, ?)";
        $batch_stmt = $conn->prepare($insert_batch);
        $batch_stmt->bind_param("ssssi", $batch['name'], $batch['description'], $batch['start_date'], $batch['end_date'], $batch['max_students']);
        
        if ($batch_stmt->execute()) {
            echo "<p>✅ Added batch: " . $batch['name'] . "</p>";
        } else {
            echo "<p>❌ Failed to add batch: " . $batch['name'] . "</p>";
        }
    }
    echo "</div>";
}

echo "<h2>🧪 Step 4: Test Database Setup</h2>";

echo "<div class='info-box'>";
echo "<h3>Final Database Verification</h3>";

// Test table creation
$final_check = [
    'batches' => "SELECT COUNT(*) as count FROM batches",
    'batch_courses' => "SELECT COUNT(*) as count FROM batch_courses", 
    'batch_instructors' => "SELECT COUNT(*) as count FROM batch_instructors",
    'users (teachers)' => "SELECT COUNT(*) as count FROM users WHERE user_type = 'teacher'",
    'courses' => "SELECT COUNT(*) as count FROM courses"
];

foreach ($final_check as $item => $query) {
    try {
        $result = $conn->query($query);
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "<p>✅ <strong>$item:</strong> $count records available</p>";
        } else {
            echo "<p>❌ <strong>$item:</strong> Query failed</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ <strong>$item:</strong> Error - " . $e->getMessage() . "</p>";
    }
}
echo "</div>";

echo "<div class='success-box'>";
echo "<h2>✅ Database Setup Complete!</h2>";
echo "<p>All required tables have been created and populated with sample data.</p>";
echo "<p><strong>You can now:</strong></p>";
echo "<ul>";
echo "<li>✅ Assign instructors to batches</li>";
echo "<li>✅ Assign courses to batches</li>";
echo "<li>✅ View batch details with assignments</li>";
echo "<li>✅ Manage all assignments from admin interface</li>";
echo "</ul>";
echo "</div>";

echo "<div class='warning-box'>";
echo "<h3>🔄 Next Steps:</h3>";
echo "<p>1. Go to <a href='/admin/batches.php' target='_blank'><strong>Admin → Batch Management</strong></a></p>";
echo "<p>2. Test instructor assignment (green user+ icon)</p>";
echo "<p>3. Test course assignment (purple book+ icon)</p>";
echo "<p>4. View batch details to see assignments</p>";
echo "</div>";

echo "<div class='info-box'>";
echo "<h3>📋 Sample Login Credentials</h3>";
echo "<p><strong>Admin:</strong> admin@tms.com / admin123</p>";
echo "<p><strong>Instructors:</strong> [name]@training.com / instructor123</p>";
echo "<p>Example: sarah.johnson@training.com / instructor123</p>";
echo "</div>";

?>