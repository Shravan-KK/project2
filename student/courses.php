<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$page_title = 'My Courses - Student';

// Get enrolled courses with progress
$sql = "SELECT c.*, u.name as teacher_name, e.enrollment_date, e.status as enrollment_status,
        (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
        (SELECT COUNT(*) FROM student_progress WHERE student_id = ? AND course_id = c.id AND lesson_completed = 1) as completed_lessons
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        LEFT JOIN users u ON c.teacher_id = u.id
        WHERE e.student_id = ? AND e.status = 'active'
        ORDER BY e.enrollment_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$enrolled_courses = $stmt->get_result();
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Courses</h1>
                    <p class="mt-2 text-gray-600">View and continue your enrolled courses</p>
                </div>
            </div>
        </div>

        <!-- Enrolled Courses -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">
                    Enrolled Courses (<?php echo $enrolled_courses->num_rows; ?> total)
                </h3>
            </div>
            
            <?php if ($enrolled_courses->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($course = $enrolled_courses->fetch_assoc()): ?>
                        <li>
                            <div class="px-4 py-4 sm:px-6">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-graduation-cap text-blue-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center">
                                                <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h4>
                                                <div class="ml-2 flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                        <?php echo ucfirst($course['enrollment_status']); ?>
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                        <?php echo htmlspecialchars($course['category']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 100)) . '...'; ?></p>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($course['teacher_name'] ?? 'Unknown'); ?></span>
                                                <span class="ml-4"><i class="fas fa-book mr-1"></i> <?php echo $course['completed_lessons']; ?>/<?php echo $course['total_lessons']; ?> lessons completed</span>
                                                <span class="ml-4"><i class="fas fa-calendar mr-1"></i> Enrolled: <?php echo formatDate($course['enrollment_date']); ?></span>
                                            </div>
                                            <?php if ($course['total_lessons'] > 0): ?>
                                                <div class="mt-2">
                                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($course['completed_lessons'] / $course['total_lessons']) * 100; ?>%"></div>
                                                    </div>
                                                    <p class="text-xs text-gray-500 mt-1"><?php echo round(($course['completed_lessons'] / $course['total_lessons']) * 100); ?>% complete</p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="course_content.php?course_id=<?php echo $course['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            <i class="fas fa-play mr-2"></i>
                                            Continue Learning
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <i class="fas fa-graduation-cap text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">No courses enrolled yet.</p>
                    <p class="text-sm text-gray-400 mt-2">Browse available courses to get started.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>


<?php require_once '../includes/footer.php'; ?>