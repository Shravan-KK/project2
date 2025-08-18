<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$action = $_GET['action'] ?? 'list';
$lesson_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Get section information if section_id is provided
$section = null;
if ($section_id) {
    $section_sql = "SELECT cs.*, c.id as course_id, c.title as course_title 
                    FROM course_sections cs 
                    JOIN courses c ON cs.course_id = c.id 
                    WHERE cs.id = ?";
    $section_stmt = $conn->prepare($section_sql);
    $section_stmt->bind_param("i", $section_id);
    $section_stmt->execute();
    $section = $section_stmt->get_result()->fetch_assoc();
    
    if ($section) {
        $course_id = $section['course_id'];
    }
}

// Get course information if course_id is provided
$course = null;
if ($course_id) {
    $course = getCourseById($conn, $course_id);
    if (!$course) {
        $error = 'Course not found';
        $course_id = 0;
    }
}

// Handle lesson form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_lesson']) || isset($_POST['edit_lesson'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $content = $_POST['content']; // Rich text content
        $order_number = (int)$_POST['order_number'];
        $duration = (int)$_POST['duration'];
        $video_url = sanitizeInput($_POST['video_url']);
        $image_url = sanitizeInput($_POST['image_url']);
        $attachment_url = sanitizeInput($_POST['attachment_url']);
        
        // Handle multiple videos and images
        $videos = [];
        $images = [];
        
        if (isset($_POST['videos']) && is_array($_POST['videos'])) {
            foreach ($_POST['videos'] as $video) {
                if (!empty(trim($video))) {
                    $videos[] = trim($video);
                }
            }
        }
        
        if (isset($_POST['images']) && is_array($_POST['images'])) {
            foreach ($_POST['images'] as $image) {
                if (!empty(trim($image))) {
                    $images[] = trim($image);
                }
            }
        }
        
        $videos_json = json_encode($videos);
        $images_json = json_encode($images);
        
        if (empty($title) || empty($description)) {
            $error = 'Please fill in all required fields';
        } else {
            if (isset($_POST['add_lesson'])) {
                // Add new lesson
                $sql = "INSERT INTO lessons (course_id, title, description, content, order_number, duration, video_url, image_url, attachment_url, videos, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssiisssss", $course_id, $title, $description, $content, $order_number, $duration, $video_url, $image_url, $attachment_url, $videos_json, $images_json);
                
                if ($stmt->execute()) {
                    $success = 'Lesson added successfully';
                    $action = 'list';
                } else {
                    $error = 'Failed to add lesson';
                }
            } else {
                // Edit existing lesson
                $sql = "UPDATE lessons SET title = ?, description = ?, content = ?, order_number = ?, duration = ?, video_url = ?, image_url = ?, attachment_url = ?, videos = ?, images = ? WHERE id = ? AND course_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssiisssssii", $title, $description, $content, $order_number, $duration, $video_url, $image_url, $attachment_url, $videos_json, $images_json, $lesson_id, $course_id);
                
                if ($stmt->execute()) {
                    $success = 'Lesson updated successfully';
                    $action = 'list';
                } else {
                    $error = 'Failed to update lesson';
                }
            }
        }
    }
}

// Get lesson for editing
$edit_lesson = null;
if ($action == 'edit' && $lesson_id && $course_id) {
    $sql = "SELECT * FROM lessons WHERE id = ? AND course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $lesson_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_lesson = $result->fetch_assoc();
    
    if (!$edit_lesson) {
        $error = 'Lesson not found';
        $action = 'list';
    }
}

// Get all lessons for the course or section
$lessons = null;
if ($course_id) {
    if ($section_id) {
        // Filter by section
        $sql = "SELECT * FROM lessons WHERE course_id = ? AND section_id = ? ORDER BY order_number ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $course_id, $section_id);
    } else {
        // All lessons for course
        $sql = "SELECT * FROM lessons WHERE course_id = ? ORDER BY order_number ASC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $course_id);
    }
    $stmt->execute();
    $lessons = $stmt->get_result();
}

$page_title = 'Lesson Management - Admin';
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <?php if ($error): ?>
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?php if ($section): ?>
                            Section Lessons: <?php echo htmlspecialchars($section['title']); ?>
                        <?php elseif ($course): ?>
                            Lessons for: <?php echo htmlspecialchars($course['title']); ?>
                        <?php else: ?>
                            Lesson Management
                        <?php endif; ?>
                    </h1>
                    <?php if ($section): ?>
                        <p class="mt-2 text-gray-600">Manage lessons for "<?php echo htmlspecialchars($section['title']); ?>" section in <?php echo htmlspecialchars($section['course_title']); ?></p>
                    <?php elseif ($course): ?>
                        <p class="mt-2 text-gray-600">Manage lessons for this course</p>
                    <?php endif; ?>
                </div>
                <div class="flex space-x-3">
                    <?php if ($section): ?>
                        <a href="section_view.php?id=<?php echo $section_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Section
                        </a>
                    <?php else: ?>
                        <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Courses
                        </a>
                    <?php endif; ?>
                    <?php if ($course): ?>
                        <a href="?action=add&course_id=<?php echo $course_id; ?><?php echo $section_id ? '&section_id=' . $section_id : ''; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <i class="fas fa-plus mr-2"></i>
                            Add Lesson
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($action == 'add' || $action == 'edit'): ?>
            <!-- Lesson Form -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">
                        <?php echo $action == 'add' ? 'Add New Lesson' : 'Edit Lesson'; ?>
                    </h3>
                    
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Lesson Title *</label>
                                <input type="text" name="title" id="title" required
                                       value="<?php echo htmlspecialchars($edit_lesson['title'] ?? ''); ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label for="order_number" class="block text-sm font-medium text-gray-700">Order Number</label>
                                <input type="number" name="order_number" id="order_number" min="1"
                                       value="<?php echo $edit_lesson['order_number'] ?? '1'; ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                            <textarea name="description" id="description" rows="3" required
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($edit_lesson['description'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700">Content</label>
                            <div id="editor" class="mt-1 border border-gray-300 rounded-md" style="height: 300px;">
                                <?php echo $edit_lesson['content'] ?? ''; ?>
                            </div>
                            <textarea name="content" id="content" style="display: none;"><?php echo htmlspecialchars($edit_lesson['content'] ?? ''); ?></textarea>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="duration" class="block text-sm font-medium text-gray-700">Duration (minutes)</label>
                                <input type="number" name="duration" id="duration" min="0"
                                       value="<?php echo $edit_lesson['duration'] ?? '0'; ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label for="video_url" class="block text-sm font-medium text-gray-700">Main Video URL</label>
                                <input type="url" name="video_url" id="video_url"
                                       value="<?php echo htmlspecialchars($edit_lesson['video_url'] ?? ''); ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                            
                            <div>
                                <label for="image_url" class="block text-sm font-medium text-gray-700">Main Image URL</label>
                                <input type="url" name="image_url" id="image_url"
                                       value="<?php echo htmlspecialchars($edit_lesson['image_url'] ?? ''); ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            </div>
                        </div>

                        <div>
                            <label for="attachment_url" class="block text-sm font-medium text-gray-700">Attachment URL</label>
                            <input type="url" name="attachment_url" id="attachment_url"
                                   value="<?php echo htmlspecialchars($edit_lesson['attachment_url'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>

                        <!-- Multiple Videos -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Videos</label>
                            <div id="videos_container">
                                <?php 
                                $videos = [];
                                if ($edit_lesson && $edit_lesson['videos']) {
                                    $videos = json_decode($edit_lesson['videos'], true) ?: [];
                                }
                                if (empty($videos)) {
                                    $videos = [''];
                                }
                                foreach ($videos as $index => $video): 
                                ?>
                                <div class="flex items-center space-x-2 mb-2">
                                    <input type="url" name="videos[]" value="<?php echo htmlspecialchars($video); ?>"
                                           class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="Video URL">
                                    <button type="button" onclick="removeVideo(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addVideo()" class="mt-2 px-3 py-2 text-sm text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-plus mr-1"></i> Add Video
                            </button>
                        </div>

                        <!-- Multiple Images -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Additional Images</label>
                            <div id="images_container">
                                <?php 
                                $images = [];
                                if ($edit_lesson && $edit_lesson['images']) {
                                    $images = json_decode($edit_lesson['images'], true) ?: [];
                                }
                                if (empty($images)) {
                                    $images = [''];
                                }
                                foreach ($images as $index => $image): 
                                ?>
                                <div class="flex items-center space-x-2 mb-2">
                                    <input type="url" name="images[]" value="<?php echo htmlspecialchars($image); ?>"
                                           class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                           placeholder="Image URL">
                                    <button type="button" onclick="removeImage(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <button type="button" onclick="addImage()" class="mt-2 px-3 py-2 text-sm text-indigo-600 hover:text-indigo-800">
                                <i class="fas fa-plus mr-1"></i> Add Image
                            </button>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="?course_id=<?php echo $course_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" name="<?php echo $action == 'add' ? 'add_lesson' : 'edit_lesson'; ?>" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                <?php echo $action == 'add' ? 'Add Lesson' : 'Update Lesson'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Lessons List -->
            <?php if ($course): ?>
                <div class="bg-white shadow overflow-hidden sm:rounded-md">
                    <div class="px-4 py-5 sm:px-6">
                        <h3 class="text-lg leading-6 font-medium text-gray-900">
                            Lessons (<?php echo $lessons ? $lessons->num_rows : 0; ?> total)
                        </h3>
                    </div>
                    
                    <?php if ($lessons && $lessons->num_rows > 0): ?>
                        <ul class="divide-y divide-gray-200">
                            <?php while ($lesson = $lessons->fetch_assoc()): ?>
                                <li>
                                    <div class="px-4 py-4 sm:px-6">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0">
                                                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                        <i class="fas fa-book text-indigo-600"></i>
                                                    </div>
                                                </div>
                                                <div class="ml-4">
                                                    <div class="flex items-center">
                                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($lesson['title']); ?></h4>
                                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                                            Lesson <?php echo $lesson['order_number']; ?>
                                                        </span>
                                                    </div>
                                                    <p class="text-sm text-gray-600 mt-1"><?php 
                                                        $description = isset($lesson['description']) ? $lesson['description'] : '';
                                                        echo htmlspecialchars(substr($description, 0, 100)) . '...'; 
                                                    ?></p>
                                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                                        <span><i class="fas fa-clock mr-1"></i> <?php echo $lesson['duration'] ? formatDuration($lesson['duration']) : 'No duration'; ?></span>
                                                        <?php if ($lesson['video_url']): ?>
                                                            <span class="ml-4"><i class="fas fa-video mr-1"></i> Has video</span>
                                                        <?php endif; ?>
                                                        <?php if (isset($lesson['image_url']) && $lesson['image_url']): ?>
                                                            <span class="ml-4"><i class="fas fa-image mr-1"></i> Has image</span>
                                                        <?php endif; ?>
                                                        <?php 
                                                        $videos = isset($lesson['videos']) && $lesson['videos'] ? json_decode($lesson['videos'], true) : null;
                                                        if ($videos && count($videos) > 0): 
                                                        ?>
                                                            <span class="ml-4"><i class="fas fa-video mr-1"></i> <?php echo count($videos); ?> additional videos</span>
                                                        <?php endif; ?>
                                                        <?php 
                                                        $images = isset($lesson['images']) && $lesson['images'] ? json_decode($lesson['images'], true) : null;
                                                        if ($images && count($images) > 0): 
                                                        ?>
                                                            <span class="ml-4"><i class="fas fa-image mr-1"></i> <?php echo count($images); ?> additional images</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                <a href="?action=edit&id=<?php echo $lesson['id']; ?>&course_id=<?php echo $course_id; ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                                    <i class="fas fa-edit mr-2"></i>
                                                    Edit
                                                </a>
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
                            <p class="text-sm text-gray-400 mt-2">Create your first lesson to get started.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="bg-white shadow rounded-lg">
                    <div class="px-4 py-8 text-center">
                        <i class="fas fa-book text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">Please select a course to manage its lessons.</p>
                        <a href="courses.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            View Courses
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <!-- Quill.js for rich text editing -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
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

        // Sync Quill content with hidden textarea
        quill.on('text-change', function() {
            document.getElementById('content').value = quill.root.innerHTML;
        });

        // Set initial content
        quill.root.innerHTML = document.getElementById('content').value;

        // Video management functions
        function addVideo() {
            const container = document.getElementById('videos_container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2 mb-2';
            div.innerHTML = `
                <input type="url" name="videos[]" class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Video URL">
                <button type="button" onclick="removeVideo(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(div);
        }

        function removeVideo(button) {
            button.parentElement.remove();
        }

        // Image management functions
        function addImage() {
            const container = document.getElementById('images_container');
            const div = document.createElement('div');
            div.className = 'flex items-center space-x-2 mb-2';
            div.innerHTML = `
                <input type="url" name="images[]" class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" placeholder="Image URL">
                <button type="button" onclick="removeImage(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            container.appendChild(div);
        }

        function removeImage(button) {
            button.parentElement.remove();
        }
    </script>


<?php require_once '../includes/footer.php'; ?>














