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
$page_title = 'Batch Progress - Teacher';

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

// Get batch progress statistics
$progress_stats_sql = "SELECT 
    COUNT(DISTINCT e.student_id) as total_students,
    COUNT(DISTINCT CASE WHEN e.progress >= 100 THEN e.student_id END) as completed_students,
    AVG(e.progress) as avg_progress
    FROM enrollments e
    WHERE e.batch_id = ? AND e.status = 'active'";
$progress_stats_stmt = $conn->prepare($progress_stats_sql);
$progress_stats_stmt->bind_param("i", $batch_id);
$progress_stats_stmt->execute();
$progress_stats = $progress_stats_stmt->get_result()->fetch_assoc();

// Get individual student progress
$student_progress_sql = "SELECT e.*, u.name, u.email
                         FROM enrollments e
                         JOIN users u ON e.student_id = u.id
                         WHERE e.batch_id = ? AND e.status = 'active'
                         ORDER BY e.progress DESC";
$student_progress_stmt = $conn->prepare($student_progress_sql);
$student_progress_stmt->bind_param("i", $batch_id);
$student_progress_stmt->execute();
$student_progress = $student_progress_stmt->get_result();
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Batch Progress</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($batch['name']); ?> - <?php echo htmlspecialchars($batch['course_title']); ?></p>
            </div>
            <a href="batches.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Batches
            </a>
        </div>
    </div>

    <!-- Progress Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $progress_stats['total_students'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $progress_stats['completed_students'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-percentage text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Progress</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo round($progress_stats['avg_progress'] ?? 0, 1); ?>%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Individual Student Progress -->
    <div class="bg-white shadow rounded-lg">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Student Progress Details</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if ($student_progress->num_rows > 0): ?>
                        <?php while ($student = $student_progress->fetch_assoc()): ?>
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
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php 
                                    $progress = $student['progress'] ?? 0;
                                    if ($progress >= 100) {
                                        $status_class = 'bg-green-100 text-green-800';
                                        $status_text = 'Completed';
                                    } elseif ($progress >= 75) {
                                        $status_class = 'bg-blue-100 text-blue-800';
                                        $status_text = 'High Progress';
                                    } elseif ($progress >= 50) {
                                        $status_class = 'bg-yellow-100 text-yellow-800';
                                        $status_text = 'Medium Progress';
                                    } else {
                                        $status_class = 'bg-red-100 text-red-800';
                                        $status_text = 'Low Progress';
                                    }
                                    ?>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-6 py-4 text-center text-gray-500">
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