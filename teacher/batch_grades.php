<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$batch_id = $_GET['batch_id'] ?? null;
if (!$batch_id) {
    header('Location: batches.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$page_title = 'Batch Grades - Teacher';

// Verify teacher has access to this batch
$batch_access_sql = "SELECT b.*, c.title as course_title FROM batches b 
                     JOIN batch_courses bc ON b.id = bc.batch_id 
                     JOIN courses c ON bc.course_id = c.id 
                     WHERE b.id = ? AND c.teacher_id = ?";
$batch_access_stmt = $conn->prepare($batch_access_sql);
$batch_access_stmt->bind_param("ii", $batch_id, $teacher_id);
$batch_access_stmt->execute();
$batch = $batch_access_stmt->get_result()->fetch_assoc();

if (!$batch) {
    header('Location: batches.php');
    exit;
}

// Get batch assignments with fallback logic
try {
    $assignments_sql = "SELECT a.*, 
                        (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count,
                        (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id AND grade IS NOT NULL) as graded_count
                        FROM assignments a
                        WHERE a.batch_id = ?
                        ORDER BY a.due_date ASC";
    $assignments_stmt = $conn->prepare($assignments_sql);
    $assignments_stmt->bind_param("i", $batch_id);
    $assignments_stmt->execute();
    $assignments = $assignments_stmt->get_result();
} catch (Exception $e) {
    // Fallback: get assignments for teacher's courses
    $assignments_sql = "SELECT a.*, 
                        (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count,
                        (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id AND grade IS NOT NULL) as graded_count
                        FROM assignments a
                        WHERE a.course_id IN (SELECT id FROM courses WHERE teacher_id = ?)
                        ORDER BY a.due_date ASC";
    $assignments_stmt = $conn->prepare($assignments_sql);
    $assignments_stmt->bind_param("i", $teacher_id);
    $assignments_stmt->execute();
    $assignments = $assignments_stmt->get_result();
}

// Get student grades summary with fallback logic
try {
    // First try with batch_id if it exists
    $student_grades_sql = "SELECT 
        e.student_id,
        u.name,
        u.email,
        e.progress,
        COUNT(DISTINCT a.id) as total_assignments,
        COUNT(DISTINCT s.assignment_id) as submitted_assignments,
        COUNT(DISTINCT CASE WHEN s.grade IS NOT NULL THEN s.assignment_id END) as graded_assignments,
        AVG(s.grade) as avg_grade,
        SUM(s.grade) as total_points,
        SUM(a.total_points) as max_possible_points
        FROM enrollments e
        JOIN users u ON e.student_id = u.id
        LEFT JOIN assignments a ON a.batch_id = e.batch_id
        LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = e.student_id
        WHERE e.batch_id = ? AND e.status = 'active'
        GROUP BY e.student_id, u.name, u.email, e.progress
        ORDER BY avg_grade DESC";
    $student_grades_stmt = $conn->prepare($student_grades_sql);
    $student_grades_stmt->bind_param("i", $batch_id);
    $student_grades_stmt->execute();
    $student_grades = $student_grades_stmt->get_result();
} catch (Exception $e) {
    // Fallback: Use course relationship through batch_courses
    try {
        $student_grades_sql = "SELECT 
            e.student_id,
            u.name,
            u.email,
            e.progress,
            COUNT(DISTINCT a.id) as total_assignments,
            COUNT(DISTINCT s.assignment_id) as submitted_assignments,
            COUNT(DISTINCT CASE WHEN s.grade IS NOT NULL THEN s.assignment_id END) as graded_assignments,
            AVG(s.grade) as avg_grade,
            SUM(s.grade) as total_points,
            SUM(a.total_points) as max_possible_points
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            LEFT JOIN batch_courses bc ON bc.batch_id = e.batch_id
            LEFT JOIN assignments a ON a.course_id = bc.course_id
            LEFT JOIN submissions s ON s.assignment_id = a.id AND s.student_id = e.student_id
            WHERE e.batch_id = ? AND e.status = 'active'
            GROUP BY e.student_id, u.name, u.email, e.progress
            ORDER BY avg_grade DESC";
        $student_grades_stmt = $conn->prepare($student_grades_sql);
        $student_grades_stmt->bind_param("i", $batch_id);
        $student_grades_stmt->execute();
        $student_grades = $student_grades_stmt->get_result();
    } catch (Exception $e2) {
        // Final fallback: Simple enrollment data
        $student_grades_sql = "SELECT 
            e.student_id,
            u.name,
            u.email,
            e.progress,
            0 as total_assignments,
            0 as submitted_assignments,
            0 as graded_assignments,
            NULL as avg_grade,
            0 as total_points,
            0 as max_possible_points
            FROM enrollments e
            JOIN users u ON e.student_id = u.id
            WHERE e.batch_id = ? AND e.status = 'active'
            ORDER BY u.name ASC";
        $student_grades_stmt = $conn->prepare($student_grades_sql);
        $student_grades_stmt->bind_param("i", $batch_id);
        $student_grades_stmt->execute();
        $student_grades = $student_grades_stmt->get_result();
    }
}

// Get grade statistics with fallback logic
try {
    $grade_stats_sql = "SELECT 
        COUNT(DISTINCT s.student_id) as students_with_grades,
        AVG(s.grade) as overall_avg_grade,
        MIN(s.grade) as lowest_grade,
        MAX(s.grade) as highest_grade,
        COUNT(CASE WHEN s.grade >= 90 THEN 1 END) as a_grades,
        COUNT(CASE WHEN s.grade >= 80 AND s.grade < 90 THEN 1 END) as b_grades,
        COUNT(CASE WHEN s.grade >= 70 AND s.grade < 80 THEN 1 END) as c_grades,
        COUNT(CASE WHEN s.grade >= 60 AND s.grade < 70 THEN 1 END) as d_grades,
        COUNT(CASE WHEN s.grade < 60 THEN 1 END) as f_grades
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        WHERE a.batch_id = ? AND s.grade IS NOT NULL";
    $grade_stats_stmt = $conn->prepare($grade_stats_sql);
    $grade_stats_stmt->bind_param("i", $batch_id);
    $grade_stats_stmt->execute();
    $grade_stats = $grade_stats_stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    // Fallback: use default values
    $grade_stats = [
        'students_with_grades' => 0,
        'overall_avg_grade' => 0,
        'lowest_grade' => 0,
        'highest_grade' => 0,
        'a_grades' => 0,
        'b_grades' => 0,
        'c_grades' => 0,
        'd_grades' => 0,
        'f_grades' => 0
    ];
}
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Batch Grades</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($batch['name']); ?> - <?php echo htmlspecialchars($batch['course_title']); ?></p>
            </div>
            <a href="batches.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Batches
            </a>
        </div>
    </div>

    <!-- Grade Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-star text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Overall Average</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo round($grade_stats['overall_avg_grade'] ?? 0, 1); ?>%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-trophy text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Highest Grade</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo round($grade_stats['highest_grade'] ?? 0, 1); ?>%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Students Graded</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $grade_stats['students_with_grades'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-chart-bar text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Assignments</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $assignments->num_rows; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Grade Distribution -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Grade Distribution</h3>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
                <div class="text-center">
                    <div class="text-2xl font-bold text-green-600"><?php echo $grade_stats['a_grades'] ?? 0; ?></div>
                    <div class="text-sm text-gray-500">A (90-100%)</div>
                    <div class="mt-2 bg-green-100 rounded-full h-2">
                        <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($grade_stats['a_grades'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-blue-600"><?php echo $grade_stats['b_grades'] ?? 0; ?></div>
                    <div class="text-sm text-gray-500">B (80-89%)</div>
                    <div class="mt-2 bg-blue-100 rounded-full h-2">
                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($grade_stats['b_grades'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-yellow-600"><?php echo $grade_stats['c_grades'] ?? 0; ?></div>
                    <div class="text-sm text-gray-500">C (70-79%)</div>
                    <div class="mt-2 bg-yellow-100 rounded-full h-2">
                        <div class="bg-yellow-600 h-2 rounded-full" style="width: <?php echo ($grade_stats['c_grades'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-orange-600"><?php echo $grade_stats['d_grades'] ?? 0; ?></div>
                    <div class="text-sm text-gray-500">D (60-69%)</div>
                    <div class="mt-2 bg-orange-100 rounded-full h-2">
                        <div class="bg-orange-600 h-2 rounded-full" style="width: <?php echo ($grade_stats['d_grades'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                    </div>
                </div>
                
                <div class="text-center">
                    <div class="text-2xl font-bold text-red-600"><?php echo $grade_stats['f_grades'] ?? 0; ?></div>
                    <div class="text-sm text-gray-500">F (<60%)</div>
                    <div class="mt-2 bg-red-100 rounded-full h-2">
                        <div class="bg-red-600 h-2 rounded-full" style="width: <?php echo ($grade_stats['f_grades'] ?? 0) > 0 ? 100 : 0; ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Student Grades Table -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Student Grades Summary</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Course Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Assignments</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Average Grade</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Points</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($student_grades->num_rows > 0): ?>
                        <?php while ($student = $student_grades->fetch_assoc()): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-10 w-10">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-user text-blue-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($student['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-16 bg-gray-200 rounded-full h-2 mr-3">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $student['progress'] ?? 0; ?>%"></div>
                                        </div>
                                        <span class="text-sm text-gray-900"><?php echo round($student['progress'] ?? 0, 1); ?>%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $student['submitted_assignments']; ?>/<?php echo $student['total_assignments']; ?> submitted
                                    <br>
                                    <span class="text-xs text-gray-500">
                                        <?php echo $student['graded_assignments']; ?> graded
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if ($student['avg_grade'] !== null): ?>
                                        <span class="font-medium"><?php echo round($student['avg_grade'], 1); ?>%</span>
                                    <?php else: ?>
                                        <span class="text-gray-400">No grades</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php if ($student['total_points'] !== null && $student['max_possible_points'] > 0): ?>
                                        <?php echo $student['total_points']; ?>/<?php echo $student['max_possible_points']; ?>
                                        <br>
                                        <span class="text-xs text-gray-500">
                                            <?php echo round(($student['total_points'] / $student['max_possible_points']) * 100, 1); ?>%
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">N/A</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="student_grades.php?student_id=<?php echo $student['student_id']; ?>&batch_id=<?php echo $batch_id; ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye mr-1"></i>View Details
                                    </a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                No students enrolled in this batch.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 