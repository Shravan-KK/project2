<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$course_id = $_GET['id'] ?? null;
if (!$course_id) {
    header('Location: courses.php');
    exit;
}

$page_title = 'Course Details - Admin';

// Get course information
$course_sql = "SELECT c.*, u.name as teacher_name, u.email as teacher_email
               FROM courses c
               LEFT JOIN users u ON c.teacher_id = u.id
               WHERE c.id = ?";
$course_stmt = $conn->prepare($course_sql);
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: courses.php');
    exit;
}

// Get course statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT e.student_id) as total_students,
    COUNT(DISTINCT l.id) as total_lessons,
    COUNT(DISTINCT a.id) as total_assignments,
    AVG(e.progress) as avg_progress,
    COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.student_id END) as active_students,
    COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.student_id END) as completed_students
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN assignments a ON c.id = a.course_id
    WHERE c.id = ?
    GROUP BY c.id";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $course_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get enrolled students
$students_sql = "SELECT e.*, u.name, u.email, u.created_at as joined_date
                 FROM enrollments e
                 JOIN users u ON e.student_id = u.id
                 WHERE e.course_id = ? AND e.status = 'active'
                 ORDER BY e.enrollment_date DESC";
$students_stmt = $conn->prepare($students_sql);
$students_stmt->bind_param("i", $course_id);
$students_stmt->execute();
$students = $students_stmt->get_result();

// Get course lessons
$lessons_sql = "SELECT * FROM lessons WHERE course_id = ? ORDER BY order_number ASC";
$lessons_stmt = $conn->prepare($lessons_sql);
$lessons_stmt->bind_param("i", $course_id);
$lessons_stmt->execute();
$lessons = $lessons_stmt->get_result();

// Get course assignments
$assignments_sql = "SELECT a.*, 
                    (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
                    FROM assignments a
                    WHERE a.course_id = ?
                    ORDER BY a.due_date ASC";
$assignments_stmt = $conn->prepare($assignments_sql);
$assignments_stmt->bind_param("i", $course_id);
$assignments_stmt->execute();
$assignments = $assignments_stmt->get_result();
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h1>
                <p class="mt-2 text-gray-600">Course Details and Analytics</p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="?edit=<?php echo $course['id']; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Course
                </a>
                <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Courses
                </a>
            </div>
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
                    <dd class="mt-1 text-sm text-gray-900">
                        <?php if ($course['teacher_name']): ?>
                            <?php echo htmlspecialchars($course['teacher_name']); ?> (<?php echo htmlspecialchars($course['teacher_email']); ?>)
                        <?php else: ?>
                            Not assigned
                        <?php endif; ?>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Duration</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($course['duration'] ?? 'Not specified'); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Price</dt>
                    <dd class="mt-1 text-sm text-gray-900">$<?php echo number_format($course['price'], 2); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $course['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($course['status']); ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Created</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo formatDate($course['created_at']); ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($course['description'] ?? 'No description available'); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Course Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_students'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-book text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Lessons</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_lessons'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-file-alt text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Assignments</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_assignments'] ?? 0; ?></dd>
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

    <!-- Enrolled Students -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Enrolled Students (<?php echo $students->num_rows; ?>)</h3>
        </div>
        <div class="border-t border-gray-200">
            <?php if ($students->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></h4>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($student['email']); ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i> Enrolled: <?php echo formatDate($student['enrollment_date']); ?></span>
                                        <span class="ml-4"><i class="fas fa-percentage mr-1"></i> Progress: <?php echo $student['progress'] ?? 0; ?>%</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="../admin/student_details.php?id=<?php echo $student['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <p class="text-gray-500">No students enrolled in this course.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Course Lessons -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Course Lessons (<?php echo $lessons->num_rows; ?>)</h3>
        </div>
        <div class="border-t border-gray-200">
            <?php if ($lessons->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($lesson = $lessons->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($lesson['content'] ?? '', 0, 100)) . '...'; ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-sort mr-1"></i> Order: <?php echo $lesson['order_number']; ?></span>
                                        <span class="ml-4"><i class="fas fa-clock mr-1"></i> Duration: <?php echo $lesson['duration']; ?> minutes</span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <p class="text-gray-500">No lessons available for this course.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Course Assignments -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Course Assignments (<?php echo $assignments->num_rows; ?>)</h3>
        </div>
        <div class="border-t border-gray-200">
            <?php if ($assignments->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($assignment = $assignments->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($assignment['description'] ?? '', 0, 100)) . '...'; ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i> Due: <?php echo formatDate($assignment['due_date']); ?></span>
                                        <span class="ml-4"><i class="fas fa-file mr-1"></i> Submissions: <?php echo $assignment['submission_count']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <p class="text-gray-500">No assignments available for this course.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 