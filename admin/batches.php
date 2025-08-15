<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$page_title = 'Batch Management - Admin';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_batch':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $max_students = (int)$_POST['max_students'];
                
                $sql = "INSERT INTO batches (name, description, start_date, end_date, max_students) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssi", $name, $description, $start_date, $end_date, $max_students);
                
                if ($stmt->execute()) {
                    $success_message = "Batch created successfully!";
                } else {
                    $error_message = "Error creating batch: " . $stmt->error;
                }
                break;
                
            case 'update_batch':
                $batch_id = (int)$_POST['batch_id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                $max_students = (int)$_POST['max_students'];
                $status = $_POST['status'];
                
                $sql = "UPDATE batches SET name = ?, description = ?, start_date = ?, end_date = ?, max_students = ?, status = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssisi", $name, $description, $start_date, $end_date, $max_students, $status, $batch_id);
                
                if ($stmt->execute()) {
                    $success_message = "Batch updated successfully!";
                } else {
                    $error_message = "Error updating batch: " . $stmt->error;
                }
                break;
                
            case 'assign_course':
                $batch_id = (int)$_POST['batch_id'];
                $course_id = (int)$_POST['course_id'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                
                $sql = "INSERT INTO batch_courses (batch_id, course_id, start_date, end_date) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE start_date = ?, end_date = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iissss", $batch_id, $course_id, $start_date, $end_date, $start_date, $end_date);
                
                if ($stmt->execute()) {
                    $success_message = "Course assigned to batch successfully!";
                } else {
                    $error_message = "Error assigning course: " . $stmt->error;
                }
                break;
                
            case 'assign_instructor':
                $batch_id = (int)$_POST['batch_id'];
                $instructor_id = (int)$_POST['instructor_id'];
                $role = $_POST['role'];
                $assigned_date = $_POST['assigned_date'];
                
                $sql = "INSERT INTO batch_instructors (batch_id, instructor_id, role, assigned_date) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE role = ?, assigned_date = ?, status = 'active'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iissss", $batch_id, $instructor_id, $role, $assigned_date, $role, $assigned_date);
                
                if ($stmt->execute()) {
                    $success_message = "Instructor assigned to batch successfully!";
                } else {
                    $error_message = "Error assigning instructor: " . $stmt->error;
                }
                break;
                
            case 'remove_instructor':
                $assignment_id = (int)$_POST['assignment_id'];
                
                $sql = "UPDATE batch_instructors SET status = 'inactive' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $assignment_id);
                
                if ($stmt->execute()) {
                    $success_message = "Instructor removed from batch successfully!";
                } else {
                    $error_message = "Error removing instructor: " . $stmt->error;
                }
                break;
                
            case 'remove_course':
                $assignment_id = (int)$_POST['assignment_id'];
                
                $sql = "UPDATE batch_courses SET status = 'inactive' WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $assignment_id);
                
                if ($stmt->execute()) {
                    $success_message = "Course removed from batch successfully!";
                } else {
                    $error_message = "Error removing course: " . $stmt->error;
                }
                break;
        }
    }
}

// Handle delete actions
if (isset($_GET['delete_batch']) && is_numeric($_GET['delete_batch'])) {
    $batch_id = (int)$_GET['delete_batch'];
    
    // Check if batch has enrollments
    $check_sql = "SELECT COUNT(*) as count FROM enrollments WHERE batch_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("i", $batch_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        $error_message = "Cannot delete batch with active enrollments. Please reassign students first.";
    } else {
        $sql = "DELETE FROM batches WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $batch_id);
        
        if ($stmt->execute()) {
            $success_message = "Batch deleted successfully!";
        } else {
            $error_message = "Error deleting batch: " . $stmt->error;
        }
    }
}

// Get all batches with statistics
$sql = "SELECT b.*, 
        COUNT(DISTINCT e.student_id) as enrolled_students,
        COUNT(DISTINCT bc.course_id) as assigned_courses
        FROM batches b 
        LEFT JOIN enrollments e ON b.id = e.batch_id AND e.status = 'active'
        LEFT JOIN batch_courses bc ON b.id = bc.batch_id AND bc.status = 'active'
        GROUP BY b.id 
        ORDER BY b.created_at DESC";
$batches = $conn->query($sql);

// Get all courses for assignment
$courses_sql = "SELECT id, title FROM courses WHERE status = 'active' ORDER BY title";
$courses = $conn->query($courses_sql);

// Get all instructors for assignment
$instructors_sql = "SELECT id, name, email FROM users WHERE user_type = 'teacher' AND status = 'active' ORDER BY name";
$instructors = $conn->query($instructors_sql);

// Get batch details for editing
$edit_batch = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $batch_id = (int)$_GET['edit'];
    $edit_sql = "SELECT * FROM batches WHERE id = ?";
    $edit_stmt = $conn->prepare($edit_sql);
    $edit_stmt->bind_param("i", $batch_id);
    $edit_stmt->execute();
    $edit_batch = $edit_stmt->get_result()->fetch_assoc();
}
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Batch Management</h1>
                <p class="mt-2 text-gray-600">Manage student batches and course assignments</p>
            </div>
            <button onclick="document.getElementById('createBatchModal').classList.remove('hidden')" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                <i class="fas fa-plus mr-2"></i>Create New Batch
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

        <!-- Batch Statistics -->
        <div class="mt-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <?php
            $stats_sql = "SELECT 
                COUNT(*) as total_batches,
                COUNT(CASE WHEN status = 'active' THEN 1 END) as active_batches,
                COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_batches,
                SUM(current_students) as total_students
                FROM batches";
            $stats = $conn->query($stats_sql)->fetch_assoc();
            ?>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-indigo-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Batches</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_batches']; ?></dd>
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
                                <dt class="text-sm font-medium text-gray-500 truncate">Active Batches</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['active_batches']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-check-circle text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Completed</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['completed_batches']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-graduate text-purple-600 text-2xl"></i>
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

        <!-- Batches List -->
        <div class="mt-8 bg-white shadow overflow-hidden sm:rounded-md">
            <div class="px-4 py-5 sm:px-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900">All Batches</h3>
            </div>
            <ul class="divide-y divide-gray-200">
                <?php if ($batches && $batches->num_rows > 0): ?>
                    <?php while ($batch = $batches->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center">
                                        <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($batch['name']); ?></h4>
                                        <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full 
                                            <?php echo $batch['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                                                    ($batch['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?>">
                                            <?php echo ucfirst($batch['status']); ?>
                                        </span>
                                    </div>
                                    <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($batch['description']); ?></p>
                                    <div class="mt-2 flex items-center text-sm text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i> <?php echo $batch['start_date']; ?> - <?php echo $batch['end_date']; ?></span>
                                        <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $batch['enrolled_students']; ?>/<?php echo $batch['max_students']; ?> students</span>
                                        <span class="ml-4"><i class="fas fa-book mr-1"></i> <?php echo $batch['assigned_courses']; ?> courses</span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="?edit=<?php echo $batch['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="batch_details.php?id=<?php echo $batch['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Details">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button onclick="openAssignModal(<?php echo $batch['id']; ?>, 'instructor')" class="text-green-600 hover:text-green-900" title="Assign Instructor">
                                        <i class="fas fa-user-plus"></i>
                                    </button>
                                    <button onclick="openAssignModal(<?php echo $batch['id']; ?>, 'course')" class="text-purple-600 hover:text-purple-900" title="Assign Course">
                                        <i class="fas fa-book-plus"></i>
                                    </button>
                                    <a href="?delete_batch=<?php echo $batch['id']; ?>" onclick="return confirm('Are you sure you want to delete this batch?')" class="text-red-600 hover:text-red-900" title="Delete Batch">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                <?php else: ?>
                    <li class="px-4 py-8 text-center">
                        <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No batches found.</p>
                        <p class="text-sm text-gray-400 mt-2">Create your first batch to get started.</p>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<!-- Create/Edit Batch Modal -->
<div id="createBatchModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full <?php echo $edit_batch ? '' : 'hidden'; ?>">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">
                <?php echo $edit_batch ? 'Edit Batch' : 'Create New Batch'; ?>
            </h3>
            <form method="POST">
                <input type="hidden" name="action" value="<?php echo $edit_batch ? 'update_batch' : 'create_batch'; ?>">
                <?php if ($edit_batch): ?>
                    <input type="hidden" name="batch_id" value="<?php echo $edit_batch['id']; ?>">
                <?php endif; ?>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Batch Name</label>
                    <input type="text" name="name" value="<?php echo $edit_batch ? htmlspecialchars($edit_batch['name']) : ''; ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Description</label>
                    <textarea name="description" rows="3" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"><?php echo $edit_batch ? htmlspecialchars($edit_batch['description']) : ''; ?></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $edit_batch ? $edit_batch['start_date'] : ''; ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">End Date</label>
                        <input type="date" name="end_date" value="<?php echo $edit_batch ? $edit_batch['end_date'] : ''; ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Max Students</label>
                        <input type="number" name="max_students" value="<?php echo $edit_batch ? $edit_batch['max_students'] : 30; ?>" min="1" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <?php if ($edit_batch): ?>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select name="status" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                                <option value="active" <?php echo $edit_batch['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $edit_batch['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                <option value="completed" <?php echo $edit_batch['status'] === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="document.getElementById('createBatchModal').classList.add('hidden')" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                        <?php echo $edit_batch ? 'Update Batch' : 'Create Batch'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Instructor Assignment Modal -->
<div id="assignInstructorModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Instructor to Batch</h3>
            <form method="POST">
                <input type="hidden" name="action" value="assign_instructor">
                <input type="hidden" name="batch_id" id="instructor_batch_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Select Instructor</label>
                    <select name="instructor_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Choose an instructor...</option>
                        <?php if ($instructors): ?>
                            <?php $instructors->data_seek(0); ?>
                            <?php while ($instructor = $instructors->fetch_assoc()): ?>
                                <option value="<?php echo $instructor['id']; ?>">
                                    <?php echo htmlspecialchars($instructor['name']); ?> (<?php echo htmlspecialchars($instructor['email']); ?>)
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Role</label>
                    <select name="role" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="lead">Lead Instructor</option>
                        <option value="assistant">Assistant Instructor</option>
                        <option value="mentor">Mentor</option>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Assignment Date</label>
                    <input type="date" name="assigned_date" value="<?php echo date('Y-m-d'); ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeAssignModal('instructor')" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                        Assign Instructor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Course Assignment Modal -->
<div id="assignCourseModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Course to Batch</h3>
            <form method="POST">
                <input type="hidden" name="action" value="assign_course">
                <input type="hidden" name="batch_id" id="course_batch_id">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Select Course</label>
                    <select name="course_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Choose a course...</option>
                        <?php if ($courses): ?>
                            <?php $courses->data_seek(0); ?>
                            <?php while ($course = $courses->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['title']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course Start Date</label>
                        <input type="date" name="start_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course End Date</label>
                        <input type="date" name="end_date" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                </div>
                
                <div class="flex justify-end space-x-3">
                    <button type="button" onclick="closeAssignModal('course')" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                        Cancel
                    </button>
                    <button type="submit" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                        Assign Course
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAssignModal(batchId, type) {
    if (type === 'instructor') {
        document.getElementById('instructor_batch_id').value = batchId;
        document.getElementById('assignInstructorModal').classList.remove('hidden');
    } else if (type === 'course') {
        document.getElementById('course_batch_id').value = batchId;
        document.getElementById('assignCourseModal').classList.remove('hidden');
    }
}

function closeAssignModal(type) {
    if (type === 'instructor') {
        document.getElementById('assignInstructorModal').classList.add('hidden');
    } else if (type === 'course') {
        document.getElementById('assignCourseModal').classList.add('hidden');
    }
}

// Close modals when clicking outside
window.onclick = function(event) {
    const instructorModal = document.getElementById('assignInstructorModal');
    const courseModal = document.getElementById('assignCourseModal');
    const createModal = document.getElementById('createBatchModal');
    
    if (event.target === instructorModal) {
        instructorModal.classList.add('hidden');
    }
    if (event.target === courseModal) {
        courseModal.classList.add('hidden');
    }
    if (event.target === createModal) {
        createModal.classList.add('hidden');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?> 