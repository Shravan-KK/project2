<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$page_title = 'Grades - Teacher';

// Get students with their grades for teacher's courses
$sql = "SELECT DISTINCT u.*, 
        (SELECT COUNT(DISTINCT c.id) FROM courses c JOIN enrollments e ON c.id = e.course_id WHERE e.student_id = u.id AND c.teacher_id = ?) as enrolled_courses,
        (SELECT AVG(CASE WHEN s.points_earned IS NOT NULL THEN (s.points_earned / a.max_points) * 100 ELSE NULL END) 
         FROM submissions s 
         JOIN assignments a ON s.assignment_id = a.id 
         JOIN courses c ON a.course_id = c.id 
         WHERE s.student_id = u.id AND c.teacher_id = ?) as avg_assignment_grade,
        (SELECT AVG(qa.score) 
         FROM quiz_attempts qa 
         JOIN quizzes q ON qa.quiz_id = q.id 
         LEFT JOIN courses c ON q.course_id = c.id 
         WHERE qa.student_id = u.id AND c.teacher_id = ?) as avg_quiz_grade,
        (SELECT COUNT(*) 
         FROM submissions s 
         JOIN assignments a ON s.assignment_id = a.id 
         JOIN courses c ON a.course_id = c.id 
         WHERE s.student_id = u.id AND c.teacher_id = ? AND s.points_earned IS NOT NULL) as graded_assignments,
        (SELECT COUNT(*) 
         FROM quiz_attempts qa 
         JOIN quizzes q ON qa.quiz_id = q.id 
         LEFT JOIN courses c ON q.course_id = c.id 
         WHERE qa.student_id = u.id AND c.teacher_id = ?) as quiz_attempts
        FROM users u 
        JOIN enrollments e ON u.id = e.student_id 
        JOIN courses c ON e.course_id = c.id 
        WHERE c.teacher_id = ? AND e.status = 'active' AND u.user_type = 'student'
        ORDER BY u.name ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiiiii", $teacher_id, $teacher_id, $teacher_id, $teacher_id, $teacher_id, $teacher_id);
$stmt->execute();
$students = $stmt->get_result();

// Get recent assignment submissions
$recent_submissions_sql = "SELECT s.*, u.name as student_name, a.title as assignment_title, c.title as course_title
                          FROM submissions s
                          JOIN users u ON s.student_id = u.id
                          JOIN assignments a ON s.assignment_id = a.id
                          JOIN courses c ON a.course_id = c.id
                          WHERE c.teacher_id = ? AND s.points_earned IS NULL
                          ORDER BY s.submitted_time DESC
                          LIMIT 10";
$recent_submissions_stmt = $conn->prepare($recent_submissions_sql);
$recent_submissions_stmt->bind_param("i", $teacher_id);
$recent_submissions_stmt->execute();
$recent_submissions = $recent_submissions_stmt->get_result();
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Student Grades</h1>
                    <p class="mt-2 text-gray-600">Track and manage student performance</p>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
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
                            <i class="fas fa-clipboard-check text-green-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Pending Grades</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $recent_submissions->num_rows; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-star text-yellow-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Avg Assignment Grade</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php 
                                    $total_avg = 0;
                                    $count = 0;
                                    $students->data_seek(0);
                                    while ($student = $students->fetch_assoc()) {
                                        if ($student['avg_assignment_grade'] !== null) {
                                            $total_avg += $student['avg_assignment_grade'];
                                            $count++;
                                        }
                                    }
                                    echo $count > 0 ? round($total_avg / $count, 1) . '%' : 'N/A';
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Avg Quiz Grade</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php 
                                    $total_quiz_avg = 0;
                                    $quiz_count = 0;
                                    $students->data_seek(0);
                                    while ($student = $students->fetch_assoc()) {
                                        if ($student['avg_quiz_grade'] !== null) {
                                            $total_quiz_avg += $student['avg_quiz_grade'];
                                            $quiz_count++;
                                        }
                                    }
                                    echo $quiz_count > 0 ? round($total_quiz_avg / $quiz_count, 1) . '%' : 'N/A';
                                    ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Students Grades -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    Student Performance (<?php echo $students->num_rows; ?> students)
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
                                        <div class="mt-2 grid grid-cols-4 gap-4 text-sm">
                                            <div>
                                                <span class="text-gray-500">Assignment Grade:</span>
                                                <span class="ml-1 font-medium <?php echo $student['avg_assignment_grade'] >= 80 ? 'text-green-600' : ($student['avg_assignment_grade'] >= 60 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                                    <?php echo $student['avg_assignment_grade'] ? round($student['avg_assignment_grade'], 1) . '%' : 'N/A'; ?>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Quiz Grade:</span>
                                                <span class="ml-1 font-medium <?php echo $student['avg_quiz_grade'] >= 80 ? 'text-green-600' : ($student['avg_quiz_grade'] >= 60 ? 'text-yellow-600' : 'text-red-600'); ?>">
                                                    <?php echo $student['avg_quiz_grade'] ? round($student['avg_quiz_grade'], 1) . '%' : 'N/A'; ?>
                                                </span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Graded Assignments:</span>
                                                <span class="ml-1 font-medium"><?php echo $student['graded_assignments']; ?></span>
                                            </div>
                                            <div>
                                                <span class="text-gray-500">Quiz Attempts:</span>
                                                <span class="ml-1 font-medium"><?php echo $student['quiz_attempts']; ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="student_grades.php?student_id=<?php echo $student['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                        <i class="fas fa-chart-line mr-2"></i>
                                        View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="px-6 py-8 text-center">
                        <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No students enrolled in your courses yet.</p>
                        <p class="text-sm text-gray-400 mt-2">Student grades will appear here once they enroll and complete assignments.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Submissions Needing Grades -->
        <?php if ($recent_submissions->num_rows > 0): ?>
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Recent Submissions Needing Grades (<?php echo $recent_submissions->num_rows; ?> total)
                    </h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php while ($submission = $recent_submissions->fetch_assoc()): ?>
                        <div class="px-6 py-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($submission['assignment_title']); ?></h4>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                                <i class="fas fa-clock mr-1"></i> Pending
                                            </span>
                                        </div>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($submission['course_title']); ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($submission['student_name']); ?></span>
                                        <span class="ml-4"><i class="fas fa-calendar mr-1"></i> Submitted: <?php echo formatDate($submission['submitted_time']); ?></span>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <a href="grade_submission.php?submission_id=<?php echo $submission['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                        <i class="fas fa-star mr-2"></i>
                                        Grade
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>


<?php require_once '../includes/footer.php'; ?>