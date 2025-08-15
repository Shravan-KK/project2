<?php
// Fix Open_basedir and Session Issues
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Path and Session Issues</h1>";
echo "<p><strong>Fixing open_basedir restrictions and session conflicts</strong></p>";

// Get the document root path
$document_root = $_SERVER['DOCUMENT_ROOT'];
$current_path = __DIR__;

echo "<h2>📍 Path Information</h2>";
echo "<p><strong>Document Root:</strong> $document_root</p>";
echo "<p><strong>Current Path:</strong> $current_path</p>";

// Define the correct absolute paths
$config_path = $current_path . '/config/database.php';
$functions_path = $current_path . '/includes/functions.php';
$header_path = $current_path . '/includes/header.php';

echo "<p><strong>Config path:</strong> $config_path</p>";
echo "<p><strong>Functions path:</strong> $functions_path</p>";
echo "<p><strong>Header path:</strong> $header_path</p>";

// Check if files exist
echo "<h2>📁 File Existence Check</h2>";
echo "<p>Config file: " . (file_exists($config_path) ? "✅ Exists" : "❌ Missing") . "</p>";
echo "<p>Functions file: " . (file_exists($functions_path) ? "✅ Exists" : "❌ Missing") . "</p>";
echo "<p>Header file: " . (file_exists($header_path) ? "✅ Exists" : "❌ Missing") . "</p>";

// Function to fix a PHP file
function fixPagePaths($file_path, $file_name) {
    global $current_path;
    
    echo "<h3>Fixing: $file_name</h3>";
    
    if (!file_exists($file_path)) {
        echo "<p>❌ File not found: $file_path</p>";
        return false;
    }
    
    $content = file_get_contents($file_path);
    
    // Create backup
    file_put_contents($file_path . '.backup', $content);
    
    // Fix the paths and session issues
    $fixed_content = $content;
    
    // Fix session_start issue
    $fixed_content = str_replace(
        '<?php' . "\n" . 'session_start();',
        '<?php' . "\n" . 'if (session_status() == PHP_SESSION_NONE) {' . "\n" . '    session_start();' . "\n" . '}',
        $fixed_content
    );
    
    // Fix relative paths to absolute paths
    $fixed_content = str_replace("require_once '../config/database.php';", "require_once '$current_path/config/database.php';", $fixed_content);
    $fixed_content = str_replace("require_once '../includes/functions.php';", "require_once '$current_path/includes/functions.php';", $fixed_content);
    $fixed_content = str_replace("require_once '../includes/header.php';", "require_once '$current_path/includes/header.php';", $fixed_content);
    $fixed_content = str_replace("require_once '../includes/footer.php';", "require_once '$current_path/includes/footer.php';", $fixed_content);
    
    // Also fix any other common relative paths
    $fixed_content = str_replace("'../config/", "'$current_path/config/", $fixed_content);
    $fixed_content = str_replace("'../includes/", "'$current_path/includes/", $fixed_content);
    
    // Write the fixed content
    if (file_put_contents($file_path, $fixed_content)) {
        echo "<p>✅ Fixed $file_name</p>";
        return true;
    } else {
        echo "<p>❌ Failed to write $file_name</p>";
        return false;
    }
}

echo "<h2>🔧 Fixing All Problematic Pages</h2>";

// List of files to fix
$files_to_fix = [
    'admin/announcements.php' => 'Admin Announcements',
    'student/classes.php' => 'Student Classes',
    'student/assignments.php' => 'Student Assignments',
    'student/announcements.php' => 'Student Announcements',
    'student/certificates.php' => 'Student Certificates',
    'student/grades.php' => 'Student Grades',
    'teacher/students.php' => 'Teacher Students',
    'teacher/grades.php' => 'Teacher Grades',
    'teacher/announcements.php' => 'Teacher Announcements'
];

$fixed_count = 0;
foreach ($files_to_fix as $file_path => $file_name) {
    if (fixPagePaths($file_path, $file_name)) {
        $fixed_count++;
    }
}

echo "<h2>📊 Fix Summary</h2>";
echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>✅ Fixed $fixed_count out of " . count($files_to_fix) . " files</h3>";
echo "<ul>";
echo "<li>Converted relative paths to absolute paths</li>";
echo "<li>Fixed session_start() conflicts</li>";
echo "<li>Created backup files (.backup extension)</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🎯 Test Your Pages Now</h2>";
echo "<p>The pages should now work without the open_basedir and session errors:</p>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; margin: 20px 0;'>";
foreach ($files_to_fix as $url => $name) {
    echo "<a href='$url' target='_blank' style='background: #28a745; color: white; padding: 10px; text-decoration: none; border-radius: 5px; text-align: center; display: block;'>$name</a>";
}
echo "</div>";

echo "<h2>🔄 If Issues Persist</h2>";
echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
echo "<h4>If you still see errors:</h4>";
echo "<ol>";
echo "<li>Run the detailed error tool again to see new error messages</li>";
echo "<li>Check if config/database.php file exists and is accessible</li>";
echo "<li>Verify your hosting provider's file permissions</li>";
echo "</ol>";
echo "</div>";

echo "<h2>⚠️ Backup Information</h2>";
echo "<p>All original files have been backed up with .backup extension. If needed, you can restore them.</p>";

// Create a restore script
$restore_script = '<?php
echo "<h1>🔄 Restore Original Files</h1>";

$files_to_restore = [
    "admin/announcements.php",
    "student/classes.php", 
    "student/assignments.php",
    "student/announcements.php",
    "student/certificates.php",
    "student/grades.php",
    "teacher/students.php",
    "teacher/grades.php",
    "teacher/announcements.php"
];

$restored = 0;
foreach ($files_to_restore as $file) {
    $backup_file = $file . ".backup";
    if (file_exists($backup_file)) {
        if (copy($backup_file, $file)) {
            echo "<p>✅ Restored $file</p>";
            unlink($backup_file);
            $restored++;
        } else {
            echo "<p>❌ Failed to restore $file</p>";
        }
    } else {
        echo "<p>⚠️ No backup found for $file</p>";
    }
}

echo "<h2>Restored $restored files</h2>";
echo "<p><a href=\"admin/dashboard.php\">Back to Dashboard</a></p>";
?>';

file_put_contents('restore_original_files.php', $restore_script);
echo "<p>✅ Created restore script: <a href='restore_original_files.php'>restore_original_files.php</a></p>";
?>