<?php
// Fix Teacher Grades Column Error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Teacher Grades Column Error</h1>";

$file_path = 'teacher/grades.php';

if (!file_exists($file_path)) {
    die("<p>❌ File not found: $file_path</p>");
}

// Read the file
$content = file_get_contents($file_path);

// Create backup
file_put_contents($file_path . '.backup', $content);
echo "<p>✅ Created backup: {$file_path}.backup</p>";

// Show the problematic line (around line 42)
$lines = explode("\n", $content);
echo "<h2>📄 Current Content Around Line 42:</h2>";
echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; overflow-x: auto;'>";

for ($i = 37; $i < 47 && $i < count($lines); $i++) {
    $line_num = $i + 1;
    $line_content = htmlspecialchars($lines[$i]);
    $highlight = ($line_num == 42) ? "background: #ffe6e6;" : "";
    echo "<div style='$highlight'>$line_num: $line_content</div>";
}
echo "</div>";

// Common fixes for the column error
$fixes = [
    // Fix 1: Replace points_earned with grade from submissions
    's.points_earned' => 's.grade',
    
    // Fix 2: Replace with grade from grades table
    's.points_earned' => 'g.grade',
    
    // Fix 3: If it's supposed to be from grades table, fix the alias
    'submissions s' => 'grades s',
    
    // Fix 4: Add proper JOIN if needed
    'FROM submissions s' => 'FROM submissions s LEFT JOIN grades g ON s.assignment_id = g.assignment_id AND s.student_id = g.student_id',
    
    // Fix 5: Remove the problematic column entirely
    ', s.points_earned' => '-- , s.points_earned (removed)',
    's.points_earned,' => '-- s.points_earned (removed),',
    's.points_earned as points' => 's.grade as points',
    's.points_earned AS points' => 's.grade AS points'
];

$original_content = $content;
$changes_made = 0;

foreach ($fixes as $old => $new) {
    if (strpos($content, $old) !== false) {
        $content = str_replace($old, $new, $content);
        $changes_made++;
        echo "<p>🔄 Replaced: <code>$old</code> → <code>$new</code></p>";
    }
}

if ($changes_made > 0) {
    file_put_contents($file_path, $content);
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>✅ File Updated Successfully!</h3>";
    echo "<p>Made $changes_made change(s) to fix the column error.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>⚠️ No Automatic Fix Applied</h3>";
    echo "<p>The script couldn't automatically fix the column error. Manual editing may be required.</p>";
    echo "</div>";
}

// Show the updated content around line 42
if ($changes_made > 0) {
    $new_lines = explode("\n", $content);
    echo "<h2>📝 Updated Content Around Line 42:</h2>";
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; overflow-x: auto;'>";
    
    for ($i = 37; $i < 47 && $i < count($new_lines); $i++) {
        $line_num = $i + 1;
        $line_content = htmlspecialchars($new_lines[$i]);
        $highlight = ($line_num == 42) ? "background: #e6ffe6;" : "";
        echo "<div style='$highlight'>$line_num: $line_content</div>";
    }
    echo "</div>";
}

// Check what tables exist and their columns
echo "<h2>🗃️ Database Table Analysis</h2>";

try {
    require_once '/home/shravan/web/training.kcdfindia.org/public_html/config/database.php';
    
    if (isset($conn)) {
        echo "<h3>📊 Checking Table Structures:</h3>";
        
        $tables_to_check = ['submissions', 'grades', 'assignments'];
        
        foreach ($tables_to_check as $table) {
            echo "<h4>Table: $table</h4>";
            
            // Check if table exists
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                echo "<p>✅ Table exists</p>";
                
                // Show columns
                $columns_result = $conn->query("DESCRIBE $table");
                if ($columns_result) {
                    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 10px 0;'>";
                    echo "<strong>Columns:</strong><br>";
                    while ($column = $columns_result->fetch_assoc()) {
                        echo "• {$column['Field']} ({$column['Type']})<br>";
                    }
                    echo "</div>";
                } else {
                    echo "<p>❌ Couldn't describe table</p>";
                }
                
                // Show record count
                $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
                if ($count_result) {
                    $count = $count_result->fetch_assoc()['count'];
                    echo "<p>📊 Records: $count</p>";
                }
            } else {
                echo "<p>❌ Table doesn't exist</p>";
                
                if ($table == 'grades') {
                    echo "<div style='background: #cce5ff; color: #004085; padding: 15px; border-radius: 5px;'>";
                    echo "<h4>🔧 Create Grades Table</h4>";
                    echo "<p>The grades table might be missing. Click to create it:</p>";
                    
                    if (isset($_GET['create_grades'])) {
                        $create_sql = "CREATE TABLE grades (
                            id INT(11) AUTO_INCREMENT PRIMARY KEY,
                            student_id INT(11),
                            assignment_id INT(11),
                            course_id INT(11),
                            grade DECIMAL(5,2),
                            points_earned DECIMAL(5,2),
                            max_points DECIMAL(5,2) DEFAULT 100.00,
                            feedback TEXT,
                            graded_by INT(11),
                            graded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                            INDEX (student_id),
                            INDEX (assignment_id)
                        )";
                        
                        if ($conn->query($create_sql) === TRUE) {
                            echo "<p>✅ Grades table created successfully with points_earned column!</p>";
                        } else {
                            echo "<p>❌ Error creating grades table: " . $conn->error . "</p>";
                        }
                    } else {
                        echo "<p><a href='?create_grades=1' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Create Grades Table</a></p>";
                    }
                    echo "</div>";
                }
            }
            echo "<hr>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p>⚠️ Database connection issue: " . $e->getMessage() . "</p>";
}

echo "<h2>🎯 Test the Fix</h2>";
echo "<p>Now test the teacher grades page:</p>";
echo "<p><a href='teacher/grades.php' target='_blank' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🧪 Test Teacher Grades Page</a></p>";

echo "<h2>🔄 Restore if Needed</h2>";
echo "<p>If the fix doesn't work, you can restore the original file:</p>";
echo "<p><a href='restore_teacher_grades.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Restore Original</a></p>";

// Create restore script
$restore_script = '<?php
$file_path = "teacher/grades.php";
$backup_path = $file_path . ".backup";

if (file_exists($backup_path)) {
    if (copy($backup_path, $file_path)) {
        echo "<h1>✅ File Restored</h1>";
        echo "<p>teacher/grades.php has been restored from backup.</p>";
        unlink($backup_path);
    } else {
        echo "<h1>❌ Restore Failed</h1>";
    }
} else {
    echo "<h1>❌ Backup Not Found</h1>";
}
echo "<p><a href=\"teacher/grades.php\">Test Page</a></p>";
?>';

file_put_contents('restore_teacher_grades.php', $restore_script);

echo "<hr>";
echo "<h2>💡 Summary</h2>";
echo "<div style='background: #e7f3ff; color: #004085; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>The Error:</strong> Your SQL query is trying to SELECT a column 'points_earned' that doesn't exist in your database table.</p>";
echo "<p><strong>Most Likely Fix:</strong> Replace 's.points_earned' with 's.grade' or create the missing grades table.</p>";
echo "<p><strong>Alternative:</strong> The query might need to join with a grades table, or the table structure needs to be updated.</p>";
echo "</div>";
?>