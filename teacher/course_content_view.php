<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$course_id) {
    header('Location: all_courses.php');
    exit();
}

// Get course details
$course_sql = "SELECT c.*, u.name as teacher_name,
               tcc.title as custom_title, tcc.description as custom_description, tcc.custom_content
               FROM courses c 
               LEFT JOIN users u ON c.teacher_id = u.id
               LEFT JOIN teacher_course_customizations tcc ON c.id = tcc.original_course_id AND tcc.teacher_id = ?
               WHERE c.id = ?";
$course_stmt = $conn->prepare($course_sql);
$course_stmt->bind_param("ii", $teacher_id, $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: all_courses.php');
    exit();
}

$page_title = 'Course Content - ' . ($course['custom_title'] ?: $course['title']);

// Get course lessons
$lessons_sql = "SELECT * FROM lessons WHERE course_id = ? ORDER BY order_number, id";
$lessons_stmt = $conn->prepare($lessons_sql);
$lessons_stmt->bind_param("i", $course_id);
$lessons_stmt->execute();
$lessons = $lessons_stmt->get_result();

// Get course assignments
$assignments_sql = "SELECT * FROM assignments WHERE course_id = ? ORDER BY due_date";
$assignments_stmt = $conn->prepare($assignments_sql);
$assignments_stmt->bind_param("i", $course_id);
$assignments_stmt->execute();
$assignments = $assignments_stmt->get_result();

// Get batches where this course is assigned
$batches_sql = "SELECT DISTINCT b.*, bc.start_date as course_start_date, bc.end_date as course_end_date
                FROM batches b 
                JOIN batch_courses bc ON b.id = bc.batch_id 
                WHERE bc.course_id = ? AND bc.status = 'active'
                ORDER BY b.name";
$batches_stmt = $conn->prepare($batches_sql);
$batches_stmt->bind_param("i", $course_id);
$batches_stmt->execute();
$batches = $batches_stmt->get_result();

// Get course statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT e.student_id) as total_enrolled,
    COUNT(DISTINCT l.id) as total_lessons,
    COUNT(DISTINCT a.id) as total_assignments
    FROM courses c
    LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
    LEFT JOIN lessons l ON c.id = l.course_id
    LEFT JOIN assignments a ON c.id = a.course_id
    WHERE c.id = ?";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $course_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">
                    <?php echo htmlspecialchars($course['custom_title'] ?: $course['title']); ?>
                    <?php if ($course['custom_title']): ?>
                        <span class="ml-3 inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            Customized
                        </span>
                    <?php endif; ?>
                </h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($course['custom_description'] ?: $course['description'] ?: 'No description available'); ?></p>
                <div class="mt-2 text-sm text-gray-500">
                    <span>Original Teacher: <?php echo htmlspecialchars($course['teacher_name'] ?: 'Unknown'); ?></span>
                    <?php if ($course['teacher_id'] == $teacher_id): ?>
                        <span class="ml-4 text-green-600">• Your Course</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="space-x-3">
                <a href="all_courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Courses
                </a>
                <?php if ($course['teacher_id'] == $teacher_id): ?>
                    <a href="course_content.php?id=<?php echo $course_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-edit mr-2"></i>
                        Edit Course
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Course Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-users text-indigo-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Enrolled Students</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_enrolled']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-book text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Lessons</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_lessons']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-tasks text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Assignments</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_assignments']; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-rupee-sign text-yellow-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Course Price</dt>
                            <dd class="text-lg font-medium text-gray-900">₹<?php echo number_format($course['price'], 2); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Custom Content -->
    <?php if ($course['custom_content']): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 mb-8">
            <h3 class="text-lg font-medium text-blue-900 mb-3">
                <i class="fas fa-edit mr-2"></i>
                Your Custom Notes & Modifications
            </h3>
            <div class="text-blue-800 whitespace-pre-line"><?php echo htmlspecialchars($course['custom_content']); ?></div>
        </div>
    <?php endif; ?>

    <!-- Batches Using This Course -->
    <?php if ($batches->num_rows > 0): ?>
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Batches Using This Course</h3>
                <p class="mt-1 max-w-2xl text-sm text-gray-500">Active batches where this course is assigned</p>
            </div>
            <div class="border-t border-gray-200">
                <ul class="divide-y divide-gray-200">
                    <?php while ($batch = $batches->fetch_assoc()): ?>
                        <li class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-base font-medium text-gray-900"><?php echo htmlspecialchars($batch['name']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($batch['description']); ?></p>
                                    <div class="mt-1 text-sm text-gray-500">
                                        <span>Students: <?php echo $batch['current_students']; ?>/<?php echo $batch['max_students']; ?></span>
                                        <?php if ($batch['course_start_date']): ?>
                                            <span class="ml-4">Start: <?php echo formatDate($batch['course_start_date']); ?></span>
                                        <?php endif; ?>
                                        <?php if ($batch['course_end_date']): ?>
                                            <span class="ml-4">End: <?php echo formatDate($batch['course_end_date']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <a href="batch_details.php?id=<?php echo $batch['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-indigo-700 bg-indigo-100 hover:bg-indigo-200">
                                    View Batch
                                </a>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
    <?php endif; ?>

    <!-- Course Content Tabs -->
    <div class="bg-white shadow rounded-lg">
        <div class="border-b border-gray-200">
            <nav class="-mb-px flex space-x-8" aria-label="Tabs">
                <button onclick="showTab('lessons')" id="lessons-tab" class="tab-button active border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-book mr-2"></i>
                    Lessons (<?php echo $stats['total_lessons']; ?>)
                </button>
                <button onclick="showTab('assignments')" id="assignments-tab" class="tab-button border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-2 px-1 border-b-2 font-medium text-sm">
                    <i class="fas fa-tasks mr-2"></i>
                    Assignments (<?php echo $stats['total_assignments']; ?>)
                </button>
            </nav>
        </div>

        <!-- Lessons Tab -->
        <div id="lessons-content" class="tab-content">
            <div class="p-6">
                <?php if ($lessons->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while ($lesson = $lessons->fetch_assoc()): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                        <?php if ($lesson['content']): ?>
                                            <p class="mt-2 text-gray-600"><?php echo nl2br(htmlspecialchars(substr($lesson['content'], 0, 200))); ?><?php echo strlen($lesson['content']) > 200 ? '...' : ''; ?></p>
                                        <?php endif; ?>
                                        <div class="mt-2 text-sm text-gray-500">
                                            <?php if ($lesson['duration']): ?>
                                                <span><i class="fas fa-clock mr-1"></i> <?php echo $lesson['duration']; ?> minutes</span>
                                            <?php endif; ?>
                                            <?php if ($lesson['video_url']): ?>
                                                <span class="ml-4"><i class="fas fa-video mr-1"></i> Has Video</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <button onclick="toggleLessonDetails(<?php echo $lesson['id']; ?>)" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            View Details
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Lesson Details (Hidden by default) -->
                                <div id="lesson-details-<?php echo $lesson['id']; ?>" class="hidden mt-4 pt-4 border-t border-gray-200">
                                    <?php if ($lesson['content']): ?>
                                        <div class="mb-4">
                                            <h5 class="font-medium text-gray-900 mb-2">Lesson Content:</h5>
                                            <div class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($lesson['content']); ?></div>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($lesson['video_url']): ?>
                                        <div class="mb-4">
                                            <h5 class="font-medium text-gray-900 mb-2">Video:</h5>
                                            <a href="<?php echo htmlspecialchars($lesson['video_url']); ?>" target="_blank" class="text-indigo-600 hover:text-indigo-900">
                                                <?php echo htmlspecialchars($lesson['video_url']); ?>
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-book text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Lessons Yet</h3>
                        <p class="text-gray-500">This course doesn't have any lessons yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Assignments Tab -->
        <div id="assignments-content" class="tab-content hidden">
            <div class="p-6">
                <?php if ($assignments->num_rows > 0): ?>
                    <div class="space-y-4">
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                            <div class="border border-gray-200 rounded-lg p-4">
                                <div class="flex items-start justify-between">
                                    <div class="flex-1">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                        <?php if ($assignment['description']): ?>
                                            <p class="mt-2 text-gray-600"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                                        <?php endif; ?>
                                        <div class="mt-2 text-sm text-gray-500">
                                            <?php if ($assignment['due_date']): ?>
                                                <span><i class="fas fa-calendar mr-1"></i> Due: <?php echo formatDate($assignment['due_date']); ?></span>
                                            <?php endif; ?>
                                            <span class="ml-4"><i class="fas fa-star mr-1"></i> Points: <?php echo $assignment['total_points']; ?></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <a href="assignment_submissions.php?assignment_id=<?php echo $assignment['id']; ?>" class="text-indigo-600 hover:text-indigo-900 text-sm font-medium">
                                            View Submissions
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-8">
                        <i class="fas fa-tasks text-gray-400 text-4xl mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No Assignments Yet</h3>
                        <p class="text-gray-500">This course doesn't have any assignments yet.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function showTab(tabName) {
    // Hide all tab contents
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tab buttons
    document.querySelectorAll('.tab-button').forEach(button => {
        button.classList.remove('active', 'border-indigo-500', 'text-indigo-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(tabName + '-content').classList.remove('hidden');
    
    // Add active class to selected tab button
    const activeButton = document.getElementById(tabName + '-tab');
    activeButton.classList.add('active', 'border-indigo-500', 'text-indigo-600');
    activeButton.classList.remove('border-transparent', 'text-gray-500');
}

function toggleLessonDetails(lessonId) {
    const detailsDiv = document.getElementById('lesson-details-' + lessonId);
    detailsDiv.classList.toggle('hidden');
}

// Initialize with lessons tab active
document.addEventListener('DOMContentLoaded', function() {
    showTab('lessons');
});
</script>

<style>
.tab-button.active {
    border-color: #4f46e5;
    color: #4f46e5;
}
</style>

<?php require_once '../includes/footer.php'; ?>