<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$lesson_id = $_GET['id'] ?? null;
if (!$lesson_id) {
    header('Location: courses.php');
    exit;
}

$page_title = 'Lesson Editor - Admin';

// Get lesson information
$lesson_sql = "SELECT l.*, s.title as section_title, c.title as course_title 
               FROM lessons l
               JOIN course_sections s ON l.section_id = s.id
               JOIN courses c ON l.course_id = c.id
               WHERE l.id = ?";
$lesson_stmt = $conn->prepare($lesson_sql);
$lesson_stmt->bind_param("i", $lesson_id);
$lesson_stmt->execute();
$lesson = $lesson_stmt->get_result()->fetch_assoc();

if (!$lesson) {
    header('Location: courses.php');
    exit;
}

// Get lesson videos
$videos_sql = "SELECT * FROM lesson_videos WHERE lesson_id = ? ORDER BY order_number ASC";
$videos_stmt = $conn->prepare($videos_sql);
$videos_stmt->bind_param("i", $lesson_id);
$videos_stmt->execute();
$videos = $videos_stmt->get_result();

// Get lesson images
$images_sql = "SELECT * FROM lesson_images WHERE lesson_id = ? ORDER BY order_number ASC";
$images_stmt = $conn->prepare($images_sql);
$images_stmt->bind_param("i", $lesson_id);
$images_stmt->execute();
$images = $images_stmt->get_result();

// Get lesson resources
$resources_sql = "SELECT * FROM lesson_resources WHERE lesson_id = ? ORDER BY order_number ASC";
$resources_stmt = $conn->prepare($resources_sql);
$resources_stmt->bind_param("i", $lesson_id);
$resources_stmt->execute();
$resources = $resources_stmt->get_result();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_lesson':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $content = trim($_POST['content']);
                $duration = (int)$_POST['duration'];
                $order_number = (int)$_POST['order_number'];
                
                $sql = "UPDATE lessons SET title = ?, description = ?, content = ?, duration = ?, order_number = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssiii", $title, $description, $content, $duration, $order_number, $lesson_id);
                
                if ($stmt->execute()) {
                    $success_message = "Lesson updated successfully!";
                    // Refresh lesson data
                    $lesson_stmt->execute();
                    $lesson = $lesson_stmt->get_result()->fetch_assoc();
                } else {
                    $error_message = "Error updating lesson: " . $stmt->error;
                }
                break;
                
            case 'add_video':
                $title = trim($_POST['video_title']);
                $video_url = trim($_POST['video_url']);
                $video_type = $_POST['video_type'];
                $duration = (int)$_POST['video_duration'];
                $order_number = (int)$_POST['video_order'];
                $is_primary = isset($_POST['is_primary']) ? 1 : 0;
                
                // If this is primary, unset others
                if ($is_primary) {
                    $unset_primary_sql = "UPDATE lesson_videos SET is_primary = 0 WHERE lesson_id = ?";
                    $unset_stmt = $conn->prepare($unset_primary_sql);
                    $unset_stmt->bind_param("i", $lesson_id);
                    $unset_stmt->execute();
                }
                
                $sql = "INSERT INTO lesson_videos (lesson_id, title, video_url, video_type, duration, order_number, is_primary) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssiii", $lesson_id, $title, $video_url, $video_type, $duration, $order_number, $is_primary);
                
                if ($stmt->execute()) {
                    $success_message = "Video added successfully!";
                    // Refresh videos
                    $videos_stmt->execute();
                    $videos = $videos_stmt->get_result();
                } else {
                    $error_message = "Error adding video: " . $stmt->error;
                }
                break;
                
            case 'add_image':
                $title = trim($_POST['image_title']);
                $image_url = trim($_POST['image_url']);
                $alt_text = trim($_POST['alt_text']);
                $order_number = (int)$_POST['image_order'];
                $is_featured = isset($_POST['is_featured']) ? 1 : 0;
                
                $sql = "INSERT INTO lesson_images (lesson_id, title, image_url, alt_text, order_number, is_featured) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssii", $lesson_id, $title, $image_url, $alt_text, $order_number, $is_featured);
                
                if ($stmt->execute()) {
                    $success_message = "Image added successfully!";
                    // Refresh images
                    $images_stmt->execute();
                    $images = $images_stmt->get_result();
                } else {
                    $error_message = "Error adding image: " . $stmt->error;
                }
                break;
                
            case 'add_resource':
                $title = trim($_POST['resource_title']);
                $file_url = trim($_POST['file_url']);
                $file_type = $_POST['file_type'];
                $file_size = (int)$_POST['file_size'];
                $description = trim($_POST['resource_description']);
                $order_number = (int)$_POST['resource_order'];
                
                $sql = "INSERT INTO lesson_resources (lesson_id, title, file_url, file_type, file_size, description, order_number) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssisi", $lesson_id, $title, $file_url, $file_type, $file_size, $description, $order_number);
                
                if ($stmt->execute()) {
                    $success_message = "Resource added successfully!";
                    // Refresh resources
                    $resources_stmt->execute();
                    $resources = $resources_stmt->get_result();
                } else {
                    $error_message = "Error adding resource: " . $stmt->error;
                }
                break;
        }
    }
}
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Lesson Editor</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($lesson['course_title']); ?> → <?php echo htmlspecialchars($lesson['section_title']); ?> → <?php echo htmlspecialchars($lesson['title']); ?></p>
            </div>
            <a href="course_sections.php?course_id=<?php echo $lesson['course_id']; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Course Sections
            </a>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if (isset($success_message)): ?>
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if (isset($error_message)): ?>
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Lesson Content Editor -->
        <div class="lg:col-span-2">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">Lesson Content</h3>
                </div>
                <div class="p-6">
                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_lesson">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Lesson Title</label>
                                <input type="text" name="title" value="<?php echo htmlspecialchars($lesson['title']); ?>" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Duration (minutes)</label>
                                <input type="number" name="duration" value="<?php echo $lesson['duration']; ?>" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($lesson['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Content (Rich Text)</label>
                            <textarea name="content" id="richTextEditor" rows="12" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo htmlspecialchars($lesson['content'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Order Number</label>
                            <input type="number" name="order_number" value="<?php echo $lesson['order_number']; ?>" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                                <i class="fas fa-save mr-2"></i>
                                Update Lesson
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Media Management Sidebar -->
        <div class="space-y-6">
            <!-- Videos Management -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Videos</h3>
                        <button onclick="openVideoModal()" class="text-blue-600 hover:text-blue-900">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="p-4">
                    <?php if ($videos->num_rows > 0): ?>
                        <div class="space-y-3">
                            <?php while ($video = $videos->fetch_assoc()): ?>
                                <div class="border rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-sm"><?php echo htmlspecialchars($video['title']); ?></h4>
                                        <?php if ($video['is_primary']): ?>
                                            <span class="px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">Primary</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($video['video_url']); ?></p>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <span class="mr-3"><?php echo $video['duration']; ?> min</span>
                                        <span><?php echo ucfirst($video['video_type']); ?></span>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 text-center py-4">No videos added yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Images Management -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Images</h3>
                        <button onclick="openImageModal()" class="text-green-600 hover:text-green-900">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="p-4">
                    <?php if ($images->num_rows > 0): ?>
                        <div class="space-y-3">
                            <?php while ($image = $images->fetch_assoc()): ?>
                                <div class="border rounded-lg p-3">
                                    <div class="flex items-center justify-between mb-2">
                                        <h4 class="font-medium text-sm"><?php echo htmlspecialchars($image['title'] ?? 'Untitled'); ?></h4>
                                        <?php if ($image['is_featured']): ?>
                                            <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Featured</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($image['image_url']); ?></p>
                                    <?php if ($image['alt_text']): ?>
                                        <p class="text-xs text-gray-500">Alt: <?php echo htmlspecialchars($image['alt_text']); ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 text-center py-4">No images added yet</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Resources Management -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-medium text-gray-900">Resources</h3>
                        <button onclick="openResourceModal()" class="text-purple-600 hover:text-purple-900">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <div class="p-4">
                    <?php if ($resources->num_rows > 0): ?>
                        <div class="space-y-3">
                            <?php while ($resource = $resources->fetch_assoc()): ?>
                                <div class="border rounded-lg p-3">
                                    <h4 class="font-medium text-sm mb-2"><?php echo htmlspecialchars($resource['title']); ?></h4>
                                    <p class="text-xs text-gray-500 mb-2"><?php echo htmlspecialchars($resource['file_url']); ?></p>
                                    <div class="flex items-center text-xs text-gray-500">
                                        <span class="mr-3"><?php echo ucfirst($resource['file_type'] ?? 'Unknown'); ?></span>
                                        <?php if ($resource['file_size']): ?>
                                            <span><?php echo number_format($resource['file_size'] / 1024, 1); ?> KB</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-sm text-gray-500 text-center py-4">No resources added yet</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Video Modal -->
<div id="videoModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Video</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_video">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Video Title</label>
                    <input type="text" name="video_title" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Video URL</label>
                    <input type="url" name="video_url" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Type</label>
                        <select name="video_type" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="youtube">YouTube</option>
                            <option value="vimeo">Vimeo</option>
                            <option value="mp4">MP4</option>
                            <option value="webm">WebM</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Duration (min)</label>
                        <input type="number" name="video_duration" value="10" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                    <input type="number" name="video_order" value="<?php echo $videos->num_rows + 1; ?>" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_primary" class="mr-2">
                        <span class="text-sm text-gray-700">Set as primary video</span>
                    </label>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeVideoModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Add Video
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Image Modal -->
<div id="imageModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Image</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_image">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Image Title</label>
                    <input type="text" name="image_title" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                    <input type="url" name="image_url" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Alt Text</label>
                    <input type="text" name="alt_text" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                    <input type="number" name="image_order" value="<?php echo $images->num_rows + 1; ?>" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_featured" class="mr-2">
                        <span class="text-sm text-gray-700">Set as featured image</span>
                    </label>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeImageModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                        Add Image
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Resource Modal -->
<div id="resourceModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Resource</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_resource">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Resource Title</label>
                    <input type="text" name="resource_title" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">File URL</label>
                    <input type="url" name="file_url" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">File Type</label>
                        <input type="text" name="file_type" placeholder="e.g., PDF, DOC" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">File Size (KB)</label>
                        <input type="number" name="file_size" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="resource_description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                    <input type="number" name="resource_order" value="<?php echo $resources->num_rows + 1; ?>" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeResourceModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700">
                        Add Resource
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Rich text editor initialization (using a simple approach - you can integrate CKEditor or TinyMCE here)
document.addEventListener('DOMContentLoaded', function() {
    const editor = document.getElementById('richTextEditor');
    if (editor) {
        // Add basic formatting buttons
        const toolbar = document.createElement('div');
        toolbar.className = 'mb-2 p-2 bg-gray-100 rounded border';
        toolbar.innerHTML = `
            <button type="button" onclick="formatText('bold')" class="px-2 py-1 mr-1 text-sm bg-white border rounded hover:bg-gray-50"><strong>B</strong></button>
            <button type="button" onclick="formatText('italic')" class="px-2 py-1 mr-1 text-sm bg-white border rounded hover:bg-gray-50"><em>I</em></button>
            <button type="button" onclick="formatText('underline')" class="px-2 py-1 mr-1 text-sm bg-white border rounded hover:bg-gray-50"><u>U</u></button>
            <button type="button" onclick="insertLink()" class="px-2 py-1 mr-1 text-sm bg-white border rounded hover:bg-gray-50">🔗</button>
        `;
        editor.parentNode.insertBefore(toolbar, editor);
    }
});

function formatText(command) {
    document.execCommand(command, false, null);
}

function insertLink() {
    const url = prompt('Enter URL:');
    if (url) {
        document.execCommand('createLink', false, url);
    }
}

// Modal functions
function openVideoModal() {
    document.getElementById('videoModal').classList.remove('hidden');
}

function closeVideoModal() {
    document.getElementById('videoModal').classList.add('hidden');
}

function openImageModal() {
    document.getElementById('imageModal').classList.remove('hidden');
}

function closeImageModal() {
    document.getElementById('imageModal').classList.add('hidden');
}

function openResourceModal() {
    document.getElementById('resourceModal').classList.remove('hidden');
}

function closeResourceModal() {
    document.getElementById('resourceModal').classList.add('hidden');
}

// Close modals when clicking outside
document.getElementById('videoModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeVideoModal();
    }
});

document.getElementById('imageModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeImageModal();
    }
});

document.getElementById('resourceModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeResourceModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 