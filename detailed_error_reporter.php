<?php
// Comprehensive Detailed Error Reporter
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);

echo "<h1>🔍 Comprehensive Error Reporter</h1>";
echo "<p><strong>Real-time error detection and detailed reporting</strong></p>";
echo "<hr>";

// CSS for better formatting
echo "<style>
.error-box { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb; }
.success-box { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb; }
.warning-box { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ffeaa7; }
.info-box { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #b3d9ff; }
.code-block { background: #f8f9fa; padding: 10px; border-radius: 3px; font-family: monospace; margin: 5px 0; overflow-x: auto; }
.test-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 20px 0; }
.test-card { border: 1px solid #ddd; border-radius: 8px; padding: 15px; }
</style>";

// Function to safely test a page and capture detailed errors
function testPageDetailed($file_path, $page_name) {
    global $conn;
    
    echo "<div class='test-card'>";
    echo "<h3>🧪 $page_name</h3>";
    echo "<p><strong>File:</strong> <code>$file_path</code></p>";
    
    if (!file_exists($file_path)) {
        echo "<div class='error-box'>❌ <strong>File Not Found</strong><br>Path: $file_path</div>";
        echo "</div>";
        return ['status' => 'missing', 'errors' => ['File not found']];
    }
    
    // Capture all errors and output
    $errors = [];
    $warnings = [];
    $notices = [];
    $fatal_errors = [];
    
    // Custom error handler
    set_error_handler(function($severity, $message, $file, $line) use (&$errors, &$warnings, &$notices) {
        $error_info = [
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'type' => ''
        ];
        
        switch ($severity) {
            case E_ERROR:
            case E_CORE_ERROR:
            case E_COMPILE_ERROR:
            case E_USER_ERROR:
                $error_info['type'] = 'Fatal Error';
                $errors[] = $error_info;
                break;
            case E_WARNING:
            case E_CORE_WARNING:
            case E_COMPILE_WARNING:
            case E_USER_WARNING:
                $error_info['type'] = 'Warning';
                $warnings[] = $error_info;
                break;
            case E_NOTICE:
            case E_USER_NOTICE:
                $error_info['type'] = 'Notice';
                $notices[] = $error_info;
                break;
        }
        return true;
    });
    
    // Start output buffering
    ob_start();
    
    try {
        // Include the file to test it
        include $file_path;
    } catch (ParseError $e) {
        $fatal_errors[] = [
            'type' => 'Parse Error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    } catch (Error $e) {
        $fatal_errors[] = [
            'type' => 'Fatal Error',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    } catch (Exception $e) {
        $fatal_errors[] = [
            'type' => 'Exception',
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ];
    }
    
    // Get output
    $output = ob_get_clean();
    
    // Restore error handler
    restore_error_handler();
    
    // Display results
    $total_issues = count($errors) + count($warnings) + count($notices) + count($fatal_errors);
    
    if ($total_issues == 0) {
        echo "<div class='success-box'>✅ <strong>No errors detected!</strong></div>";
        if (!empty($output)) {
            echo "<p>📄 Page produced output (" . strlen($output) . " bytes)</p>";
        }
        $status = 'success';
    } else {
        echo "<div class='error-box'>❌ <strong>Found $total_issues issue(s)</strong></div>";
        $status = 'error';
        
        // Display fatal errors
        foreach ($fatal_errors as $i => $error) {
            echo "<div class='error-box'>";
            echo "<h4>🚫 {$error['type']} #" . ($i + 1) . "</h4>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($error['message']) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($error['file']) . "</p>";
            echo "<p><strong>Line:</strong> {$error['line']}</p>";
            if (isset($error['trace'])) {
                echo "<details><summary>📋 Stack Trace</summary>";
                echo "<div class='code-block'>" . htmlspecialchars($error['trace']) . "</div>";
                echo "</details>";
            }
            echo "</div>";
        }
        
        // Display other errors
        foreach ($errors as $i => $error) {
            echo "<div class='error-box'>";
            echo "<h4>❌ {$error['type']} #" . ($i + 1) . "</h4>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($error['message']) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($error['file']) . "</p>";
            echo "<p><strong>Line:</strong> {$error['line']}</p>";
            echo "</div>";
        }
        
        // Display warnings
        foreach ($warnings as $i => $warning) {
            echo "<div class='warning-box'>";
            echo "<h4>⚠️ {$warning['type']} #" . ($i + 1) . "</h4>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($warning['message']) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($warning['file']) . "</p>";
            echo "<p><strong>Line:</strong> {$warning['line']}</p>";
            echo "</div>";
        }
        
        // Display notices (only first 3 to avoid spam)
        $notice_count = count($notices);
        foreach (array_slice($notices, 0, 3) as $i => $notice) {
            echo "<div class='info-box'>";
            echo "<h4>ℹ️ {$notice['type']} #" . ($i + 1) . "</h4>";
            echo "<p><strong>Message:</strong> " . htmlspecialchars($notice['message']) . "</p>";
            echo "<p><strong>File:</strong> " . htmlspecialchars($notice['file']) . "</p>";
            echo "<p><strong>Line:</strong> {$notice['line']}</p>";
            echo "</div>";
        }
        
        if ($notice_count > 3) {
            echo "<div class='info-box'>... and " . ($notice_count - 3) . " more notices</div>";
        }
    }
    
    echo "</div>";
    
    return [
        'status' => $status,
        'errors' => $fatal_errors,
        'warnings' => $warnings,
        'notices' => $notices,
        'total_issues' => $total_issues
    ];
}

// Database connection test
echo "<h2>💾 Database Connection Test</h2>";
try {
    require_once '/home/shravan/web/training.kcdfindia.org/public_html/config/database.php';
    if (isset($conn) && $conn instanceof mysqli) {
        echo "<div class='success-box'>✅ Database connected successfully<br>";
        echo "<strong>Server:</strong> " . $conn->server_info . "<br>";
        echo "<strong>Host:</strong> " . $conn->host_info . "</div>";
    } else {
        echo "<div class='error-box'>❌ Database connection object not found or invalid</div>";
    }
} catch (Exception $e) {
    echo "<div class='error-box'>❌ Database connection failed: " . htmlspecialchars($e->getMessage()) . "</div>";
}

// Session test
echo "<h2>🔐 Session Status</h2>";
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    echo "<div class='info-box'>🔄 Started new session</div>";
} else {
    echo "<div class='success-box'>✅ Session already active</div>";
}

// Set up test session if none exists
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['user_type'] = 'admin';
    $_SESSION['name'] = 'Test Admin';
    $_SESSION['email'] = 'admin@test.com';
    echo "<div class='info-box'>🔧 Created test session for debugging</div>";
} else {
    echo "<div class='success-box'>✅ User session exists: " . $_SESSION['name'] . " (" . $_SESSION['user_type'] . ")</div>";
}

// Test all problematic pages
echo "<h2>📋 Page-by-Page Error Testing</h2>";

$pages_to_test = [
    'admin/announcements.php' => 'Admin Announcements',
    'admin/dashboard.php' => 'Admin Dashboard',
    'student/classes.php' => 'Student Classes',
    'student/assignments.php' => 'Student Assignments',
    'student/announcements.php' => 'Student Announcements',
    'student/certificates.php' => 'Student Certificates',
    'student/grades.php' => 'Student Grades',
    'teacher/students.php' => 'Teacher Students',
    'teacher/grades.php' => 'Teacher Grades',
    'teacher/announcements.php' => 'Teacher Announcements'
];

echo "<div class='test-grid'>";
$results = [];
foreach ($pages_to_test as $file_path => $page_name) {
    $results[$page_name] = testPageDetailed($file_path, $page_name);
}
echo "</div>";

// Overall summary
echo "<h2>📊 Overall Error Summary</h2>";

$total_pages = count($results);
$successful_pages = array_filter($results, function($result) { return $result['status'] == 'success'; });
$error_pages = array_filter($results, function($result) { return $result['status'] == 'error'; });
$missing_pages = array_filter($results, function($result) { return $result['status'] == 'missing'; });

echo "<div class='info-box'>";
echo "<h3>📈 Statistics</h3>";
echo "<ul>";
echo "<li><strong>Total Pages Tested:</strong> $total_pages</li>";
echo "<li><strong>✅ Successful:</strong> " . count($successful_pages) . "</li>";
echo "<li><strong>❌ With Errors:</strong> " . count($error_pages) . "</li>";
echo "<li><strong>📁 Missing Files:</strong> " . count($missing_pages) . "</li>";
echo "</ul>";
echo "</div>";

if (count($error_pages) > 0) {
    echo "<div class='error-box'>";
    echo "<h3>🚨 Pages with Errors:</h3>";
    echo "<ul>";
    foreach ($error_pages as $page => $result) {
        echo "<li><strong>$page:</strong> {$result['total_issues']} issue(s)</li>";
    }
    echo "</ul>";
    echo "</div>";
}

// Check server error logs
echo "<h2>📜 Server Error Log Analysis</h2>";

$log_paths = [
    '/var/log/apache2/error.log',
    '/var/log/nginx/error.log',
    __DIR__ . '/error.log',
    __DIR__ . '/../error.log'
];

$found_logs = false;
foreach ($log_paths as $log_path) {
    if (file_exists($log_path) && is_readable($log_path)) {
        $found_logs = true;
        echo "<h3>📄 Log: $log_path</h3>";
        
        $log_content = file_get_contents($log_path);
        $lines = explode("\n", $log_content);
        $recent_lines = array_slice($lines, -50); // Last 50 lines
        
        // Filter for recent errors related to our domain
        $relevant_errors = array_filter($recent_lines, function($line) {
            return strpos($line, 'training.kcdfindia.org') !== false || 
                   strpos($line, 'PHP') !== false ||
                   strpos($line, 'Fatal') !== false ||
                   strpos($line, 'Warning') !== false;
        });
        
        if (!empty($relevant_errors)) {
            echo "<div class='code-block'>";
            foreach (array_slice($relevant_errors, -10) as $error_line) {
                echo htmlspecialchars($error_line) . "\n";
            }
            echo "</div>";
        } else {
            echo "<div class='info-box'>No recent relevant errors found in this log</div>";
        }
        break;
    }
}

if (!$found_logs) {
    echo "<div class='warning-box'>⚠️ No accessible error logs found. Check with your hosting provider for log access.</div>";
}

// Quick database table check
echo "<h2>🗃️ Database Table Status</h2>";

if (isset($conn)) {
    $critical_tables = [
        'users', 'courses', 'enrollments', 'assignments', 'submissions',
        'announcements', 'classes', 'certificates', 'grades', 
        'student_progress', 'quiz_attempts', 'announcement_reads'
    ];
    
    echo "<div class='code-block'>";
    foreach ($critical_tables as $table) {
        try {
            $result = $conn->query("SELECT COUNT(*) as count FROM $table");
            if ($result) {
                $count = $result->fetch_assoc()['count'];
                echo "✅ $table: $count records\n";
            } else {
                echo "❌ $table: Query failed - " . $conn->error . "\n";
            }
        } catch (Exception $e) {
            echo "❌ $table: " . $e->getMessage() . "\n";
        }
    }
    echo "</div>";
}

// Recommendations
echo "<h2>💡 Recommendations</h2>";

if (count($error_pages) == 0) {
    echo "<div class='success-box'>";
    echo "<h3>🎉 Congratulations!</h3>";
    echo "<p>All tested pages are working without errors. Your application appears to be functioning correctly.</p>";
    echo "</div>";
} else {
    echo "<div class='warning-box'>";
    echo "<h3>🔧 Next Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Focus on the specific errors</strong> shown above for each page</li>";
    echo "<li><strong>Missing tables?</strong> Run the table creation scripts</li>";
    echo "<li><strong>Path issues?</strong> Run the absolute path fix script</li>";
    echo "<li><strong>Function errors?</strong> Check if all required files are included properly</li>";
    echo "<li><strong>Database issues?</strong> Verify table structures and data</li>";
    echo "</ol>";
    echo "</div>";
}

// Cleanup
if (isset($conn)) {
    $conn->close();
}

echo "<hr>";
echo "<p><strong>Report generated:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "<p><a href='fix_all_files_absolute_paths.php'>🔧 Fix All Path Issues</a> | <a href='fix_missing_tables_final.php'>🗃️ Fix Missing Tables</a></p>";
?>