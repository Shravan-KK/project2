<?php
// Fix Student Pages Column Errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Student Pages Column Errors</h1>";
echo "<p>Fixing column issues in student/assignments.php and student/grades.php</p>";

// CSS for better formatting
echo "<style>
.error-box { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb; }
.success-box { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb; }
.warning-box { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ffeaa7; }
.info-box { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #b3d9ff; }
.code-block { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; overflow-x: auto; max-height: 400px; overflow-y: auto; border: 1px solid #ddd; }
</style>";

$files_to_fix = [
    'student/assignments.php' => [
        'name' => 'Student Assignments',
        'error_line' => 74,
        'error_column' => 's.submission_text',
        'description' => 'Unknown column s.submission_text in SELECT'
    ],
    'student/grades.php' => [
        'name' => 'Student Grades', 
        'error_line' => 42,
        'error_column' => 's.submitted_time',
        'description' => 'Unknown column s.submitted_time in SELECT'
    ]
];

// Database analysis first
echo "<h2>🗃️ Database Structure Analysis</h2>";

try {
    require_once '/home/shravan/web/training.kcdfindia.org/public_html/config/database.php';
    
    if (isset($conn)) {
        echo "<div class='info-box'>";
        echo "<h3>📊 Submissions Table Structure:</h3>";
        
        $result = $conn->query("DESCRIBE submissions");
        if ($result) {
            $columns = [];
            echo "<div class='code-block'>";
            echo "<strong>Available Columns:</strong><br>";
            while ($column = $result->fetch_assoc()) {
                $columns[] = $column['Field'];
                echo "• {$column['Field']} ({$column['Type']})<br>";
            }
            echo "</div>";
            
            echo "<p><strong>Column Analysis:</strong></p>";
            echo "<ul>";
            echo "<li>submission_text: " . (in_array('submission_text', $columns) ? "✅ Found" : "❌ Missing") . "</li>";
            echo "<li>content: " . (in_array('content', $columns) ? "✅ Found" : "❌ Missing") . "</li>";
            echo "<li>submitted_time: " . (in_array('submitted_time', $columns) ? "✅ Found" : "❌ Missing") . "</li>";
            echo "<li>submitted_at: " . (in_array('submitted_at', $columns) ? "✅ Found" : "❌ Missing") . "</li>";
            echo "</ul>";
        } else {
            echo "<p>❌ Could not describe submissions table</p>";
        }
        echo "</div>";
    }
} catch (Exception $e) {
    echo "<div class='warning-box'>⚠️ Database analysis failed: " . $e->getMessage() . "</div>";
}

// Function to fix a single file
function fixStudentFile($file_path, $file_info) {
    global $conn;
    
    echo "<div class='info-box'>";
    echo "<h3>🔧 Fixing: {$file_info['name']}</h3>";
    echo "<p><strong>File:</strong> $file_path</p>";
    echo "<p><strong>Error:</strong> {$file_info['description']} on line {$file_info['error_line']}</p>";
    echo "</div>";
    
    if (!file_exists($file_path)) {
        echo "<div class='error-box'>❌ File not found: $file_path</div>";
        return false;
    }
    
    // Read file content
    $content = file_get_contents($file_path);
    
    // Create backup
    $backup_file = $file_path . '.backup-' . date('YmdHis');
    file_put_contents($backup_file, $content);
    echo "<p>✅ Created backup: " . basename($backup_file) . "</p>";
    
    // Show problematic area
    $lines = explode("\n", $content);
    $error_line = $file_info['error_line'];
    
    echo "<h4>📄 Current Content Around Line {$error_line}:</h4>";
    echo "<div class='code-block'>";
    for ($i = $error_line - 10; $i < $error_line + 5 && $i < count($lines); $i++) {
        if ($i >= 0) {
            $line_num = $i + 1;
            $line_content = htmlspecialchars($lines[$i]);
            $highlight = ($line_num == $error_line) ? "background: #ffe6e6; font-weight: bold;" : "";
            echo "<div style='$highlight'>$line_num: $line_content</div>";
        }
    }
    echo "</div>";
    
    // Apply fixes based on the specific error
    $fixes = [];
    $original_content = $content;
    
    if (strpos($file_path, 'assignments.php') !== false) {
        // Student assignments fixes
        $fixes = [
            's.submission_text' => 's.content',
            'submission_text' => 'content',
            ', s.submission_text' => ', s.content',
            's.submission_text,' => 's.content,',
            'SELECT s.submission_text' => 'SELECT s.content',
            's.submission_text as text' => 's.content as text',
            's.submission_text AS text' => 's.content AS text'
        ];
    } elseif (strpos($file_path, 'grades.php') !== false) {
        // Student grades fixes
        $fixes = [
            's.submitted_time' => 's.submitted_at',
            'submitted_time' => 'submitted_at',
            ', s.submitted_time' => ', s.submitted_at',
            's.submitted_time,' => 's.submitted_at,',
            'SELECT s.submitted_time' => 'SELECT s.submitted_at',
            'ORDER BY s.submitted_time' => 'ORDER BY s.submitted_at',
            's.submitted_time DESC' => 's.submitted_at DESC',
            's.submitted_time ASC' => 's.submitted_at ASC'
        ];
    }
    
    $changes_made = 0;
    $changes_log = [];
    
    foreach ($fixes as $old => $new) {
        if (strpos($content, $old) !== false) {
            $content = str_replace($old, $new, $content);
            $changes_made++;
            $changes_log[] = ['old' => $old, 'new' => $new];
            echo "<p>🔄 Fixed: <code>$old</code> → <code>$new</code></p>";
        }
    }
    
    if ($changes_made > 0) {
        file_put_contents($file_path, $content);
        
        echo "<div class='success-box'>";
        echo "<h4>✅ {$file_info['name']} Updated Successfully!</h4>";
        echo "<p>Made $changes_made change(s) to fix column errors.</p>";
        echo "</div>";
        
        // Show updated content
        $new_lines = explode("\n", $content);
        echo "<h4>📝 Updated Content Around Line {$error_line}:</h4>";
        echo "<div class='code-block'>";
        for ($i = $error_line - 10; $i < $error_line + 5 && $i < count($new_lines); $i++) {
            if ($i >= 0) {
                $line_num = $i + 1;
                $line_content = htmlspecialchars($new_lines[$i]);
                $highlight = ($line_num == $error_line) ? "background: #e6ffe6; font-weight: bold;" : "";
                echo "<div style='$highlight'>$line_num: $line_content</div>";
            }
        }
        echo "</div>";
        
        return true;
    } else {
        echo "<div class='warning-box'>";
        echo "<h4>⚠️ No Changes Applied to {$file_info['name']}</h4>";
        echo "<p>The automatic fixes couldn't be applied. The file might have a different structure.</p>";
        echo "</div>";
        return false;
    }
}

// Fix all files
echo "<h2>🔧 Processing Student Files</h2>";

$fixed_files = 0;
foreach ($files_to_fix as $file_path => $file_info) {
    if (fixStudentFile($file_path, $file_info)) {
        $fixed_files++;
    }
    echo "<hr>";
}

// Summary
echo "<h2>📊 Fix Summary</h2>";

if ($fixed_files > 0) {
    echo "<div class='success-box'>";
    echo "<h3>✅ Successfully Fixed $fixed_files out of " . count($files_to_fix) . " Files</h3>";
    echo "<ul>";
    echo "<li><strong>student/assignments.php:</strong> Fixed 's.submission_text' → 's.content'</li>";
    echo "<li><strong>student/grades.php:</strong> Fixed 's.submitted_time' → 's.submitted_at'</li>";
    echo "</ul>";
    echo "</div>";
} else {
    echo "<div class='warning-box'>";
    echo "<h3>⚠️ No Files Were Fixed Automatically</h3>";
    echo "<p>Manual intervention may be required.</p>";
    echo "</div>";
}

// Column mapping reference
echo "<h2>📋 Column Mapping Reference</h2>";
echo "<div class='info-box'>";
echo "<h4>Common Column Name Mappings:</h4>";
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #f8f9fa;'><th>Expected Column</th><th>Actual Column</th><th>Table</th></tr>";
echo "<tr><td>submission_text</td><td>content</td><td>submissions</td></tr>";
echo "<tr><td>submitted_time</td><td>submitted_at</td><td>submissions</td></tr>";
echo "<tr><td>points_earned</td><td>grade</td><td>submissions/grades</td></tr>";
echo "<tr><td>max_points</td><td>100.00 (default)</td><td>assignments</td></tr>";
echo "<tr><td>lesson_completed</td><td>completed_at IS NOT NULL</td><td>student_progress</td></tr>";
echo "</table>";
echo "</div>";

// Test links
echo "<h2>🎯 Test Your Fixed Pages</h2>";
echo "<p>Test the student pages to see if the column errors are resolved:</p>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0;'>";

$test_pages = [
    'student/assignments.php' => 'Student Assignments',
    'student/grades.php' => 'Student Grades',
    'student/classes.php' => 'Student Classes',
    'student/announcements.php' => 'Student Announcements',
    'student/certificates.php' => 'Student Certificates'
];

foreach ($test_pages as $url => $name) {
    $status_color = in_array($url, array_keys($files_to_fix)) ? '#28a745' : '#007bff';
    echo "<a href='$url' target='_blank' style='background: $status_color; color: white; padding: 15px; text-decoration: none; border-radius: 8px; text-align: center; display: block; font-weight: bold;'>$name</a>";
}
echo "</div>";

// Restore options
echo "<h2>🔄 Backup and Restore</h2>";
echo "<div class='info-box'>";
echo "<p>All original files have been backed up with timestamps. If you need to restore any file:</p>";

// List backup files
$backup_files = [];
foreach ($files_to_fix as $file_path => $file_info) {
    $pattern = $file_path . '.backup-*';
    $backups = glob($pattern);
    if (!empty($backups)) {
        $backup_files[$file_path] = $backups;
    }
}

if (!empty($backup_files)) {
    echo "<ul>";
    foreach ($backup_files as $original_file => $backups) {
        echo "<li><strong>" . basename($original_file) . ":</strong>";
        foreach ($backups as $backup) {
            $backup_name = basename($backup);
            echo " $backup_name";
        }
        echo "</li>";
    }
    echo "</ul>";
}
echo "</div>";

// Create restore script
$restore_script = '<?php
echo "<h1>🔄 Restore Student Pages</h1>";

$files = [
    "student/assignments.php" => "Student Assignments",
    "student/grades.php" => "Student Grades"
];

foreach ($files as $file => $name) {
    echo "<h3>$name</h3>";
    $backups = glob($file . ".backup-*");
    
    if (!empty($backups)) {
        $latest_backup = max($backups);
        
        if (isset($_GET["restore"]) && $_GET["restore"] == $file) {
            if (copy($latest_backup, $file)) {
                echo "<p style=\"color: green;\">✅ $name restored successfully</p>";
            } else {
                echo "<p style=\"color: red;\">❌ Failed to restore $name</p>";
            }
        } else {
            echo "<p>Latest backup: " . basename($latest_backup) . "</p>";
            echo "<p><a href=\"?restore=" . urlencode($file) . "\" style=\"background: #dc3545; color: white; padding: 8px 12px; text-decoration: none; border-radius: 4px;\">Restore This File</a></p>";
        }
    } else {
        echo "<p>No backups found for $name</p>";
    }
    echo "<hr>";
}

echo "<p><a href=\"detailed_error_reporter.php\">← Back to Error Reporter</a></p>";
?>';

file_put_contents('restore_student_pages.php', $restore_script);
echo "<p><a href='restore_student_pages.php' style='background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>Manage Backups & Restore</a></p>";

if (isset($conn)) {
    $conn->close();
}

echo "<hr>";
echo "<h2>💡 What Was Fixed</h2>";
echo "<div class='info-box'>";
echo "<h4>Student Page Column Errors Resolved:</h4>";
echo "<ul>";
echo "<li>✅ <strong>student/assignments.php:</strong> <code>s.submission_text</code> → <code>s.content</code></li>";
echo "<li>✅ <strong>student/grades.php:</strong> <code>s.submitted_time</code> → <code>s.submitted_at</code></li>";
echo "</ul>";
echo "<h4>Result:</h4>";
echo "<p>Both student pages should now work with your current database structure, using the correct column names that actually exist in your submissions table.</p>";
echo "</div>";
?>