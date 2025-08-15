<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$page_title = 'My Grades - Student';

// Get overall statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT e.course_id) as total_courses,
    AVG(CASE WHEN s.points_earned IS NOT NULL THEN (s.points_earned / a.max_points) * 100 ELSE NULL END) as avg_assignment_grade,
    AVG(CASE WHEN qa.score IS NOT NULL THEN qa.score ELSE NULL END) as avg_quiz_grade
    FROM enrollments e
    LEFT JOIN assignments a ON e.course_id = a.course_id
    LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = e.student_id
    LEFT JOIN quiz_attempts qa ON e.student_id = qa.student_id
    WHERE e.student_id = ? AND e.status = 'active'";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $student_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get assignment grades
$assignments_sql = "SELECT a.title as assignment_title, c.title as course_title, 
    a.max_points, s.points_earned, s.submitted_time, s.teacher_notes,
    CASE 
        WHEN s.points_earned IS NOT NULL THEN (s.points_earned / a.max_points) * 100
        ELSE NULL 
    END as percentage
    FROM assignments a
    JOIN courses c ON a.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = e.student_id
    WHERE e.student_id = ? AND e.status = 'active' AND a.is_active = 1
    ORDER BY s.submitted_time DESC";
$assignments_stmt = $conn->prepare($assignments_sql);
$assignments_stmt->bind_param("i", $student_id);
$assignments_stmt->execute();
$assignments = $assignments_stmt->get_result();

// Get quiz grades
$quizzes_sql = "SELECT q.title as quiz_title, c.title as course_title,
    qa.score, qa.total_questions, qa.correct_answers, qa.attempted_at,
    CASE 
        WHEN qa.score IS NOT NULL AND qa.total_questions > 0 THEN (qa.correct_answers / qa.total_questions) * 100
        ELSE NULL 
    END as percentage
    FROM quiz_attempts qa
    JOIN quizzes q ON qa.quiz_id = q.id
    LEFT JOIN courses c ON q.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY qa.attempted_at DESC";
$quizzes_stmt = $conn->prepare($quizzes_sql);
$quizzes_stmt->bind_param("i", $student_id);
$quizzes_stmt->execute();
$quizzes = $quizzes_stmt->get_result();

// Get course progress
$courses_sql = "SELECT c.title as course_title, c.id as course_id,
    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
    (SELECT COUNT(*) FROM student_progress WHERE student_id = ? AND course_id = c.id AND lesson_completed = 1) as completed_lessons,
    (SELECT COUNT(*) FROM assignments WHERE course_id = c.id AND is_active = 1) as total_assignments,
    (SELECT COUNT(*) FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE s.student_id = ? AND a.course_id = c.id) as submitted_assignments
    FROM enrollments e
    JOIN courses c ON e.course_id = c.id
    WHERE e.student_id = ? AND e.status = 'active'
    ORDER BY e.enrollment_date DESC";
$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bind_param("iii", $student_id, $student_id, $student_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result();
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Grades</h1>
                    <p class="mt-2 text-gray-600">Track your academic performance and progress</p>
                </div>
            </div>
        </div>

        <!-- Overall Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-book text-blue-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Enrolled Courses</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_courses'] ?? 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-clipboard-check text-green-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Avg Assignment Grade</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php echo $stats['avg_assignment_grade'] ? round($stats['avg_assignment_grade'], 1) . '%' : 'N/A'; ?>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Avg Quiz Grade</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php echo $stats['avg_quiz_grade'] ? round($stats['avg_quiz_grade'], 1) . '%' : 'N/A'; ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Course Progress -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Course Progress</h3>
            </div>
            <div class="divide-y divide-gray-200">
                <?php if ($courses->num_rows > 0): ?>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course['course_title']); ?></h4>
                                    <div class="mt-2 grid grid-cols-3 gap-4 text-sm">
                                        <div>
                                            <span class="text-gray-500">Lessons:</span>
                                            <span class="ml-2 font-medium"><?php echo $course['completed_lessons']; ?>/<?php echo $course['total_lessons']; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Assignments:</span>
                                            <span class="ml-2 font-medium"><?php echo $course['submitted_assignments']; ?>/<?php echo $course['total_assignments']; ?></span>
                                        </div>
                                        <div>
                                            <span class="text-gray-500">Progress:</span>
                                            <span class="ml-2 font-medium">
                                                <?php 
                                                $total_items = $course['total_lessons'] + $course['total_assignments'];
                                                $completed_items = $course['completed_lessons'] + $course['submitted_assignments'];
                                                echo $total_items > 0 ? round(($completed_items / $total_items) * 100) : 0;
                                                ?>%
                                            </span>
                                        </div>
                                    </div>
                                    <?php if ($course['total_lessons'] > 0): ?>
                                        <div class="mt-2">
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($course['completed_lessons'] / $course['total_lessons']) * 100; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-4">
                                    <a href="course_content.php?course_id=<?php echo $course['course_id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        <i class="fas fa-play mr-2"></i>
                                        Continue
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="px-6 py-8 text-center">
                        <i class="fas fa-graduation-cap text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No courses enrolled yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assignment Grades -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Assignment Grades</h3>
            </div>
            <div class="divide-y divide-gray-200">
                <?php if ($assignments->num_rows > 0): ?>
                    <?php while ($assignment = $assignments->fetch_assoc()): ?>
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($assignment['assignment_title']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($assignment['course_title']); ?></p>
                                    <div class="mt-2 flex items-center space-x-4 text-sm">
                                        <span class="text-gray-500">Submitted: <?php echo formatDate($assignment['submitted_time']); ?></span>
                                        <?php if ($assignment['points_earned'] !== null): ?>
                                            <span class="font-medium text-green-600">
                                                Grade: <?php echo $assignment['points_earned']; ?>/<?php echo $assignment['max_points']; ?>
                                                (<?php echo round($assignment['percentage'], 1); ?>%)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-gray-500">Not graded yet</span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($assignment['teacher_notes']): ?>
                                        <div class="mt-2 p-3 bg-gray-50 rounded-md">
                                            <span class="text-sm font-medium text-gray-700">Teacher Notes:</span>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo nl2br(htmlspecialchars($assignment['teacher_notes'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="px-6 py-8 text-center">
                        <i class="fas fa-clipboard-list text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No assignment grades available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Quiz Grades -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">Quiz Results</h3>
            </div>
            <div class="divide-y divide-gray-200">
                <?php if ($quizzes->num_rows > 0): ?>
                    <?php while ($quiz = $quizzes->fetch_assoc()): ?>
                        <div class="px-6 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($quiz['quiz_title']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($quiz['course_title']); ?></p>
                                    <div class="mt-2 flex items-center space-x-4 text-sm">
                                        <span class="text-gray-500">Attempted: <?php echo formatDate($quiz['attempted_at']); ?></span>
                                        <span class="font-medium text-purple-600">
                                            Score: <?php echo $quiz['correct_answers']; ?>/<?php echo $quiz['total_questions']; ?>
                                            (<?php echo round($quiz['percentage'], 1); ?>%)
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="px-6 py-8 text-center">
                        <i class="fas fa-question-circle text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No quiz results available.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>


<?php require_once '../includes/footer.php'; ?>