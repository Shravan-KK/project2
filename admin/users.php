<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$page_title = 'User Management - Admin';

// Get all users
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM enrollments WHERE student_id = u.id) as enrollment_count,
        (SELECT COUNT(*) FROM courses WHERE teacher_id = u.id) as course_count
        FROM users u 
        ORDER BY u.created_at DESC";
$users = $conn->query($sql);
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                    <p class="mt-2 text-gray-600">Manage all users in the system</p>
                </div>
            </div>
        </div>

        <!-- Users List -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    All Users (<?php echo $users->num_rows; ?> total)
                </h3>
            </div>
            
            <?php if ($users->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($user = $users->fetch_assoc()): ?>
                        <li>
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                <i class="fas fa-user text-indigo-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center">
                                                <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h4>
                                                <div class="ml-2 flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo ($user['status'] ?? 'active') == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                        <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                        <?php echo $user['user_type'] == 'admin' ? 'bg-red-100 text-red-800' : 
                                                              ($user['user_type'] == 'teacher' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'); ?>">
                                                        <?php echo ucfirst($user['user_type']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($user['email']); ?></p>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <?php if ($user['user_type'] == 'student'): ?>
                                                    <span><i class="fas fa-graduation-cap mr-1"></i> <?php echo $user['enrollment_count']; ?> enrollments</span>
                                                <?php elseif ($user['user_type'] == 'teacher'): ?>
                                                    <span><i class="fas fa-chalkboard mr-1"></i> <?php echo $user['course_count']; ?> courses</span>
                                                <?php endif; ?>
                                                <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($user['created_at']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">No users found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
