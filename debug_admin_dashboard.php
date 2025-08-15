<?php
// Debug Admin Dashboard Issues
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

echo "<h1>🔍 Admin Dashboard Debug Tool</h1>";
echo "<p><strong>Debug Date:</strong> " . date('Y-m-d H:i:s') . "</p>";

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

echo "<h2>🔐 Session Information:</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p>✅ User logged in</p>";
    echo "<ul>";
    echo "<li><strong>User ID:</strong> " . $_SESSION['user_id'] . "</li>";
    echo "<li><strong>User Type:</strong> " . ($_SESSION['user_type'] ?? 'Not set') . "</li>";
    echo "<li><strong>Name:</strong> " . ($_SESSION['name'] ?? 'Not set') . "</li>";
    echo "<li><strong>Email:</strong> " . ($_SESSION['email'] ?? 'Not set') . "</li>";
    echo "</ul>";
} else {
    echo "<p>❌ No user session found</p>";
    echo "<p><a href='index.php'>Login first</a></p>";
}

echo "<h2>💾 Database Connection Test:</h2>";

try {
    require_once 'config/database.php';
    echo "<p>✅ Database connection successful</p>";
    echo "<p><strong>Database:</strong> " . $conn->get_server_info() . "</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
    die();
}

echo "<h2>📋 Table Existence Check:</h2>";
$required_tables = ['users', 'courses', 'enrollments', 'payments', 'messages', 'lessons', 'assignments', 'submissions'];

$missing_tables = [];
foreach ($required_tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "<p>✅ Table '$table' exists</p>";
    } else {
        echo "<p>❌ Table '$table' missing</p>";
        $missing_tables[] = $table;
    }
}

if (count($missing_tables) > 0) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>⚠️ Missing Tables Found!</h3>";
    echo "<p>The following tables are missing:</p>";
    echo "<ul>";
    foreach ($missing_tables as $table) {
        echo "<li>$table</li>";
    }
    echo "</ul>";
    echo "<p><strong>Solution:</strong> <a href='setup_database.php'>Run Database Setup</a></p>";
    echo "</div>";
}

echo "<h2>🔧 Function Test:</h2>";

// Test functions.php
try {
    require_once 'includes/functions.php';
    echo "<p>✅ functions.php loaded successfully</p>";
    
    // Test requireAdmin function
    if (function_exists('requireAdmin')) {
        echo "<p>✅ requireAdmin function exists</p>";
        
        // Test if user is admin
        if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin') {
            echo "<p>✅ User has admin privileges</p>";
        } else {
            echo "<p>❌ User does not have admin privileges</p>";
        }
    } else {
        echo "<p>❌ requireAdmin function missing</p>";
    }
    
    // Test getDashboardStats function
    if (function_exists('getDashboardStats')) {
        echo "<p>✅ getDashboardStats function exists</p>";
        
        try {
            $stats = getDashboardStats($conn, 'admin');
            echo "<p>✅ getDashboardStats executed successfully</p>";
            echo "<ul>";
            foreach ($stats as $key => $value) {
                echo "<li><strong>$key:</strong> $value</li>";
            }
            echo "</ul>";
        } catch (Exception $e) {
            echo "<p>❌ getDashboardStats error: " . $e->getMessage() . "</p>";
        }
    } else {
        echo "<p>❌ getDashboardStats function missing</p>";
    }
    
    // Test getUnreadMessages function
    if (function_exists('getUnreadMessages') && isset($_SESSION['user_id'])) {
        try {
            $unread = getUnreadMessages($conn, $_SESSION['user_id']);
            echo "<p>✅ getUnreadMessages executed successfully: $unread unread messages</p>";
        } catch (Exception $e) {
            echo "<p>❌ getUnreadMessages error: " . $e->getMessage() . "</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p>❌ functions.php error: " . $e->getMessage() . "</p>";
}

echo "<h2>📝 Query Tests:</h2>";

// Test individual queries from dashboard
$queries = [
    'recent_enrollments' => "SELECT e.*, u.name as student_name, c.title as course_title, c.id as course_id 
                            FROM enrollments e 
                            JOIN users u ON e.student_id = u.id 
                            JOIN courses c ON e.course_id = c.id 
                            ORDER BY e.enrollment_date DESC LIMIT 5",
    
    'recent_courses' => "SELECT c.*, u.name as teacher_name 
                        FROM courses c 
                        LEFT JOIN users u ON c.teacher_id = u.id 
                        ORDER BY c.created_at DESC LIMIT 5"
];

foreach ($queries as $name => $sql) {
    try {
        $result = $conn->query($sql);
        if ($result) {
            echo "<p>✅ Query '$name' executed successfully ({$result->num_rows} rows)</p>";
        } else {
            echo "<p>❌ Query '$name' failed: " . $conn->error . "</p>";
        }
    } catch (Exception $e) {
        echo "<p>❌ Query '$name' error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>🛠️ Quick Fixes:</h2>";

if (count($missing_tables) > 0) {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border: 1px solid #ffeaa7; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>🔧 Database Setup Required</h3>";
    echo "<p>Some tables are missing. Click the button below to create them:</p>";
    echo "<a href='setup_database.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Create Missing Tables</a>";
    echo "</div>";
}

echo "<h2>🚀 Next Steps:</h2>";
echo "<ol>";
echo "<li>If tables are missing, run the database setup</li>";
echo "<li>If all tests pass, try accessing the admin dashboard again</li>";
echo "<li>If issues persist, check your web server error logs</li>";
echo "</ol>";

echo "<p><a href='admin/dashboard.php' style='background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Try Admin Dashboard Again</a></p>";

$conn->close();
?>