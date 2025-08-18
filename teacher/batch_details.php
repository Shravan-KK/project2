<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$batch_id = $_GET['id'] ?? null;
if (!$batch_id) {
    header('Location: batches.php');
    exit;
}

$teacher_id = $_SESSION['user_id'];
$page_title = 'Batch Details - Teacher';

// Verify teacher has access to this batch
$batch_access_sql = "SELECT b.*, c.id as course_id, c.title as course_title FROM batches b 
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

// Get batch sessions with fallback logic
try {
    $sessions_sql = "SELECT * FROM class_sessions WHERE class_id = ? ORDER BY scheduled_date ASC";
    $sessions_stmt = $conn->prepare($sessions_sql);
    $sessions_stmt->bind_param("i", $batch_id);
    $sessions_stmt->execute();
    $sessions = $sessions_stmt->get_result();
} catch (Exception $e) {
    // Fallback: create empty result set
    $sessions_sql = "SELECT 
        0 as id, 
        ? as class_id, 
        'No sessions' as title, 
        'No sessions scheduled' as description, 
        NOW() as scheduled_date, 
        60 as duration, 
        'No location' as location, 
        'scheduled' as status 
        WHERE 1=0";
    $sessions_stmt = $conn->prepare($sessions_sql);
    $sessions_stmt->bind_param("i", $batch_id);
    $sessions_stmt->execute();
    $sessions = $sessions_stmt->get_result();
}

// Get batch assignments with fallback logic
try {
    $assignments_sql = "SELECT a.*, 
                        (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
                        FROM assignments a
                        WHERE a.batch_id = ?
                        ORDER BY a.due_date ASC";
    $assignments_stmt = $conn->prepare($assignments_sql);
    $assignments_stmt->bind_param("i", $batch_id);
    $assignments_stmt->execute();
    $assignments = $assignments_stmt->get_result();
} catch (Exception $e) {
    // Fallback: get assignments for teacher's courses
    try {
        $assignments_sql = "SELECT a.*, 
                            (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count
                            FROM assignments a
                            WHERE a.course_id IN (SELECT id FROM courses WHERE teacher_id = ?)
                            ORDER BY a.due_date ASC";
        $assignments_stmt = $conn->prepare($assignments_sql);
        $assignments_stmt->bind_param("i", $teacher_id);
        $assignments_stmt->execute();
        $assignments = $assignments_stmt->get_result();
    } catch (Exception $e2) {
        // Final fallback: empty result
        $assignments_sql = "SELECT 
            0 as id, 
            'No assignments' as title, 
            'No assignments available' as description, 
            NOW() as due_date, 
            100 as total_points, 
            0 as submission_count 
            WHERE 1=0";
        $assignments_stmt = $conn->prepare($assignments_sql);
        $assignments_stmt->execute();
        $assignments = $assignments_stmt->get_result();
    }
}
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Batch Details</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($batch['name']); ?> - <?php echo htmlspecialchars($batch['course_title']); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="course_content.php?course_id=<?php echo $batch['course_id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                    <i class="fas fa-cog mr-2"></i>
                    Customize Course
                </a>
                <a href="batches.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Batches
                </a>
            </div>
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

    <!-- Batch Sessions -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Batch Sessions (<?php echo $sessions->num_rows; ?>)</h3>
        </div>
        <div class="border-t border-gray-200">
            <?php if ($sessions->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($session = $sessions->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($session['title']); ?></h4>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($session['description'] ?? 'No description'); ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($session['scheduled_date']); ?></span>
                                        <span class="ml-4"><i class="fas fa-clock mr-1"></i> <?php echo $session['duration']; ?> minutes</span>
                                        <span class="ml-4"><i class="fas fa-map-marker-alt mr-1"></i> <?php echo htmlspecialchars($session['location'] ?? 'Online'); ?></span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $session['status'] == 'completed' ? 'bg-green-100 text-green-800' : ($session['status'] == 'scheduled' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo ucfirst($session['status']); ?>
                                    </span>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <p class="text-gray-500">No sessions scheduled for this batch.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Batch Assignments -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Batch Assignments (<?php echo $assignments->num_rows; ?>)</h3>
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
                                        <span class="ml-4"><i class="fas fa-star mr-1"></i> Points: <?php echo $assignment['total_points']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <p class="text-gray-500">No assignments available for this batch.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 