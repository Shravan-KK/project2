<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$section_id = isset($_GET['section_id']) ? (int)$_GET['section_id'] : 0;
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$section_id || !$course_id) {
    header('Location: courses.php');
    exit();
}

// Verify access to this course section
$verify_sql = "SELECT cs.*, c.title as course_title 
               FROM course_sections cs 
               JOIN courses c ON cs.course_id = c.id 
               WHERE cs.id = ? AND cs.course_id = ? AND 
               (c.teacher_id = ? OR c.id IN (
                   SELECT original_course_id FROM teacher_course_customizations WHERE teacher_id = ?
               ))";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("iiii", $section_id, $course_id, $teacher_id, $teacher_id);
$verify_stmt->execute();
$section = $verify_stmt->get_result()->fetch_assoc();

if (!$section) {
    header('Location: courses.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle AJAX requests for lesson data
if (isset($_GET['ajax']) && $_GET['ajax'] === 'get_lesson' && isset($_GET['lesson_id'])) {
    $lesson_id = (int)$_GET['lesson_id'];
    $lesson_sql = "SELECT * FROM lessons WHERE id = ? AND section_id = ? AND course_id = ?";
    $lesson_stmt = $conn->prepare($lesson_sql);
    $lesson_stmt->bind_param("iii", $lesson_id, $section_id, $course_id);
    $lesson_stmt->execute();
    $lesson_data = $lesson_stmt->get_result()->fetch_assoc();
    
    if ($lesson_data) {
        // Parse JSON fields
        $lesson_data['videos_array'] = $lesson_data['videos'] ? json_decode($lesson_data['videos'], true) : [];
        $lesson_data['images_array'] = $lesson_data['images'] ? json_decode($lesson_data['images'], true) : [];
        
        header('Content-Type: application/json');
        echo json_encode($lesson_data);
        exit;
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Lesson not found']);
        exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_lesson'])) {
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']); // Brief description
        $rich_content = $_POST['rich_content']; // Rich text content
        $video_url = sanitizeInput($_POST['video_url']);
        $duration = (int)$_POST['duration'];
        $order_number = (int)$_POST['order_number'];
        
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
        
        $sql = "INSERT INTO lessons (course_id, section_id, title, content, rich_content, video_url, duration, order_number, videos, images) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iissssiiss", $course_id, $section_id, $title, $content, $rich_content, $video_url, $duration, $order_number, $videos_json, $images_json);
        
        if ($stmt->execute()) {
            $lesson_id = $conn->insert_id;
            $success_message = "Lesson added successfully!";
            
            // Handle file uploads
            handleLessonMediaUploads($lesson_id, $conn);
        } else {
            $error_message = "Error adding lesson: " . $stmt->error;
        }
    }
    
    if (isset($_POST['edit_lesson'])) {
        $lesson_id = (int)$_POST['lesson_id'];
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']); // Brief description
        $rich_content = $_POST['rich_content']; // Rich text content
        $video_url = sanitizeInput($_POST['video_url']);
        $duration = (int)$_POST['duration'];
        $order_number = (int)$_POST['order_number'];
        
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
        
        $sql = "UPDATE lessons SET title = ?, content = ?, rich_content = ?, video_url = ?, duration = ?, order_number = ?, videos = ?, images = ? 
                WHERE id = ? AND section_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssiissii", $title, $content, $rich_content, $video_url, $duration, $order_number, $videos_json, $images_json, $lesson_id, $section_id);
        
        if ($stmt->execute()) {
            $success_message = "Lesson updated successfully!";
            
            // Handle file uploads
            handleLessonMediaUploads($lesson_id, $conn);
        } else {
            $error_message = "Error updating lesson: " . $stmt->error;
        }
    }
    
    if (isset($_POST['delete_lesson'])) {
        $lesson_id = (int)$_POST['lesson_id'];
        
        // Delete associated media files first
        $media_sql = "SELECT * FROM lesson_media WHERE lesson_id = ?";
        $media_stmt = $conn->prepare($media_sql);
        $media_stmt->bind_param("i", $lesson_id);
        $media_stmt->execute();
        $media_files = $media_stmt->get_result();
        
        while ($media = $media_files->fetch_assoc()) {
            if (file_exists($media['file_path'])) {
                unlink($media['file_path']);
            }
        }
        
        // Delete media records
        $delete_media = "DELETE FROM lesson_media WHERE lesson_id = ?";
        $media_stmt = $conn->prepare($delete_media);
        $media_stmt->bind_param("i", $lesson_id);
        $media_stmt->execute();
        
        // Delete the lesson
        $sql = "DELETE FROM lessons WHERE id = ? AND section_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $lesson_id, $section_id);
        
        if ($stmt->execute()) {
            $success_message = "Lesson deleted successfully!";
        } else {
            $error_message = "Error deleting lesson: " . $stmt->error;
        }
    }
}

// Function to handle media uploads
function handleLessonMediaUploads($lesson_id, $conn) {
    $upload_base = '../uploads/lessons/';
    
    // Handle video uploads
    if (isset($_FILES['videos']) && !empty($_FILES['videos']['name'][0])) {
        foreach ($_FILES['videos']['name'] as $key => $filename) {
            if ($_FILES['videos']['error'][$key] === UPLOAD_ERR_OK) {
                $file_info = pathinfo($filename);
                $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_info['filename']) . '.' . $file_info['extension'];
                $upload_path = $upload_base . 'videos/' . $safe_filename;
                
                if (move_uploaded_file($_FILES['videos']['tmp_name'][$key], $upload_path)) {
                    $insert_media = "INSERT INTO lesson_media (lesson_id, media_type, file_name, file_path, file_size, mime_type) 
                                    VALUES (?, 'video', ?, ?, ?, ?)";
                    $media_stmt = $conn->prepare($insert_media);
                    $file_size = $_FILES['videos']['size'][$key];
                    $mime_type = $_FILES['videos']['type'][$key];
                    $media_stmt->bind_param("issis", $lesson_id, $safe_filename, $upload_path, $file_size, $mime_type);
                    $media_stmt->execute();
                }
            }
        }
    }
    
    // Handle image uploads
    if (isset($_FILES['images']) && !empty($_FILES['images']['name'][0])) {
        foreach ($_FILES['images']['name'] as $key => $filename) {
            if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                $file_info = pathinfo($filename);
                $safe_filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $file_info['filename']) . '.' . $file_info['extension'];
                $upload_path = $upload_base . 'images/' . $safe_filename;
                
                if (move_uploaded_file($_FILES['images']['tmp_name'][$key], $upload_path)) {
                    $insert_media = "INSERT INTO lesson_media (lesson_id, media_type, file_name, file_path, file_size, mime_type) 
                                    VALUES (?, 'image', ?, ?, ?, ?)";
                    $media_stmt = $conn->prepare($insert_media);
                    $file_size = $_FILES['images']['size'][$key];
                    $mime_type = $_FILES['images']['type'][$key];
                    $media_stmt->bind_param("issis", $lesson_id, $safe_filename, $upload_path, $file_size, $mime_type);
                    $media_stmt->execute();
                }
            }
        }
    }
}

// Get lessons for this section (using existing columns for media counts)
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

$page_title = 'Section Lessons - ' . $section['title'];
?>

<!-- Include Quill.js for rich text editing -->
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Section Lessons</h1>
                <p class="mt-2 text-gray-600">
                    <a href="course_sections.php?course_id=<?php echo $course_id; ?>" class="text-indigo-600 hover:text-indigo-800">
                        <?php echo htmlspecialchars($section['course_title']); ?>
                    </a>
                    → <?php echo htmlspecialchars($section['title']); ?>
                </p>
            </div>
            <div class="space-x-3">
                <a href="course_sections.php?course_id=<?php echo $course_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Sections
                </a>
                <button onclick="openLessonModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-plus mr-2"></i>
                    Add Lesson
                </button>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Lessons List -->
    <div class="space-y-4">
        <?php if ($lessons->num_rows > 0): ?>
            <?php while ($lesson = $lessons->fetch_assoc()): ?>
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-start">
                                    <span class="flex items-center justify-center w-8 h-8 bg-green-100 text-green-600 rounded-full text-sm font-medium mr-3 mt-1">
                                        <?php echo $lesson['order_number']; ?>
                                    </span>
                                    <div class="flex-1">
                                        <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($lesson['title']); ?></h3>
                                        <?php if ($lesson['content']): ?>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($lesson['content'], 0, 150)); ?><?php echo strlen($lesson['content']) > 150 ? '...' : ''; ?></p>
                                        <?php endif; ?>
                                        
                                        <div class="flex items-center mt-2 text-xs text-gray-500 space-x-4">
                                            <?php if ($lesson['duration']): ?>
                                                <span><i class="fas fa-clock mr-1"></i> <?php echo $lesson['duration']; ?> min</span>
                                            <?php endif; ?>
                                            <?php if ($lesson['video_count'] > 0): ?>
                                                <span><i class="fas fa-video mr-1"></i> <?php echo $lesson['video_count']; ?> video(s)</span>
                                            <?php endif; ?>
                                            <?php if ($lesson['image_count'] > 0): ?>
                                                <span><i class="fas fa-image mr-1"></i> <?php echo $lesson['image_count']; ?> image(s)</span>
                                            <?php endif; ?>
                                            <?php if ($lesson['video_url']): ?>
                                                <span><i class="fas fa-external-link-alt mr-1"></i> External video</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 ml-4">
                                <button onclick="viewLessonDetails(<?php echo $lesson['id']; ?>)" 
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    <i class="fas fa-eye mr-2"></i>
                                    View
                                </button>
                                <button onclick="editLesson(<?php echo $lesson['id']; ?>)" 
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-edit mr-2"></i>
                                    Edit
                                </button>
                                <button onclick="confirmDeleteLesson(<?php echo $lesson['id']; ?>, '<?php echo htmlspecialchars($lesson['title']); ?>')" 
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                    <i class="fas fa-trash mr-2"></i>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-12 bg-white rounded-lg shadow">
                <i class="fas fa-book-open text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Lessons Yet</h3>
                <p class="text-gray-500 mb-4">Start adding lessons to this section.</p>
                <button onclick="openLessonModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-plus mr-2"></i>
                    Add First Lesson
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Lesson Modal -->
<div id="lessonModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-10 mx-auto p-5 border w-full max-w-4xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 id="lesson-modal-title" class="text-lg font-medium text-gray-900 mb-4">Add Lesson</h3>
            <form method="POST" action="" enctype="multipart/form-data">
                <input type="hidden" name="lesson_id" id="modal_lesson_id">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Lesson Title:</label>
                        <input type="text" name="title" id="modal_lesson_title" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label for="order_number" class="block text-sm font-medium text-gray-700 mb-2">Order:</label>
                        <input type="number" name="order_number" id="modal_lesson_order" min="1" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">Brief Description:</label>
                    <textarea name="content" id="modal_lesson_content" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="rich_content" class="block text-sm font-medium text-gray-700 mb-2">Detailed Content (Rich Text):</label>
                    <textarea name="rich_content" id="modal_rich_content" rows="10" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="video_url" class="block text-sm font-medium text-gray-700 mb-2">External Video URL:</label>
                        <input type="url" name="video_url" id="modal_video_url" 
                               placeholder="https://youtube.com/watch?v=..." 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    
                    <div>
                        <label for="duration" class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes):</label>
                        <input type="number" name="duration" id="modal_duration" min="0" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                
                <!-- Multiple Videos -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Videos (URLs):</label>
                    <div id="modal_videos_container">
                        <!-- Video inputs will be added here -->
                    </div>
                    <button type="button" onclick="addVideoField()" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-plus mr-1"></i> Add Video URL
                    </button>
                </div>

                <!-- Multiple Images -->
                <div class="mb-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Additional Images (URLs):</label>
                    <div id="modal_images_container">
                        <!-- Image inputs will be added here -->
                    </div>
                    <button type="button" onclick="addImageField()" class="mt-2 text-sm text-blue-600 hover:text-blue-800">
                        <i class="fas fa-plus mr-1"></i> Add Image URL
                    </button>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeLessonModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" name="add_lesson" id="lesson-submit-button"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
                        Add Lesson
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
            <p class="text-gray-600 mb-4">Are you sure you want to delete the lesson "<span id="delete-lesson-name"></span>"? This will also delete all associated media files.</p>
            
            <form method="POST" action="">
                <input type="hidden" name="lesson_id" id="delete_lesson_id">
                <input type="hidden" name="delete_lesson" value="1">
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeDeleteModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700">
                        Delete Lesson
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let isEditMode = false;

// Initialize Quill editor
var quill;
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('modal_rich_content')) {
        quill = new Quill('#modal_rich_content', {
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
        
        // Handle form submission to get Quill content
        const form = document.querySelector('#lessonModal form');
        if (form) {
            form.addEventListener('submit', function() {
                // Get Quill content and put it in the hidden textarea
                const richContent = quill.root.innerHTML;
                const richContentTextarea = document.querySelector('textarea[name="rich_content"]');
                if (richContentTextarea) {
                    richContentTextarea.value = richContent;
                }
            });
        }
    }
});

function openLessonModal() {
    isEditMode = false;
    
    document.getElementById('lesson-modal-title').textContent = 'Add Lesson';
    document.getElementById('lesson-submit-button').textContent = 'Add Lesson';
    document.getElementById('lesson-submit-button').name = 'add_lesson';
    
    document.getElementById('modal_lesson_id').value = '';
    document.getElementById('modal_lesson_title').value = '';
    document.getElementById('modal_lesson_content').value = '';
    document.getElementById('modal_video_url').value = '';
    document.getElementById('modal_duration').value = '';
    document.getElementById('modal_lesson_order').value = <?php echo $lessons->num_rows + 1; ?>;
    
    if (quill) {
        quill.setContents([]);
    }
    
    // Initialize with empty video and image fields
    document.getElementById('modal_videos_container').innerHTML = '';
    document.getElementById('modal_images_container').innerHTML = '';
    addVideoField('');
    addImageField('');
    
    document.getElementById('lessonModal').classList.remove('hidden');
}

function closeLessonModal() {
    document.getElementById('lessonModal').classList.add('hidden');
}

function addVideoField(value = '') {
    const container = document.getElementById('modal_videos_container');
    const div = document.createElement('div');
    div.className = 'flex items-center space-x-2 mb-2';
    div.innerHTML = `
        <input type="url" name="videos[]" value="${value}" 
               class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
               placeholder="Video URL">
        <button type="button" onclick="removeField(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

function addImageField(value = '') {
    const container = document.getElementById('modal_images_container');
    const div = document.createElement('div');
    div.className = 'flex items-center space-x-2 mb-2';
    div.innerHTML = `
        <input type="url" name="images[]" value="${value}" 
               class="flex-1 border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500" 
               placeholder="Image URL">
        <button type="button" onclick="removeField(this)" class="px-3 py-2 text-red-600 hover:text-red-800">
            <i class="fas fa-times"></i>
        </button>
    `;
    container.appendChild(div);
}

function removeField(button) {
    button.parentElement.remove();
}

function editLesson(lessonId) {
    isEditMode = true;
    
    document.getElementById('lesson-modal-title').textContent = 'Edit Lesson';
    document.getElementById('lesson-submit-button').textContent = 'Update Lesson';
    document.getElementById('lesson-submit-button').name = 'edit_lesson';
    document.getElementById('modal_lesson_id').value = lessonId;
    
    // Fetch lesson data via AJAX
    fetch(`?ajax=get_lesson&lesson_id=${lessonId}&section_id=<?php echo $section_id; ?>&course_id=<?php echo $course_id; ?>`)
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                alert('Error loading lesson data: ' + data.error);
                return;
            }
            
            // Populate form fields
            document.getElementById('modal_lesson_title').value = data.title || '';
            document.getElementById('modal_lesson_content').value = data.content || '';
            document.getElementById('modal_video_url').value = data.video_url || '';
            document.getElementById('modal_duration').value = data.duration || '';
            document.getElementById('modal_lesson_order').value = data.order_number || '';
            
            // Set rich content in Quill editor
            if (quill && data.rich_content) {
                quill.root.innerHTML = data.rich_content;
            } else if (quill) {
                quill.setContents([]);
            }
            
            // Clear and populate videos
            const videosContainer = document.getElementById('modal_videos_container');
            if (videosContainer) {
                videosContainer.innerHTML = '';
                if (data.videos_array && data.videos_array.length > 0) {
                    data.videos_array.forEach(video => {
                        if (video) {
                            addVideoField(video);
                        }
                    });
                } else {
                    addVideoField('');
                }
            }
            
            // Clear and populate images  
            const imagesContainer = document.getElementById('modal_images_container');
            if (imagesContainer) {
                imagesContainer.innerHTML = '';
                if (data.images_array && data.images_array.length > 0) {
                    data.images_array.forEach(image => {
                        if (image) {
                            addImageField(image);
                        }
                    });
                } else {
                    addImageField('');
                }
            }
            
            // Open modal
            document.getElementById('lessonModal').classList.remove('hidden');
        })
        .catch(error => {
            console.error('Error fetching lesson data:', error);
            alert('Error loading lesson data. Please try again.');
        });
}

function viewLessonDetails(lessonId) {
    // Redirect to lesson details page
    window.location.href = 'lesson_details.php?id=' + lessonId + '&section_id=<?php echo $section_id; ?>&course_id=<?php echo $course_id; ?>';
}

function confirmDeleteLesson(lessonId, lessonName) {
    document.getElementById('delete_lesson_id').value = lessonId;
    document.getElementById('delete-lesson-name').textContent = lessonName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modals when clicking outside
document.getElementById('lessonModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLessonModal();
    }
});

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>