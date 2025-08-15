<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    header('Location: courses.php');
    exit;
}

$page_title = 'Course Lessons - Admin';

// Get course information
$course_sql = "SELECT c.*, u.name as teacher_name FROM courses c LEFT JOIN users u ON c.teacher_id = u.id WHERE c.id = ?";
$course_stmt = $conn->prepare($course_sql);
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: courses.php');
    exit;
}

// Get course lessons
$lessons_sql = "SELECT * FROM lessons WHERE course_id = ? ORDER BY order_number ASC";
$lessons_stmt = $conn->prepare($lessons_sql);
$lessons_stmt->bind_param("i", $course_id);
$lessons_stmt->execute();
$lessons = $lessons_stmt->get_result();
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Course Lessons</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($course['title']); ?></p>
            </div>
            <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Courses
            </a>
        </div>
    </div>

    <!-- Course Information -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Course Information</h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Title</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($course['title']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Teacher</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($course['teacher_name'] ?? 'Not assigned'); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Lessons</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo $lessons->num_rows; ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $course['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($course['status']); ?>
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Lessons List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Lessons (<?php echo $lessons->num_rows; ?> total)
            </h3>
        </div>
        
        <?php if ($lessons->num_rows > 0): ?>
            <ul class="divide-y divide-gray-200">
                <?php while ($lesson = $lessons->fetch_assoc()): ?>
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-blue-600"><?php echo $lesson['order_number']; ?></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($lesson['content'] ?? '', 0, 100)) . '...'; ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-clock mr-1"></i> Duration: <?php echo $lesson['duration']; ?> minutes</span>
                                            <?php if ($lesson['video_url']): ?>
                                                <span class="ml-4"><i class="fas fa-video mr-1"></i> Has video</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <div class="px-4 py-8 text-center">
                <i class="fas fa-book text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No lessons found for this course.</p>
                <p class="text-sm text-gray-400 mt-2">Lessons will appear here once they are added.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 