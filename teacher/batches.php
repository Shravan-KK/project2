<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$page_title = 'My Batches - Teacher';

// Get batches assigned to this teacher
$batches_sql = "SELECT b.*, 
                COUNT(DISTINCT ce.student_id) as total_students,
                COUNT(DISTINCT cs.id) as total_sessions,
                COUNT(DISTINCT CASE WHEN cs.status = 'completed' THEN cs.id END) as completed_sessions,
                AVG(e.progress) as avg_progress
                FROM batches b
                JOIN batch_courses bc ON b.id = bc.batch_id
                JOIN courses c ON bc.course_id = c.id
                LEFT JOIN class_enrollments ce ON b.id = ce.class_id AND ce.status = 'active'
                LEFT JOIN class_sessions cs ON b.id = cs.class_id
                LEFT JOIN enrollments e ON e.batch_id = b.id AND e.status = 'active'
                WHERE c.teacher_id = ?
                GROUP BY b.id, b.name, b.description, b.start_date, b.end_date, b.max_students, b.status, b.created_at, b.updated_at
                ORDER BY b.start_date DESC";
$batches_stmt = $conn->prepare($batches_sql);
$batches_stmt->bind_param("i", $teacher_id);
$batches_stmt->execute();
$batches = $batches_stmt->get_result();

// Get batch statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT b.id) as total_batches,
    COUNT(DISTINCT ce.student_id) as total_students,
    AVG(e.progress) as avg_progress,
    COUNT(DISTINCT CASE WHEN b.status = 'active' THEN b.id END) as active_batches
    FROM batches b
    JOIN batch_courses bc ON b.id = bc.batch_id
    JOIN courses c ON bc.course_id = c.id
    LEFT JOIN class_enrollments ce ON b.id = ce.class_id AND ce.status = 'active'
    LEFT JOIN enrollments e ON e.batch_id = b.id AND e.status = 'active'
    WHERE c.teacher_id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $teacher_id);
$stats_stmt->execute();
$batch_stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Batches</h1>
                <p class="mt-2 text-gray-600">Manage batches assigned to your courses</p>
            </div>
        </div>
    </div>

    <!-- Batch Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-layer-group text-indigo-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Batches</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $batch_stats['total_batches'] ?: 0; ?></dd>
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
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $batch_stats['total_students'] ?: 0; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-play-circle text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Active Batches</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $batch_stats['active_batches'] ?: 0; ?></dd>
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
                            <dd class="text-lg font-medium text-gray-900"><?php echo round($batch_stats['avg_progress'] ?: 0, 1); ?>%</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Batches List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Assigned Batches (<?php echo $batches->num_rows; ?> total)
            </h3>
        </div>
        
        <?php if ($batches->num_rows > 0): ?>
            <ul class="divide-y divide-gray-200">
                <?php while ($batch = $batches->fetch_assoc()): ?>
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <i class="fas fa-layer-group text-indigo-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($batch['name']); ?></h4>
                                            <div class="ml-2 flex items-center space-x-2">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $batch['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo ucfirst($batch['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($batch['description'] ?? 'No description available'); ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($batch['start_date']); ?> - <?php echo formatDate($batch['end_date']); ?></span>
                                            <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $batch['total_students']; ?> students</span>
                                            <span class="ml-4"><i class="fas fa-play mr-1"></i> <?php echo $batch['completed_sessions']; ?>/<?php echo $batch['total_sessions']; ?> sessions</span>
                                        </div>
                                        <?php if ($batch['total_sessions'] > 0): ?>
                                            <div class="mt-2">
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($batch['completed_sessions'] / $batch['total_sessions']) * 100; ?>%"></div>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1"><?php echo round(($batch['completed_sessions'] / $batch['total_sessions']) * 100); ?>% sessions completed</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="batch_details.php?id=<?php echo $batch['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="batch_students.php?batch_id=<?php echo $batch['id']; ?>" class="text-green-600 hover:text-green-900" title="View Students">
                                        <i class="fas fa-users"></i>
                                    </a>
                                    <a href="batch_progress.php?batch_id=<?php echo $batch['id']; ?>" class="text-purple-600 hover:text-purple-900" title="View Progress">
                                        <i class="fas fa-chart-line"></i>
                                    </a>
                                    <a href="batch_assignments.php?batch_id=<?php echo $batch['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="View Assignments">
                                        <i class="fas fa-file-alt"></i>
                                    </a>
                                    <a href="batch_grades.php?batch_id=<?php echo $batch['id']; ?>" class="text-yellow-600 hover:text-yellow-900" title="View Grades">
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
                <i class="fas fa-layer-group text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No batches assigned to your courses.</p>
                <p class="text-sm text-gray-400 mt-2">Batches will appear here once they are created and assigned to your courses.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 