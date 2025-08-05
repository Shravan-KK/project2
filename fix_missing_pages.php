<?php
// Script to create missing admin pages
require_once 'config/database.php';

// Create admin/enrollments.php
$enrollments_content = '<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";
requireAdmin();
$page_title = "Enrollment Management - Admin";
$sql = "SELECT e.*, c.title as course_title, c.price as course_price, u.name as student_name, t.name as teacher_name FROM enrollments e JOIN courses c ON e.course_id = c.id JOIN users u ON e.student_id = u.id LEFT JOIN users t ON c.teacher_id = t.id ORDER BY e.enrollment_date DESC";
$enrollments = $conn->query($sql);
?>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Enrollment Management</h1>
            <p class="mt-2 text-gray-600">View all course enrollments</p>
        </div>
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">All Enrollments (' . $enrollments->num_rows . ' total)</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                    <li class="px-4 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($enrollment["student_name"]); ?></h4>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($enrollment["course_title"]); ?></p>
                                <p class="text-sm text-gray-500"><?php echo formatDate($enrollment["enrollment_date"]); ?></p>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</body>
</html>';

file_put_contents('admin/enrollments.php', $enrollments_content);

// Create admin/payments.php
$payments_content = '<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";
requireAdmin();
$page_title = "Payment Management - Admin";
$sql = "SELECT p.*, u.name as student_name, c.title as course_title FROM payments p JOIN users u ON p.student_id = u.id JOIN courses c ON p.course_id = c.id ORDER BY p.payment_date DESC";
$payments = $conn->query($sql);
?>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Payment Management</h1>
            <p class="mt-2 text-gray-600">View all payment transactions</p>
        </div>
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">All Payments (' . $payments->num_rows . ' total)</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                <?php while ($payment = $payments->fetch_assoc()): ?>
                    <li class="px-4 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($payment["student_name"]); ?></h4>
                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($payment["course_title"]); ?></p>
                                <p class="text-sm text-gray-500">₹<?php echo number_format($payment["amount"], 2); ?> - <?php echo formatDate($payment["payment_date"]); ?></p>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        </div>
    </div>
</body>
</html>';

file_put_contents('admin/payments.php', $payments_content);

// Create admin/reports.php
$reports_content = '<?php
session_start();
require_once "../config/database.php";
require_once "../includes/functions.php";
require_once "../includes/header.php";
requireAdmin();
$page_title = "Reports - Admin";

// Get statistics
$total_users = $conn->query("SELECT COUNT(*) as count FROM users")->fetch_assoc()["count"];
$total_courses = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()["count"];
$total_enrollments = $conn->query("SELECT COUNT(*) as count FROM enrollments")->fetch_assoc()["count"];
$total_revenue = $conn->query("SELECT SUM(amount) as total FROM payments")->fetch_assoc()["total"] ?? 0;

// Top courses by enrollment
$top_courses = $conn->query("SELECT c.title, COUNT(e.id) as enrollment_count FROM courses c LEFT JOIN enrollments e ON c.id = e.course_id GROUP BY c.id ORDER BY enrollment_count DESC LIMIT 5");

// Recent enrollments
$recent_enrollments = $conn->query("SELECT e.*, u.name as student_name, c.title as course_title FROM enrollments e JOIN users u ON e.student_id = u.id JOIN courses c ON e.course_id = c.id ORDER BY e.enrollment_date DESC LIMIT 10");
?>
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Reports & Analytics</h1>
            <p class="mt-2 text-gray-600">System overview and statistics</p>
        </div>
        
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-indigo-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Users</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $total_users; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-graduation-cap text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Courses</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $total_courses; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-graduate text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Enrollments</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $total_enrollments; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-rupee-sign text-yellow-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Revenue</dt>
                                <dd class="text-lg font-medium text-gray-900">₹<?php echo number_format($total_revenue, 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Top Courses -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Top Courses by Enrollment</h3>
            </div>
            <div class="divide-y divide-gray-200">
                <?php while ($course = $top_courses->fetch_assoc()): ?>
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($course["title"]); ?></h4>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo $course["enrollment_count"]; ?> enrollments
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        
        <!-- Recent Enrollments -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Recent Enrollments</h3>
            </div>
            <div class="divide-y divide-gray-200">
                <?php while ($enrollment = $recent_enrollments->fetch_assoc()): ?>
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($enrollment["student_name"]); ?></h4>
                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars($enrollment["course_title"]); ?></p>
                            </div>
                            <div class="text-sm text-gray-500">
                                <?php echo formatDate($enrollment["enrollment_date"]); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</body>
</html>';

file_put_contents('admin/reports.php', $reports_content);

echo "Missing admin pages created successfully!\n";
?> 