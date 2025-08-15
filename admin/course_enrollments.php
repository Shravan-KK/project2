<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    header('Location: courses.php');
    exit;
}

$page_title = 'Course Enrollments - Admin';

// Get course information
$course_sql = "SELECT c.*, u.name as teacher_name FROM courses c LEFT JOIN users u ON c.teacher_id = u.id WHERE c.id = ?";
$course_stmt = $conn->prepare($course_sql);
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: courses.php');
    exit;
}

// Get course enrollments
$enrollments_sql = "SELECT e.*, u.name, u.email, u.created_at as joined_date,
                     (SELECT COUNT(*) FROM student_progress WHERE student_id = e.student_id AND course_id = e.course_id AND lesson_completed = 1) as completed_lessons,
                     (SELECT COUNT(*) FROM lessons WHERE course_id = e.course_id) as total_lessons
                     FROM enrollments e
                     JOIN users u ON e.student_id = u.id
                     WHERE e.course_id = ? AND e.status = 'active'
                     ORDER BY e.enrollment_date DESC";
$enrollments_stmt = $conn->prepare($enrollments_sql);
$enrollments_stmt->bind_param("i", $course_id);
$enrollments_stmt->execute();
$enrollments = $enrollments_stmt->get_result();

// Get enrollment statistics
$stats_sql = "SELECT 
    COUNT(*) as total_enrollments,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_enrollments,
    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_enrollments,
    COUNT(CASE WHEN status = 'dropped' THEN 1 END) as dropped_enrollments,
    AVG(progress) as avg_progress
    FROM enrollments 
    WHERE course_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $course_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Course Enrollments</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($course['title']); ?></p>
            </div>
            <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Courses
            </a>
        </div>
    </div>

    <!-- Course Information -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Course Information</h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Title</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($course['title']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Teacher</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($course['teacher_name'] ?? 'Not assigned'); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Enrollments</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo $enrollments->num_rows; ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $course['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($course['status']); ?>
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Enrollment Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Enrollments</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_enrollments'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-play-circle text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['active_enrollments'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['completed_enrollments'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-percentage text-indigo-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Progress</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo round($stats['avg_progress'] ?? 0, 1); ?>%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Enrollments List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Enrolled Students (<?php echo $enrollments->num_rows; ?> total)
            </h3>
        </div>
        
        <?php if ($enrollments->num_rows > 0): ?>
            <ul class="divide-y divide-gray-200">
                <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($enrollment['name']); ?></h4>
                                            <div class="ml-2 flex items-center space-x-2">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                    <?php echo ucfirst($enrollment['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($enrollment['email']); ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> Enrolled: <?php echo formatDate($enrollment['enrollment_date']); ?></span>
                                            <span class="ml-4"><i class="fas fa-book mr-1"></i> Progress: <?php echo $enrollment['completed_lessons']; ?>/<?php echo $enrollment['total_lessons']; ?> lessons</span>
                                            <span class="ml-4"><i class="fas fa-percentage mr-1"></i> <?php echo $enrollment['progress'] ?? 0; ?>% complete</span>
                                        </div>
                                        <?php if ($enrollment['total_lessons'] > 0): ?>
                                            <div class="mt-2">
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($enrollment['completed_lessons'] / $enrollment['total_lessons']) * 100; ?>%"></div>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1"><?php echo round(($enrollment['completed_lessons'] / $enrollment['total_lessons']) * 100); ?>% complete</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="student_details.php?id=<?php echo $enrollment['student_id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <div class="px-4 py-8 text-center">
                <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No students enrolled in this course.</p>
                <p class="text-sm text-gray-400 mt-2">Students will appear here once they enroll.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 