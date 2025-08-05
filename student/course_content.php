<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$lesson_id = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
$action = $_GET['action'] ?? 'view';
$page_title = 'Course Content - Student';

// Verify student is enrolled in the course
if ($course_id) {
    $enrollment_sql = "SELECT * FROM enrollments WHERE student_id = ? AND course_id = ? AND status = 'active'";
    $enrollment_stmt = $conn->prepare($enrollment_sql);
    $enrollment_stmt->bind_param("ii", $student_id, $course_id);
    $enrollment_stmt->execute();
    $enrollment = $enrollment_stmt->get_result()->fetch_assoc();
    
    if (!$enrollment) {
        header('Location: courses.php');
        exit;
    }
}

// Get course details
$course = null;
if ($course_id) {
    $course_sql = "SELECT c.*, u.name as teacher_name 
                   FROM courses c 
                   LEFT JOIN users u ON c.teacher_id = u.id 
                   WHERE c.id = ?";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bind_param("i", $course_id);
    $course_stmt->execute();
    $course = $course_stmt->get_result()->fetch_assoc();
}

// Get lessons for the course
$lessons = null;
if ($course_id) {
    $lessons_sql = "SELECT l.*, 
                    CASE WHEN sp.lesson_completed = 1 THEN 1 ELSE 0 END as is_completed
                    FROM lessons l 
                    LEFT JOIN student_progress sp ON l.id = sp.lesson_id AND sp.student_id = ?
                    WHERE l.course_id = ? 
                    ORDER BY l.order_number ASC";
    $lessons_stmt = $conn->prepare($lessons_sql);
    $lessons_stmt->bind_param("ii", $student_id, $course_id);
    $lessons_stmt->execute();
    $lessons = $lessons_stmt->get_result();
}

// Get specific lesson
$lesson = null;
if ($lesson_id) {
    $lesson_sql = "SELECT l.*, 
                   CASE WHEN sp.lesson_completed = 1 THEN 1 ELSE 0 END as is_completed
                   FROM lessons l 
                   LEFT JOIN student_progress sp ON l.id = sp.lesson_id AND sp.student_id = ?
                   WHERE l.id = ? AND l.course_id = ?";
    $lesson_stmt = $conn->prepare($lesson_sql);
    $lesson_stmt->bind_param("iii", $student_id, $lesson_id, $course_id);
    $lesson_stmt->execute();
    $lesson = $lesson_stmt->get_result()->fetch_assoc();
}

// Mark lesson as completed
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_completed']) && $lesson_id) {
    $sql = "INSERT INTO student_progress (student_id, course_id, lesson_id, lesson_completed, completed_at) 
            VALUES (?, ?, ?, 1, NOW()) 
            ON DUPLICATE KEY UPDATE lesson_completed = 1, completed_at = NOW()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $student_id, $course_id, $lesson_id);
    $stmt->execute();
    
    // Redirect to refresh the page
    header("Location: course_content.php?course_id=$course_id&lesson_id=$lesson_id");
    exit;
}
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if ($course): ?>
            <!-- Course Header -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h1>
                            <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($course['description']); ?></p>
                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($course['teacher_name'] ?? 'Unknown'); ?></span>
                                <span class="ml-4"><i class="fas fa-tag mr-1"></i> <?php echo htmlspecialchars($course['category']); ?></span>
                                <span class="ml-4"><i class="fas fa-rupee-sign mr-1"></i> <?php echo number_format($course['price']); ?></span>
                            </div>
                        </div>
                        <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Courses
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Lessons Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Lessons</h3>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php if ($lessons && $lessons->num_rows > 0): ?>
                                <?php while ($lesson_item = $lessons->fetch_assoc()): ?>
                                    <div class="px-4 py-3 hover:bg-gray-50">
                                        <a href="?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson_item['id']; ?>" 
                                           class="flex items-center justify-between text-sm">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <?php if ($lesson_item['is_completed']): ?>
                                                        <i class="fas fa-check-circle text-green-600"></i>
                                                    <?php else: ?>
                                                        <i class="fas fa-circle text-gray-400"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="ml-2 <?php echo $lesson_id == $lesson_item['id'] ? 'font-medium text-blue-600' : 'text-gray-700'; ?>">
                                                    <?php echo htmlspecialchars($lesson_item['title']); ?>
                                                </span>
                                            </div>
                                            <span class="text-xs text-gray-500">
                                                <?php echo $lesson_item['duration'] ? formatDuration($lesson_item['duration']) : 'N/A'; ?>
                                            </span>
                                        </a>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="px-4 py-3 text-sm text-gray-500">
                                    No lessons available yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Lesson Content -->
                <div class="lg:col-span-3">
                    <?php if ($lesson): ?>
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($lesson['title']); ?></h2>
                                        <p class="text-sm text-gray-600 mt-1">
                                            Lesson <?php echo $lesson['order_number']; ?> • 
                                            <?php echo $lesson['duration'] ? formatDuration($lesson['duration']) : 'No duration set'; ?>
                                        </p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <?php if ($lesson['is_completed']): ?>
                                            <span class="px-3 py-1 text-sm font-medium rounded-full bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i> Completed
                                            </span>
                                        <?php else: ?>
                                            <form method="POST" class="inline">
                                                <button type="submit" name="mark_completed" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                                    <i class="fas fa-check mr-2"></i>
                                                    Mark as Complete
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="px-6 py-4">
                                <!-- Lesson Content -->
                                <?php if ($lesson['content']): ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">Content</h3>
                                        <div class="prose max-w-none lesson-content">
                                            <?php echo $lesson['content']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Videos -->
                                <?php if ($lesson['video_url']): ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">Video</h3>
                                        <div class="aspect-w-16 aspect-h-9">
                                            <iframe src="<?php echo htmlspecialchars($lesson['video_url']); ?>" 
                                                    class="w-full h-64 rounded-lg" 
                                                    frameborder="0" 
                                                    allowfullscreen></iframe>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Multiple Videos -->
                                <?php if ($lesson['videos']): ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">Additional Videos</h3>
                                        <?php 
                                        $videos = json_decode($lesson['videos'], true);
                                        if (is_array($videos)):
                                            foreach ($videos as $index => $video_url):
                                        ?>
                                            <div class="mb-4">
                                                <h4 class="text-sm font-medium text-gray-700 mb-2">Video <?php echo $index + 1; ?></h4>
                                                <div class="aspect-w-16 aspect-h-9">
                                                    <iframe src="<?php echo htmlspecialchars($video_url); ?>" 
                                                            class="w-full h-64 rounded-lg" 
                                                            frameborder="0" 
                                                            allowfullscreen></iframe>
                                                </div>
                                            </div>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                <?php endif; ?>

                                <!-- Images -->
                                <?php if ($lesson['image_url']): ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">Image</h3>
                                        <img src="<?php echo htmlspecialchars($lesson['image_url']); ?>" 
                                             alt="Lesson Image" 
                                             class="max-w-full h-auto rounded-lg">
                                    </div>
                                <?php endif; ?>

                                <!-- Multiple Images -->
                                <?php if ($lesson['images']): ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">Additional Images</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <?php 
                                            $images = json_decode($lesson['images'], true);
                                            if (is_array($images)):
                                                foreach ($images as $image_url):
                                            ?>
                                                <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                                     alt="Lesson Image" 
                                                     class="max-w-full h-auto rounded-lg">
                                            <?php 
                                                endforeach;
                                            endif;
                                            ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Attachments -->
                                <?php if ($lesson['attachment_url']): ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">Attachment</h3>
                                        <a href="<?php echo htmlspecialchars($lesson['attachment_url']); ?>" 
                                           target="_blank" 
                                           class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            <i class="fas fa-download mr-2"></i>
                                            Download Attachment
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-6 py-8 text-center">
                                <i class="fas fa-book-open text-gray-400 text-4xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Select a Lesson</h3>
                                <p class="text-gray-500">Choose a lesson from the sidebar to start learning.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-8 text-center">
                    <i class="fas fa-exclamation-triangle text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Course Not Found</h3>
                    <p class="text-gray-500">The requested course could not be found or you are not enrolled.</p>
                    <a href="courses.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Courses
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <style>
        .lesson-content {
            line-height: 1.6;
        }
        .lesson-content h1, .lesson-content h2, .lesson-content h3, .lesson-content h4, .lesson-content h5, .lesson-content h6 {
            margin-top: 1.5em;
            margin-bottom: 0.5em;
            font-weight: 600;
        }
        .lesson-content p {
            margin-bottom: 1em;
        }
        .lesson-content ul, .lesson-content ol {
            margin-bottom: 1em;
            padding-left: 1.5em;
        }
        .lesson-content li {
            margin-bottom: 0.5em;
        }
        .lesson-content blockquote {
            border-left: 4px solid #e5e7eb;
            padding-left: 1em;
            margin: 1em 0;
            font-style: italic;
        }
        .lesson-content code {
            background-color: #f3f4f6;
            padding: 0.125em 0.25em;
            border-radius: 0.25em;
            font-family: monospace;
        }
        .lesson-content pre {
            background-color: #f3f4f6;
            padding: 1em;
            border-radius: 0.5em;
            overflow-x: auto;
            margin: 1em 0;
        }
    </style>
</body>
</html>
