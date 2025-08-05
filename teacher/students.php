<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$page_title = 'My Students - Teacher';

// Get students enrolled in teacher's courses
$sql = "SELECT DISTINCT u.*, 
        (SELECT COUNT(DISTINCT c.id) FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE e.student_id = u.id AND c.teacher_id = ?) as enrolled_courses,
        (SELECT COUNT(*) FROM student_progress sp JOIN courses c ON sp.course_id = c.id WHERE sp.student_id = u.id AND c.teacher_id = ? AND sp.lesson_completed = 1) as completed_lessons,
        (SELECT COUNT(*) FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id LEFT JOIN courses c ON q.course_id = c.id WHERE qa.student_id = u.id AND c.teacher_id = ?) as quiz_attempts,
        (SELECT AVG(qa.score) FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id LEFT JOIN courses c ON q.course_id = c.id WHERE qa.student_id = u.id AND c.teacher_id = ?) as avg_quiz_score
        FROM users u 
        JOIN enrollments e ON u.id = e.student_id 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.teacher_id = ? AND e.status = 'active' AND u.user_type = 'student'
        ORDER BY u.name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiii", $teacher_id, $teacher_id, $teacher_id, $teacher_id, $teacher_id);
$stmt->execute();
$students = $stmt->get_result();
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Students</h1>
                    <p class="mt-2 text-gray-600">Track student progress and performance</p>
                </div>
            </div>
        </div>

        <!-- Students List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    Enrolled Students (<?php echo $students->num_rows; ?> total)
                </h3>
            </div>
            <div class="divide-y divide-gray-200">
                <?php if ($students->num_rows > 0): ?>
                    <?php while ($student = $students->fetch_assoc()): ?>
                        <div class="px-6 py-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-12 w-12 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-user text-blue-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></h4>
                                            <div class="ml-2 flex items-center space-x-2">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                    <?php echo $student['enrolled_courses']; ?> courses
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($student['email']); ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-book mr-1"></i> <?php echo $student['completed_lessons']; ?> lessons completed</span>
                                            <span class="ml-4"><i class="fas fa-question-circle mr-1"></i> <?php echo $student['quiz_attempts']; ?> quiz attempts</span>
                                            <span class="ml-4"><i class="fas fa-star mr-1"></i> Avg Quiz Score: <?php echo $student['avg_quiz_score'] ? round($student['avg_quiz_score'], 1) . '%' : 'N/A'; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="student_progress.php?student_id=<?php echo $student['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                        <i class="fas fa-chart-line mr-2"></i>
                                        View Progress
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="px-6 py-8 text-center">
                        <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No students enrolled in your courses yet.</p>
                        <p class="text-sm text-gray-400 mt-2">Students will appear here once they enroll in your courses.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quick Stats -->
        <?php if ($students->num_rows > 0): ?>
            <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-users text-blue-600 text-3xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                                    <dd class="text-lg font-medium text-gray-900"><?php echo $students->num_rows; ?></dd>
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
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Lessons Completed</dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        <?php 
                                        $total_lessons = 0;
                                        $students->data_seek(0);
                                        while ($student = $students->fetch_assoc()) {
                                            $total_lessons += $student['completed_lessons'];
                                        }
                                        echo $total_lessons;
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-white overflow-hidden shadow rounded-lg">
                    <div class="p-5">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-question-circle text-purple-600 text-3xl"></i>
                            </div>
                            <div class="ml-5 w-0 flex-1">
                                <dl>
                                    <dt class="text-sm font-medium text-gray-500 truncate">Total Quiz Attempts</dt>
                                    <dd class="text-lg font-medium text-gray-900">
                                        <?php 
                                        $total_attempts = 0;
                                        $students->data_seek(0);
                                        while ($student = $students->fetch_assoc()) {
                                            $total_attempts += $student['quiz_attempts'];
                                        }
                                        echo $total_attempts;
                                        ?>
                                    </dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
