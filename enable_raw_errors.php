<?php
// Enable Raw PHP Errors - Temporarily disable custom error handling
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔧 Enable Raw PHP Errors</h1>";
echo "<p>This will temporarily modify your error display to show raw PHP errors instead of generic messages.</p>";

// Read the current error_display.php file
$error_file = 'config/error_display.php';

if (file_exists($error_file)) {
    $content = file_get_contents($error_file);
    
    // Create a backup
    file_put_contents($error_file . '.backup', $content);
    echo "<p>✅ Created backup: {$error_file}.backup</p>";
    
    // Modify the content to disable custom error handlers
    $modified_content = $content;
    
    // Comment out the custom error handler
    $modified_content = str_replace(
        'set_error_handler(\'customErrorHandler\');',
        '// set_error_handler(\'customErrorHandler\'); // Temporarily disabled',
        $modified_content
    );
    
    // Comment out the custom exception handler  
    $modified_content = str_replace(
        'set_exception_handler(\'customExceptionHandler\');',
        '// set_exception_handler(\'customExceptionHandler\'); // Temporarily disabled',
        $modified_content
    );
    
    // Force display errors on
    $modified_content = str_replace(
        'ini_set(\'display_errors\', 0);',
        'ini_set(\'display_errors\', 1); // Temporarily enabled for debugging',
        $modified_content
    );
    
    $modified_content = str_replace(
        'ini_set(\'display_startup_errors\', 0);',
        'ini_set(\'display_startup_errors\', 1); // Temporarily enabled for debugging',
        $modified_content
    );
    
    // Write the modified content
    file_put_contents($error_file, $modified_content);
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>✅ Raw Error Mode Enabled!</h3>";
    echo "<p>Your pages will now show detailed PHP errors instead of generic messages.</p>";
    echo "<ul>";
    echo "<li>Disabled custom error handler</li>";
    echo "<li>Disabled custom exception handler</li>";
    echo "<li>Enabled display_errors</li>";
    echo "<li>Enabled display_startup_errors</li>";
    echo "</ul>";
    echo "</div>";
    
} else {
    echo "<p style='color: red;'>❌ Error: {$error_file} not found</p>";
}

echo "<h2>🎯 Test Your Pages Now</h2>";
echo "<p>Visit your problematic pages now and you should see detailed error messages instead of 'An error occurred':</p>";

$test_links = [
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

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; margin: 20px 0;'>";
foreach ($test_links as $url => $name) {
    echo "<a href='$url' target='_blank' style='background: #dc3545; color: white; padding: 10px; text-decoration: none; border-radius: 5px; text-align: center; display: block;'>$name</a>";
}
echo "</div>";

echo "<h2>⚠️ Important Notes</h2>";
echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
echo "<ul>";
echo "<li><strong>This is for debugging only</strong> - you'll see raw PHP errors</li>";
echo "<li><strong>Copy the error messages</strong> and share them for specific fixes</li>";
echo "<li><strong>Restore normal mode</strong> when done debugging</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🔄 Restore Normal Mode</h2>";
echo "<p>When you're done debugging, click this button to restore normal error handling:</p>";
echo "<a href='restore_normal_errors.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🔄 Restore Normal Error Mode</a>";

// Create the restore script
$restore_script = '<?php
// Restore Normal Error Mode
$error_file = "config/error_display.php";
$backup_file = $error_file . ".backup";

if (file_exists($backup_file)) {
    $backup_content = file_get_contents($backup_file);
    file_put_contents($error_file, $backup_content);
    unlink($backup_file);
    
    echo "<h1>✅ Normal Error Mode Restored</h1>";
    echo "<p>Custom error handling has been restored.</p>";
    echo "<p><a href=\\"admin/dashboard.php\\">Back to Dashboard</a></p>";
} else {
    echo "<h1>❌ Backup not found</h1>";
    echo "<p>Could not restore normal error mode.</p>";
}
?>';

file_put_contents('restore_normal_errors.php', $restore_script);
echo "<p>✅ Created restore script</p>";
?>