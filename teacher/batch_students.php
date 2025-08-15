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
$page_title = 'Batch Students - Teacher';

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

// Get students enrolled in this batch (with fallback logic)
try {
    // First try with class_attendances table
    $students_sql = "SELECT e.*, u.name, u.email, u.created_at as joined_date,
                     (SELECT COUNT(*) FROM class_sessions WHERE class_id = ?) as total_sessions,
                     (SELECT COUNT(*) FROM class_attendances ca 
                      JOIN class_sessions cs ON ca.session_id = cs.id 
                      WHERE cs.class_id = ? AND ca.student_id = e.student_id) as attended_sessions
                     FROM enrollments e
                     JOIN users u ON e.student_id = u.id
                     WHERE e.batch_id = ? AND e.status = 'active'
                     ORDER BY e.enrollment_date ASC";
    $students_stmt = $conn->prepare($students_sql);
    $students_stmt->bind_param("iii", $batch_id, $batch_id, $batch_id);
    $students_stmt->execute();
    $students = $students_stmt->get_result();
} catch (Exception $e) {
    // Fallback: Get students without attendance data
    try {
        $students_sql = "SELECT e.*, u.name, u.email, u.created_at as joined_date,
                         (SELECT COUNT(*) FROM class_sessions WHERE class_id = ?) as total_sessions,
                         0 as attended_sessions
                         FROM enrollments e
                         JOIN users u ON e.student_id = u.id
                         WHERE e.batch_id = ? AND e.status = 'active'
                         ORDER BY e.enrollment_date ASC";
        $students_stmt = $conn->prepare($students_sql);
        $students_stmt->bind_param("ii", $batch_id, $batch_id);
        $students_stmt->execute();
        $students = $students_stmt->get_result();
    } catch (Exception $e2) {
        // Final fallback: Simple student list
        $students_sql = "SELECT e.*, u.name, u.email, u.created_at as joined_date,
                         0 as total_sessions,
                         0 as attended_sessions
                         FROM enrollments e
                         JOIN users u ON e.student_id = u.id
                         WHERE e.batch_id = ? AND e.status = 'active'
                         ORDER BY e.enrollment_date ASC";
        $students_stmt = $conn->prepare($students_sql);
        $students_stmt->bind_param("i", $batch_id);
        $students_stmt->execute();
        $students = $students_stmt->get_result();
    }
}

// Get batch statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT e.student_id) as total_students,
    COUNT(DISTINCT cs.id) as total_sessions,
    COUNT(DISTINCT CASE WHEN cs.status = 'completed' THEN cs.id END) as completed_sessions,
    AVG(e.progress) as avg_progress
    FROM enrollments e
    LEFT JOIN class_sessions cs ON e.batch_id = cs.class_id
    WHERE e.batch_id = ? AND e.status = 'active'";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $batch_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Batch Students</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($batch['name']); ?> - <?php echo htmlspecialchars($batch['course_title']); ?></p>
            </div>
            <a href="batches.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Batches
            </a>
        </div>
    </div>

    <!-- Batch Information -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Batch Information</h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Batch Name</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($batch['name']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Course</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($batch['course_title']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo formatDate($batch['start_date']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">End Date</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo formatDate($batch['end_date']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $batch['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($batch['status']); ?>
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Max Students</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo $batch['max_students']; ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($batch['description'] ?? 'No description available'); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Batch Statistics -->
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
                        <i class="fas fa-play-circle text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Sessions</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_sessions'] ?? 0; ?></dd>
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Completed Sessions</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['completed_sessions'] ?? 0; ?></dd>
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

    <!-- Students List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Enrolled Students (<?php echo $students->num_rows; ?> total)
            </h3>
        </div>
        
        <?php if ($students->num_rows > 0): ?>
            <ul class="divide-y divide-gray-200">
                <?php while ($student = $students->fetch_assoc()): ?>
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
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></h4>
                                            <div class="ml-2 flex items-center space-x-2">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                    <?php echo ucfirst($student['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($student['email']); ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> Enrolled: <?php echo formatDate($student['enrollment_date']); ?></span>
                                            <span class="ml-4"><i class="fas fa-play mr-1"></i> Attendance: <?php echo $student['attended_sessions']; ?>/<?php echo $student['total_sessions']; ?> sessions</span>
                                            <span class="ml-4"><i class="fas fa-percentage mr-1"></i> Progress: <?php echo $student['progress'] ?? 0; ?>%</span>
                                        </div>
                                        <?php if ($student['total_sessions'] > 0): ?>
                                            <div class="mt-2">
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($student['attended_sessions'] / $student['total_sessions']) * 100; ?>%"></div>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1"><?php echo round(($student['attended_sessions'] / $student['total_sessions']) * 100); ?>% attendance rate</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="student_progress.php?student_id=<?php echo $student['student_id']; ?>&batch_id=<?php echo $batch_id; ?>" class="text-blue-600 hover:text-blue-900" title="View Progress">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                    <a href="student_assignments.php?student_id=<?php echo $student['student_id']; ?>&batch_id=<?php echo $batch_id; ?>" class="text-green-600 hover:text-green-900" title="View Assignments">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                    <a href="student_grades.php?student_id=<?php echo $student['student_id']; ?>&batch_id=<?php echo $batch_id; ?>" class="text-purple-600 hover:text-purple-900" title="View Grades">
                                        <i class="fas fa-graduation-cap"></i>
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
                <p class="text-gray-500">No students enrolled in this batch.</p>
                <p class="text-sm text-gray-400 mt-2">Students will appear here once they enroll.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 