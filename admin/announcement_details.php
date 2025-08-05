<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/navigation.php';

requireAdmin();

$announcement_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get announcement details
$announcement = null;
if ($announcement_id) {
    $sql = "SELECT a.*, u.name as created_by_name 
            FROM announcements a 
            LEFT JOIN users u ON a.created_by = u.id 
            WHERE a.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $announcement = $result->fetch_assoc();
}

// Get read statistics
$read_stats = null;
if ($announcement) {
    $sql = "SELECT 
                COUNT(ar.id) as read_count,
                CASE 
                    WHEN a.target_audience = 'students' THEN (SELECT COUNT(*) FROM users WHERE user_type = 'student')
                    WHEN a.target_audience = 'teachers' THEN (SELECT COUNT(*) FROM users WHERE user_type = 'teacher')
                    WHEN a.target_audience = 'both' THEN (SELECT COUNT(*) FROM users WHERE user_type IN ('student', 'teacher'))
                END as total_recipients
            FROM announcements a 
            LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id
            WHERE a.id = ?
            GROUP BY a.id";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $read_stats = $stmt->get_result()->fetch_assoc();
}

// Get users who have read the announcement
$read_users = null;
if ($announcement) {
    $sql = "SELECT u.name, u.email, u.user_type, ar.read_at
            FROM announcement_reads ar
            JOIN users u ON ar.user_id = u.id
            WHERE ar.announcement_id = ?
            ORDER BY ar.read_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $read_users = $stmt->get_result();
}

// Get users who haven't read the announcement
$unread_users = null;
if ($announcement) {
    $sql = "SELECT u.name, u.email, u.user_type
            FROM users u
            WHERE u.user_type IN (
                CASE 
                    WHEN ? = 'students' THEN 'student'
                    WHEN ? = 'teachers' THEN 'teacher'
                    WHEN ? = 'both' THEN 'student'
                END,
                CASE 
                    WHEN ? = 'both' THEN 'teacher'
                    ELSE NULL
                END
            )
            AND u.id NOT IN (
                SELECT user_id FROM announcement_reads WHERE announcement_id = ?
            )
            ORDER BY u.name ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $announcement['target_audience'], $announcement['target_audience'], $announcement['target_audience'], $announcement['target_audience'], $announcement_id);
    $stmt->execute();
    $unread_users = $stmt->get_result();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement Details - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php renderNavigation('admin', 'announcements'); ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Announcement Details</h1>
                    <p class="mt-2 text-gray-600">View announcement statistics and read status</p>
                </div>
                <a href="announcements.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Announcements
                </a>
            </div>
        </div>

        <?php if ($announcement): ?>
            <!-- Announcement Details -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-2xl font-bold text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h2>
                            <div class="flex items-center space-x-2 mt-2">
                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $announcement['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                    <?php echo $announcement['is_active'] ? 'Active' : 'Inactive'; ?>
                                </span>
                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    <?php echo $announcement['target_audience'] == 'students' ? 'bg-blue-100 text-blue-800' : 
                                          ($announcement['target_audience'] == 'teachers' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800'); ?>">
                                    <?php echo ucfirst($announcement['target_audience']); ?>
                                </span>
                            </div>
                        </div>
                        <a href="announcements.php?action=edit&id=<?php echo $announcement['id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Announcement
                        </a>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm mb-6">
                        <div>
                            <span class="font-medium text-gray-500">Created By:</span>
                            <span class="ml-2 text-gray-900"><?php echo htmlspecialchars($announcement['created_by_name'] ?? 'Unknown'); ?></span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500">Created:</span>
                            <span class="ml-2 text-gray-900"><?php echo formatDate($announcement['created_at']); ?></span>
                        </div>
                        <div>
                            <span class="font-medium text-gray-500">Last Updated:</span>
                            <span class="ml-2 text-gray-900"><?php echo formatDate($announcement['updated_at']); ?></span>
                        </div>
                    </div>
                    
                    <div class="prose max-w-none">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Content:</h3>
                        <div class="bg-gray-50 p-4 rounded-md">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Read Statistics -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Read Statistics</h3>
                </div>
                <div class="px-6 py-4">
                    <?php if ($read_stats): ?>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <div class="text-3xl font-bold text-indigo-600"><?php echo $read_stats['read_count']; ?></div>
                                <div class="text-sm text-gray-500">Users Read</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-gray-600"><?php echo $read_stats['total_recipients']; ?></div>
                                <div class="text-sm text-gray-500">Total Recipients</div>
                            </div>
                            <div class="text-center">
                                <div class="text-3xl font-bold text-green-600">
                                    <?php echo $read_stats['total_recipients'] > 0 ? round(($read_stats['read_count'] / $read_stats['total_recipients']) * 100, 1) : 0; ?>%
                                </div>
                                <div class="text-sm text-gray-500">Read Rate</div>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="mt-6">
                            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                                <span>Read Progress</span>
                                <span><?php echo $read_stats['read_count']; ?> / <?php echo $read_stats['total_recipients']; ?></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-indigo-600 h-2 rounded-full" style="width: <?php echo $read_stats['total_recipients'] > 0 ? ($read_stats['read_count'] / $read_stats['total_recipients']) * 100 : 0; ?>%"></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No read statistics available.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Users Who Have Read -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Users Who Have Read</h3>
                </div>
                <div class="px-6 py-4">
                    <?php if ($read_users && $read_users->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Read At</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($user = $read_users->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $user['user_type'] == 'student' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                                    <?php echo ucfirst($user['user_type']); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                                <?php echo formatDate($user['read_at']); ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">No users have read this announcement yet.</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Users Who Haven't Read -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Users Who Haven't Read</h3>
                </div>
                <div class="px-6 py-4">
                    <?php if ($unread_users && $unread_users->num_rows > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php while ($user = $unread_users->fetch_assoc()): ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['name']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $user['user_type'] == 'student' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                                    <?php echo ucfirst($user['user_type']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-gray-500">All target users have read this announcement.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white shadow rounded-lg p-6">
                <p class="text-gray-500">Announcement not found.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 