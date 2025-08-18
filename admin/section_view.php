<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$section_id = $_GET['id'] ?? null;
if (!$section_id) {
    header('Location: courses.php');
    exit;
}

$page_title = 'View Section - Admin';

// Get section details with course information
$section_sql = "SELECT cs.*, c.title as course_title, c.id as course_id, u.name as teacher_name 
                FROM course_sections cs
                JOIN courses c ON cs.course_id = c.id
                LEFT JOIN users u ON c.teacher_id = u.id
                WHERE cs.id = ?";
$section_stmt = $conn->prepare($section_sql);
$section_stmt->bind_param("i", $section_id);
$section_stmt->execute();
$section = $section_stmt->get_result()->fetch_assoc();

if (!$section) {
    header('Location: courses.php');
    exit;
}

// Get lessons for this section
$lessons_sql = "SELECT l.*, 
                CASE WHEN l.videos IS NOT NULL AND l.videos != '' THEN 
                    JSON_LENGTH(l.videos) + (CASE WHEN l.video_url IS NOT NULL AND l.video_url != '' THEN 1 ELSE 0 END)
                ELSE 
                    (CASE WHEN l.video_url IS NOT NULL AND l.video_url != '' THEN 1 ELSE 0 END)
                END as video_count,
                CASE WHEN l.images IS NOT NULL AND l.images != '' THEN 
                    JSON_LENGTH(l.images) + (CASE WHEN l.image_url IS NOT NULL AND l.image_url != '' THEN 1 ELSE 0 END)
                ELSE 
                    (CASE WHEN l.image_url IS NOT NULL AND l.image_url != '' THEN 1 ELSE 0 END)
                END as image_count
                FROM lessons l 
                WHERE l.section_id = ? 
                ORDER BY l.order_number, l.id";
$lessons_stmt = $conn->prepare($lessons_sql);
$lessons_stmt->bind_param("i", $section_id);
$lessons_stmt->execute();
$lessons = $lessons_stmt->get_result();

// Get section statistics
$total_lessons = $lessons->num_rows;
$total_duration = 0;
$lessons_array = [];
while ($lesson = $lessons->fetch_assoc()) {
    $total_duration += $lesson['duration'] ?? 0;
    $lessons_array[] = $lesson;
}
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Section Details</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($section['title']); ?> - <?php echo htmlspecialchars($section['course_title']); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="section_edit.php?id=<?php echo $section_id; ?>" class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-edit mr-2"></i>
                    Edit Section
                </a>
                <a href="course_sections.php?course_id=<?php echo $section['course_id']; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Sections
                </a>
            </div>
        </div>
    </div>

    <!-- Section Information -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Section Information</h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Section Title</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($section['title']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Course</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($section['course_title']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Teacher</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($section['teacher_name'] ?? 'Unassigned'); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Order Number</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo $section['order_number']; ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Description</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($section['description'] ?? 'No description available'); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Section Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-book text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Lessons</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $total_lessons; ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-clock text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Total Duration</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo $total_duration; ?> min</dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="bg-white overflow-hidden shadow rounded-lg">
            <div class="p-5">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <i class="fas fa-calendar text-purple-600 text-2xl"></i>
                    </div>
                    <div class="ml-5 w-0 flex-1">
                        <dl>
                            <dt class="text-sm font-medium text-gray-500 truncate">Created</dt>
                            <dd class="text-lg font-medium text-gray-900"><?php echo formatDate($section['created_at']); ?></dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Section Lessons -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Section Lessons (<?php echo $total_lessons; ?>)</h3>
                <a href="lessons.php?section_id=<?php echo $section_id; ?>&course_id=<?php echo $section['course_id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <i class="fas fa-cogs mr-2"></i>
                    Manage Lessons
                </a>
            </div>
        </div>
        <div class="border-t border-gray-200">
            <?php if (!empty($lessons_array)): ?>
                <ul class="divide-y divide-gray-200">
                    <?php foreach ($lessons_array as $lesson): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                        <span class="ml-2 text-sm text-gray-500">#<?php echo $lesson['order_number']; ?></span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($lesson['description'] ?? '', 0, 150)) . (strlen($lesson['description'] ?? '') > 150 ? '...' : ''); ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-clock mr-1"></i> <?php echo $lesson['duration']; ?> minutes</span>
                                        <?php if ($lesson['video_count'] > 0): ?>
                                            <span class="ml-4"><i class="fas fa-video mr-1"></i> <?php echo $lesson['video_count']; ?> video<?php echo $lesson['video_count'] > 1 ? 's' : ''; ?></span>
                                        <?php endif; ?>
                                        <?php if ($lesson['image_count'] > 0): ?>
                                            <span class="ml-4"><i class="fas fa-image mr-1"></i> <?php echo $lesson['image_count']; ?> image<?php echo $lesson['image_count'] > 1 ? 's' : ''; ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="lessons.php?action=edit&id=<?php echo $lesson['id']; ?>&course_id=<?php echo $section['course_id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit Lesson">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <i class="fas fa-book text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">No lessons in this section yet.</p>
                    <p class="text-sm text-gray-400 mt-2">Create lessons to add content to this section.</p>
                    <a href="lessons.php?section_id=<?php echo $section_id; ?>&course_id=<?php echo $section['course_id']; ?>" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>
                        Add First Lesson
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>