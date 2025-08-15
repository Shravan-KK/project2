<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$stats = getDashboardStats($conn, 'admin');
$unread_messages = getUnreadMessages($conn, $_SESSION['user_id']);

// Get recent enrollments
$sql = "SELECT e.*, u.name as student_name, c.title as course_title, c.id as course_id 
        FROM enrollments e 
        JOIN users u ON e.student_id = u.id 
        JOIN courses c ON e.course_id = c.id 
        ORDER BY e.enrollment_date DESC LIMIT 5";
$recent_enrollments = $conn->query($sql);

// Get recent courses
$sql = "SELECT c.*, u.name as teacher_name 
        FROM courses c 
        LEFT JOIN users u ON c.teacher_id = u.id 
        ORDER BY c.created_at DESC LIMIT 5";
$recent_courses = $conn->query($sql);
$page_title = 'Admin Dashboard';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
            <p class="mt-2 text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-indigo-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_users']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-book text-green-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Courses</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_courses']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-graduate text-blue-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Enrollments</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_enrollments']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-rupee-sign text-yellow-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                                <dd class="text-lg font-medium text-gray-900">₹<?php echo number_format($stats['total_revenue'], 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Activity -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Recent Enrollments -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Enrollments</h3>
                    <div class="space-y-4">
                        <?php if ($recent_enrollments->num_rows > 0): ?>
                            <?php while ($enrollment = $recent_enrollments->fetch_assoc()): ?>
                                <div class="flex items-center justify-between">
                                <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($enrollment['student_name']); ?></p>
                                    <a href="course_content.php?course_id=<?php echo $enrollment['course_id']; ?>" class="block hover:bg-gray-50 rounded-md p-1 -m-1 transition-colors duration-200">
                                        <p class="text-sm text-gray-500 hover:text-indigo-600"><?php echo htmlspecialchars($enrollment['course_title']); ?></p>
                                    </a>
                                    </div>
                                <div class="text-sm text-gray-500 ml-4">
                                        <?php echo formatDate($enrollment['enrollment_date']); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm">No recent enrollments</p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4">
                        <a href="enrollments.php" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">View all enrollments →</a>
                    </div>
                </div>
            </div>

            <!-- Recent Courses -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Courses</h3>
                    <div class="space-y-4">
                        <?php if ($recent_courses->num_rows > 0): ?>
                            <?php while ($course = $recent_courses->fetch_assoc()): ?>
                                <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <a href="lessons.php?course_id=<?php echo $course['id']; ?>" class="block hover:bg-gray-50 rounded-md p-2 -m-2 transition-colors duration-200">
                                        <p class="text-sm font-medium text-gray-900 hover:text-indigo-600"><?php echo htmlspecialchars($course['title']); ?></p>
                                        <p class="text-sm text-gray-500">by <?php echo htmlspecialchars($course['teacher_name'] ?? 'Unassigned'); ?></p>
                                    </a>
                                    </div>
                                <div class="text-sm text-gray-500 ml-4">
                                        <?php echo formatDate($course['created_at']); ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm">No recent courses</p>
                        <?php endif; ?>
                    </div>
                    <div class="mt-4">
                        <a href="courses.php" class="text-indigo-600 hover:text-indigo-500 text-sm font-medium">View all courses →</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <a href="users.php?action=add" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-user-plus mr-2"></i>
                        Add New User
                    </a>
                    <a href="courses.php?action=add" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>
                        Create Course
                    </a>
                    <a href="lessons.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                        <i class="fas fa-book mr-2"></i>
                        Manage Lessons
                    </a>
                    <a href="reports.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-chart-bar mr-2"></i>
                        View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>

<?php require_once '../includes/footer.php'; ?> 