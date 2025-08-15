<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireStudent();

$batch_id = $_GET['id'] ?? null;
if (!$batch_id) {
    header('Location: batches.php');
    exit;
}

$student_id = $_SESSION['user_id'];
$page_title = 'Batch Details - Student';

// Get batch information and verify student is enrolled
$batch_sql = "SELECT b.*, c.title as course_title, c.description as course_description, u.name as instructor_name,
               (SELECT COUNT(*) FROM class_sessions WHERE class_id = b.id) as total_sessions,
               (SELECT COUNT(*) FROM class_sessions WHERE class_id = b.id AND status = 'completed') as completed_sessions,
               e.enrollment_date, e.progress as batch_progress
               FROM batches b
               JOIN batch_courses bc ON b.id = bc.batch_id
               JOIN courses c ON bc.course_id = c.id
               LEFT JOIN users u ON c.teacher_id = u.id
               JOIN enrollments e ON e.batch_id = b.id AND e.student_id = ?
               WHERE b.id = ? AND e.status = 'active'";
$batch_stmt = $conn->prepare($batch_sql);
$batch_stmt->bind_param("ii", $student_id, $batch_id);
$batch_stmt->execute();
$batch = $batch_stmt->get_result()->fetch_assoc();

if (!$batch) {
    header('Location: batches.php');
    exit;
}

// Get upcoming class sessions
$sessions_sql = "SELECT cs.*, 
                  (SELECT COUNT(*) FROM class_attendances WHERE session_id = cs.id AND student_id = ?) as attendance_status
                  FROM class_sessions cs
                  WHERE cs.class_id = ? AND cs.status = 'scheduled'
                  ORDER BY cs.scheduled_date ASC";
$sessions_stmt = $conn->prepare($sessions_sql);
$sessions_stmt->bind_param("ii", $student_id, $batch_id);
$sessions_stmt->execute();
$sessions = $sessions_stmt->get_result();

// Get completed class sessions
$completed_sessions_sql = "SELECT cs.*, 
                          (SELECT COUNT(*) FROM class_attendances WHERE session_id = cs.id AND student_id = ?) as attendance_status
                          FROM class_sessions cs
                          WHERE cs.class_id = ? AND cs.status = 'completed'
                          ORDER BY cs.scheduled_date DESC";
$completed_sessions_stmt = $conn->prepare($completed_sessions_sql);
$completed_sessions_stmt->bind_param("ii", $student_id, $batch_id);
$completed_sessions_stmt->execute();
$completed_sessions = $completed_sessions_stmt->get_result();

// Get batch assignments
$assignments_sql = "SELECT a.*, 
                    (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id AND student_id = ?) as submission_status,
                    (SELECT grade FROM submissions WHERE assignment_id = a.id AND student_id = ?) as grade
                    FROM assignments a
                    WHERE a.batch_id = ?
                    ORDER BY a.due_date ASC";
$assignments_stmt = $conn->prepare($assignments_sql);
$assignments_stmt->bind_param("iii", $student_id, $student_id, $batch_id);
$assignments_stmt->execute();
$assignments = $assignments_stmt->get_result();
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($batch['name']); ?></h1>
                <p class="mt-2 text-gray-600">Batch Details and Progress</p>
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
                    <dt class="text-sm font-medium text-gray-500">Course</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($batch['course_title']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Instructor</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($batch['instructor_name'] ?? 'Not assigned'); ?></dd>
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
                    <dt class="text-sm font-medium text-gray-500">Enrollment Date</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo formatDate($batch['enrollment_date']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $batch['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($batch['status']); ?>
                        </span>
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($batch['description'] ?? 'No description available'); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Progress Overview -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-play-circle text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Sessions</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $batch['total_sessions']; ?></dd>
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Completed Sessions</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $batch['completed_sessions']; ?></dd>
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Progress</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $batch['batch_progress'] ?? 0; ?>%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upcoming Sessions -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Upcoming Sessions</h3>
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
                                    <?php if ($session['attendance_status'] > 0): ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                            Attended
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                            Scheduled
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <p class="text-gray-500">No upcoming sessions scheduled.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Assignments -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Assignments</h3>
        </div>
        <div class="border-t border-gray-200">
            <?php if ($assignments->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($assignment = $assignments->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($assignment['description'] ?? 'No description'); ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i> Due: <?php echo formatDate($assignment['due_date']); ?></span>
                                        <span class="ml-4"><i class="fas fa-file mr-1"></i> <?php echo $assignment['submission_status'] > 0 ? 'Submitted' : 'Not Submitted'; ?></span>
                                        <?php if ($assignment['grade'] !== null): ?>
                                            <span class="ml-4"><i class="fas fa-star mr-1"></i> Grade: <?php echo $assignment['grade']; ?>%</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <?php if ($assignment['submission_status'] > 0): ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                            Submitted
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                            Pending
                                        </span>
                                    <?php endif; ?>
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