<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$lesson_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$lesson_id || !$course_id) {
    header('Location: courses.php');
    exit;
}

// Get lesson details
$lesson_sql = "SELECT l.*, cs.title as section_title, c.title as course_title 
               FROM lessons l
               LEFT JOIN course_sections cs ON l.section_id = cs.id
               JOIN courses c ON l.course_id = c.id
               WHERE l.id = ? AND l.course_id = ? AND c.teacher_id = ?";
$lesson_stmt = $conn->prepare($lesson_sql);
$lesson_stmt->bind_param("iii", $lesson_id, $course_id, $_SESSION['user_id']);
$lesson_stmt->execute();
$lesson = $lesson_stmt->get_result()->fetch_assoc();

if (!$lesson) {
    header('Location: courses.php');
    exit;
}

// Parse videos and images JSON
$videos = [];
$images = [];
if ($lesson['videos']) {
    $videos = json_decode($lesson['videos'], true) ?: [];
}
if ($lesson['images']) {
    $images = json_decode($lesson['images'], true) ?: [];
}

$page_title = 'Lesson Details - ' . $lesson['title'];
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <nav class="flex" aria-label="Breadcrumb">
                            <ol class="inline-flex items-center space-x-1 md:space-x-3">
                                <li class="inline-flex items-center">
                                    <a href="courses.php" class="text-gray-700 hover:text-gray-900">
                                        <i class="fas fa-graduation-cap mr-1"></i>
                                        Courses
                                    </a>
                                </li>
                                <li>
                                    <div class="flex items-center">
                                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                        <a href="course_sections.php?course_id=<?php echo $course_id; ?>" class="text-gray-700 hover:text-gray-900"><?php echo htmlspecialchars($lesson['course_title']); ?></a>
                                    </div>
                                </li>
                                <?php if ($lesson['section_title']): ?>
                                <li>
                                    <div class="flex items-center">
                                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                        <a href="section_lessons.php?section_id=<?php echo $section_id; ?>&course_id=<?php echo $course_id; ?>" class="text-gray-700 hover:text-gray-900"><?php echo htmlspecialchars($lesson['section_title']); ?></a>
                                    </div>
                                </li>
                                <?php endif; ?>
                                <li>
                                    <div class="flex items-center">
                                        <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                        <span class="text-gray-500"><?php echo htmlspecialchars($lesson['title']); ?></span>
                                    </div>
                                </li>
                            </ol>
                        </nav>
                        <h1 class="text-2xl font-bold text-gray-900 mt-2"><?php echo htmlspecialchars($lesson['title']); ?></h1>
                        <p class="text-sm text-gray-600 mt-1">
                            Lesson <?php echo $lesson['order_number']; ?> • 
                            <?php echo $lesson['duration'] ? formatDuration($lesson['duration']) : 'No duration set'; ?>
                        </p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <a href="section_lessons.php?section_id=<?php echo $section_id; ?>&course_id=<?php echo $course_id; ?>&action=edit&lesson_id=<?php echo $lesson_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                            <i class="fas fa-edit mr-2"></i>
                            Edit Lesson
                        </a>
                        <a href="section_lessons.php?section_id=<?php echo $section_id; ?>&course_id=<?php echo $course_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Lessons
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lesson Content -->
    <div class="px-4 sm:px-0">
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-6">
                
                <!-- Brief Description -->
                <?php if ($lesson['content']): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Brief Description</h3>
                        <div class="prose max-w-none">
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($lesson['content'])); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Rich Content -->
                <?php if ($lesson['rich_content']): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Detailed Content</h3>
                        <div class="prose max-w-none border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <?php echo $lesson['rich_content']; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- External Video -->
                <?php if ($lesson['video_url']): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">External Video</h3>
                        <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
                            <a href="<?php echo htmlspecialchars($lesson['video_url']); ?>" 
                               target="_blank" 
                               class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                <i class="fas fa-external-link-alt mr-2"></i>
                                <?php echo htmlspecialchars($lesson['video_url']); ?>
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Additional Videos -->
                <?php if (!empty($videos)): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Additional Videos</h3>
                        <div class="grid grid-cols-1 gap-3">
                            <?php foreach ($videos as $index => $video): ?>
                                <?php if (!empty($video)): ?>
                                    <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
                                        <a href="<?php echo htmlspecialchars($video); ?>" 
                                           target="_blank" 
                                           class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-video mr-2"></i>
                                            Video <?php echo $index + 1; ?> - <?php echo htmlspecialchars($video); ?>
                                        </a>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Single Image -->
                <?php if ($lesson['image_url']): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Image</h3>
                        <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
                            <img src="<?php echo htmlspecialchars($lesson['image_url']); ?>" 
                                 alt="Lesson image" 
                                 class="max-w-full h-auto rounded-lg shadow-md"
                                 onerror="this.parentElement.innerHTML='<p class=\'text-gray-500\'><i class=\'fas fa-image mr-2\'></i>Image could not be loaded: <?php echo htmlspecialchars($lesson['image_url']); ?></p>'">
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Additional Images -->
                <?php if (!empty($images)): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Additional Images</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <?php foreach ($images as $index => $image): ?>
                                <?php if (!empty($image)): ?>
                                    <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
                                        <img src="<?php echo htmlspecialchars($image); ?>" 
                                             alt="Lesson image <?php echo $index + 1; ?>" 
                                             class="w-full h-48 object-cover rounded-lg shadow-md"
                                             onerror="this.parentElement.innerHTML='<p class=\'text-gray-500 h-48 flex items-center justify-center\'><i class=\'fas fa-image mr-2\'></i>Image <?php echo $index + 1; ?> could not be loaded</p>'">
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Attachment -->
                <?php if ($lesson['attachment_url']): ?>
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-3">Attachment</h3>
                        <div class="bg-gray-100 border border-gray-200 rounded-lg p-4">
                            <a href="<?php echo htmlspecialchars($lesson['attachment_url']); ?>" 
                               target="_blank" 
                               class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-download mr-2"></i>
                                Download Attachment
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Lesson Meta Information -->
                <div class="border-t border-gray-200 pt-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-3">Lesson Information</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">Order</dt>
                            <dd class="text-lg text-gray-900"><?php echo $lesson['order_number']; ?></dd>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">Duration</dt>
                            <dd class="text-lg text-gray-900"><?php echo $lesson['duration'] ? formatDuration($lesson['duration']) : 'Not set'; ?></dd>
                        </div>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="text-lg text-gray-900"><?php echo date('M j, Y', strtotime($lesson['updated_at'])); ?></dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>