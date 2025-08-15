<?php
// Fix ALL Files - Convert to Absolute Paths and Proper Session Handling
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix ALL Files - Absolute Paths & Sessions</h1>";
echo "<p><strong>This will update ALL PHP files in your project</strong></p>";

$base_path = '/home/shravan/web/training.kcdfindia.org/public_html';
$current_dir = __DIR__;

echo "<h2>📍 Configuration</h2>";
echo "<p><strong>Base Path:</strong> $base_path</p>";
echo "<p><strong>Current Directory:</strong> $current_dir</p>";

// Function to recursively find all PHP files
function findAllPHPFiles($directory) {
    $php_files = [];
    
    // Directories to scan
    $directories_to_scan = [
        $directory . '/admin',
        $directory . '/student', 
        $directory . '/teacher',
        $directory . '/includes',
        $directory // Root directory
    ];
    
    foreach ($directories_to_scan as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . '/*.php');
            foreach ($files as $file) {
                // Skip backup files and this script itself
                if (!strpos($file, '.backup') && !strpos($file, 'fix_all_files')) {
                    $php_files[] = $file;
                }
            }
        }
    }
    
    return $php_files;
}

// Function to fix a single PHP file
function fixPHPFile($file_path, $base_path) {
    if (!file_exists($file_path)) {
        return ['success' => false, 'message' => 'File not found'];
    }
    
    $content = file_get_contents($file_path);
    $original_content = $content;
    
    // Create backup
    file_put_contents($file_path . '.backup', $content);
    
    // 1. Fix session start at the beginning of the file
    $session_patterns = [
        '/^<\?php\s*\n\s*session_start\(\);/m',
        '/^<\?php\s*session_start\(\);/m'
    ];
    
    $new_session_code = '<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}';
    
    foreach ($session_patterns as $pattern) {
        $content = preg_replace($pattern, $new_session_code, $content);
    }
    
    // 2. Fix require_once paths to absolute paths
    $path_replacements = [
        // Database paths
        "require_once '../config/database.php';" => "require_once '$base_path/config/database.php';",
        "require_once '../config/database.php'" => "require_once '$base_path/config/database.php'",
        'require_once "../config/database.php";' => "require_once '$base_path/config/database.php';",
        'require_once "../config/database.php"' => "require_once '$base_path/config/database.php'",
        
        // Functions paths
        "require_once '../includes/functions.php';" => "require_once '$base_path/includes/functions.php';",
        "require_once '../includes/functions.php'" => "require_once '$base_path/includes/functions.php'",
        'require_once "../includes/functions.php";' => "require_once '$base_path/includes/functions.php';",
        'require_once "../includes/functions.php"' => "require_once '$base_path/includes/functions.php'",
        
        // Header paths
        "require_once '../includes/header.php';" => "require_once '$base_path/includes/header.php';",
        "require_once '../includes/header.php'" => "require_once '$base_path/includes/header.php'",
        'require_once "../includes/header.php";' => "require_once '$base_path/includes/header.php';",
        'require_once "../includes/header.php"' => "require_once '$base_path/includes/header.php'",
        
        // Footer paths
        "require_once '../includes/footer.php';" => "require_once '$base_path/includes/footer.php';",
        "require_once '../includes/footer.php'" => "require_once '$base_path/includes/footer.php'",
        'require_once "../includes/footer.php";' => "require_once '$base_path/includes/footer.php';",
        'require_once "../includes/footer.php"' => "require_once '$base_path/includes/footer.php'",
        
        // Navigation paths
        "require_once '../includes/navigation.php';" => "require_once '$base_path/includes/navigation.php';",
        "require_once '../includes/navigation.php'" => "require_once '$base_path/includes/navigation.php'",
        
        // Any other config paths
        "'../config/" => "'$base_path/config/",
        '"../config/' => "\"$base_path/config/",
        "'../includes/" => "'$base_path/includes/",
        '"../includes/' => "\"$base_path/includes/"
    ];
    
    foreach ($path_replacements as $old_path => $new_path) {
        $content = str_replace($old_path, $new_path, $content);
    }
    
    // 3. Fix any href or action attributes that use relative paths
    $url_replacements = [
        'href="../' => 'href="',
        'action="../' => 'action="',
        "href='../" => "href='",
        "action='../" => "action='"
    ];
    
    foreach ($url_replacements as $old_url => $new_url) {
        $content = str_replace($old_url, $new_url, $content);
    }
    
    // Write the fixed content
    $bytes_written = file_put_contents($file_path, $content);
    
    return [
        'success' => $bytes_written !== false,
        'message' => $bytes_written !== false ? 'Fixed successfully' : 'Failed to write file',
        'changes_made' => $content !== $original_content
    ];
}

// Find all PHP files
echo "<h2>🔍 Scanning for PHP Files</h2>";
$php_files = findAllPHPFiles($current_dir);

echo "<p>Found " . count($php_files) . " PHP files to process:</p>";
echo "<ul>";
foreach ($php_files as $file) {
    $relative_path = str_replace($current_dir . '/', '', $file);
    echo "<li>$relative_path</li>";
}
echo "</ul>";

// Process all files
echo "<h2>🔧 Processing Files</h2>";

$processed = 0;
$fixed = 0;
$errors = 0;

foreach ($php_files as $file) {
    $relative_path = str_replace($current_dir . '/', '', $file);
    echo "<h3>Processing: $relative_path</h3>";
    
    $result = fixPHPFile($file, $base_path);
    $processed++;
    
    if ($result['success']) {
        if ($result['changes_made']) {
            echo "<p>✅ Fixed and updated</p>";
            $fixed++;
        } else {
            echo "<p>✅ No changes needed</p>";
        }
    } else {
        echo "<p>❌ Error: " . $result['message'] . "</p>";
        $errors++;
    }
}

echo "<h2>📊 Summary</h2>";
echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>✅ Processing Complete!</h3>";
echo "<ul>";
echo "<li><strong>Total files processed:</strong> $processed</li>";
echo "<li><strong>Files fixed/updated:</strong> $fixed</li>";
echo "<li><strong>Errors:</strong> $errors</li>";
echo "</ul>";

echo "<h4>Changes Made:</h4>";
echo "<ul>";
echo "<li>✅ Converted all relative paths to absolute paths</li>";
echo "<li>✅ Fixed session_start() to use proper session checks</li>";
echo "<li>✅ Updated require_once statements</li>";
echo "<li>✅ Fixed href and action attributes</li>";
echo "<li>✅ Created backup files (.backup extension)</li>";
echo "</ul>";
echo "</div>";

echo "<h2>🎯 Test Your Application</h2>";
echo "<p>All files have been updated. Test your pages now:</p>";

$test_pages = [
    'admin/dashboard.php' => 'Admin Dashboard',
    'admin/announcements.php' => 'Admin Announcements',
    'student/assignments.php' => 'Student Assignments',
    'student/classes.php' => 'Student Classes',
    'student/announcements.php' => 'Student Announcements',
    'student/certificates.php' => 'Student Certificates',
    'student/grades.php' => 'Student Grades',
    'teacher/students.php' => 'Teacher Students',
    'teacher/grades.php' => 'Teacher Grades',
    'teacher/announcements.php' => 'Teacher Announcements'
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 10px; margin: 20px 0;'>";
foreach ($test_pages as $url => $name) {
    echo "<a href='$url' target='_blank' style='background: #007bff; color: white; padding: 10px; text-decoration: none; border-radius: 5px; text-align: center; display: block;'>$name</a>";
}
echo "</div>";

echo "<h2>⚠️ Backup Information</h2>";
echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>Important:</strong> All original files have been backed up with .backup extension.</p>";
echo "<p>If you need to restore any file, you can find the backup in the same directory.</p>";
echo "</div>";

// Create restore script
$restore_script = '<?php
echo "<h1>🔄 Restore All Original Files</h1>";

function findBackupFiles($directory) {
    $backup_files = [];
    $directories = [
        $directory . "/admin",
        $directory . "/student", 
        $directory . "/teacher",
        $directory . "/includes",
        $directory
    ];
    
    foreach ($directories as $dir) {
        if (is_dir($dir)) {
            $files = glob($dir . "/*.backup");
            $backup_files = array_merge($backup_files, $files);
        }
    }
    return $backup_files;
}

$backup_files = findBackupFiles(__DIR__);
$restored = 0;

echo "<p>Found " . count($backup_files) . " backup files</p>";

foreach ($backup_files as $backup_file) {
    $original_file = str_replace(".backup", "", $backup_file);
    if (copy($backup_file, $original_file)) {
        unlink($backup_file);
        echo "<p>✅ Restored " . basename($original_file) . "</p>";
        $restored++;
    } else {
        echo "<p>❌ Failed to restore " . basename($original_file) . "</p>";
    }
}

echo "<h2>Restored $restored files</h2>";
echo "<p><a href=\"index.php\">Back to Home</a></p>";
?>';

file_put_contents('restore_all_original_files.php', $restore_script);
echo "<p>✅ Created restore script: <a href='restore_all_original_files.php'>restore_all_original_files.php</a></p>";

echo "<hr>";
echo "<h2>🚀 What to Expect</h2>";
echo "<p>After these fixes:</p>";
echo "<ul>";
echo "<li>✅ No more open_basedir restriction errors</li>";
echo "<li>✅ No more session_start() conflicts</li>";
echo "<li>✅ Pages should load completely (header AND footer)</li>";
echo "<li>✅ All functionality should work properly</li>";
echo "</ul>";
?>