<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$course_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_title = 'My Courses - Teacher';

// Handle course creation/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_course']) || isset($_POST['update_course'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $category = sanitizeInput($_POST['category']);
        $price = (float)$_POST['price'];
        $intro_video_url = sanitizeInput($_POST['intro_video_url']);
        $thumbnail_url = sanitizeInput($_POST['thumbnail_url']);
        $max_students = (int)$_POST['max_students'];
        $visibility = sanitizeInput($_POST['visibility']);
        $password = sanitizeInput($_POST['password']);
        $activation_date = !empty($_POST['activation_date']) ? $_POST['activation_date'] : null;
        
        if (isset($_POST['create_course'])) {
            $sql = "INSERT INTO courses (title, description, category, price, teacher_id, intro_video_url, thumbnail_url, max_students, visibility, password, activation_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdsssssss", $title, $description, $category, $price, $teacher_id, $intro_video_url, $thumbnail_url, $max_students, $visibility, $password, $activation_date);
        } else {
            $sql = "UPDATE courses SET title = ?, description = ?, category = ?, price = ?, intro_video_url = ?, thumbnail_url = ?, max_students = ?, visibility = ?, password = ?, activation_date = ? WHERE id = ? AND teacher_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdssssssii", $title, $description, $category, $price, $intro_video_url, $thumbnail_url, $max_students, $visibility, $password, $activation_date, $course_id, $teacher_id);
        }
        
        if ($stmt->execute()) {
            $success_message = isset($_POST['create_course']) ? "Course created successfully!" : "Course updated successfully!";
            $action = 'list';
        } else {
            $error_message = "Error saving course.";
        }
    }
}

// Get course for editing
$course = null;
if ($course_id && $action == 'edit') {
    $sql = "SELECT * FROM courses WHERE id = ? AND teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $course_id, $teacher_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    
    if (!$course) {
        header('Location: courses.php');
        exit;
    }
}

// Get teacher's courses
$sql = "SELECT c.*, 
        (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id AND status = 'active') as enrolled_students,
        (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons
        FROM courses c 
        WHERE c.teacher_id = ? 
        ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses = $stmt->get_result();
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?php echo $action == 'create' ? 'Create New Course' : ($action == 'edit' ? 'Edit Course' : 'My Courses'); ?>
                    </h1>
                    <p class="mt-2 text-gray-600">
                        <?php echo $action == 'create' ? 'Create a new course for your students' : ($action == 'edit' ? 'Update course information' : 'Manage your courses and content'); ?>
                    </p>
                </div>
                <?php if ($action == 'list'): ?>
                    <a href="?action=create" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>
                        Create Course
                    </a>
                <?php endif; ?>
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

        <?php if ($action == 'create' || $action == 'edit'): ?>
            <!-- Course Form -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <?php echo $action == 'create' ? 'Course Information' : 'Edit Course Information'; ?>
                    </h3>
                </div>
                <form method="POST" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Course Title *</label>
                            <input type="text" id="title" name="title" required
                                   value="<?php echo htmlspecialchars($course['title'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label for="category" class="block text-sm font-medium text-gray-700">Category *</label>
                            <select id="category" name="category" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                <option value="">Select Category</option>
                                <option value="Programming" <?php echo ($course['category'] ?? '') == 'Programming' ? 'selected' : ''; ?>>Programming</option>
                                <option value="Design" <?php echo ($course['category'] ?? '') == 'Design' ? 'selected' : ''; ?>>Design</option>
                                <option value="Business" <?php echo ($course['category'] ?? '') == 'Business' ? 'selected' : ''; ?>>Business</option>
                                <option value="Marketing" <?php echo ($course['category'] ?? '') == 'Marketing' ? 'selected' : ''; ?>>Marketing</option>
                                <option value="Language" <?php echo ($course['category'] ?? '') == 'Language' ? 'selected' : ''; ?>>Language</option>
                                <option value="Other" <?php echo ($course['category'] ?? '') == 'Other' ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>

                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700">Price (₹) *</label>
                            <input type="number" id="price" name="price" step="0.01" min="0" required
                                   value="<?php echo $course['price'] ?? ''; ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label for="max_students" class="block text-sm font-medium text-gray-700">Maximum Students</label>
                            <input type="number" id="max_students" name="max_students" min="1"
                                   value="<?php echo $course['max_students'] ?? ''; ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label for="visibility" class="block text-sm font-medium text-gray-700">Visibility *</label>
                            <select id="visibility" name="visibility" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                <option value="public" <?php echo ($course['visibility'] ?? '') == 'public' ? 'selected' : ''; ?>>Public</option>
                                <option value="private" <?php echo ($course['visibility'] ?? '') == 'private' ? 'selected' : ''; ?>>Private</option>
                                <option value="password" <?php echo ($course['visibility'] ?? '') == 'password' ? 'selected' : ''; ?>>Password Protected</option>
                            </select>
                        </div>

                        <div id="password_field" style="display: <?php echo ($course['visibility'] ?? '') == 'password' ? 'block' : 'none'; ?>;">
                            <label for="password" class="block text-sm font-medium text-gray-700">Password</label>
                            <input type="text" id="password" name="password"
                                   value="<?php echo htmlspecialchars($course['password'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label for="activation_date" class="block text-sm font-medium text-gray-700">Activation Date</label>
                            <input type="datetime-local" id="activation_date" name="activation_date"
                                   value="<?php echo $course['activation_date'] ? date('Y-m-d\TH:i', strtotime($course['activation_date'])) : ''; ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                        </div>
                    </div>

                    <div>
                        <label for="intro_video_url" class="block text-sm font-medium text-gray-700">Intro Video URL</label>
                        <input type="url" id="intro_video_url" name="intro_video_url"
                               value="<?php echo htmlspecialchars($course['intro_video_url'] ?? ''); ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                               placeholder="https://www.youtube.com/watch?v=...">
                    </div>

                    <div>
                        <label for="thumbnail_url" class="block text-sm font-medium text-gray-700">Thumbnail URL</label>
                        <input type="url" id="thumbnail_url" name="thumbnail_url"
                               value="<?php echo htmlspecialchars($course['thumbnail_url'] ?? ''); ?>"
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                               placeholder="https://example.com/image.jpg">
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                        <textarea id="description" name="description" rows="4" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                  placeholder="Describe your course..."><?php echo htmlspecialchars($course['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" name="<?php echo $action == 'create' ? 'create_course' : 'update_course'; ?>"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo $action == 'create' ? 'Create Course' : 'Update Course'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Courses List -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Your Courses (<?php echo $courses->num_rows; ?> total)
                    </h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php if ($courses->num_rows > 0): ?>
                        <?php while ($course_item = $courses->fetch_assoc()): ?>
                            <div class="px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                                <i class="fas fa-graduation-cap text-green-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center">
                                                <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course_item['title']); ?></h4>
                                                <div class="ml-2 flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                        <?php echo htmlspecialchars($course_item['category']); ?>
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                                        <?php echo ucfirst($course_item['visibility']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($course_item['description'], 0, 150)) . '...'; ?></p>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <span><i class="fas fa-users mr-1"></i> <?php echo $course_item['enrolled_students']; ?> students</span>
                                                <span class="ml-4"><i class="fas fa-book mr-1"></i> <?php echo $course_item['total_lessons']; ?> lessons</span>
                                                <span class="ml-4"><i class="fas fa-rupee-sign mr-1"></i> <?php echo number_format($course_item['price']); ?></span>
                                                <span class="ml-4"><i class="fas fa-calendar mr-1"></i> Created: <?php echo formatDate($course_item['created_at']); ?></span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="course_content.php?course_id=<?php echo $course_item['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            <i class="fas fa-eye mr-2"></i>
                                            Content
                                        </a>
                                        <a href="?action=edit&id=<?php echo $course_item['id']; ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            <i class="fas fa-edit mr-2"></i>
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="px-6 py-8 text-center">
                            <i class="fas fa-graduation-cap text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No courses created yet.</p>
                            <p class="text-sm text-gray-400 mt-2">Create your first course to get started.</p>
                            <a href="?action=create" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-plus mr-2"></i>
                                Create Course
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
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
    </script>
</body>
</html>
