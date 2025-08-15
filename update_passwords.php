<?php
/**
 * Password Update Script
 * This script will update the default user passwords to the correct hashes
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Password Update</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin:10px 0;} .error{background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin:10px 0;} .info{background:#d1ecf1;color:#0c5460;padding:10px;border-radius:5px;margin:10px 0;}</style>";
echo "</head><body>";

echo "<h1>🔐 Password Update Script</h1>";

// Define the correct passwords and their hashes
$users = [
    [
        'email' => 'admin@tms.com',
        'password' => 'admin123',
        'name' => 'Admin User',
        'user_type' => 'admin'
    ],
    [
        'email' => 'teacher@tms.com',
        'password' => 'teacher123',
        'name' => 'John Teacher',
        'user_type' => 'teacher'
    ],
    [
        'email' => 'student@tms.com',
        'password' => 'student123',
        'name' => 'Jane Student',
        'user_type' => 'student'
    ]
];

echo "<h2>Current Users in Database:</h2>";

// First, let's see what users exist
$result = $conn->query("SELECT id, name, email, user_type, created_at FROM users ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' style='border-collapse:collapse; width:100%; margin:10px 0;'>";
    echo "<tr style='background:#f8f9fa;'><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No users found in database!</div>";
}

echo "<h2>Updating Passwords...</h2>";

foreach ($users as $user) {
    // Generate proper password hash
    $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);
    
    // Check if user exists
    $check_sql = "SELECT id FROM users WHERE email = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("s", $user['email']);
    $check_stmt->execute();
    $exists = $check_stmt->get_result()->num_rows > 0;
    
    if ($exists) {
        // Update existing user password
        $update_sql = "UPDATE users SET password = ? WHERE email = ?";
        $update_stmt = $conn->prepare($update_sql);
        $update_stmt->bind_param("ss", $password_hash, $user['email']);
        
        if ($update_stmt->execute()) {
            echo "<div class='success'>✅ Updated password for {$user['email']} (Password: {$user['password']})</div>";
        } else {
            echo "<div class='error'>❌ Failed to update {$user['email']}: " . $update_stmt->error . "</div>";
        }
    } else {
        // Create new user
        $insert_sql = "INSERT INTO users (name, email, password, user_type) VALUES (?, ?, ?, ?)";
        $insert_stmt = $conn->prepare($insert_sql);
        $insert_stmt->bind_param("ssss", $user['name'], $user['email'], $password_hash, $user['user_type']);
        
        if ($insert_stmt->execute()) {
            echo "<div class='success'>✅ Created new user {$user['email']} (Password: {$user['password']})</div>";
        } else {
            echo "<div class='error'>❌ Failed to create {$user['email']}: " . $insert_stmt->error . "</div>";
        }
    }
}

echo "<h2>🎉 Password Update Complete!</h2>";

echo "<div class='info'>";
echo "<h3>Updated Login Credentials:</h3>";
echo "<ul>";
echo "<li><strong>Admin:</strong> admin@tms.com / admin123</li>";
echo "<li><strong>Teacher:</strong> teacher@tms.com / teacher123</li>";
echo "<li><strong>Student:</strong> student@tms.com / student123</li>";
echo "</ul>";
echo "</div>";

echo "<h3>Test Login:</h3>";
echo "<p><a href='index.php' style='background: #007bff; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>🚀 Go to Login Page</a></p>";

echo "<h3>Verify Password Hashes:</h3>";
echo "<p>Let's verify the password hashes are correct:</p>";

foreach ($users as $user) {
    $result = $conn->query("SELECT password FROM users WHERE email = '{$user['email']}'");
    if ($result && $row = $result->fetch_assoc()) {
        $stored_hash = $row['password'];
        $verify_result = password_verify($user['password'], $stored_hash);
        
        if ($verify_result) {
            echo "<div class='success'>✅ {$user['email']}: Password hash verified correctly</div>";
        } else {
            echo "<div class='error'>❌ {$user['email']}: Password hash verification failed</div>";
        }
    }
}

echo "<h3>🗑️ Security Note:</h3>";
echo "<p><strong>Delete this file after use:</strong> <code>update_passwords.php</code></p>";

echo "</body></html>";
?>