<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$page_title = 'My Classes - Student';

// Get student's enrolled classes with progress
$sql = "SELECT c.*, co.title as course_title, u.name as instructor_name,
        COUNT(DISTINCT cs.id) as total_sessions,
        COUNT(DISTINCT CASE WHEN cs.status = 'completed' THEN cs.id END) as completed_sessions,
        COUNT(DISTINCT ce2.student_id) as total_students,
        ce.progress as class_progress
        FROM class_enrollments ce 
        JOIN classes c ON ce.class_id = c.id 
        JOIN courses co ON c.course_id = co.id
        LEFT JOIN users u ON c.instructor_id = u.id
        LEFT JOIN class_sessions cs ON c.id = cs.class_id
        LEFT JOIN class_enrollments ce2 ON c.id = ce2.class_id AND ce2.status = 'active'
        WHERE ce.student_id = ? AND ce.status = 'active'
        GROUP BY c.id, co.title, u.name, ce.progress
        ORDER BY c.start_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$enrolled_classes = $stmt->get_result();

// Get class statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT ce.class_id) as total_classes,
    AVG(ce.progress) as avg_progress,
    COUNT(DISTINCT CASE WHEN c.status = 'active' THEN c.id END) as active_classes,
    COUNT(DISTINCT CASE WHEN c.status = 'completed' THEN c.id END) as completed_classes
    FROM class_enrollments ce 
    JOIN classes c ON ce.class_id = c.id 
    WHERE ce.student_id = ? AND ce.status = 'active'";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $student_id);
$stats_stmt->execute();
$class_stats = $stats_stmt->get_result()->fetch_assoc();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Classes</h1>
                <p class="mt-2 text-gray-600">View your enrolled instructor-led classes and sessions</p>
            </div>
        </div>

        <!-- Class Statistics -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chalkboard-teacher text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Classes</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $class_stats['total_classes'] ?: 0; ?></dd>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Classes</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $class_stats['active_classes'] ?: 0; ?></dd>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $class_stats['completed_classes'] ?: 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-orange-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Avg Progress</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo round($class_stats['avg_progress'] ?: 0, 1); ?>%</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Enrolled Classes -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Enrolled Classes</h3>
            </div>
            <?php if ($enrolled_classes && $enrolled_classes->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($class = $enrolled_classes->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($class['name']); ?></h4>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $class['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                    ($class['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?>">
                                            <?php echo ucfirst($class['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($class['description']); ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($class['course_title']); ?></span>
                                        <span class="ml-4"><i class="fas fa-chalkboard-teacher mr-1"></i> <?php echo htmlspecialchars($class['instructor_name']); ?></span>
                                        <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo $class['start_date']; ?> - <?php echo $class['end_date']; ?></span>
                                        <span class="ml-4"><i class="fas fa-clock mr-1"></i> <?php echo htmlspecialchars($class['schedule']); ?></span>
                                        <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $class['total_students']; ?>/<?php echo $class['max_students']; ?> students</span>
                                    </div>
                                    
                                    <!-- Progress Bar -->
                                    <div class="mt-3">
                                        <div class="flex items-center justify-between text-sm">
                                            <span class="text-gray-600">Progress</span>
                                            <span class="text-gray-900 font-medium"><?php echo $class['class_progress'] ?: 0; ?>%</span>
                                        </div>
                                        <div class="mt-1 bg-gray-200 rounded-full h-2">
                                            <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $class['class_progress'] ?: 0; ?>%"></div>
                                        </div>
                                    </div>
                                    
                                    <!-- Session Progress -->
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-video mr-1"></i> <?php echo $class['completed_sessions']; ?>/<?php echo $class['total_sessions']; ?> sessions completed</span>
                                        <?php if ($class['meeting_platform']): ?>
                                            <span class="ml-4"><i class="fas fa-video-camera mr-1"></i> <?php echo htmlspecialchars($class['meeting_platform']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="class_details.php?id=<?php echo $class['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="class_sessions.php?class_id=<?php echo $class['id']; ?>" class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                    <?php if ($class['meeting_link']): ?>
                                        <a href="<?php echo htmlspecialchars($class['meeting_link']); ?>" target="_blank" class="text-purple-600 hover:text-purple-900">
                                            <i class="fas fa-video"></i>
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <i class="fas fa-chalkboard-teacher text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">You are not enrolled in any classes.</p>
                    <p class="text-sm text-gray-400 mt-2">Enroll in instructor-led classes to see them here.</p>
                    <a href="courses.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        Browse Courses
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Upcoming Sessions -->
        <?php
        // Get upcoming sessions for enrolled classes
        $sessions_sql = "SELECT cs.*, c.name as class_name, c.meeting_link as class_meeting_link
                        FROM class_sessions cs 
                        JOIN classes c ON cs.class_id = c.id
                        JOIN class_enrollments ce ON c.id = ce.class_id
                        WHERE ce.student_id = ? AND ce.status = 'active'
                        AND cs.session_date >= CURDATE()
                        AND cs.status = 'scheduled'
                        ORDER BY cs.session_date ASC, cs.start_time ASC
                        LIMIT 5";
        $sessions_stmt = $conn->prepare($sessions_sql);
        $sessions_stmt->bind_param("i", $student_id);
        $sessions_stmt->execute();
        $upcoming_sessions = $sessions_stmt->get_result();
        ?>
        
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Upcoming Sessions</h3>
            </div>
            <?php if ($upcoming_sessions && $upcoming_sessions->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($session = $upcoming_sessions->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($session['title']); ?></h4>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($session['class_name']); ?></p>
                                    <div class="mt-1 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i> <?php echo date('M j, Y', strtotime($session['session_date'])); ?></span>
                                        <span class="ml-4"><i class="fas fa-clock mr-1"></i> <?php echo date('g:i A', strtotime($session['start_time'])); ?> - <?php echo date('g:i A', strtotime($session['end_time'])); ?></span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <?php if ($session['meeting_link']): ?>
                                        <a href="<?php echo htmlspecialchars($session['meeting_link']); ?>" target="_blank" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-video"></i> Join
                                        </a>
                                    <?php elseif ($session['class_meeting_link']): ?>
                                        <a href="<?php echo htmlspecialchars($session['class_meeting_link']); ?>" target="_blank" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-video"></i> Join
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <i class="fas fa-calendar text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">No upcoming sessions.</p>
                    <p class="text-sm text-gray-400 mt-2">Check back later for scheduled sessions.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 