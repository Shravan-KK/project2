<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$course_id) {
    header('Location: courses.php');
    exit();
}

// Verify teacher owns this course
$verify_sql = "SELECT * FROM courses WHERE id = ? AND teacher_id = ?";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("ii", $course_id, $teacher_id);
$verify_stmt->execute();
$course = $verify_stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: courses.php');
    exit();
}

$page_title = 'Course View - ' . $course['title'];

// Get course statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT e.student_id) as enrolled_students,
    COUNT(DISTINCT l.id) as total_lessons,
    COUNT(DISTINCT a.id) as total_assignments,
    AVG(e.progress) as avg_progress,
    COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.student_id END) as completed_students
    FROM courses c 
    LEFT JOIN enrollments e ON c.id = e.course_id
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN assignments a ON c.id = a.course_id
    WHERE c.id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $course_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

// Get enrolled students
$students_sql = "SELECT e.*, u.name, u.email, u.created_at as joined_date
                FROM enrollments e 
                JOIN users u ON e.student_id = u.id
                WHERE e.course_id = ? AND e.status = 'active'
                ORDER BY e.enrollment_date DESC";
$students_stmt = $conn->prepare($students_sql);
$students_stmt->bind_param("i", $course_id);
$students_stmt->execute();
$enrolled_students = $students_stmt->get_result();

// Get lessons
$lessons_sql = "SELECT * FROM lessons WHERE course_id = ? ORDER BY order_number ASC";
$lessons_stmt = $conn->prepare($lessons_sql);
$lessons_stmt->bind_param("i", $course_id);
$lessons_stmt->execute();
$lessons = $lessons_stmt->get_result();

// Get assignments
$assignments_sql = "SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date ASC";
$assignments_stmt = $conn->prepare($assignments_sql);
$assignments_stmt->bind_param("i", $course_id);
$assignments_stmt->execute();
$assignments = $assignments_stmt->get_result();

// Get recent announcements
$announcements_sql = "SELECT * FROM announcements WHERE course_id = ? ORDER BY created_at DESC LIMIT 5";
$announcements_stmt = $conn->prepare($announcements_sql);
$announcements_stmt->bind_param("i", $course_id);
$announcements_stmt->execute();
$announcements = $announcements_stmt->get_result();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <!-- Course Header -->
        <div class="bg-white shadow rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h1>
                        <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($course['description']); ?></p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <span class="px-3 py-1 text-sm font-medium rounded-full 
                            <?php echo $course['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($course['status']); ?>
                        </span>
                        <a href="course_content.php?course_id=<?php echo $course['id']; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                            <i class="fas fa-edit mr-2"></i>Edit Course
                        </a>
                    </div>
                </div>
                <div class="mt-4 flex items-center text-sm text-gray-500">
                    <span><i class="fas fa-clock mr-1"></i> <?php echo htmlspecialchars($course['duration']); ?></span>
                    <span class="ml-4"><i class="fas fa-dollar-sign mr-1"></i> $<?php echo number_format($course['price'], 2); ?></span>
                    <span class="ml-4"><i class="fas fa-calendar mr-1"></i> Created: <?php echo date('M j, Y', strtotime($course['created_at'])); ?></span>
                </div>
            </div>
        </div>

        <!-- Course Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Enrolled Students</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['enrolled_students'] ?: 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Lessons</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_lessons'] ?: 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-tasks text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Assignments</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_assignments'] ?: 0; ?></dd>
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
                                <dd class="text-lg font-medium text-gray-900"><?php echo round($stats['avg_progress'] ?: 0, 1); ?>%</dd>
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
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['completed_students'] ?: 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Tabs -->
        <div class="bg-white shadow rounded-lg">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button onclick="showTab('students')" class="tab-button border-b-2 border-blue-500 py-4 px-1 text-sm font-medium text-blue-600" id="tab-students">
                        <i class="fas fa-users mr-2"></i>Students
                    </button>
                    <button onclick="showTab('lessons')" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700" id="tab-lessons">
                        <i class="fas fa-file-alt mr-2"></i>Lessons
                    </button>
                    <button onclick="showTab('assignments')" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700" id="tab-assignments">
                        <i class="fas fa-tasks mr-2"></i>Assignments
                    </button>
                    <button onclick="showTab('announcements')" class="tab-button border-b-2 border-transparent py-4 px-1 text-sm font-medium text-gray-500 hover:text-gray-700" id="tab-announcements">
                        <i class="fas fa-bullhorn mr-2"></i>Announcements
                    </button>
                </nav>
            </div>

            <!-- Students Tab -->
            <div id="tab-content-students" class="tab-content p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Enrolled Students</h3>
                    <span class="text-sm text-gray-500"><?php echo $enrolled_students->num_rows; ?> students</span>
                </div>
                
                <?php if ($enrolled_students->num_rows > 0): ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Student</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Progress</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enrolled Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php while ($student = $enrolled_students->fetch_assoc()): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($student['email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-16 bg-gray-200 rounded-full h-2 mr-2">
                                                    <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $student['progress'] ?: 0; ?>%"></div>
                                                </div>
                                                <span class="text-sm text-gray-900"><?php echo $student['progress'] ?: 0; ?>%</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            <?php echo date('M j, Y', strtotime($student['enrollment_date'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="student_progress.php?student_id=<?php echo $student['student_id']; ?>&course_id=<?php echo $course_id; ?>" class="text-blue-600 hover:text-blue-900">
                                                <i class="fas fa-eye mr-1"></i>View Progress
                                            </a>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No students enrolled yet.</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Lessons Tab -->
            <div id="tab-content-lessons" class="tab-content p-6 hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Course Lessons</h3>
                    <a href="lessons.php?course_id=<?php echo $course_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Add Lesson
                    </a>
                </div>
                
                <?php if ($lessons->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while ($lesson = $lessons->fetch_assoc()): ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($lesson['content'], 0, 150)) . '...'; ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-sort-numeric-up mr-1"></i> Order: <?php echo $lesson['lesson_order']; ?></span>
                                            <span class="ml-4"><i class="fas fa-clock mr-1"></i> Duration: <?php echo $lesson['duration']; ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="lesson_edit.php?id=<?php echo $lesson['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="lesson_view.php?id=<?php echo $lesson['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-file-alt text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No lessons created yet.</p>
                        <a href="lessons.php?course_id=<?php echo $course_id; ?>" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Create First Lesson
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Assignments Tab -->
            <div id="tab-content-assignments" class="tab-content p-6 hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Course Assignments</h3>
                    <a href="assignments.php?course_id=<?php echo $course_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Add Assignment
                    </a>
                </div>
                
                <?php if ($assignments->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($assignment['description'], 0, 150)) . '...'; ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> Due: <?php echo date('M j, Y', strtotime($assignment['due_date'])); ?></span>
                                            <span class="ml-4"><i class="fas fa-star mr-1"></i> Points: <?php echo $assignment['points']; ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="assignment_edit.php?id=<?php echo $assignment['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="assignment_submissions.php?assignment_id=<?php echo $assignment['id']; ?>" class="text-green-600 hover:text-green-900">
                                            <i class="fas fa-file-upload"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-tasks text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No assignments created yet.</p>
                        <a href="assignments.php?course_id=<?php echo $course_id; ?>" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Create First Assignment
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Announcements Tab -->
            <div id="tab-content-announcements" class="tab-content p-6 hidden">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Course Announcements</h3>
                    <a href="announcements.php?course_id=<?php echo $course_id; ?>" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                        <i class="fas fa-plus mr-2"></i>Add Announcement
                    </a>
                </div>
                
                <?php if ($announcements->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while ($announcement = $announcements->fetch_assoc()): ?>
                            <div class="border rounded-lg p-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($announcement['content'], 0, 150)) . '...'; ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> Posted: <?php echo date('M j, Y', strtotime($announcement['created_at'])); ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="announcement_edit.php?id=<?php echo $announcement['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="announcement_view.php?id=<?php echo $announcement['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-bullhorn text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No announcements posted yet.</p>
                        <a href="announcements.php?course_id=<?php echo $course_id; ?>" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Post First Announcement
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.classList.add('hidden'));
    
    // Remove active state from all tab buttons
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('border-blue-500', 'text-blue-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById('tab-content-' + tabName).classList.remove('hidden');
    
    // Add active state to selected tab button
    document.getElementById('tab-' + tabName).classList.remove('border-transparent', 'text-gray-500');
    document.getElementById('tab-' + tabName).classList.add('border-blue-500', 'text-blue-600');
}
</script>

<?php require_once '../includes/footer.php'; ?> 