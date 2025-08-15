<?php
// Debug Specific Page Errors - Shows actual PHP errors instead of generic messages
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 Debug Specific Page Errors</h1>";
echo "<p><strong>This tool will show you the ACTUAL errors happening in each page</strong></p>";

// Database connection
try {
    require_once 'config/database.php';
    echo "<p>✅ Database connected</p>";
} catch (Exception $e) {
    die("<p>❌ Database failed: " . $e->getMessage() . "</p>");
}

// Start session and create test admin session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Create test session for debugging
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['name'] = 'Debug Admin';
    $_SESSION['email'] = 'debug@test.com';
    echo "<p>🔧 Created debug session</p>";
}

echo "<h2>📋 Testing Individual Pages</h2>";

// Function to safely test a page
function testPage($page_path, $page_name) {
    echo "<h3>Testing: $page_name</h3>";
    echo "<div style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; border-radius: 5px; margin: 10px 0;'>";
    
    // Start output buffering to catch any output
    ob_start();
    $error_occurred = false;
    
    try {
        // Try to include the page
        if (file_exists($page_path)) {
            echo "<p>📁 File exists: $page_path</p>";
            
            // Capture any errors
            set_error_handler(function($severity, $message, $file, $line) use (&$error_occurred) {
                $error_occurred = true;
                echo "<p>❌ <strong>PHP Error:</strong> $message in $file on line $line</p>";
            });
            
            // Try to include the file
            include_once $page_path;
            
            if (!$error_occurred) {
                echo "<p>✅ Page loaded without PHP errors</p>";
            }
            
            restore_error_handler();
            
        } else {
            echo "<p>❌ File not found: $page_path</p>";
        }
        
    } catch (Throwable $e) {
        echo "<p>❌ <strong>Exception:</strong> " . $e->getMessage() . "</p>";
        echo "<p><strong>File:</strong> " . $e->getFile() . " <strong>Line:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // Get any output and discard it
    $output = ob_get_clean();
    
    echo "</div>";
}

// Test problematic pages
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

foreach ($pages_to_test as $page_path => $page_name) {
    testPage($page_path, $page_name);
}

echo "<h2>🔧 Quick Individual Page Tests</h2>";

// Test specific functionality
echo "<h3>Testing Admin Announcements Functionality:</h3>";
try {
    // Test the exact query from admin announcements
    $sql = "SELECT a.*, u.name as created_by_name FROM announcements a LEFT JOIN users u ON a.created_by = u.id ORDER BY a.created_at DESC";
    $result = $conn->query($sql);
    if ($result) {
        echo "<p>✅ Admin announcements query works (" . $result->num_rows . " rows)</p>";
    } else {
        echo "<p>❌ Admin announcements query failed: " . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<p>❌ Admin announcements error: " . $e->getMessage() . "</p>";
}

echo "<h3>Testing Student Classes Functionality:</h3>";
try {
    // Test student classes query
    $student_id = 1; // Use a test student ID
    $sql = "SELECT cl.*, c.title as course_title FROM classes cl JOIN courses c ON cl.course_id = c.id JOIN enrollments e ON c.id = e.course_id WHERE e.student_id = ? ORDER BY cl.class_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    echo "<p>✅ Student classes query works (" . $result->num_rows . " rows)</p>";
} catch (Exception $e) {
    echo "<p>❌ Student classes error: " . $e->getMessage() . "</p>";
}

echo "<h3>Testing Functions:</h3>";
try {
    require_once 'includes/functions.php';
    echo "<p>✅ Functions file loaded</p>";
    
    // Test specific functions
    if (function_exists('requireAdmin')) {
        echo "<p>✅ requireAdmin function exists</p>";
    } else {
        echo "<p>❌ requireAdmin function missing</p>";
    }
    
    if (function_exists('sanitizeInput')) {
        echo "<p>✅ sanitizeInput function exists</p>";
    } else {
        echo "<p>❌ sanitizeInput function missing</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Functions error: " . $e->getMessage() . "</p>";
}

echo "<h2>📊 Database Table Contents Check</h2>";

$tables_to_check = ['announcements', 'classes', 'certificates', 'grades', 'users', 'courses', 'enrollments', 'assignments'];

foreach ($tables_to_check as $table) {
    try {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "<p>✅ $table: $count records</p>";
        } else {
            echo "<p>❌ $table: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ $table error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>🎯 Error Log Analysis</h2>";

// Try to read error logs if available
$error_log_paths = [
    'error.log',
    '../error.log',
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log'
];

foreach ($error_log_paths as $log_path) {
    if (file_exists($log_path) && is_readable($log_path)) {
        echo "<h3>Error log: $log_path</h3>";
        $log_content = file_get_contents($log_path);
        $recent_errors = array_slice(explode("\n", $log_content), -20); // Last 20 lines
        echo "<pre style='background: #f8f9fa; padding: 10px; border: 1px solid #dee2e6; max-height: 200px; overflow-y: auto;'>";
        echo htmlspecialchars(implode("\n", $recent_errors));
        echo "</pre>";
        break;
    }
}

echo "<h2>🔧 Recommended Actions</h2>";
echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px;'>";
echo "<p>Based on the tests above, here's what to do next:</p>";
echo "<ol>";
echo "<li>Check the specific error messages shown for each page</li>";
echo "<li>If any tables show 0 records, add some test data</li>";
echo "<li>If specific functions are missing, they need to be added</li>";
echo "<li>Check your web server error logs for more details</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='add_test_data.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Add Test Data</a></p>";

$conn->close();
?>