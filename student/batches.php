<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$page_title = 'My Batches - Student';

// Get student's enrolled batches with progress
$sql = "SELECT b.*, c.title as course_title, u.name as instructor_name,
        COUNT(DISTINCT cs.id) as total_sessions,
        COUNT(DISTINCT CASE WHEN cs.status = 'completed' THEN cs.id END) as completed_sessions,
        COUNT(DISTINCT ce.student_id) as total_students,
        e.progress as batch_progress
        FROM enrollments e 
        JOIN batches b ON e.batch_id = b.id 
        JOIN batch_courses bc ON b.id = bc.batch_id
        JOIN courses c ON bc.course_id = c.id
        LEFT JOIN users u ON c.teacher_id = u.id
        LEFT JOIN class_sessions cs ON b.id = cs.class_id
        LEFT JOIN class_enrollments ce ON b.id = ce.class_id AND ce.status = 'active'
        WHERE e.student_id = ? AND e.status = 'active' AND e.batch_id IS NOT NULL
        GROUP BY b.id, c.title, u.name, e.progress
        ORDER BY b.start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$enrolled_batches = $stmt->get_result();

// Get batch statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT e.batch_id) as total_batches,
    AVG(e.progress) as avg_progress,
    COUNT(DISTINCT CASE WHEN b.status = 'active' THEN b.id END) as active_batches,
    COUNT(DISTINCT CASE WHEN b.status = 'completed' THEN b.id END) as completed_batches
    FROM enrollments e 
    JOIN batches b ON e.batch_id = b.id 
    WHERE e.student_id = ? AND e.status = 'active' AND e.batch_id IS NOT NULL";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $student_id);
$stats_stmt->execute();
$batch_stats = $stats_stmt->get_result()->fetch_assoc();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Batches</h1>
                <p class="mt-2 text-gray-600">View your enrolled batches and track your progress</p>
            </div>
        </div>

        <!-- Batch Statistics -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
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
                            <i class="fas fa-play-circle text-green-600 text-2xl"></i>
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
                            <i class="fas fa-check-circle text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $batch_stats['completed_batches'] ?: 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-purple-600 text-2xl"></i>
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

        <!-- Enrolled Batches -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Enrolled Batches</h3>
            </div>
            <?php if ($enrolled_batches && $enrolled_batches->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($batch = $enrolled_batches->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($batch['name']); ?></h4>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $batch['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                    ($batch['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?>">
                                            <?php echo ucfirst($batch['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($batch['description']); ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($batch['course_title']); ?></span>
                                        <span class="ml-4"><i class="fas fa-chalkboard-teacher mr-1"></i> <?php echo htmlspecialchars($batch['instructor_name']); ?></span>
                                        <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo $batch['start_date']; ?> - <?php echo $batch['end_date']; ?></span>
                                        <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $batch['total_students']; ?> students</span>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="mt-3">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">Progress</span>
                                            <span class="text-gray-900 font-medium"><?php echo $batch['batch_progress'] ?: 0; ?>%</span>
                                        </div>
                                        <div class="mt-1 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $batch['batch_progress'] ?: 0; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Session Progress -->
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-video mr-1"></i> <?php echo $batch['completed_sessions']; ?>/<?php echo $batch['total_sessions']; ?> sessions completed</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="batch_details.php?id=<?php echo $batch['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="course_content.php?course_id=<?php echo $batch['course_id']; ?>" class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-play"></i>
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <i class="fas fa-layer-group text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">You are not enrolled in any batches.</p>
                    <p class="text-sm text-gray-400 mt-2">Enroll in courses with batch assignments to see them here.</p>
                    <a href="courses.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Browse Courses
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Batches for Enrollment -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Available Batches</h3>
                <p class="mt-1 text-sm text-gray-500">Batches you can enroll in</p>
            </div>
            <?php
            // Get available batches for courses the student is enrolled in
            $available_sql = "SELECT b.id, b.name, b.description, b.start_date, b.end_date, b.max_students, b.current_students, b.status, b.created_at, b.updated_at, 
                            c.title as course_title, u.name as instructor_name,
                            COUNT(DISTINCT ce.student_id) as enrolled_students
                            FROM batches b 
                            JOIN batch_courses bc ON b.id = bc.batch_id
                            JOIN courses c ON bc.course_id = c.id
                            LEFT JOIN users u ON c.teacher_id = u.id
                            LEFT JOIN class_enrollments ce ON b.id = ce.class_id AND ce.status = 'active'
                            WHERE b.status = 'active' 
                            AND b.id NOT IN (SELECT batch_id FROM enrollments WHERE student_id = ? AND batch_id IS NOT NULL)
                            AND c.id IN (SELECT course_id FROM enrollments WHERE student_id = ? AND status = 'active')
                            GROUP BY b.id, b.name, b.description, b.start_date, b.end_date, b.max_students, b.current_students, b.status, b.created_at, b.updated_at, c.title, u.name
                            HAVING enrolled_students < b.max_students
                            ORDER BY b.start_date ASC";
            $available_stmt = $conn->prepare($available_sql);
            $available_stmt->bind_param("ii", $student_id, $student_id);
            $available_stmt->execute();
            $available_batches = $available_stmt->get_result();
            ?>
            
            <?php if ($available_batches && $available_batches->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($batch = $available_batches->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($batch['name']); ?></h4>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                            Available
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($batch['description']); ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($batch['course_title']); ?></span>
                                        <span class="ml-4"><i class="fas fa-chalkboard-teacher mr-1"></i> <?php echo htmlspecialchars($batch['instructor_name']); ?></span>
                                        <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo $batch['start_date']; ?> - <?php echo $batch['end_date']; ?></span>
                                        <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $batch['enrolled_students']; ?>/<?php echo $batch['max_students']; ?> students</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="enroll_in_batch.php?batch_id=<?php echo $batch['id']; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 text-sm">
                                        <i class="fas fa-plus mr-1"></i>Join Batch
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <i class="fas fa-info-circle text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">No available batches for enrollment.</p>
                    <p class="text-sm text-gray-400 mt-2">You may need to enroll in more courses to see available batches.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 