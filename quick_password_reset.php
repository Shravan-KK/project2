<?php
/**
 * Quick Password Reset - One-time use script
 */

require_once 'config/database.php';

// Generate fresh password hashes
$admin_hash = password_hash('admin123', PASSWORD_DEFAULT);
$teacher_hash = password_hash('teacher123', PASSWORD_DEFAULT);
$student_hash = password_hash('student123', PASSWORD_DEFAULT);

echo "<h1>🔐 Quick Password Reset</h1>";

// Update passwords
$updates = [
    ['email' => 'admin@tms.com', 'password' => $admin_hash, 'plain' => 'admin123'],
    ['email' => 'teacher@tms.com', 'password' => $teacher_hash, 'plain' => 'teacher123'],
    ['email' => 'student@tms.com', 'password' => $student_hash, 'plain' => 'student123']
];

foreach ($updates as $update) {
    $sql = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $update['password'], $update['email']);
    
    if ($stmt->execute() && $stmt->affected_rows > 0) {
        echo "✅ Updated {$update['email']} → Password: {$update['plain']}<br>";
        
        // Verify it works
        $verify_sql = "SELECT password FROM users WHERE email = ?";
        $verify_stmt = $conn->prepare($verify_sql);
        $verify_stmt->bind_param("s", $update['email']);
        $verify_stmt->execute();
        $result = $verify_stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $check = password_verify($update['plain'], $row['password']);
            echo $check ? "   ✅ Verification: PASSED<br>" : "   ❌ Verification: FAILED<br>";
        }
    } else {
        echo "❌ Failed to update {$update['email']}<br>";
    }
}

echo "<br><h2>🚀 Try Login Now:</h2>";
echo "<a href='index.php' style='background: #28a745; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Go to Login Page</a>";

echo "<br><br><p><strong>Credentials:</strong></p>";
echo "<ul>";
echo "<li>admin@tms.com / admin123</li>";
echo "<li>teacher@tms.com / teacher123</li>";
echo "<li>student@tms.com / student123</li>";
echo "</ul>";

echo "<p style='color: red;'><strong>Delete this file after use!</strong></p>";
?>