<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$lesson_id = isset($_GET['lesson_id']) ? (int)$_GET['lesson_id'] : 0;
$action = $_GET['action'] ?? 'view';
$page_title = 'Course Content - Teacher';

// Verify teacher owns the course
if ($course_id) {
    $course_check = "SELECT * FROM courses WHERE id = ? AND teacher_id = ?";
    $course_stmt = $conn->prepare($course_check);
    $course_stmt->bind_param("ii", $course_id, $teacher_id);
    $course_stmt->execute();
    $course = $course_stmt->get_result()->fetch_assoc();
    
    if (!$course) {
        header('Location: courses.php');
        exit;
    }
}

// Handle lesson creation/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_lesson']) || isset($_POST['update_lesson'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $content = $_POST['content']; // Rich text content
        $order_number = (int)$_POST['order_number'];
        $duration = sanitizeInput($_POST['duration']);
        $video_url = sanitizeInput($_POST['video_url']);
        $image_url = sanitizeInput($_POST['image_url']);
        $attachment_url = sanitizeInput($_POST['attachment_url']);
        
        // Handle multiple videos and images
        $videos = [];
        $images = [];
        
        if (isset($_POST['videos']) && is_array($_POST['videos'])) {
            foreach ($_POST['videos'] as $video) {
                if (!empty(trim($video))) {
                    $videos[] = sanitizeInput($video);
                }
            }
        }
        
        if (isset($_POST['images']) && is_array($_POST['images'])) {
            foreach ($_POST['images'] as $image) {
                if (!empty(trim($image))) {
                    $images[] = sanitizeInput($image);
                }
            }
        }
        
        $videos_json = !empty($videos) ? json_encode($videos) : null;
        $images_json = !empty($images) ? json_encode($images) : null;
        
        if (isset($_POST['create_lesson'])) {
            $sql = "INSERT INTO lessons (course_id, title, description, content, order_number, duration, video_url, image_url, attachment_url, videos, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssissssss", $course_id, $title, $description, $content, $order_number, $duration, $video_url, $image_url, $attachment_url, $videos_json, $images_json);
        } else {
            $sql = "UPDATE lessons SET title = ?, description = ?, content = ?, order_number = ?, duration = ?, video_url = ?, image_url = ?, attachment_url = ?, videos = ?, images = ? WHERE id = ? AND course_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssissssssii", $title, $description, $content, $order_number, $duration, $video_url, $image_url, $attachment_url, $videos_json, $images_json, $lesson_id, $course_id);
        }
        
        if ($stmt->execute()) {
            $success_message = isset($_POST['create_lesson']) ? "Lesson created successfully!" : "Lesson updated successfully!";
            $action = 'view';
        } else {
            $error_message = "Error saving lesson.";
        }
    }
}

// Get lesson for editing
$lesson = null;
if ($lesson_id && $action == 'edit') {
    $lesson_sql = "SELECT * FROM lessons WHERE id = ? AND course_id = ?";
    $lesson_stmt = $conn->prepare($lesson_sql);
    $lesson_stmt->bind_param("ii", $lesson_id, $course_id);
    $lesson_stmt->execute();
    $lesson = $lesson_stmt->get_result()->fetch_assoc();
}

// Get lessons for the course
$lessons = null;
if ($course_id) {
    $lessons_sql = "SELECT * FROM lessons WHERE course_id = ? ORDER BY order_number ASC";
    $lessons_stmt = $conn->prepare($lessons_sql);
    $lessons_stmt->bind_param("i", $course_id);
    $lessons_stmt->execute();
    $lessons = $lessons_stmt->get_result();
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
                                <span><i class="fas fa-tag mr-1"></i> <?php echo htmlspecialchars($course['category']); ?></span>
                                <span class="ml-4"><i class="fas fa-rupee-sign mr-1"></i> <?php echo number_format($course['price']); ?></span>
                                <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $course['max_students'] ? $course['max_students'] . ' max students' : 'Unlimited'; ?></span>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            <a href="assignments.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-clipboard-list mr-2"></i>
                                Manage Assignments
                            </a>
                            <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-arrow-left mr-2"></i>
                                Back to Courses
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($success_message)): ?>
                <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                <!-- Lessons Sidebar -->
                <div class="lg:col-span-1">
                    <div class="bg-white shadow rounded-lg">
                        <div class="px-4 py-3 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h3 class="text-lg font-medium text-gray-900">Lessons</h3>
                                <a href="?course_id=<?php echo $course_id; ?>&action=create" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-white bg-green-600 hover:bg-green-700">
                                    <i class="fas fa-plus mr-1"></i>
                                    Add
                                </a>
                            </div>
                        </div>
                        <div class="divide-y divide-gray-200">
                            <?php if ($lessons && $lessons->num_rows > 0): ?>
                                <?php while ($lesson_item = $lessons->fetch_assoc()): ?>
                                    <div class="px-4 py-3 hover:bg-gray-50">
                                        <div class="flex items-center justify-between">
                                            <a href="?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson_item['id']; ?>" 
                                               class="flex items-center justify-between text-sm flex-1">
                                                <div class="flex items-center">
                                                    <span class="text-xs text-gray-500 mr-2"><?php echo $lesson_item['order_number']; ?></span>
                                                    <span class="<?php echo $lesson_id == $lesson_item['id'] ? 'font-medium text-green-600' : 'text-gray-700'; ?>">
                                                        <?php echo htmlspecialchars($lesson_item['title']); ?>
                                                    </span>
                                                </div>
                                                <span class="text-xs text-gray-500">
                                                    <?php echo $lesson_item['duration'] ? formatDuration($lesson_item['duration']) : 'N/A'; ?>
                                                </span>
                                            </a>
                                            <div class="ml-2 flex items-center space-x-1">
                                                <a href="?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson_item['id']; ?>&action=edit" 
                                                   class="text-gray-400 hover:text-gray-600">
                                                    <i class="fas fa-edit text-xs"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="px-4 py-3 text-sm text-gray-500">
                                    No lessons created yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Lesson Content -->
                <div class="lg:col-span-3">
                    <?php if ($action == 'create' || $action == 'edit'): ?>
                        <!-- Lesson Form -->
                        <div class="bg-white shadow rounded-lg">
                            <div class="px-6 py-4 border-b border-gray-200">
                                <h3 class="text-lg font-medium text-gray-900">
                                    <?php echo $action == 'create' ? 'Create New Lesson' : 'Edit Lesson'; ?>
                                </h3>
                            </div>
                            <form method="POST" class="p-6 space-y-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="title" class="block text-sm font-medium text-gray-700">Lesson Title *</label>
                                        <input type="text" id="title" name="title" required
                                               value="<?php echo htmlspecialchars($lesson['title'] ?? ''); ?>"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>

                                    <div>
                                        <label for="order_number" class="block text-sm font-medium text-gray-700">Order Number *</label>
                                        <input type="number" id="order_number" name="order_number" min="1" required
                                               value="<?php echo $lesson['order_number'] ?? ''; ?>"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>

                                    <div>
                                        <label for="duration" class="block text-sm font-medium text-gray-700">Duration</label>
                                        <input type="time" id="duration" name="duration"
                                               value="<?php echo $lesson['duration'] ?? ''; ?>"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                    </div>

                                    <div>
                                        <label for="video_url" class="block text-sm font-medium text-gray-700">Main Video URL</label>
                                        <input type="url" id="video_url" name="video_url"
                                               value="<?php echo htmlspecialchars($lesson['video_url'] ?? ''); ?>"
                                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                               placeholder="https://www.youtube.com/watch?v=...">
                                    </div>
                                </div>

                                <div>
                                    <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                                    <textarea id="description" name="description" rows="3"
                                              class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                              placeholder="Brief description of the lesson..."><?php echo htmlspecialchars($lesson['description'] ?? ''); ?></textarea>
                                </div>

                                <div>
                                    <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                                    <div id="editor" class="mt-1 border border-gray-300 rounded-md">
                                        <?php echo $lesson['content'] ?? ''; ?>
                                    </div>
                                    <input type="hidden" name="content" id="content_input">
                                </div>

                                <div>
                                    <label for="image_url" class="block text-sm font-medium text-gray-700">Main Image URL</label>
                                    <input type="url" id="image_url" name="image_url"
                                           value="<?php echo htmlspecialchars($lesson['image_url'] ?? ''); ?>"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                           placeholder="https://example.com/image.jpg">
                                </div>

                                <div>
                                    <label for="attachment_url" class="block text-sm font-medium text-gray-700">Attachment URL</label>
                                    <input type="url" id="attachment_url" name="attachment_url"
                                           value="<?php echo htmlspecialchars($lesson['attachment_url'] ?? ''); ?>"
                                           class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                           placeholder="https://example.com/file.pdf">
                                </div>

                                <!-- Multiple Videos -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Additional Videos</label>
                                    <div id="videos_container" class="mt-1 space-y-2">
                                        <?php 
                                        if ($lesson && $lesson['videos']) {
                                            $videos = json_decode($lesson['videos'], true);
                                            if (is_array($videos)) {
                                                foreach ($videos as $video) {
                                                    echo '<div class="flex items-center space-x-2"><input type="url" name="videos[]" value="' . htmlspecialchars($video) . '" class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500" placeholder="Video URL"><button type="button" class="remove-video text-red-600 hover:text-red-800"><i class="fas fa-times"></i></button></div>';
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                    <button type="button" id="add_video" class="mt-2 inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                        <i class="fas fa-plus mr-1"></i> Add Video
                                    </button>
                                </div>

                                <!-- Multiple Images -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Additional Images</label>
                                    <div id="images_container" class="mt-1 space-y-2">
                                        <?php 
                                        if ($lesson && $lesson['images']) {
                                            $images = json_decode($lesson['images'], true);
                                            if (is_array($images)) {
                                                foreach ($images as $image) {
                                                    echo '<div class="flex items-center space-x-2"><input type="url" name="images[]" value="' . htmlspecialchars($image) . '" class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500" placeholder="Image URL"><button type="button" class="remove-image text-red-600 hover:text-red-800"><i class="fas fa-times"></i></button></div>';
                                                }
                                            }
                                        }
                                        ?>
                                    </div>
                                    <button type="button" id="add_image" class="mt-2 inline-flex items-center px-3 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50">
                                        <i class="fas fa-plus mr-1"></i> Add Image
                                    </button>
                                </div>

                                <div class="flex justify-end space-x-3">
                                    <a href="?course_id=<?php echo $course_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Cancel
                                    </a>
                                    <button type="submit" name="<?php echo $action == 'create' ? 'create_lesson' : 'update_lesson'; ?>"
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                        <i class="fas fa-save mr-2"></i>
                                        <?php echo $action == 'create' ? 'Create Lesson' : 'Update Lesson'; ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    <?php elseif ($lesson): ?>
                        <!-- View Lesson -->
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
                                    <a href="?course_id=<?php echo $course_id; ?>&lesson_id=<?php echo $lesson['id']; ?>&action=edit" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                        <i class="fas fa-edit mr-2"></i>
                                        Edit Lesson
                                    </a>
                                </div>
                            </div>
                            
                            <div class="px-6 py-4">
                                <?php if ($lesson['description']): ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-2">Description</h3>
                                        <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($lesson['description'])); ?></p>
                                    </div>
                                <?php endif; ?>

                                <?php if ($lesson['content']): ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">Content</h3>
                                        <div class="prose max-w-none lesson-content">
                                            <?php echo $lesson['content']; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?php if ($lesson['video_url']): ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">Main Video</h3>
                                        <div class="aspect-w-16 aspect-h-9">
                                            <iframe src="<?php echo htmlspecialchars($lesson['video_url']); ?>" 
                                                    class="w-full h-64 rounded-lg" 
                                                    frameborder="0" 
                                                    allowfullscreen></iframe>
                                        </div>
                                    </div>
                                <?php endif; ?>

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

                                <?php if ($lesson['image_url']): ?>
                                    <div class="mb-6">
                                        <h3 class="text-lg font-medium text-gray-900 mb-3">Main Image</h3>
                                        <img src="<?php echo htmlspecialchars($lesson['image_url']); ?>" 
                                             alt="Lesson Image" 
                                             class="max-w-full h-auto rounded-lg">
                                    </div>
                                <?php endif; ?>

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
                                <p class="text-gray-500">Choose a lesson from the sidebar to view or edit its content.</p>
                                <a href="?course_id=<?php echo $course_id; ?>&action=create" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                    <i class="fas fa-plus mr-2"></i>
                                    Create First Lesson
                                </a>
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
                    <p class="text-gray-500">The requested course could not be found or you don't have permission to access it.</p>
                    <a href="courses.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Courses
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quill.js for rich text editing -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

    <script>
        // Initialize Quill editor
        var quill = new Quill('#editor', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    ['link', 'image'],
                    ['clean']
                ]
            }
        });

        // Set initial content if editing
        <?php if ($lesson && $lesson['content']): ?>
        quill.root.innerHTML = <?php echo json_encode($lesson['content']); ?>;
        <?php endif; ?>

        // Update hidden input before form submission
        document.querySelector('form').addEventListener('submit', function() {
            document.getElementById('content_input').value = quill.root.innerHTML;
        });

        // Add video functionality
        document.getElementById('add_video').addEventListener('click', function() {
            const container = document.getElementById('videos_container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.innerHTML = '<input type="url" name="videos[]" class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500" placeholder="Video URL"><button type="button" class="remove-video text-red-600 hover:text-red-800"><i class="fas fa-times"></i></button>';
            container.appendChild(div);
        });

        // Add image functionality
        document.getElementById('add_image').addEventListener('click', function() {
            const container = document.getElementById('images_container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2';
            div.innerHTML = '<input type="url" name="images[]" class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500" placeholder="Image URL"><button type="button" class="remove-image text-red-600 hover:text-red-800"><i class="fas fa-times"></i></button>';
            container.appendChild(div);
        });

        // Remove video/image functionality
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-video') || e.target.closest('.remove-video')) {
                e.target.closest('div').remove();
            }
            if (e.target.classList.contains('remove-image') || e.target.closest('.remove-image')) {
                e.target.closest('div').remove();
            }
        });
    </script>

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
