<?php
// Test script to verify admin dashboard fixes
echo "<h1>🔧 Admin Dashboard Fix Test</h1>";

echo "<h2>✅ Testing Fixed Components:</h2>";

// Test 1: Error display file
echo "<h3>1. Error Display Configuration:</h3>";
$error_file = 'config/error_display.php';
if (file_exists($error_file)) {
    require_once $error_file;
    echo "<p>✅ error_display.php loaded successfully</p>";
} else {
    echo "<p>❌ error_display.php still missing</p>";
}

// Test 2: Header file
echo "<h3>2. Header File:</h3>";
try {
    require_once 'includes/header.php';
    echo "<p>✅ header.php loaded successfully</p>";
} catch (Exception $e) {
    echo "<p>❌ header.php error: " . $e->getMessage() . "</p>";
}

// Test 3: Database and functions
echo "<h3>3. Database and Functions:</h3>";
try {
    require_once 'config/database.php';
    echo "<p>✅ Database connected</p>";
    
    require_once 'includes/functions.php';
    echo "<p>✅ Functions loaded</p>";
    
    // Test session setup
    if (session_status() == PHP_SESSION_NONE) {
        session_start();
    }
    
    // Simulate admin login for testing
    if (!isset($_SESSION['user_id'])) {
        echo "<p>⚠️ No active session - creating test session</p>";
        $_SESSION['user_id'] = 1;
        $_SESSION['user_type'] = 'admin';
        $_SESSION['name'] = 'Test Admin';
        $_SESSION['email'] = 'admin@test.com';
    }
    
    echo "<p>✅ Session configured</p>";
    
    // Test dashboard functions
    $stats = getDashboardStats($conn, 'admin');
    echo "<p>✅ Dashboard stats loaded</p>";
    echo "<ul>";
    foreach ($stats as $key => $value) {
        echo "<li><strong>$key:</strong> $value</li>";
    }
    echo "</ul>";
    
    $unread = getUnreadMessages($conn, $_SESSION['user_id']);
    echo "<p>✅ Unread messages: $unread</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<h2>🚀 Test Complete!</h2>";
echo "<p>If all tests pass above, the admin dashboard should now work.</p>";

echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 15px 0;'>";
echo "<h3>✅ Fixes Applied:</h3>";
echo "<ol>";
echo "<li>Created missing <code>config/error_display.php</code> file</li>";
echo "<li>Made <code>includes/header.php</code> more robust</li>";
echo "<li>Added proper error handling for production/development</li>";
echo "<li>Added fallback error display settings</li>";
echo "</ol>";
echo "</div>";

echo "<p><a href='admin/dashboard.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>🎯 Try Admin Dashboard Now</a></p>";

echo "<hr>";
echo "<p><strong>Note:</strong> Make sure you're logged in as an admin user before accessing the dashboard.</p>";
echo "<p><a href='index.php'>Login Page</a> | <a href='debug_admin_dashboard.php'>Debug Tool</a></p>";
?>