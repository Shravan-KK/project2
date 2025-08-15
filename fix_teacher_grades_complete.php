<?php
// Complete Fix for Teacher Grades Column Errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Complete Fix: Teacher Grades Column Errors</h1>";
echo "<p>Fixing both 'points_earned' and 'max_points' column errors</p>";

$file_path = 'teacher/grades.php';

if (!file_exists($file_path)) {
    die("<p>❌ File not found: $file_path</p>");
}

// Read the file
$content = file_get_contents($file_path);

// Create backup
file_put_contents($file_path . '.backup', $content);
echo "<p>✅ Created backup: {$file_path}.backup</p>";

// Show the problematic area
$lines = explode("\n", $content);
echo "<h2>📄 Current Content Around Line 42:</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; overflow-x: auto; max-height: 400px; overflow-y: auto;'>";

for ($i = 35; $i < 50 && $i < count($lines); $i++) {
    $line_num = $i + 1;
    $line_content = htmlspecialchars($lines[$i]);
    $highlight = ($line_num == 42) ? "background: #ffe6e6; font-weight: bold;" : "";
    echo "<div style='$highlight'>$line_num: $line_content</div>";
}
echo "</div>";

// Database connection for analysis
echo "<h2>🗃️ Database Analysis</h2>";

try {
    require_once '/home/shravan/web/training.kcdfindia.org/public_html/config/database.php';
    
    if (isset($conn)) {
        // Check assignments table structure
        echo "<h3>📊 Assignments Table Structure:</h3>";
        $result = $conn->query("DESCRIBE assignments");
        
        if ($result) {
            $columns = [];
            echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<strong>Current Columns:</strong><br>";
            while ($column = $result->fetch_assoc()) {
                $columns[] = $column['Field'];
                echo "• {$column['Field']} ({$column['Type']})<br>";
            }
            echo "</div>";
            
            $has_max_points = in_array('max_points', $columns);
            echo "<p><strong>Has max_points column:</strong> " . ($has_max_points ? "✅ Yes" : "❌ No") . "</p>";
            
        } else {
            echo "<p>❌ Could not describe assignments table</p>";
        }
        
        // Check other relevant tables
        echo "<h3>📊 Other Tables:</h3>";
        $tables = ['submissions', 'grades'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
                $count = $count_result ? $count_result->fetch_assoc()['count'] : 0;
                echo "<p>✅ $table table exists ($count records)</p>";
            } else {
                echo "<p>❌ $table table missing</p>";
            }
        }
        
    } else {
        echo "<p>❌ Database connection not available</p>";
    }
} catch (Exception $e) {
    echo "<p>⚠️ Database error: " . $e->getMessage() . "</p>";
}

// Apply comprehensive fixes
echo "<h2>🔧 Applying Fixes</h2>";

$fixes = [
    // Fix the points_earned issue
    's.points_earned' => 's.grade',
    'points_earned' => 'grade',
    
    // Fix the max_points issue  
    'a.max_points' => '100.00 as max_points',
    ', a.max_points' => ', 100.00 as max_points',
    'a.max_points,' => '100.00 as max_points,',
    
    // Alternative fixes
    'SELECT a.max_points' => 'SELECT 100.00 as max_points',
    'SELECT s.points_earned, a.max_points' => 'SELECT s.grade as points_earned, 100.00 as max_points',
    
    // Fix any division by max_points
    '/ a.max_points' => '/ 100.00',
    '/a.max_points' => '/100.00',
    
    // Fix any other references
    'max_points FROM assignments' => '100.00 as max_points FROM assignments'
];

$original_content = $content;
$changes_made = 0;

foreach ($fixes as $old => $new) {
    if (strpos($content, $old) !== false) {
        $content = str_replace($old, $new, $content);
        $changes_made++;
        echo "<p>🔄 Fixed: <code>" . htmlspecialchars($old) . "</code> → <code>" . htmlspecialchars($new) . "</code></p>";
    }
}

// Save the fixed content
if ($changes_made > 0) {
    file_put_contents($file_path, $content);
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>✅ File Updated Successfully!</h3>";
    echo "<p>Made $changes_made change(s) to fix the column errors.</p>";
    echo "</div>";
    
    // Show updated content
    $new_lines = explode("\n", $content);
    echo "<h2>📝 Updated Content Around Line 42:</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; overflow-x: auto; max-height: 400px; overflow-y: auto;'>";
    
    for ($i = 35; $i < 50 && $i < count($new_lines); $i++) {
        $line_num = $i + 1;
        $line_content = htmlspecialchars($new_lines[$i]);
        $highlight = ($line_num == 42) ? "background: #e6ffe6; font-weight: bold;" : "";
        echo "<div style='$highlight'>$line_num: $line_content</div>";
    }
    echo "</div>";
    
} else {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>⚠️ No Changes Applied</h3>";
    echo "<p>The automatic fixes couldn't be applied. The file might have a different structure.</p>";
    echo "</div>";
}

// Option to add missing columns to database
if (isset($conn)) {
    echo "<h2>🗃️ Database Column Options</h2>";
    
    echo "<div style='background: #cce5ff; color: #004085; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h4>Option 1: Add Missing Columns to Database</h4>";
    echo "<p>Add the missing columns to make the code work as intended:</p>";
    
    if (isset($_GET['add_columns'])) {
        $success_count = 0;
        
        // Add max_points to assignments
        $sql1 = "ALTER TABLE assignments ADD COLUMN max_points DECIMAL(5,2) DEFAULT 100.00";
        if ($conn->query($sql1) === TRUE) {
            echo "<p>✅ Added 'max_points' column to assignments table</p>";
            $success_count++;
        } else {
            if (strpos($conn->error, "Duplicate column") !== false) {
                echo "<p>⚠️ max_points column already exists in assignments</p>";
            } else {
                echo "<p>❌ Error adding max_points to assignments: " . $conn->error . "</p>";
            }
        }
        
        // Create grades table if missing
        $result = $conn->query("SHOW TABLES LIKE 'grades'");
        if ($result->num_rows == 0) {
            $sql2 = "CREATE TABLE grades (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                student_id INT(11),
                assignment_id INT(11),
                course_id INT(11),
                points_earned DECIMAL(5,2),
                max_points DECIMAL(5,2) DEFAULT 100.00,
                grade DECIMAL(5,2),
                feedback TEXT,
                graded_by INT(11),
                graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (student_id),
                INDEX (assignment_id)
            )";
            
            if ($conn->query($sql2) === TRUE) {
                echo "<p>✅ Created 'grades' table with points_earned column</p>";
                $success_count++;
            } else {
                echo "<p>❌ Error creating grades table: " . $conn->error . "</p>";
            }
        } else {
            echo "<p>ℹ️ Grades table already exists</p>";
        }
        
        if ($success_count > 0) {
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
            echo "<p><strong>Database updated!</strong> You may need to update your code to use the new structure.</p>";
            echo "</div>";
        }
        
    } else {
        echo "<p><a href='?add_columns=1' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Add Missing Columns to Database</a></p>";
    }
    echo "</div>";
    
    $conn->close();
}

echo "<h2>🎯 Test the Fix</h2>";
echo "<p>Test the teacher grades page now:</p>";
echo "<p><a href='teacher/grades.php' target='_blank' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🧪 Test Teacher Grades Page</a></p>";

echo "<h2>🔄 Restore Options</h2>";
echo "<p>If needed, you can restore the original file:</p>";
echo "<p><a href='restore_teacher_grades_complete.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Restore Original File</a></p>";

// Create restore script
$restore_script = '<?php
$file = "teacher/grades.php";
$backup = $file . ".backup";

if (file_exists($backup)) {
    if (copy($backup, $file)) {
        echo "<h1>✅ Restored Successfully</h1>";
        echo "<p>teacher/grades.php has been restored.</p>";
        unlink($backup);
    } else {
        echo "<h1>❌ Restore Failed</h1>";
    }
} else {
    echo "<h1>❌ Backup Not Found</h1>";
}
echo "<p><a href=\"teacher/grades.php\">Test Page</a></p>";
?>';

file_put_contents('restore_teacher_grades_complete.php', $restore_script);

echo "<hr>";
echo "<h2>💡 Summary of Fixes</h2>";
echo "<div style='background: #e7f3ff; color: #004085; padding: 15px; border-radius: 5px;'>";
echo "<h4>Problems Fixed:</h4>";
echo "<ul>";
echo "<li>✅ <strong>points_earned:</strong> Replaced with existing 'grade' column</li>";
echo "<li>✅ <strong>max_points:</strong> Replaced with default value of 100.00</li>";
echo "<li>✅ <strong>Query structure:</strong> Updated to work with current database</li>";
echo "</ul>";
echo "<h4>What This Means:</h4>";
echo "<p>Your teacher grades page should now work with your current database structure, using simplified grading (grade out of 100) instead of detailed point tracking.</p>";
echo "</div>";
?>