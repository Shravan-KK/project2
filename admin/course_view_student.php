<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$lesson_id = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;

if (!$course_id) {
    header('Location: courses.php');
    exit;
}

$page_title = 'Course View (Student Perspective) - Admin';

// Get course details
$course_sql = "SELECT c.*, u.name as teacher_name 
               FROM courses c 
               LEFT JOIN users u ON c.teacher_id = u.id 
               WHERE c.id = ?";
$course_stmt = $conn->prepare($course_sql);
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: courses.php');
    exit;
}

// Get course sections and lessons
$sections = null;
try {
    $sections_sql = "SELECT cs.*, 
                     COUNT(l.id) as lesson_count,
                     SUM(l.duration) as total_duration
                     FROM course_sections cs
                     LEFT JOIN lessons l ON cs.id = l.section_id 
                     WHERE cs.course_id = ?
                     GROUP BY cs.id
                     ORDER BY cs.order_number ASC";
    $sections_stmt = $conn->prepare($sections_sql);
    $sections_stmt->bind_param("i", $course_id);
    $sections_stmt->execute();
    $sections = $sections_stmt->get_result();
} catch (Exception $e) {
    $sections = null;
}

// Get lessons for the course (fallback or for sections)
$lessons = null;
try {
    $lessons_sql = "SELECT l.*, cs.title as section_title, cs.id as section_id
                    FROM lessons l 
                    LEFT JOIN course_sections cs ON l.section_id = cs.id
                    WHERE l.course_id = ? 
                    ORDER BY cs.order_number ASC, l.order_number ASC";
    $lessons_stmt = $conn->prepare($lessons_sql);
    $lessons_stmt->bind_param("i", $course_id);
    $lessons_stmt->execute();
    $lessons = $lessons_stmt->get_result();
} catch (Exception $e) {
    // Fallback to simple lessons query
    $lessons_sql = "SELECT l.* FROM lessons l WHERE l.course_id = ? ORDER BY l.order_number ASC";
    $lessons_stmt = $conn->prepare($lessons_sql);
    $lessons_stmt->bind_param("i", $course_id);
    $lessons_stmt->execute();
    $lessons = $lessons_stmt->get_result();
}

// Get specific lesson
$lesson = null;
if ($lesson_id) {
    try {
        $lesson_sql = "SELECT l.*, cs.title as section_title, cs.id as section_id
                       FROM lessons l 
                       LEFT JOIN course_sections cs ON l.section_id = cs.id
                       WHERE l.id = ? AND l.course_id = ?";
        $lesson_stmt = $conn->prepare($lesson_sql);
        $lesson_stmt->bind_param("ii", $lesson_id, $course_id);
        $lesson_stmt->execute();
        $lesson = $lesson_stmt->get_result()->fetch_assoc();
    } catch (Exception $e) {
        // Fallback without sections
        $lesson_sql = "SELECT l.* FROM lessons l WHERE l.id = ? AND l.course_id = ?";
        $lesson_stmt = $conn->prepare($lesson_sql);
        $lesson_stmt->bind_param("ii", $lesson_id, $course_id);
        $lesson_stmt->execute();
        $lesson = $lesson_stmt->get_result()->fetch_assoc();
    }
}
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Admin Header -->
    <div class="bg-yellow-50 border border-yellow-200 rounded-md p-4 mb-6">
        <div class="flex items-center">
            <div class="flex-shrink-0">
                <i class="fas fa-eye text-yellow-600"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800">Admin View</h3>
                <div class="mt-2 text-sm text-yellow-700">
                    <p>You are viewing this course from a student's perspective. This is how students see the course content.</p>
                </div>
            </div>
            <div class="ml-auto">
                <a href="courses.php" class="inline-flex items-center px-3 py-2 border border-yellow-300 shadow-sm text-sm leading-4 font-medium rounded-md text-yellow-700 bg-white hover:bg-yellow-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Courses
                </a>
            </div>
        </div>
    </div>

    <!-- Course Header -->
    <div class="bg-white shadow rounded-lg mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h1>
                    <p class="text-gray-600 mt-1"><?php echo htmlspecialchars($course['description']); ?></p>
                    <div class="mt-2 flex items-center text-sm text-gray-500">
                        <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($course['teacher_name'] ?? 'Unknown'); ?></span>
                        <span class="ml-4"><i class="fas fa-tag mr-1"></i> <?php echo htmlspecialchars($course['category'] ?? 'Uncategorized'); ?></span>
                        <?php if ($course['price'] > 0): ?>
                            <span class="ml-4"><i class="fas fa-rupee-sign mr-1"></i> <?php echo number_format($course['price']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Course Sections & Lessons Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Course Content</h3>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <?php 
                    // Check if we have sections data
                    if ($sections && $sections->num_rows > 0): 
                        // Group lessons by section
                        $lessons_array = [];
                        if ($lessons && $lessons->num_rows > 0) {
                            while ($lesson_item = $lessons->fetch_assoc()) {
                                $section_id_key = $lesson_item['section_id'] ?? 'no_section';
                                $lessons_array[$section_id_key][] = $lesson_item;
                            }
                        }
                        
                        // Display sections with lessons
                        while ($section = $sections->fetch_assoc()): 
                    ?>
                        <div class="border-b border-gray-100">
                            <!-- Section Header -->
                            <div class="px-4 py-3 bg-gray-50 cursor-pointer section-header" onclick="toggleSection('section_<?php echo $section['id']; ?>')">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <i class="fas fa-chevron-down transition-transform duration-200 text-gray-500 section-arrow" id="arrow_<?php echo $section['id']; ?>"></i>
                                        <h4 class="ml-2 font-medium text-gray-900"><?php echo htmlspecialchars($section['title']); ?></h4>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="text-xs text-gray-500">
                                            <?php echo ($section['lesson_count'] ?? 0); ?> lessons
                                        </span>
                                    </div>
                                </div>
                                <?php if ($section['description']): ?>
                                    <p class="text-xs text-gray-600 mt-1"><?php echo htmlspecialchars(substr($section['description'], 0, 100)) . '...'; ?></p>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Section Lessons -->
                            <div class="section-content" id="section_<?php echo $section['id']; ?>">
                                <?php if (isset($lessons_array[$section['id']]) && !empty($lessons_array[$section['id']])): ?>
                                    <?php foreach ($lessons_array[$section['id']] as $lesson_item): ?>
                                        <div class="px-6 py-2 hover:bg-gray-50 border-l-2 <?php echo $lesson_id == $lesson_item['id'] ? 'border-blue-500 bg-blue-50' : 'border-transparent'; ?>">
                                            <a href="?id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson_item['id']; ?>" 
                                               class="flex items-center justify-between text-sm">
                                                <div class="flex items-center">
                                                    <div class="flex-shrink-0">
                                                        <i class="fas fa-play-circle text-gray-400"></i>
                                                    </div>
                                                    <span class="ml-2 <?php echo $lesson_id == $lesson_item['id'] ? 'font-medium text-blue-600' : 'text-gray-700'; ?>">
                                                        <?php echo htmlspecialchars($lesson_item['title']); ?>
                                                    </span>
                                                </div>
                                                <span class="text-xs text-gray-500">
                                                    <?php echo $lesson_item['duration'] ? formatDuration($lesson_item['duration']) : ''; ?>
                                                </span>
                                            </a>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="px-6 py-2 text-xs text-gray-500">
                                        No lessons in this section yet.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endwhile; ?>
                    
                    <?php 
                    // Show lessons without sections if any
                    if (isset($lessons_array['no_section']) && !empty($lessons_array['no_section'])): 
                    ?>
                        <div class="border-b border-gray-100">
                            <div class="px-4 py-2 bg-gray-50">
                                <h4 class="font-medium text-gray-900">Other Lessons</h4>
                            </div>
                            <?php foreach ($lessons_array['no_section'] as $lesson_item): ?>
                                <div class="px-4 py-2 hover:bg-gray-50">
                                    <a href="?id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson_item['id']; ?>" 
                                       class="flex items-center justify-between text-sm">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-play-circle text-gray-400"></i>
                                            </div>
                                            <span class="ml-2 <?php echo $lesson_id == $lesson_item['id'] ? 'font-medium text-blue-600' : 'text-gray-700'; ?>">
                                                <?php echo htmlspecialchars($lesson_item['title']); ?>
                                            </span>
                                        </div>
                                        <span class="text-xs text-gray-500">
                                            <?php echo $lesson_item['duration'] ? formatDuration($lesson_item['duration']) : ''; ?>
                                        </span>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php else: 
                        // Fallback: Display lessons without sections
                        if ($lessons && $lessons->num_rows > 0): 
                    ?>
                        <div class="px-4 py-2 bg-gray-50">
                            <h4 class="font-medium text-gray-900">Lessons</h4>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php while ($lesson_item = $lessons->fetch_assoc()): ?>
                                <div class="px-4 py-3 hover:bg-gray-50">
                                    <a href="?id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson_item['id']; ?>" 
                                       class="flex items-center justify-between text-sm">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <i class="fas fa-play-circle text-gray-400"></i>
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
                        </div>
                    <?php else: ?>
                        <div class="px-4 py-3 text-sm text-gray-500">
                            No content available yet.
                        </div>
                    <?php endif; ?>
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
                                <?php if (isset($lesson['section_title']) && $lesson['section_title']): ?>
                                    <p class="text-sm text-blue-600 font-medium mb-1">
                                        <i class="fas fa-folder mr-1"></i><?php echo htmlspecialchars($lesson['section_title']); ?>
                                    </p>
                                <?php endif; ?>
                                <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($lesson['title']); ?></h2>
                                <p class="text-sm text-gray-600 mt-1">
                                    Lesson <?php echo $lesson['order_number']; ?> • 
                                    <?php echo $lesson['duration'] ? formatDuration($lesson['duration']) : 'No duration set'; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4">
                        <!-- Lesson Description -->
                        <?php if ($lesson['description']): ?>
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Description</h3>
                                <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($lesson['description'])); ?></p>
                            </div>
                        <?php endif; ?>

                        <!-- Lesson Content -->
                        <?php if ($lesson['content']): ?>
                            <div class="mb-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-3">Content</h3>
                                <div class="prose max-w-none lesson-content">
                                    <?php echo $lesson['content']; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Main Video -->
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
                                        if (!empty(trim($video_url))):
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
                                        endif;
                                    endforeach;
                                endif;
                                ?>
                            </div>
                        <?php endif; ?>

                        <!-- Main Image -->
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
                                            if (!empty(trim($image_url))):
                                    ?>
                                        <img src="<?php echo htmlspecialchars($image_url); ?>" 
                                             alt="Lesson Image" 
                                             class="max-w-full h-auto rounded-lg">
                                    <?php 
                                            endif;
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
                        <p class="text-gray-500">Choose a lesson from the sidebar to view its content.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
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
    .section-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    .section-content.active {
        max-height: 1000px;
    }
    .section-arrow.rotated {
        transform: rotate(-90deg);
    }
</style>

<script>
    function toggleSection(sectionId) {
        const content = document.getElementById(sectionId);
        const arrow = document.getElementById('arrow_' + sectionId.replace('section_', ''));
        
        if (content.classList.contains('active')) {
            content.classList.remove('active');
            arrow.classList.add('rotated');
        } else {
            content.classList.add('active');
            arrow.classList.remove('rotated');
        }
    }

    // Initialize sections to be open by default
    document.addEventListener('DOMContentLoaded', function() {
        const sections = document.querySelectorAll('.section-content');
        sections.forEach(section => {
            section.classList.add('active');
        });
    });
</script>

<?php require_once '../includes/footer.php'; ?>