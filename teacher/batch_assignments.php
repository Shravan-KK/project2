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
$page_title = 'Batch Assignments - Teacher';

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
    // First try with batch_id if column exists
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
    // Fallback: try with course relationship
    try {
        $assignments_sql = "SELECT a.*, 
                            (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count,
                            (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id AND grade IS NOT NULL) as graded_count
                            FROM assignments a
                            JOIN batch_courses bc ON a.course_id = bc.course_id
                            WHERE bc.batch_id = ?
                            ORDER BY a.due_date ASC";
        $assignments_stmt = $conn->prepare($assignments_sql);
        $assignments_stmt->bind_param("i", $batch_id);
        $assignments_stmt->execute();
        $assignments = $assignments_stmt->get_result();
    } catch (Exception $e2) {
        // Final fallback: get all assignments for course
        $assignments_sql = "SELECT a.*, 
                            (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count,
                            (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id AND grade IS NOT NULL) as graded_count
                            FROM assignments a
                            WHERE a.course_id IN (SELECT course_id FROM courses WHERE teacher_id = ?)
                            ORDER BY a.due_date ASC";
        $assignments_stmt = $conn->prepare($assignments_sql);
        $assignments_stmt->bind_param("i", $teacher_id);
        $assignments_stmt->execute();
        $assignments = $assignments_stmt->get_result();
    }
}

// Get assignment statistics with fallback logic
try {
    $assignment_stats_sql = "SELECT 
        COUNT(*) as total_assignments,
        COUNT(CASE WHEN due_date >= CURDATE() THEN 1 END) as upcoming_assignments,
        COUNT(CASE WHEN due_date < CURDATE() THEN 1 END) as past_due_assignments,
        AVG(total_points) as avg_points
        FROM assignments 
        WHERE batch_id = ?";
    $assignment_stats_stmt = $conn->prepare($assignment_stats_sql);
    $assignment_stats_stmt->bind_param("i", $batch_id);
    $assignment_stats_stmt->execute();
    $assignment_stats = $assignment_stats_stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    // Fallback: use simple count
    $assignment_stats = [
        'total_assignments' => $assignments->num_rows,
        'upcoming_assignments' => 0,
        'past_due_assignments' => 0,
        'avg_points' => 100
    ];
}
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Batch Assignments</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($batch['name']); ?> - <?php echo htmlspecialchars($batch['course_title']); ?></p>
            </div>
            <a href="batches.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Batches
            </a>
        </div>
    </div>

    <!-- Assignment Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-file-alt text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Assignments</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $assignment_stats['total_assignments'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Upcoming</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $assignment_stats['upcoming_assignments'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-triangle text-red-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Past Due</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $assignment_stats['past_due_assignments'] ?? 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-star text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Avg Points</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo round($assignment_stats['avg_points'] ?? 0, 0); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Assignments List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Assignments (<?php echo $assignments->num_rows; ?> total)
            </h3>
        </div>
        
        <?php if ($assignments->num_rows > 0): ?>
            <ul class="divide-y divide-gray-200">
                <?php while ($assignment = $assignments->fetch_assoc()): ?>
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <i class="fas fa-file-alt text-blue-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                            <div class="ml-2 flex items-center space-x-2">
                                                <?php 
                                                $due_date = new DateTime($assignment['due_date']);
                                                $now = new DateTime();
                                                $is_overdue = $due_date < $now;
                                                ?>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $is_overdue ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800'; ?>">
                                                    <?php echo $is_overdue ? 'Overdue' : 'Active'; ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($assignment['description'] ?? '', 0, 100)) . '...'; ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> Due: <?php echo formatDate($assignment['due_date']); ?></span>
                                            <span class="ml-4"><i class="fas fa-star mr-1"></i> Points: <?php echo $assignment['total_points']; ?></span>
                                            <span class="ml-4"><i class="fas fa-file mr-1"></i> Submissions: <?php echo $assignment['submission_count']; ?></span>
                                            <span class="ml-4"><i class="fas fa-check mr-1"></i> Graded: <?php echo $assignment['graded_count']; ?></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="assignment_submissions.php?assignment_id=<?php echo $assignment['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Submissions">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit Assignment">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <div class="px-4 py-8 text-center">
                <i class="fas fa-file-alt text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No assignments created for this batch.</p>
                <p class="text-sm text-gray-400 mt-2">Create assignments to help students learn and assess their progress.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 