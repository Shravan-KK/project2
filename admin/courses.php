<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$page_title = 'Courses Management - Admin';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_course':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $teacher_id = (int)$_POST['teacher_id'];
                $duration = trim($_POST['duration']);
        $price = (float)$_POST['price'];
                $status = $_POST['status'];
                
                $sql = "INSERT INTO courses (title, description, teacher_id, duration, price, status) VALUES (?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisds", $title, $description, $teacher_id, $duration, $price, $status);
                
                if ($stmt->execute()) {
                    $success_message = "Course created successfully!";
                } else {
                    $error_message = "Error creating course: " . $stmt->error;
                }
                break;
                
            case 'update_course':
                $course_id = (int)$_POST['course_id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $duration = trim($_POST['duration']);
                $price = (float)$_POST['price'];
                $status = $_POST['status'];
                
                // Do not update teacher_id - course creator cannot be changed
                $sql = "UPDATE courses SET title = ?, description = ?, duration = ?, price = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssdsi", $title, $description, $duration, $price, $status, $course_id);
                
                if ($stmt->execute()) {
                    $success_message = "Course updated successfully!";
                } else {
                    $error_message = "Error updating course: " . $stmt->error;
                }
                break;
        }
    }
}

// Handle delete actions
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $course_id = (int)$_GET['delete'];
    
    // Check if course has enrollments
    $check_sql = "SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $course_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error_message = "Cannot delete course with active enrollments. Please remove students first.";
    } else {
        $sql = "DELETE FROM courses WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
        
        if ($stmt->execute()) {
            $success_message = "Course deleted successfully!";
        } else {
            $error_message = "Error deleting course: " . $stmt->error;
        }
    }
}

// Get all courses with statistics
$sql = "SELECT c.*, u.name as teacher_name, 
        COUNT(DISTINCT e.student_id) as enrolled_students,
        COUNT(DISTINCT l.id) as total_lessons,
        COUNT(DISTINCT a.id) as total_assignments,
        AVG(e.progress) as avg_progress
        FROM courses c 
        LEFT JOIN users u ON c.teacher_id = u.id 
        LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
        LEFT JOIN lessons l ON c.id = l.course_id
        LEFT JOIN assignments a ON c.id = a.course_id
        GROUP BY c.id 
        ORDER BY c.created_at DESC";
$courses = $conn->query($sql);

// Get teachers for course creation
$teachers_sql = "SELECT id, name, email FROM users WHERE user_type = 'teacher' ORDER BY name";
$teachers = $conn->query($teachers_sql);

// Get course details for editing
$edit_course = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $course_id = (int)$_GET['edit'];
    $edit_sql = "SELECT * FROM courses WHERE id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("i", $course_id);
    $edit_stmt->execute();
    $edit_course = $edit_stmt->get_result()->fetch_assoc();
}

// Get course statistics
$stats_sql = "SELECT 
    COUNT(*) as total_courses,
    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_courses,
    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_courses,
    AVG(price) as avg_price
    FROM courses";
$stats = $conn->query($stats_sql)->fetch_assoc();
?>

    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Courses Management</h1>
                <p class="mt-2 text-gray-600">Manage all courses in the system</p>
            </div>
            <button onclick="document.getElementById('createCourseModal').classList.remove('hidden')" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Create New Course
            </button>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Course Statistics -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-book text-blue-600 text-2xl"></i>
                </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Courses</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_courses']; ?></dd>
                            </dl>
            </div>
        </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-play-circle text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Courses</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['active_courses']; ?></dd>
                            </dl>
                        </div>
                        </div>
                        </div>
                        </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-pause-circle text-yellow-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Inactive Courses</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['inactive_courses']; ?></dd>
                            </dl>
                        </div>
                        </div>
                        </div>
                        </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-dollar-sign text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Avg Price</dt>
                                <dd class="text-lg font-medium text-gray-900">$<?php echo number_format($stats['avg_price'] ?: 0, 2); ?></dd>
                            </dl>
                        </div>
                    </div>
                    </div>
            </div>
                </div>
                
        <!-- Courses List -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">All Courses</h3>
            </div>
                    <ul class="divide-y divide-gray-200">
                <?php if ($courses && $courses->num_rows > 0): ?>
                        <?php while ($course = $courses->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                                    <div class="flex items-center justify-between">
                                <div class="flex-1">
                                                <div class="flex items-center">
                                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h4>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $course['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                            <?php echo ucfirst($course['status']); ?>
                                                        </span>
                                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($course['description'], 0, 150)) . '...'; ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-chalkboard-teacher mr-1"></i> <?php echo htmlspecialchars($course['teacher_name'] ?: 'Unassigned'); ?></span>
                                        <span class="ml-4"><i class="fas fa-clock mr-1"></i> <?php echo htmlspecialchars($course['duration']); ?></span>
                                        <span class="ml-4"><i class="fas fa-dollar-sign mr-1"></i> $<?php echo number_format($course['price'], 2); ?></span>
                                        <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $course['enrolled_students'] ?: 0; ?> students</span>
                                        <span class="ml-4"><i class="fas fa-file-alt mr-1"></i> <?php echo $course['total_lessons'] ?: 0; ?> lessons</span>
                                        <span class="ml-4"><i class="fas fa-tasks mr-1"></i> <?php echo $course['total_assignments'] ?: 0; ?> assignments</span>
                                                </div>
                                    
                                    <!-- Progress Bar -->
                                    <?php if ($course['enrolled_students'] > 0): ?>
                                        <div class="mt-3">
                                            <div class="flex items-center justify-between text-sm">
                                                <span class="text-gray-600">Average Student Progress</span>
                                                <span class="text-gray-900 font-medium"><?php echo round($course['avg_progress'] ?: 0, 1); ?>%</span>
                                                </div>
                                            <div class="mt-1 bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $course['avg_progress'] ?: 0; ?>%"></div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                    <a href="course_view_student.php?id=<?php echo $course['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View as Student">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?edit=<?php echo $course['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="course_sections.php?course_id=<?php echo $course['id']; ?>" class="text-green-600 hover:text-green-900" title="Manage Sections & Lessons">
                                        <i class="fas fa-folder-open"></i>
                                    </a>
                                    <a href="course_enrollments.php?course_id=<?php echo $course['id']; ?>" class="text-purple-600 hover:text-purple-900">
                                        <i class="fas fa-users"></i>
                                    </a>
                                    <a href="?delete=<?php echo $course['id']; ?>" onclick="return confirm('Are you sure you want to delete this course?')" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                <?php else: ?>
                    <li class="px-4 py-8 text-center">
                        <i class="fas fa-book text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No courses found.</p>
                        <p class="text-sm text-gray-400 mt-2">Create your first course to get started.</p>
                    </li>
                <?php endif; ?>
            </ul>
            </div>
    </div>
</div>

<!-- Create/Edit Course Modal -->
<div id="createCourseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full <?php echo $edit_course ? '' : 'hidden'; ?>">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <?php echo $edit_course ? 'Edit Course' : 'Create New Course'; ?>
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_course ? 'update_course' : 'create_course'; ?>">
                <?php if ($edit_course): ?>
                    <input type="hidden" name="course_id" value="<?php echo $edit_course['id']; ?>">
                <?php endif; ?>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Course Title</label>
                    <input type="text" name="title" value="<?php echo $edit_course ? htmlspecialchars($edit_course['title']) : ''; ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?php echo $edit_course ? htmlspecialchars($edit_course['description']) : ''; ?></textarea>
    </div>

                <?php if ($edit_course): ?>
                    <!-- Show teacher info as read-only when editing -->
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Course Creator</label>
                        <div class="mt-1 p-3 bg-gray-100 border border-gray-300 rounded-md">
                            <div class="flex items-center">
                                <i class="fas fa-user text-gray-500 mr-2"></i>
                                <span class="text-gray-900">
                                    <?php 
                                    // Get teacher name for display
                                    $teacher_name = 'Unknown Teacher';
                                    $teacher_email = '';
                                    $teachers_copy = $conn->query("SELECT name, email FROM users WHERE id = " . $edit_course['teacher_id']);
                                    if ($teacher_info = $teachers_copy->fetch_assoc()) {
                                        $teacher_name = $teacher_info['name'];
                                        $teacher_email = $teacher_info['email'];
                                    }
                                    echo htmlspecialchars($teacher_name);
                                    ?>
                                </span>
                                <span class="ml-2 text-sm text-gray-500">(<?php echo htmlspecialchars($teacher_email); ?>)</span>
                            </div>
                            <p class="text-xs text-gray-500 mt-1">Course creator cannot be changed</p>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Allow teacher selection only when creating new course -->
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Teacher</label>
                    <select name="teacher_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Select a teacher</option>
                        <?php while ($teacher = $teachers->fetch_assoc()): ?>
                                <option value="<?php echo $teacher['id']; ?>">
                                <?php echo htmlspecialchars($teacher['name']); ?> (<?php echo htmlspecialchars($teacher['email']); ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Duration</label>
                        <input type="text" name="duration" value="<?php echo $edit_course ? htmlspecialchars($edit_course['duration']) : ''; ?>" placeholder="e.g., 8 weeks" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Price ($)</label>
                        <input type="number" name="price" value="<?php echo $edit_course ? $edit_course['price'] : ''; ?>" step="0.01" min="0" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Status</label>
                    <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="active" <?php echo ($edit_course && $edit_course['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo ($edit_course && $edit_course['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                    </select>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('createCourseModal').classList.add('hidden')" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        <?php echo $edit_course ? 'Update Course' : 'Create Course'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>