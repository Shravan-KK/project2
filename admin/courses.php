<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$action = $_GET['action'] ?? 'list';
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Handle course form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_course']) || isset($_POST['edit_course'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $category = sanitizeInput($_POST['category']);
        $price = (float)$_POST['price'];
        $duration = sanitizeInput($_POST['duration']);
        $level = sanitizeInput($_POST['level']);
        $status = sanitizeInput($_POST['status']);
        $intro_video_url = sanitizeInput($_POST['intro_video_url']);
        $thumbnail_url = sanitizeInput($_POST['thumbnail_url']);
        $max_students = (int)$_POST['max_students'];
        $visibility = sanitizeInput($_POST['visibility']);
        $password = sanitizeInput($_POST['password']);
        $activation_date = !empty($_POST['activation_date']) ? $_POST['activation_date'] : null;
        
        if (empty($title) || empty($description) || empty($category)) {
            $error = 'Please fill in all required fields';
        } else {
            if (isset($_POST['add_course'])) {
                // Add new course
                $sql = "INSERT INTO courses (title, description, category, price, duration, level, status, intro_video_url, thumbnail_url, max_students, visibility, password, activation_date, teacher_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssdsssssisss", $title, $description, $category, $price, $duration, $level, $status, $intro_video_url, $thumbnail_url, $max_students, $visibility, $password, $activation_date, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success = 'Course added successfully';
                    $action = 'list';
                } else {
                    $error = 'Failed to add course';
                }
            } else {
                // Edit existing course
                $sql = "UPDATE courses SET title = ?, description = ?, category = ?, price = ?, duration = ?, level = ?, status = ?, intro_video_url = ?, thumbnail_url = ?, max_students = ?, visibility = ?, password = ?, activation_date = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssdsssssisssi", $title, $description, $category, $price, $duration, $level, $status, $intro_video_url, $thumbnail_url, $max_students, $visibility, $password, $activation_date, $course_id);
                
                if ($stmt->execute()) {
                    $success = 'Course updated successfully';
                    $action = 'list';
                } else {
                    $error = 'Failed to update course';
                }
            }
        }
    }
}

// Get course for editing
$edit_course = null;
if ($action == 'edit' && $course_id) {
    $sql = "SELECT * FROM courses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_course = $result->fetch_assoc();
    
    if (!$edit_course) {
        $error = 'Course not found';
        $action = 'list';
    }
}

// Get all courses with teacher names
$sql = "SELECT c.*, u.name as teacher_name, 
        (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id) as enrollment_count
        FROM courses c 
        LEFT JOIN users u ON c.teacher_id = u.id 
        ORDER BY c.created_at DESC";
$courses = $conn->query($sql);
?>

<?php $page_title = 'Course Management - Admin'; ?>

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
                        <?php echo $action == 'add' ? 'Add New Course' : ($action == 'edit' ? 'Edit Course' : 'Course Management'); ?>
                    </h1>
                    <p class="mt-2 text-gray-600">Manage all courses in the system</p>
                </div>
                <?php if ($action == 'list'): ?>
                    <a href="?action=add" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-plus mr-2"></i>
                        Add Course
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($action == 'add' || $action == 'edit'): ?>
            <!-- Course Form -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <?php echo $action == 'add' ? 'Create New Course' : 'Edit Course'; ?>
                    </h3>
                </div>
                <form method="POST" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Course Title *</label>
                            <input type="text" name="title" id="title" required 
                                   value="<?php echo htmlspecialchars($edit_course['title'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Category *</label>
                            <select name="category" id="category" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Category</option>
                                <option value="Programming" <?php echo ($edit_course['category'] ?? '') == 'Programming' ? 'selected' : ''; ?>>Programming</option>
                                <option value="Design" <?php echo ($edit_course['category'] ?? '') == 'Design' ? 'selected' : ''; ?>>Design</option>
                                <option value="Business" <?php echo ($edit_course['category'] ?? '') == 'Business' ? 'selected' : ''; ?>>Business</option>
                                <option value="Marketing" <?php echo ($edit_course['category'] ?? '') == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Technology" <?php echo ($edit_course['category'] ?? '') == 'Technology' ? 'selected' : ''; ?>>Technology</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700">Price (₹)</label>
                            <input type="number" name="price" id="price" step="0.01" min="0"
                                   value="<?php echo htmlspecialchars($edit_course['price'] ?? '0'); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="duration" class="block text-sm font-medium text-gray-700">Duration</label>
                            <input type="text" name="duration" id="duration" placeholder="e.g., 8 weeks, 40 hours"
                                   value="<?php echo htmlspecialchars($edit_course['duration'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="level" class="block text-sm font-medium text-gray-700">Level</label>
                            <select name="level" id="level" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="">Select Level</option>
                                <option value="Beginner" <?php echo ($edit_course['level'] ?? '') == 'Beginner' ? 'selected' : ''; ?>>Beginner</option>
                                <option value="Intermediate" <?php echo ($edit_course['level'] ?? '') == 'Intermediate' ? 'selected' : ''; ?>>Intermediate</option>
                                <option value="Advanced" <?php echo ($edit_course['level'] ?? '') == 'Advanced' ? 'selected' : ''; ?>>Advanced</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" id="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="active" <?php echo ($edit_course['status'] ?? '') == 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo ($edit_course['status'] ?? '') == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="draft" <?php echo ($edit_course['status'] ?? '') == 'draft' ? 'selected' : ''; ?>>Draft</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="intro_video_url" class="block text-sm font-medium text-gray-700">Intro Video URL</label>
                            <input type="url" name="intro_video_url" id="intro_video_url" placeholder="https://youtube.com/watch?v=..."
                                   value="<?php echo htmlspecialchars($edit_course['intro_video_url'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="thumbnail_url" class="block text-sm font-medium text-gray-700">Thumbnail URL</label>
                            <input type="url" name="thumbnail_url" id="thumbnail_url" placeholder="https://example.com/image.jpg"
                                   value="<?php echo htmlspecialchars($edit_course['thumbnail_url'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="max_students" class="block text-sm font-medium text-gray-700">Max Students</label>
                            <input type="number" name="max_students" id="max_students" min="1" placeholder="0 for unlimited"
                                   value="<?php echo htmlspecialchars($edit_course['max_students'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="visibility" class="block text-sm font-medium text-gray-700">Visibility</label>
                            <select name="visibility" id="visibility" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="public" <?php echo ($edit_course['visibility'] ?? '') == 'public' ? 'selected' : ''; ?>>Public</option>
                                <option value="private" <?php echo ($edit_course['visibility'] ?? '') == 'private' ? 'selected' : ''; ?>>Private</option>
                                <option value="password" <?php echo ($edit_course['visibility'] ?? '') == 'password' ? 'selected' : ''; ?>>Password Protected</option>
                            </select>
                        </div>
                        
                        <div id="password_field" style="display: none;">
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="text" name="password" id="password" placeholder="Enter course password"
                                   value="<?php echo htmlspecialchars($edit_course['password'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                        
                        <div>
                            <label for="activation_date" class="block text-sm font-medium text-gray-700">Activation Date</label>
                            <input type="datetime-local" name="activation_date" id="activation_date"
                                   value="<?php echo $edit_course['activation_date'] ? date('Y-m-d\TH:i', strtotime($edit_course['activation_date'])) : ''; ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        </div>
                    </div>
                    
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                        <textarea name="description" id="description" rows="4" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?php echo htmlspecialchars($edit_course['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="flex justify-end space-x-3">
                        <a href="?action=list" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" name="<?php echo $action == 'add' ? 'add_course' : 'edit_course'; ?>" 
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <?php echo $action == 'add' ? 'Add Course' : 'Update Course'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Courses List -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        All Courses (<?php echo $courses->num_rows; ?> total)
                    </h3>
                </div>
                
                <?php if ($courses->num_rows > 0): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php while ($course = $courses->fetch_assoc()): ?>
                            <li>
                                <div class="px-4 py-4 sm:px-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <i class="fas fa-graduation-cap text-indigo-600"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="flex items-center">
                                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h4>
                                                    <div class="ml-2 flex items-center space-x-2">
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $course['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                            <?php echo ucfirst($course['status']); ?>
                                                        </span>
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                            <?php echo htmlspecialchars($course['category']); ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($course['description'], 0, 100)) . '...'; ?></p>
                                                <div class="mt-2 flex items-center text-sm text-gray-500">
                                                    <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($course['teacher_name'] ?? 'Unknown'); ?></span>
                                                    <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $course['enrollment_count']; ?> students</span>
                                                    <span class="ml-4"><i class="fas fa-rupee-sign mr-1"></i> ₹<?php echo number_format($course['price'], 2); ?></span>
                                                    <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($course['created_at']); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <a href="course_content.php?course_id=<?php echo $course['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                                <i class="fas fa-eye mr-2"></i>
                                                View Lessons
                                            </a>
                                            <a href="?action=edit&id=<?php echo $course['id']; ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
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
                        <i class="fas fa-graduation-cap text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No courses found.</p>
                        <p class="text-sm text-gray-400 mt-2">Create your first course to get started.</p>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Show/hide password field based on visibility selection
        document.getElementById('visibility').addEventListener('change', function() {
            const passwordField = document.getElementById('password_field');
            if (this.value === 'password') {
                passwordField.style.display = 'block';
            } else {
                passwordField.style.display = 'none';
            }
        });

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            const visibilitySelect = document.getElementById('visibility');
            if (visibilitySelect.value === 'password') {
                document.getElementById('password_field').style.display = 'block';
            }
        });
    </script>
</body>
</html>
