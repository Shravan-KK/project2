<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$stats = getDashboardStats($conn, 'student', $student_id);
$unread_messages = getUnreadMessages($conn, $student_id);

// Get enrolled courses
$enrolled_courses = getEnrolledCourses($conn, $student_id);

// Get recent assignments
$sql = "SELECT a.*, c.title as course_title, c.id as course_id 
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        JOIN enrollments e ON c.id = e.course_id 
        WHERE e.student_id = ? AND e.status = 'active' 
        ORDER BY a.due_date ASC LIMIT 5";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$recent_assignments = $stmt->get_result();
$page_title = 'Student Dashboard';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page header -->
        <div class="px-4 py-6 sm:px-0">
            <h1 class="text-3xl font-bold text-gray-900">Student Dashboard</h1>
            <p class="mt-2 text-gray-600">Welcome back, <?php echo htmlspecialchars($_SESSION['name']); ?>!</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-book text-blue-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Enrolled Courses</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['enrolled_courses']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-layer-group text-purple-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Enrolled Batches</dt>
                                <dd class="text-lg font-medium text-gray-900">
                                    <?php
                                    $batch_count_sql = "SELECT COUNT(DISTINCT batch_id) as count FROM enrollments WHERE student_id = ? AND batch_id IS NOT NULL AND status = 'active'";
                                    $batch_count_stmt = $conn->prepare($batch_count_sql);
                                    $batch_count_stmt->bind_param("i", $student_id);
                                    $batch_count_stmt->execute();
                                    $batch_count = $batch_count_stmt->get_result()->fetch_assoc();
                                    echo $batch_count['count'] ?: 0;
                                    ?>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-certificate text-green-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed Courses</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['completed_courses']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-envelope text-indigo-600 text-3xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Unread Messages</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $unread_messages; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- My Courses -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">My Enrolled Courses</h3>
                    <a href="courses.php" class="text-blue-600 hover:text-blue-500 text-sm font-medium">View all courses →</a>
                </div>
                
                <?php if ($enrolled_courses->num_rows > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while ($course = $enrolled_courses->fetch_assoc()): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h4>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        <?php echo $course['progress']; ?>% Complete
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-3">
                                    <span><i class="fas fa-chalkboard-teacher mr-1"></i> <?php echo htmlspecialchars($course['teacher_name']); ?></span>
                                    <span><i class="fas fa-clock mr-1"></i> <?php echo $course['duration']; ?></span>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="mb-3">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Progress</span>
                                        <span><?php echo $course['progress']; ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $course['progress']; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="course_content.php?course_id=<?php echo $course['id']; ?>" class="w-full text-center px-3 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-md">
                                        <i class="fas fa-play mr-2"></i> Continue Learning
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-book text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">You haven't enrolled in any courses yet.</p>
                        <a href="../courses.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Browse Available Courses
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- My Batches -->
        <?php
        // Get student's enrolled batches
        $batches_sql = "SELECT b.*, c.title as course_title, u.name as instructor_name, e.progress as batch_progress
                        FROM enrollments e 
                        JOIN batches b ON e.batch_id = b.id 
                        JOIN batch_courses bc ON b.id = bc.batch_id
                        JOIN courses c ON bc.course_id = c.id
                        LEFT JOIN users u ON c.teacher_id = u.id
                        WHERE e.student_id = ? AND e.status = 'active' AND e.batch_id IS NOT NULL
                        ORDER BY b.start_date DESC LIMIT 3";
        $batches_stmt = $conn->prepare($batches_sql);
        $batches_stmt->bind_param("i", $student_id);
        $batches_stmt->execute();
        $enrolled_batches = $batches_stmt->get_result();
        ?>
        
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">My Enrolled Batches</h3>
                    <a href="batches.php" class="text-purple-600 hover:text-purple-500 text-sm font-medium">View all batches →</a>
                </div>
                
                <?php if ($enrolled_batches->num_rows > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while ($batch = $enrolled_batches->fetch_assoc()): ?>
                            <div class="border rounded-lg p-4 hover:shadow-md transition-shadow">
                                <div class="flex items-center justify-between mb-2">
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($batch['name']); ?></h4>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                        <?php echo $batch['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo ucfirst($batch['status']); ?>
                                    </span>
                                </div>
                                <p class="text-sm text-gray-600 mb-3"><?php echo htmlspecialchars(substr($batch['description'], 0, 100)) . '...'; ?></p>
                                <div class="flex items-center justify-between text-sm text-gray-500 mb-3">
                                    <span><i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($batch['course_title']); ?></span>
                                    <span><i class="fas fa-chalkboard-teacher mr-1"></i> <?php echo htmlspecialchars($batch['instructor_name']); ?></span>
                                </div>
                                
                                <!-- Progress Bar -->
                                <div class="mb-3">
                                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                                        <span>Progress</span>
                                        <span><?php echo $batch['batch_progress'] ?: 0; ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-purple-600 h-2 rounded-full" style="width: <?php echo $batch['batch_progress'] ?: 0; ?>%"></div>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2">
                                    <a href="batch_details.php?id=<?php echo $batch['id']; ?>" class="w-full text-center px-3 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-md">
                                        <i class="fas fa-eye mr-2"></i> View Details
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-layer-group text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">You haven't enrolled in any batches yet.</p>
                        <a href="batches.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-purple-600 hover:bg-purple-700">
                            View Available Batches
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Recent Assignments -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Upcoming Assignments</h3>
                <div class="space-y-4">
                    <?php if ($recent_assignments->num_rows > 0): ?>
                        <?php while ($assignment = $recent_assignments->fetch_assoc()): ?>
                            <div class="flex items-center justify-between p-4 border rounded-lg">
                                <div>
                                    <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                    <p class="text-sm text-gray-500"><?php echo htmlspecialchars($assignment['course_title']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm text-gray-500">Due: <?php echo formatDate($assignment['due_date']); ?></p>
                                    <a href="assignment_view.php?id=<?php echo $assignment['id']; ?>" class="text-blue-600 hover:text-blue-500 text-sm font-medium">
                                        View Assignment
                                    </a>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <p class="text-gray-500 text-sm">No upcoming assignments</p>
                    <?php endif; ?>
                </div>
                <div class="mt-4">
                    <a href="assignments.php" class="text-blue-600 hover:text-blue-500 text-sm font-medium">View all assignments →</a>
                </div>
            </div>
        </div>
    </div>


<?php require_once '../includes/footer.php'; ?>