<?php
require_once 'config/database.php';

echo "<h2>Checking Announcements Table Structure</h2>";

// Check if announcements table exists
$check_table = $conn->query("SHOW TABLES LIKE 'announcements'");
if ($check_table->num_rows > 0) {
    echo "<p style='color: green;'>✓ Announcements table exists</p>";
    
    // Show table structure
    $structure = $conn->query("DESCRIBE announcements");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = $structure->fetch_assoc()) {
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
    
    // Show sample data
    $sample = $conn->query("SELECT * FROM announcements LIMIT 3");
    echo "<h3>Sample Data:</h3>";
    if ($sample->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        $first = true;
        while ($row = $sample->fetch_assoc()) {
            if ($first) {
                echo "<tr>";
                foreach (array_keys($row) as $column) {
                    echo "<th>$column</th>";
                }
                echo "</tr>";
                $first = false;
            }
            echo "<tr>";
            foreach ($row as $value) {
                echo "<td>" . htmlspecialchars($value ?? 'NULL') . "</td>";
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No data in announcements table</p>";
    }
} else {
    echo "<p style='color: red;'>✗ Announcements table does not exist</p>";
    echo "<p>Creating announcements table...</p>";
    
    $create_announcements = "CREATE TABLE announcements (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        target_audience ENUM('students', 'teachers', 'both') DEFAULT 'both',
        is_active TINYINT(1) DEFAULT 1,
        created_by INT(11),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
    )";
    
    if ($conn->query($create_announcements)) {
        echo "<p style='color: green;'>✓ Announcements table created</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating announcements table: " . $conn->error . "</p>";
    }
}

// Check announcement_reads table
echo "<h2>Checking Announcement Reads Table</h2>";
$check_reads = $conn->query("SHOW TABLES LIKE 'announcement_reads'");
if ($check_reads->num_rows > 0) {
    echo "<p style='color: green;'>✓ Announcement_reads table exists</p>";
} else {
    echo "<p style='color: red;'>✗ Announcement_reads table does not exist</p>";
    echo "<p>Creating announcement_reads table...</p>";
    
    $create_reads = "CREATE TABLE announcement_reads (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        announcement_id INT(11),
        user_id INT(11),
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_read (announcement_id, user_id)
    )";
    
    if ($conn->query($create_reads)) {
        echo "<p style='color: green;'>✓ Announcement_reads table created</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating announcement_reads table: " . $conn->error . "</p>";
    }
}

echo "<p><a href='teacher/course_view.php?id=1'>Test Course View Page</a></p>";
?>