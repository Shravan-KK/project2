<?php
// Test page to verify error display is working
require_once 'config/error_display.php';

echo "<h1>Error Display Test Page</h1>";
echo "<p>This page tests various types of errors to ensure they display properly in the browser.</p>";

echo "<h2>1. Testing Notice (Undefined Variable)</h2>";
echo $undefined_variable; // This will generate a notice

echo "<h2>2. Testing Warning (Division by Zero)</h2>";
$result = 10 / 0; // This will generate a warning

echo "<h2>3. Testing User Error</h2>";
trigger_error("This is a custom user error for testing", E_USER_ERROR);

echo "<h2>4. Testing Database Connection</h2>";
require_once 'config/database.php';

// Test a query with an intentional error
$sql = "SELECT * FROM non_existent_table";
$result = $conn->query($sql);

echo "<p>If you see this message, the test completed successfully!</p>";
?>