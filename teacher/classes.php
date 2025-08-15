<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$page_title = 'My Classes - Teacher';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_class':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $course_id = (int)$_POST['course_id'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $schedule = trim($_POST['schedule']);
                $max_students = (int)$_POST['max_students'];
                $meeting_platform = trim($_POST['meeting_platform']);
                $meeting_link = trim($_POST['meeting_link']);
                
                $sql = "INSERT INTO classes (name, description, course_id, instructor_id, start_date, end_date, schedule, max_students, meeting_platform, meeting_link) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssiississ", $name, $description, $course_id, $teacher_id, $start_date, $end_date, $schedule, $max_students, $meeting_platform, $meeting_link);
                
                if ($stmt->execute()) {
                    $success_message = "Class created successfully!";
                } else {
                    $error_message = "Error creating class: " . $stmt->error;
                }
                break;
                
            case 'update_class':
                $class_id = (int)$_POST['class_id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $schedule = trim($_POST['schedule']);
                $max_students = (int)$_POST['max_students'];
                $status = $_POST['status'];
                $meeting_platform = trim($_POST['meeting_platform']);
                $meeting_link = trim($_POST['meeting_link']);
                
                $sql = "UPDATE classes SET name = ?, description = ?, start_date = ?, end_date = ?, schedule = ?, max_students = ?, status = ?, meeting_platform = ?, meeting_link = ? WHERE id = ? AND instructor_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssisssii", $name, $description, $start_date, $end_date, $schedule, $max_students, $status, $meeting_platform, $meeting_link, $class_id, $teacher_id);
                
                if ($stmt->execute()) {
                    $success_message = "Class updated successfully!";
                } else {
                    $error_message = "Error updating class: " . $stmt->error;
                }
                break;
                
            case 'add_session':
                $class_id = (int)$_POST['class_id'];
                $session_number = (int)$_POST['session_number'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $session_date = $_POST['session_date'];
                $start_time = $_POST['start_time'];
                $end_time = $_POST['end_time'];
                $meeting_link = trim($_POST['meeting_link']);
                $notes = trim($_POST['notes']);
                
                $sql = "INSERT INTO class_sessions (class_id, session_number, title, description, session_date, start_time, end_time, meeting_link, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iisssssss", $class_id, $session_number, $title, $description, $session_date, $start_time, $end_time, $meeting_link, $notes);
                
                if ($stmt->execute()) {
                    $success_message = "Session added successfully!";
                } else {
                    $error_message = "Error adding session: " . $stmt->error;
                }
                break;
        }
    }
}

// Handle delete actions
if (isset($_GET['delete_class']) && is_numeric($_GET['delete_class'])) {
    $class_id = (int)$_GET['delete_class'];
    
    // Check if class has enrollments
    $check_sql = "SELECT COUNT(*) as count FROM class_enrollments WHERE class_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $class_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error_message = "Cannot delete class with active enrollments. Please remove students first.";
    } else {
        $sql = "DELETE FROM classes WHERE id = ? AND instructor_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $class_id, $teacher_id);
        
        if ($stmt->execute()) {
            $success_message = "Class deleted successfully!";
        } else {
            $error_message = "Error deleting class: " . $stmt->error;
        }
    }
}

// Get teacher's classes with statistics
$sql = "SELECT c.*, co.title as course_title,
        COUNT(DISTINCT ce.student_id) as enrolled_students,
        COUNT(DISTINCT cs.id) as total_sessions,
        COUNT(DISTINCT CASE WHEN cs.status = 'completed' THEN cs.id END) as completed_sessions
        FROM classes c 
        JOIN courses co ON c.course_id = co.id
        LEFT JOIN class_enrollments ce ON c.id = ce.class_id AND ce.status = 'active'
        LEFT JOIN class_sessions cs ON c.id = cs.class_id
        WHERE c.instructor_id = ? 
        GROUP BY c.id 
        ORDER BY c.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$classes = $stmt->get_result();

// Get teacher's courses for class creation
$courses_sql = "SELECT id, title FROM courses WHERE teacher_id = ? AND status = 'active' ORDER BY title";
$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bind_param("i", $teacher_id);
$courses_stmt->execute();
$teacher_courses = $courses_stmt->get_result();

// Get class details for editing
$edit_class = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $class_id = (int)$_GET['edit'];
    $edit_sql = "SELECT * FROM classes WHERE id = ? AND instructor_id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("ii", $class_id, $teacher_id);
    $edit_stmt->execute();
    $edit_class = $edit_stmt->get_result()->fetch_assoc();
}
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Classes</h1>
                <p class="mt-2 text-gray-600">Manage your instructor-led classes and sessions</p>
            </div>
            <button onclick="document.getElementById('createClassModal').classList.remove('hidden')" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                <i class="fas fa-plus mr-2"></i>Create New Class
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

        <!-- Class Statistics -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <?php
            $stats_sql = "SELECT 
                COUNT(*) as total_classes,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_classes,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_classes,
                SUM(current_students) as total_students
                FROM classes WHERE instructor_id = ?";
            $stats_stmt = $conn->prepare($stats_sql);
            $stats_stmt->bind_param("i", $teacher_id);
            $stats_stmt->execute();
            $stats = $stats_stmt->get_result()->fetch_assoc();
            ?>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chalkboard-teacher text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Classes</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_classes']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-play-circle text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Classes</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['active_classes']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['completed_classes']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-orange-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_students'] ?: 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Classes List -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">All Classes</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                <?php if ($classes && $classes->num_rows > 0): ?>
                    <?php while ($class = $classes->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($class['name']); ?></h4>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $class['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                    ($class['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 
                                                    ($class['status'] === 'cancelled' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')); ?>">
                                            <?php echo ucfirst($class['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($class['description']); ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($class['course_title']); ?></span>
                                        <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo $class['start_date']; ?> - <?php echo $class['end_date']; ?></span>
                                        <span class="ml-4"><i class="fas fa-clock mr-1"></i> <?php echo htmlspecialchars($class['schedule']); ?></span>
                                        <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $class['enrolled_students']; ?>/<?php echo $class['max_students']; ?> students</span>
                                        <span class="ml-4"><i class="fas fa-video mr-1"></i> <?php echo $class['total_sessions']; ?> sessions (<?php echo $class['completed_sessions']; ?> completed)</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="class_details.php?id=<?php echo $class['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="?edit=<?php echo $class['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="class_sessions.php?class_id=<?php echo $class['id']; ?>" class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-calendar-alt"></i>
                                    </a>
                                    <a href="?delete_class=<?php echo $class['id']; ?>" onclick="return confirm('Are you sure you want to delete this class?')" class="text-red-600 hover:text-red-900">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="px-4 py-8 text-center">
                        <i class="fas fa-chalkboard-teacher text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No classes found.</p>
                        <p class="text-sm text-gray-400 mt-2">Create your first class to get started.</p>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Create/Edit Class Modal -->
<div id="createClassModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full <?php echo $edit_class ? '' : 'hidden'; ?>">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <?php echo $edit_class ? 'Edit Class' : 'Create New Class'; ?>
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_class ? 'update_class' : 'create_class'; ?>">
                <?php if ($edit_class): ?>
                    <input type="hidden" name="class_id" value="<?php echo $edit_class['id']; ?>">
                <?php endif; ?>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Class Name</label>
                    <input type="text" name="name" value="<?php echo $edit_class ? htmlspecialchars($edit_class['name']) : ''; ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?php echo $edit_class ? htmlspecialchars($edit_class['description']) : ''; ?></textarea>
                </div>
                
                <?php if (!$edit_class): ?>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700">Course</label>
                        <select name="course_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select a course</option>
                            <?php while ($course = $teacher_courses->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>"><?php echo htmlspecialchars($course['title']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                <?php endif; ?>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $edit_class ? $edit_class['start_date'] : ''; ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" value="<?php echo $edit_class ? $edit_class['end_date'] : ''; ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Schedule</label>
                    <input type="text" name="schedule" value="<?php echo $edit_class ? htmlspecialchars($edit_class['schedule']) : ''; ?>" placeholder="e.g., Monday, Wednesday 9:00 AM - 11:00 AM" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Max Students</label>
                        <input type="number" name="max_students" value="<?php echo $edit_class ? $edit_class['max_students'] : 30; ?>" min="1" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <?php if ($edit_class): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="active" <?php echo $edit_class['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_class['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="completed" <?php echo $edit_class['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo $edit_class['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Meeting Platform</label>
                        <select name="meeting_platform" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Select platform</option>
                            <option value="Zoom" <?php echo ($edit_class && $edit_class['meeting_platform'] === 'Zoom') ? 'selected' : ''; ?>>Zoom</option>
                            <option value="Google Meet" <?php echo ($edit_class && $edit_class['meeting_platform'] === 'Google Meet') ? 'selected' : ''; ?>>Google Meet</option>
                            <option value="Microsoft Teams" <?php echo ($edit_class && $edit_class['meeting_platform'] === 'Microsoft Teams') ? 'selected' : ''; ?>>Microsoft Teams</option>
                            <option value="Skype" <?php echo ($edit_class && $edit_class['meeting_platform'] === 'Skype') ? 'selected' : ''; ?>>Skype</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Meeting Link</label>
                        <input type="url" name="meeting_link" value="<?php echo $edit_class ? htmlspecialchars($edit_class['meeting_link']) : ''; ?>" placeholder="https://..." class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('createClassModal').classList.add('hidden')" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        <?php echo $edit_class ? 'Update Class' : 'Create Class'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?> 