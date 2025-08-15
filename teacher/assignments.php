<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_title = 'Assignments - Teacher';

// Handle assignment creation/editing
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_assignment']) || isset($_POST['update_assignment'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $course_id = (int)$_POST['course_id'];
        $due_date = $_POST['due_date'];
        $due_time = $_POST['due_time'];
        $max_points = (int)$_POST['max_points'];
        $instructions = sanitizeInput($_POST['instructions']);
        $attachment_url = sanitizeInput($_POST['attachment_url']);
        
        // Verify teacher owns the course
        $course_check = "SELECT id FROM courses WHERE id = ? AND teacher_id = ?";
        $course_stmt = $conn->prepare($course_check);
        $course_stmt->bind_param("ii", $course_id, $teacher_id);
        $course_stmt->execute();
        if (!$course_stmt->get_result()->fetch_assoc()) {
            $error_message = "Invalid course selected.";
        } else {
            if (isset($_POST['create_assignment'])) {
                $sql = "INSERT INTO assignments (title, description, course_id, due_date, due_time, max_points, instructions, attachment_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisssss", $title, $description, $course_id, $due_date, $due_time, $max_points, $instructions, $attachment_url);
            } else {
                $sql = "UPDATE assignments SET title = ?, description = ?, course_id = ?, due_date = ?, due_time = ?, max_points = ?, instructions = ?, attachment_url = ? WHERE id = ? AND course_id IN (SELECT id FROM courses WHERE teacher_id = ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssisssssii", $title, $description, $course_id, $due_date, $due_time, $max_points, $instructions, $attachment_url, $assignment_id, $teacher_id);
            }
            
            if ($stmt->execute()) {
                $success_message = isset($_POST['create_assignment']) ? "Assignment created successfully!" : "Assignment updated successfully!";
                $action = 'list';
            } else {
                $error_message = "Error saving assignment.";
            }
        }
    }
}

// Get assignment for editing
$assignment = null;
if ($assignment_id && $action == 'edit') {
    $sql = "SELECT a.* FROM assignments a 
            JOIN courses c ON a.course_id = c.id 
            WHERE a.id = ? AND c.teacher_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $assignment_id, $teacher_id);
    $stmt->execute();
    $assignment = $stmt->get_result()->fetch_assoc();
    
    if (!$assignment) {
        header('Location: assignments.php');
        exit;
    }
}

// Get teacher's courses for dropdown
$courses_sql = "SELECT id, title FROM courses WHERE teacher_id = ? ORDER BY title ASC";
$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bind_param("i", $teacher_id);
$courses_stmt->execute();
$courses = $courses_stmt->get_result();

// Get teacher's assignments
$assignments_sql = "SELECT a.*, c.title as course_title,
        (SELECT COUNT(*) FROM submissions WHERE assignment_id = a.id) as submission_count,
        (SELECT COUNT(*) FROM enrollments WHERE course_id = a.course_id AND status = 'active') as enrolled_students
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        WHERE c.teacher_id = ? 
        ORDER BY a.due_date DESC";
$assignments_stmt = $conn->prepare($assignments_sql);
$assignments_stmt->bind_param("i", $teacher_id);
$assignments_stmt->execute();
$assignments = $assignments_stmt->get_result();
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?php echo $action == 'create' ? 'Create New Assignment' : ($action == 'edit' ? 'Edit Assignment' : 'Assignments'); ?>
                    </h1>
                    <p class="mt-2 text-gray-600">
                        <?php echo $action == 'create' ? 'Create a new assignment for your students' : ($action == 'edit' ? 'Update assignment details' : 'Manage assignments and track submissions'); ?>
                    </p>
                </div>
                <?php if ($action == 'list'): ?>
                    <a href="?action=create" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>
                        Create Assignment
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
            <!-- Assignment Form -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <?php echo $action == 'create' ? 'Assignment Information' : 'Edit Assignment Information'; ?>
                    </h3>
                </div>
                <form method="POST" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="title" class="block text-sm font-medium text-gray-700">Assignment Title *</label>
                            <input type="text" id="title" name="title" required
                                   value="<?php echo htmlspecialchars($assignment['title'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label for="course_id" class="block text-sm font-medium text-gray-700">Course *</label>
                            <select id="course_id" name="course_id" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                                <option value="">Select Course</option>
                                <?php while ($course = $courses->fetch_assoc()): ?>
                                    <option value="<?php echo $course['id']; ?>" <?php echo ($assignment['course_id'] ?? '') == $course['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($course['title']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div>
                            <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date *</label>
                            <input type="date" id="due_date" name="due_date" required
                                   value="<?php echo $assignment['due_date'] ?? ''; ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label for="due_time" class="block text-sm font-medium text-gray-700">Due Time</label>
                            <input type="time" id="due_time" name="due_time"
                                   value="<?php echo $assignment['due_time'] ?? ''; ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label for="max_points" class="block text-sm font-medium text-gray-700">Maximum Points *</label>
                            <input type="number" id="max_points" name="max_points" min="1" required
                                   value="<?php echo $assignment['max_points'] ?? '100'; ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                        </div>

                        <div>
                            <label for="attachment_url" class="block text-sm font-medium text-gray-700">Attachment URL</label>
                            <input type="url" id="attachment_url" name="attachment_url"
                                   value="<?php echo htmlspecialchars($assignment['attachment_url'] ?? ''); ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                   placeholder="https://example.com/file.pdf">
                        </div>
                    </div>

                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                        <textarea id="description" name="description" rows="4" required
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                  placeholder="Describe the assignment..."><?php echo htmlspecialchars($assignment['description'] ?? ''); ?></textarea>
                    </div>

                    <div>
                        <label for="instructions" class="block text-sm font-medium text-gray-700">Instructions</label>
                        <textarea id="instructions" name="instructions" rows="3"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                  placeholder="Provide specific instructions for students..."><?php echo htmlspecialchars($assignment['instructions'] ?? ''); ?></textarea>
                    </div>

                    <div class="flex justify-end space-x-3">
                        <a href="assignments.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            Cancel
                        </a>
                        <button type="submit" name="<?php echo $action == 'create' ? 'create_assignment' : 'update_assignment'; ?>"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-save mr-2"></i>
                            <?php echo $action == 'create' ? 'Create Assignment' : 'Update Assignment'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php else: ?>
            <!-- Assignments List -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Your Assignments (<?php echo $assignments->num_rows; ?> total)
                    </h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php if ($assignments->num_rows > 0): ?>
                        <?php while ($assignment_item = $assignments->fetch_assoc()): ?>
                            <div class="px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-12 w-12 rounded-full bg-green-100 flex items-center justify-center">
                                                <i class="fas fa-clipboard-list text-green-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center">
                                                <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($assignment_item['title']); ?></h4>
                                                <div class="ml-2 flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                        <?php echo htmlspecialchars($assignment_item['course_title']); ?>
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800">
                                                        <?php echo $assignment_item['max_points']; ?> points
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($assignment_item['description'], 0, 150)) . '...'; ?></p>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <span><i class="fas fa-calendar mr-1"></i> Due: <?php echo formatDate($assignment_item['due_date']); ?></span>
                                                <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $assignment_item['submission_count']; ?>/<?php echo $assignment_item['enrolled_students']; ?> submissions</span>
                                                <span class="ml-4"><i class="fas fa-star mr-1"></i> <?php echo $assignment_item['max_points']; ?> points</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="assignment_submissions.php?assignment_id=<?php echo $assignment_item['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            <i class="fas fa-eye mr-2"></i>
                                            Submissions
                                        </a>
                                        <a href="?action=edit&id=<?php echo $assignment_item['id']; ?>" class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                            <i class="fas fa-edit mr-2"></i>
                                            Edit
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="px-6 py-8 text-center">
                            <i class="fas fa-clipboard-list text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No assignments created yet.</p>
                            <p class="text-sm text-gray-400 mt-2">Create your first assignment to get started.</p>
                            <a href="?action=create" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-plus mr-2"></i>
                                Create Assignment
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>


<?php require_once '../includes/footer.php'; ?>