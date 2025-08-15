<?php
/**
 * Login Debug Script
 * This will help us identify exactly what's wrong with the login process
 */

require_once 'config/database.php';

echo "<!DOCTYPE html>";
echo "<html><head><title>Login Debug</title>";
echo "<style>body{font-family:Arial,sans-serif;margin:20px;} .success{background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin:10px 0;} .error{background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin:10px 0;} .info{background:#d1ecf1;color:#0c5460;padding:10px;border-radius:5px;margin:10px 0;} .warning{background:#fff3cd;color:#856404;padding:10px;border-radius:5px;margin:10px 0;} table{border-collapse:collapse;width:100%;margin:10px 0;} th,td{border:1px solid #ddd;padding:8px;text-align:left;} th{background:#f8f9fa;}</style>";
echo "</head><body>";

echo "<h1>🔍 Login System Debug</h1>";

// Test 1: Check database connection
echo "<h2>1. Database Connection Test</h2>";
if ($conn) {
    echo "<div class='success'>✅ Database connection successful</div>";
} else {
    echo "<div class='error'>❌ Database connection failed</div>";
    exit();
}

// Test 2: Check if users table exists and show all users
echo "<h2>2. Users Table Content</h2>";
$result = $conn->query("SELECT id, name, email, user_type, password, created_at FROM users ORDER BY id");
if ($result && $result->num_rows > 0) {
    echo "<table>";
    echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Type</th><th>Password Hash (first 50 chars)</th><th>Created</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>" . htmlspecialchars($row['email']) . "</td>";
        echo "<td>" . htmlspecialchars($row['user_type']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($row['password'], 0, 50)) . "...</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='error'>❌ No users found in database!</div>";
}

// Test 3: Password verification test
echo "<h2>3. Password Verification Test</h2>";

$test_credentials = [
    ['email' => 'admin@tms.com', 'password' => 'admin123'],
    ['email' => 'teacher@tms.com', 'password' => 'teacher123'],
    ['email' => 'student@tms.com', 'password' => 'student123']
];

foreach ($test_credentials as $cred) {
    echo "<h3>Testing: {$cred['email']} / {$cred['password']}</h3>";
    
    // Get user from database
    $sql = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $cred['email']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        echo "<div class='info'>✅ User found in database</div>";
        echo "<p><strong>Database Info:</strong></p>";
        echo "<ul>";
        echo "<li>ID: " . $user['id'] . "</li>";
        echo "<li>Name: " . htmlspecialchars($user['name']) . "</li>";
        echo "<li>Email: " . htmlspecialchars($user['email']) . "</li>";
        echo "<li>Type: " . htmlspecialchars($user['user_type']) . "</li>";
        echo "<li>Password Hash: " . htmlspecialchars(substr($user['password'], 0, 60)) . "...</li>";
        echo "</ul>";
        
        // Test password verification
        $password_match = password_verify($cred['password'], $user['password']);
        if ($password_match) {
            echo "<div class='success'>✅ Password verification PASSED</div>";
        } else {
            echo "<div class='error'>❌ Password verification FAILED</div>";
            
            // Let's create a new hash and test it
            $new_hash = password_hash($cred['password'], PASSWORD_DEFAULT);
            echo "<p><strong>Creating new hash for testing:</strong></p>";
            echo "<p>New hash: " . htmlspecialchars($new_hash) . "</p>";
            
            $new_test = password_verify($cred['password'], $new_hash);
            if ($new_test) {
                echo "<div class='warning'>⚠️ New hash works! The database hash is corrupted. Will update it now...</div>";
                
                // Update the database with working hash
                $update_sql = "UPDATE users SET password = ? WHERE email = ?";
                $update_stmt = $conn->prepare($update_sql);
                $update_stmt->bind_param("ss", $new_hash, $cred['email']);
                if ($update_stmt->execute()) {
                    echo "<div class='success'>✅ Updated database with working hash</div>";
                } else {
                    echo "<div class='error'>❌ Failed to update hash: " . $update_stmt->error . "</div>";
                }
            } else {
                echo "<div class='error'>❌ Even new hash doesn't work - something is very wrong!</div>";
            }
        }
    } else {
        echo "<div class='error'>❌ User not found in database</div>";
    }
    
    echo "<hr>";
}

// Test 4: Simulate actual login process
echo "<h2>4. Simulate Login Process</h2>";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['test_email']) && isset($_POST['test_password'])) {
    $test_email = trim($_POST['test_email']);
    $test_password = $_POST['test_password'];
    
    echo "<h3>Testing Login: $test_email / $test_password</h3>";
    
    if (empty($test_email) || empty($test_password)) {
        echo "<div class='error'>❌ Empty fields detected</div>";
    } else {
        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $test_email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        echo "<p>Query executed: SELECT * FROM users WHERE email = '$test_email'</p>";
        echo "<p>Rows found: " . $result->num_rows . "</p>";
        
        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            echo "<div class='info'>✅ User found</div>";
            
            $password_check = password_verify($test_password, $user['password']);
            echo "<p>Password verify result: " . ($password_check ? 'TRUE' : 'FALSE') . "</p>";
            
            if ($password_check) {
                echo "<div class='success'>✅ LOGIN SUCCESS! User would be logged in as {$user['user_type']}</div>";
                
                // Show what session variables would be set
                echo "<p><strong>Session variables that would be set:</strong></p>";
                echo "<ul>";
                echo "<li>user_id: " . $user['id'] . "</li>";
                echo "<li>user_type: " . $user['user_type'] . "</li>";
                echo "<li>name: " . htmlspecialchars($user['name']) . "</li>";
                echo "</ul>";
            } else {
                echo "<div class='error'>❌ LOGIN FAILED: Invalid password</div>";
            }
        } else {
            echo "<div class='error'>❌ LOGIN FAILED: User not found</div>";
        }
    }
}

// Test form
echo "<h2>5. Test Login Form</h2>";
echo "<form method='POST'>";
echo "<p>";
echo "<label>Email: </label>";
echo "<select name='test_email'>";
echo "<option value='admin@tms.com'>admin@tms.com</option>";
echo "<option value='teacher@tms.com'>teacher@tms.com</option>";
echo "<option value='student@tms.com'>student@tms.com</option>";
echo "</select>";
echo "</p>";
echo "<p>";
echo "<label>Password: </label>";
echo "<select name='test_password'>";
echo "<option value='admin123'>admin123</option>";
echo "<option value='teacher123'>teacher123</option>";
echo "<option value='student123'>student123</option>";
echo "</select>";
echo "</p>";
echo "<button type='submit' style='background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 5px;'>Test Login</button>";
echo "</form>";

echo "<h2>6. Next Steps</h2>";
echo "<div class='info'>";
echo "<p>If password verification is failing:</p>";
echo "<ol>";
echo "<li>The hashes above should have been automatically updated</li>";
echo "<li>Try the actual login page now: <a href='index.php'>index.php</a></li>";
echo "<li>If it still doesn't work, there might be a session or redirect issue</li>";
echo "</ol>";
echo "</div>";

echo "<p><strong>⚠️ Delete this file after debugging: debug_login.php</strong></p>";

echo "</body></html>";
?>