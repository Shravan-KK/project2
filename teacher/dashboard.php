<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$stats = getDashboardStats($conn, 'teacher', $teacher_id);
$unread_messages = getUnreadMessages($conn, $teacher_id);

// Get teacher's courses
$teacher_courses = getTeacherCourses($conn, $teacher_id);

// Get recent students
$sql = "SELECT DISTINCT u.*, e.enrollment_date 
        FROM users u 
        JOIN enrollments e ON u.id = e.student_id 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.teacher_id = ? AND e.status = 'active' 
        ORDER BY e.enrollment_date DESC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$recent_students = $stmt->get_result();
$page_title = 'Teacher Dashboard';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Teacher Dashboard</h1>
            <p class="mt-2 text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-book text-green-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">My Courses</dt>
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
                            <i class="fas fa-users text-blue-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_students']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-envelope text-indigo-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Unread Messages</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $unread_messages; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Courses -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">My Courses</h3>
                    <a href="courses.php?action=add" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>
                        Create Course
                    </a>
                </div>
                
                <?php if ($teacher_courses->num_rows > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while ($course = $teacher_courses->fetch_assoc()): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $course['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo ucfirst($course['status']); ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-3">
                                    <span><i class="fas fa-users mr-1"></i> <?php echo $course['enrolled_students']; ?> students</span>
                                    <span><i class="fas fa-rupee-sign mr-1"></i> ₹<?php echo number_format($course['price'], 2); ?></span>
                                </div>
                                <div class="flex space-x-2">
                                    <a href="course_content.php?course_id=<?php echo $course['id']; ?>" class="flex-1 text-center px-3 py-2 text-sm font-medium text-green-600 hover:text-green-700 border border-green-600 rounded-md">
                                        <i class="fas fa-play mr-1"></i> Content
                                    </a>
                                    <a href="courses.php?action=edit&id=<?php echo $course['id']; ?>" class="flex-1 text-center px-3 py-2 text-sm font-medium text-purple-600 hover:text-purple-700 border border-purple-600 rounded-md">
                                        Edit
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-book text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">You haven't created any courses yet.</p>
                        <a href="courses.php?action=add" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            Create Your First Course
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Students -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Recent Students</h3>
                <div class="space-y-4">
                    <?php if ($recent_students->num_rows > 0): ?>
                        <?php while ($student = $recent_students->fetch_assoc()): ?>
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10">
                                        <div class="h-10 w-10 rounded-full bg-green-100 flex items-center justify-center">
                                            <i class="fas fa-user text-green-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($student['email']); ?></div>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    Enrolled <?php echo formatDate($student['enrollment_date']); ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm">No students enrolled yet</p>
                    <?php endif; ?>
                </div>
                <div class="mt-4">
                    <a href="students.php" class="text-green-600 hover:text-green-500 text-sm font-medium">View all students →</a>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 