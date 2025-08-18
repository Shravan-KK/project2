<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$page_title = 'My Batches - Teacher';

// Get batches assigned to this teacher (either as course teacher or batch instructor)
$batches_sql = "SELECT DISTINCT b.*, 
                COUNT(DISTINCT e.student_id) as total_students,
                AVG(e.progress) as avg_progress,
                CASE 
                    WHEN bi.instructor_id IS NOT NULL THEN CONCAT('Instructor (', bi.role, ')')
                    ELSE 'Course Teacher'
                END as teacher_role
                FROM batches b
                LEFT JOIN batch_instructors bi ON b.id = bi.batch_id AND bi.instructor_id = ? AND bi.status = 'active'
                LEFT JOIN batch_courses bc ON b.id = bc.batch_id AND bc.status = 'active'
                LEFT JOIN courses c ON bc.course_id = c.id AND c.teacher_id = ?
                LEFT JOIN enrollments e ON e.batch_id = b.id AND e.status = 'active'
                WHERE (bi.instructor_id = ? OR c.teacher_id = ?) AND b.status = 'active'
                GROUP BY b.id, b.name, b.description, b.start_date, b.end_date, b.max_students, b.status, b.created_at, b.updated_at, bi.instructor_id, bi.role
                ORDER BY b.start_date DESC";
$batches_stmt = $conn->prepare($batches_sql);
$batches_stmt->bind_param("iiii", $teacher_id, $teacher_id, $teacher_id, $teacher_id);
$batches_stmt->execute();
$batches = $batches_stmt->get_result();

// Get batch statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT b.id) as total_batches,
    COUNT(DISTINCT e.student_id) as total_students,
    AVG(e.progress) as avg_progress,
    COUNT(DISTINCT CASE WHEN b.status = 'active' THEN b.id END) as active_batches
    FROM batches b
    LEFT JOIN batch_instructors bi ON b.id = bi.batch_id AND bi.instructor_id = ? AND bi.status = 'active'
    LEFT JOIN batch_courses bc ON b.id = bc.batch_id AND bc.status = 'active'
    LEFT JOIN courses c ON bc.course_id = c.id AND c.teacher_id = ?
    LEFT JOIN enrollments e ON e.batch_id = b.id AND e.status = 'active'
    WHERE (bi.instructor_id = ? OR c.teacher_id = ?) AND b.status = 'active'";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("iiii", $teacher_id, $teacher_id, $teacher_id, $teacher_id);
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
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo strpos($batch['teacher_role'], 'Instructor') !== false ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'; ?>">
                                                    <?php echo htmlspecialchars($batch['teacher_role']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($batch['description'] ?? 'No description available'); ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($batch['start_date']); ?> - <?php echo formatDate($batch['end_date']); ?></span>
                                            <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $batch['total_students']; ?> students</span>
                                            <?php if (isset($batch['avg_progress']) && $batch['avg_progress'] > 0): ?>
                                                <span class="ml-4"><i class="fas fa-chart-line mr-1"></i> <?php echo round($batch['avg_progress'], 1); ?>% avg progress</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if (isset($batch['avg_progress']) && $batch['avg_progress'] > 0): ?>
                                            <div class="mt-2">
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $batch['avg_progress']; ?>%"></div>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1"><?php echo round($batch['avg_progress']); ?>% average progress</p>
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