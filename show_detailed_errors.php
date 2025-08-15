<?php
// Detailed Error Display Tool - Shows EXACT errors in each page
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 Detailed Error Display Tool</h1>";
echo "<p><strong>This will show you the EXACT errors happening in each page</strong></p>";

// Function to safely test a page and show detailed errors
function testPageWithDetails($page_path, $page_name) {
    echo "<div style='border: 2px solid #007bff; margin: 20px 0; padding: 15px; border-radius: 8px;'>";
    echo "<h2>🧪 Testing: $page_name</h2>";
    echo "<p><strong>File:</strong> $page_path</p>";
    
    if (!file_exists($page_path)) {
        echo "<p style='color: red;'>❌ File not found: $page_path</p>";
        echo "</div>";
        return;
    }
    
    echo "<p>✅ File exists</p>";
    
    // Start output buffering
    ob_start();
    
    // Custom error handler to capture all errors
    $errors = [];
    set_error_handler(function($severity, $message, $file, $line) use (&$errors) {
        $errors[] = [
            'type' => 'Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line
        ];
        return true; // Don't execute the internal error handler
    });
    
    // Custom exception handler
    set_exception_handler(function($exception) use (&$errors) {
        $errors[] = [
            'type' => 'Exception',
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
    });
    
    try {
        // Try to include the page
        include $page_path;
        
    } catch (Throwable $e) {
        $errors[] = [
            'type' => 'Fatal Error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    // Get any output
    $output = ob_get_clean();
    
    // Restore default handlers
    restore_error_handler();
    restore_exception_handler();
    
    // Display results
    if (empty($errors)) {
        echo "<p style='color: green;'>✅ <strong>Page loaded successfully with no errors!</strong></p>";
        if (!empty($output)) {
            echo "<p>📄 Page produced output (likely working correctly)</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ <strong>Found " . count($errors) . " error(s):</strong></p>";
        
        foreach ($errors as $i => $error) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>Error #" . ($i + 1) . " - {$error['type']}</h4>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($error['message']) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($error['file']) . "</p>";
            echo "<p><strong>Line:</strong> " . $error['line'] . "</p>";
            
            if (isset($error['trace'])) {
                echo "<details><summary>📋 Stack Trace</summary>";
                echo "<pre style='background: #f1f1f1; padding: 10px; border-radius: 3px; overflow-x: auto;'>";
                echo htmlspecialchars($error['trace']);
                echo "</pre></details>";
            }
            echo "</div>";
        }
    }
    
    echo "</div>";
    return $errors;
}

// Database connection test
echo "<h2>💾 Database Connection Test</h2>";
try {
    require_once 'config/database.php';
    echo "<p>✅ Database connected successfully</p>";
    echo "<p><strong>Database Info:</strong> " . $conn->get_server_info() . "</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

// Session setup test
echo "<h2>🔐 Session Test</h2>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Set up test session if none exists
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['name'] = 'Test Admin';
    $_SESSION['email'] = 'admin@test.com';
    echo "<p>⚙️ Created test session for debugging</p>";
} else {
    echo "<p>✅ Session exists: User ID " . $_SESSION['user_id'] . " (" . $_SESSION['user_type'] . ")</p>";
}

echo "<h2>📋 Testing Individual Pages</h2>";
echo "<p><strong>This will show the exact error for each problematic page:</strong></p>";

// Test the problematic pages
$pages_to_test = [
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

$all_errors = [];
foreach ($pages_to_test as $page_path => $page_name) {
    $page_errors = testPageWithDetails($page_path, $page_name);
    if (!empty($page_errors)) {
        $all_errors[$page_name] = $page_errors;
    }
}

echo "<h2>📊 Error Summary</h2>";

if (empty($all_errors)) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<h3>🎉 All pages loaded successfully!</h3>";
    echo "<p>No errors found in any of the tested pages.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>⚠️ Found errors in " . count($all_errors) . " page(s)</h3>";
    echo "<ul>";
    foreach ($all_errors as $page_name => $errors) {
        echo "<li><strong>$page_name:</strong> " . count($errors) . " error(s)</li>";
    }
    echo "</ul>";
    echo "</div>";
}

echo "<h2>🔧 Quick Database Table Check</h2>";

// Quick check of tables that commonly cause issues
$critical_tables = ['announcements', 'student_progress', 'quiz_attempts', 'announcement_reads', 'classes', 'certificates', 'grades'];

foreach ($critical_tables as $table) {
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "<p>✅ $table: $count records</p>";
        } else {
            echo "<p style='color: red;'>❌ $table: Query failed - " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ $table: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>💡 Next Steps</h2>";
echo "<div style='background: #cce5ff; color: #004085; padding: 15px; border-radius: 5px;'>";
echo "<ol>";
echo "<li><strong>Look at the detailed errors above</strong> - they show exactly what's wrong</li>";
echo "<li><strong>Missing tables?</strong> Run the table creation scripts</li>";
echo "<li><strong>Missing functions?</strong> Check if includes are working</li>";
echo "<li><strong>SQL errors?</strong> The exact query and error will be shown</li>";
echo "<li><strong>Still showing generic errors?</strong> The issue is now visible above</li>";
echo "</ol>";
echo "</div>";

if (isset($conn)) {
    $conn->close();
}
?>