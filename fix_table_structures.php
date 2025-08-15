<?php
// Fix Table Structures - Diagnose and repair table column issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Table Structures</h1>";

try {
    require_once 'config/database.php';
    echo "<p>✅ Database connected</p>";
} catch (Exception $e) {
    die("<p>❌ Database failed: " . $e->getMessage() . "</p>");
}

echo "<h2>📋 Current Table Structures</h2>";

// Check what tables exist and their structures
$tables_result = $conn->query("SHOW TABLES");
$existing_tables = [];

if ($tables_result) {
    while ($table = $tables_result->fetch_array()) {
        $table_name = $table[0];
        $existing_tables[] = $table_name;
        
        echo "<h3>Table: $table_name</h3>";
        
        // Show table structure
        $structure_result = $conn->query("DESCRIBE $table_name");
        if ($structure_result) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
            while ($column = $structure_result->fetch_assoc()) {
                echo "<tr>";
                echo "<td>" . $column['Field'] . "</td>";
                echo "<td>" . $column['Type'] . "</td>";
                echo "<td>" . $column['Null'] . "</td>";
                echo "<td>" . $column['Key'] . "</td>";
                echo "<td>" . $column['Default'] . "</td>";
                echo "<td>" . $column['Extra'] . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
}

echo "<h2>🔨 Fixing Table Structure Issues</h2>";

// Fix classes table if it exists but has wrong structure
if (in_array('classes', $existing_tables)) {
    echo "<h3>Fixing 'classes' table:</h3>";
    
    // Check if class_date column exists
    $column_check = $conn->query("SHOW COLUMNS FROM classes LIKE 'class_date'");
    if ($column_check->num_rows == 0) {
        echo "<p>❌ 'class_date' column missing from classes table</p>";
        
        // Add missing columns to classes table
        $alter_queries = [
            "ALTER TABLE classes ADD COLUMN class_date DATE",
            "ALTER TABLE classes ADD COLUMN start_time TIME",
            "ALTER TABLE classes ADD COLUMN end_time TIME", 
            "ALTER TABLE classes ADD COLUMN topic VARCHAR(200)",
            "ALTER TABLE classes ADD COLUMN description TEXT",
            "ALTER TABLE classes ADD COLUMN status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled'"
        ];
        
        foreach ($alter_queries as $query) {
            try {
                if ($conn->query($query) === TRUE) {
                    echo "<p>✅ Added column successfully</p>";
                } else {
                    // Column might already exist, check specific error
                    if (strpos($conn->error, "Duplicate column name") !== false) {
                        echo "<p>⚠️ Column already exists: " . $conn->error . "</p>";
                    } else {
                        echo "<p>❌ Error adding column: " . $conn->error . "</p>";
                    }
                }
            } catch (Exception $e) {
                echo "<p>❌ Exception: " . $e->getMessage() . "</p>";
            }
        }
    } else {
        echo "<p>✅ 'classes' table has correct structure</p>";
    }
} else {
    echo "<p>❌ 'classes' table doesn't exist, creating it...</p>";
    
    $create_classes_sql = "CREATE TABLE classes (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        course_id INT(11),
        batch_id INT(11),
        class_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        topic VARCHAR(200),
        description TEXT,
        status ENUM('scheduled', 'completed', 'cancelled') DEFAULT 'scheduled',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    )";
    
    if ($conn->query($create_classes_sql) === TRUE) {
        echo "<p>✅ Created 'classes' table successfully</p>";
    } else {
        echo "<p>❌ Error creating 'classes' table: " . $conn->error . "</p>";
    }
}

// Create other missing tables with correct structures
$required_tables = [
    'announcements' => "CREATE TABLE IF NOT EXISTS announcements (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(200) NOT NULL,
        content TEXT NOT NULL,
        target_audience ENUM('all', 'students', 'teachers', 'admins') DEFAULT 'all',
        is_active BOOLEAN DEFAULT TRUE,
        created_by INT(11),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX (created_by)
    )",
    
    'batches' => "CREATE TABLE IF NOT EXISTS batches (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        course_id INT(11),
        start_date DATE,
        end_date DATE,
        max_students INT(11) DEFAULT 30,
        status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (course_id)
    )",
    
    'certificates' => "CREATE TABLE IF NOT EXISTS certificates (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        student_id INT(11),
        course_id INT(11),
        certificate_number VARCHAR(50) UNIQUE,
        issue_date DATE,
        completion_date DATE,
        grade VARCHAR(10),
        certificate_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (student_id),
        INDEX (course_id)
    )",
    
    'grades' => "CREATE TABLE IF NOT EXISTS grades (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        student_id INT(11),
        assignment_id INT(11),
        course_id INT(11),
        grade DECIMAL(5,2),
        max_grade DECIMAL(5,2) DEFAULT 100.00,
        feedback TEXT,
        graded_by INT(11),
        graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX (student_id),
        INDEX (assignment_id),
        INDEX (course_id)
    )"
];

echo "<h3>Creating/Verifying Other Tables:</h3>";

foreach ($required_tables as $table_name => $create_sql) {
    if (!in_array($table_name, $existing_tables)) {
        echo "<p>Creating table '$table_name'...</p>";
        if ($conn->query($create_sql) === TRUE) {
            echo "<p>✅ Table '$table_name' created successfully</p>";
        } else {
            echo "<p>❌ Error creating table '$table_name': " . $conn->error . "</p>";
        }
    } else {
        echo "<p>✅ Table '$table_name' already exists</p>";
    }
}

echo "<h2>📊 Updated Table Structures</h2>";

// Show updated structures
$tables_result = $conn->query("SHOW TABLES");
if ($tables_result) {
    while ($table = $tables_result->fetch_array()) {
        $table_name = $table[0];
        
        // Get row count
        $count_result = $conn->query("SELECT COUNT(*) as count FROM $table_name");
        $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
        
        echo "<p>✅ $table_name: $count records</p>";
    }
}

echo "<h2>🎯 Test Data Compatible Script</h2>";

// Create a new test data script that works with the actual table structure
$compatible_test_data = '<?php
// Compatible Test Data Script
error_reporting(E_ALL);
ini_set("display_errors", 1);

echo "<h1>📊 Add Compatible Test Data</h1>";

try {
    require_once "config/database.php";
    echo "<p>✅ Database connected</p>";
} catch (Exception $e) {
    die("<p>❌ Database failed: " . $e->getMessage() . "</p>");
}

// Add test users
$test_users = [
    ["name" => "Admin User", "email" => "admin@test.com", "password" => "' . md5('admin123') . '", "type" => "admin"],
    ["name" => "John Teacher", "email" => "teacher@test.com", "password" => "' . md5('teacher123') . '", "type" => "teacher"],
    ["name" => "Jane Student", "email" => "student@test.com", "password" => "' . md5('student123') . '", "type" => "student"]
];

foreach ($test_users as $user) {
    $stmt = $conn->prepare("INSERT IGNORE INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $user["name"], $user["email"], $user["password"], $user["type"]);
    $stmt->execute();
}

// Add test courses
$stmt = $conn->prepare("INSERT IGNORE INTO courses (title, description, teacher_id, price) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssid", $title, $description, $teacher_id, $price);

$title = "Introduction to Programming"; $description = "Learn basic programming"; $teacher_id = 2; $price = 99.99;
$stmt->execute();

$title = "Web Development"; $description = "HTML, CSS, JavaScript"; $teacher_id = 2; $price = 149.99;
$stmt->execute();

// Add test enrollments
$stmt = $conn->prepare("INSERT IGNORE INTO enrollments (student_id, course_id) VALUES (?, ?)");
$stmt->bind_param("ii", $student_id, $course_id);

$student_id = 3; $course_id = 1; $stmt->execute();
$student_id = 3; $course_id = 2; $stmt->execute();

// Add test announcements
$stmt = $conn->prepare("INSERT IGNORE INTO announcements (title, content, target_audience, created_by) VALUES (?, ?, ?, ?)");
$stmt->bind_param("sssi", $title, $content, $target_audience, $created_by);

$title = "Welcome"; $content = "Welcome to our platform!"; $target_audience = "all"; $created_by = 1;
$stmt->execute();

$title = "New Course"; $content = "Check out our new course"; $target_audience = "students"; $created_by = 1;
$stmt->execute();

// Check if classes table has the right columns before inserting
$columns_result = $conn->query("SHOW COLUMNS FROM classes");
$has_class_date = false;
while ($column = $columns_result->fetch_assoc()) {
    if ($column["Field"] == "class_date") {
        $has_class_date = true;
        break;
    }
}

if ($has_class_date) {
    // Add test classes
    $stmt = $conn->prepare("INSERT IGNORE INTO classes (course_id, class_date, start_time, end_time, topic) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issss", $course_id, $class_date, $start_time, $end_time, $topic);

    $course_id = 1; $class_date = "2025-01-15"; $start_time = "10:00:00"; $end_time = "11:30:00"; $topic = "Introduction";
    $stmt->execute();

    echo "<p>✅ Test classes added</p>";
} else {
    echo "<p>⚠️ Classes table structure not compatible, skipping class data</p>";
}

echo "<h2>✅ Compatible test data added successfully!</h2>";
echo "<p><a href=\"admin/announcements.php\">Test Admin Announcements</a></p>";
echo "<p><a href=\"student/classes.php\">Test Student Classes</a></p>";

$conn->close();
?>';

file_put_contents('add_compatible_test_data.php', $compatible_test_data);
echo "<p>✅ Created 'add_compatible_test_data.php'</p>";

echo "<h2>🚀 Next Steps</h2>";
echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px;'>";
echo "<ol>";
echo "<li>Tables have been fixed/created with correct structures</li>";
echo "<li>Use the new compatible test data script: <a href='add_compatible_test_data.php'>add_compatible_test_data.php</a></li>";
echo "<li>Test your pages again after adding data</li>";
echo "</ol>";
echo "</div>";

$conn->close();
?>