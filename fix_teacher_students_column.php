<?php
// Fix Teacher Students Column Error
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Teacher Students Column Error</h1>";

$file_path = 'teacher/students.php';

if (!file_exists($file_path)) {
    die("<p>❌ File not found: $file_path</p>");
}

// Read the file
$content = file_get_contents($file_path);

// Create backup
file_put_contents($file_path . '.backup', $content);
echo "<p>✅ Created backup: {$file_path}.backup</p>";

// Show the problematic line (around line 25)
$lines = explode("\n", $content);
echo "<h2>📄 Current Content Around Line 25:</h2>";
echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;'>";

for ($i = 20; $i < 30 && $i < count($lines); $i++) {
    $line_num = $i + 1;
    $line_content = htmlspecialchars($lines[$i]);
    $highlight = ($line_num == 25) ? "background: #ffe6e6;" : "";
    echo "<div style='$highlight'>$line_num: $line_content</div>";
}
echo "</div>";

// Common fixes for the column error
$fixes = [
    // Fix 1: Replace lesson_completed with completed_at checks
    'sp.lesson_completed = 1' => 'sp.completed_at IS NOT NULL',
    'sp.lesson_completed = 0' => 'sp.completed_at IS NULL',
    'sp.lesson_completed' => 'sp.completed_at',
    
    // Fix 2: Replace with progress percentage checks
    'lesson_completed = 1' => 'progress_percentage = 100',
    'lesson_completed = 0' => 'progress_percentage < 100',
    
    // Fix 3: Remove the problematic column reference entirely
    'AND sp.lesson_completed' => '-- AND sp.lesson_completed (removed)',
    'WHERE sp.lesson_completed' => 'WHERE sp.completed_at IS NOT NULL',
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

// Show the updated content around line 25
if ($changes_made > 0) {
    $new_lines = explode("\n", $content);
    echo "<h2>📝 Updated Content Around Line 25:</h2>";
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace;'>";
    
    for ($i = 20; $i < 30 && $i < count($new_lines); $i++) {
        $line_num = $i + 1;
        $line_content = htmlspecialchars($new_lines[$i]);
        $highlight = ($line_num == 25) ? "background: #e6ffe6;" : "";
        echo "<div style='$highlight'>$line_num: $line_content</div>";
    }
    echo "</div>";
}

// Alternative: Add the missing column to the database
echo "<h2>🗃️ Alternative: Add Missing Column</h2>";
echo "<p>If you prefer to add the missing column instead of changing the query:</p>";

try {
    require_once '/home/shravan/web/training.kcdfindia.org/public_html/config/database.php';
    
    if (isset($conn)) {
        echo "<div style='background: #cce5ff; color: #004085; padding: 15px; border-radius: 5px;'>";
        echo "<h4>🔧 Database Option:</h4>";
        echo "<p>Click the button below to add the missing column to your database:</p>";
        
        if (isset($_GET['add_column'])) {
            $sql = "ALTER TABLE student_progress ADD COLUMN lesson_completed BOOLEAN DEFAULT FALSE";
            if ($conn->query($sql) === TRUE) {
                echo "<p>✅ Added 'lesson_completed' column successfully!</p>";
            } else {
                echo "<p>❌ Error adding column: " . $conn->error . "</p>";
            }
        } else {
            echo "<p><a href='?add_column=1' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Add lesson_completed Column</a></p>";
        }
        echo "</div>";
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p>⚠️ Database connection issue: " . $e->getMessage() . "</p>";
}

echo "<h2>🎯 Test the Fix</h2>";
echo "<p>Now test the teacher students page:</p>";
echo "<p><a href='teacher/students.php' target='_blank' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🧪 Test Teacher Students Page</a></p>";

echo "<h2>🔄 Restore if Needed</h2>";
echo "<p>If the fix doesn't work, you can restore the original file:</p>";
echo "<p><a href='restore_teacher_students.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Restore Original</a></p>";

// Create restore script
$restore_script = '<?php
$file_path = "teacher/students.php";
$backup_path = $file_path . ".backup";

if (file_exists($backup_path)) {
    if (copy($backup_path, $file_path)) {
        echo "<h1>✅ File Restored</h1>";
        echo "<p>teacher/students.php has been restored from backup.</p>";
        unlink($backup_path);
    } else {
        echo "<h1>❌ Restore Failed</h1>";
    }
} else {
    echo "<h1>❌ Backup Not Found</h1>";
}
echo "<p><a href=\"teacher/students.php\">Test Page</a></p>";
?>';

file_put_contents('restore_teacher_students.php', $restore_script);

echo "<hr>";
echo "<p><strong>Summary:</strong> The error means your SQL query is looking for a column 'lesson_completed' that doesn't exist in your database table. The fix involves either changing the query or adding the missing column.</p>";
?>