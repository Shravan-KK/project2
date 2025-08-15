<?php
// Export Users Data Script
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

echo "<h1>Export Users Data</h1>";

// Check if export type is specified
$export_type = $_GET['type'] ?? 'view';

// Get all users
$sql = "SELECT * FROM users ORDER BY id";
$result = $conn->query($sql);

if (!$result) {
    die("Error: " . $conn->error);
}

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo "<p><strong>Total Users:</strong> " . count($users) . "</p>";

// Export options
echo "<div style='margin: 20px 0;'>";
echo "<h3>Export Options:</h3>";
echo "<a href='?type=view' style='margin-right: 10px; padding: 8px 12px; background: #007bff; color: white; text-decoration: none; border-radius: 4px;'>View Data</a>";
echo "<a href='?type=sql' style='margin-right: 10px; padding: 8px 12px; background: #28a745; color: white; text-decoration: none; border-radius: 4px;'>SQL Export</a>";
echo "<a href='?type=csv' style='margin-right: 10px; padding: 8px 12px; background: #17a2b8; color: white; text-decoration: none; border-radius: 4px;'>CSV Export</a>";
echo "<a href='?type=json' style='margin-right: 10px; padding: 8px 12px; background: #ffc107; color: black; text-decoration: none; border-radius: 4px;'>JSON Export</a>";
echo "</div>";

switch ($export_type) {
    case 'view':
        echo "<h3>Users Data (Table View):</h3>";
        if (count($users) > 0) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%; font-size: 12px;'>";
            echo "<tr style='background: #f8f9fa;'>";
            echo "<th>ID</th><th>Name</th><th>Email</th><th>Password (MD5)</th><th>Type</th><th>Phone</th><th>Address</th><th>Created</th></tr>";
            
            foreach ($users as $user) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($user['id']) . "</td>";
                echo "<td>" . htmlspecialchars($user['name']) . "</td>";
                echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                echo "<td>" . substr($user['password'], 0, 10) . "...</td>";
                echo "<td>" . htmlspecialchars($user['user_type']) . "</td>";
                echo "<td>" . htmlspecialchars($user['phone'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars(substr($user['address'] ?? '', 0, 30)) . "...</td>";
                echo "<td>" . htmlspecialchars($user['created_at']) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No users found.</p>";
        }
        break;
        
    case 'sql':
        echo "<h3>SQL INSERT Statements:</h3>";
        echo "<textarea style='width: 100%; height: 400px; font-family: monospace; font-size: 12px;'>";
        echo "-- Users Export from teaching_management database\n";
        echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
        
        foreach ($users as $user) {
            $name = addslashes($user['name']);
            $email = addslashes($user['email']);
            $password = $user['password']; // Already MD5 hashed
            $user_type = $user['user_type'];
            $phone = $user['phone'] ? "'" . addslashes($user['phone']) . "'" : 'NULL';
            $address = $user['address'] ? "'" . addslashes($user['address']) . "'" : 'NULL';
            
            echo "INSERT INTO users (name, email, password, user_type, phone, address) VALUES ";
            echo "('{$name}', '{$email}', '{$password}', '{$user_type}', {$phone}, {$address});\n";
        }
        echo "</textarea>";
        break;
        
    case 'csv':
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, ['ID', 'Name', 'Email', 'Password_Hash', 'User_Type', 'Phone', 'Address', 'Created_At', 'Updated_At']);
        
        // CSV Data
        foreach ($users as $user) {
            fputcsv($output, [
                $user['id'],
                $user['name'],
                $user['email'],
                $user['password'],
                $user['user_type'],
                $user['phone'] ?? '',
                $user['address'] ?? '',
                $user['created_at'],
                $user['updated_at']
            ]);
        }
        
        fclose($output);
        exit;
        
    case 'json':
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="users_export_' . date('Y-m-d') . '.json"');
        
        $export_data = [
            'export_date' => date('Y-m-d H:i:s'),
            'database' => 'teaching_management',
            'table' => 'users',
            'total_records' => count($users),
            'data' => $users
        ];
        
        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
}

$conn->close();

echo "<hr>";
echo "<p><a href='index.php'>← Back to Login</a> | <a href='database_manager.php'>Database Manager</a></p>";
?>