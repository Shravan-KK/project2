<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/navigation.php';

requireAdmin();

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$action = $_GET['action'] ?? 'list';
$error = '';
$success = '';

// Get course information
if ($course_id) {
    $course = getCourseById($conn, $course_id);
    if (!$course) {
        $error = 'Course not found';
        header('Location: courses.php');
        exit();
    }
}

// Handle assignment form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_assignment']) || isset($_POST['edit_assignment'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $instructions = sanitizeInput($_POST['instructions']);
        $due_date = sanitizeInput($_POST['due_date']);
        $due_time = sanitizeInput($_POST['due_time']);
        $max_points = (int)$_POST['max_points'];
        $attachment_url = sanitizeInput($_POST['attachment_url']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($title) || empty($description)) {
            $error = 'Please fill in all required fields';
        } else {
            if (isset($_POST['add_assignment'])) {
                // Add new assignment
                $sql = "INSERT INTO assignments (course_id, title, description, instructions, due_date, due_time, max_points, attachment_url, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("isssssiis", $course_id, $title, $description, $instructions, $due_date, $due_time, $max_points, $attachment_url, $is_active);
                
                if ($stmt->execute()) {
                    $success = 'Assignment added successfully';
                    $action = 'list';
                } else {
                    $error = 'Failed to add assignment';
                }
            } else {
                // Edit existing assignment
                $sql = "UPDATE assignments SET title = ?, description = ?, instructions = ?, due_date = ?, due_time = ?, max_points = ?, attachment_url = ?, is_active = ? WHERE id = ? AND course_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssisii", $title, $description, $instructions, $due_date, $due_time, $max_points, $attachment_url, $is_active, $assignment_id, $course_id);
                
                if ($stmt->execute()) {
                    $success = 'Assignment updated successfully';
                    $action = 'list';
                } else {
                    $error = 'Failed to update assignment';
                }
            }
        }
    }
}

// Handle assignment deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_assignment_id = (int)$_GET['delete'];
    $sql = "DELETE FROM assignments WHERE id = ? AND course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $delete_assignment_id, $course_id);
    
    if ($stmt->execute()) {
        $success = 'Assignment deleted successfully';
    } else {
        $error = 'Failed to delete assignment';
    }
}

// Get all assignments for the course
if ($course_id) {
    $sql = "SELECT a.*, 
            (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) as submission_count,
            (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id AND s.grade IS NOT NULL) as graded_count
            FROM assignments a 
            WHERE a.course_id = ? 
            ORDER BY a.due_date ASC, a.due_time ASC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $assignments = $stmt->get_result();
}

// Get specific assignment for editing
$current_assignment = null;
if ($assignment_id && $course_id) {
    $sql = "SELECT * FROM assignments WHERE id = ? AND course_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $assignment_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_assignment = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assignment Management - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php renderNavigation('admin', 'assignments'); ?>

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
                        <?php echo $action == 'add' ? 'Add New Assignment' : ($action == 'edit' ? 'Edit Assignment' : 'Assignment Management'); ?>
                    </h1>
                    <?php if ($course): ?>
                        <p class="mt-2 text-gray-600">Course: <?php echo htmlspecialchars($course['title']); ?></p>
                    <?php endif; ?>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Courses
                    </a>
                    <?php if ($action == 'list' && $course_id): ?>
                        <a href="?course_id=<?php echo $course_id; ?>&action=add" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <i class="fas fa-plus mr-2"></i>
                            Add New Assignment
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if ($action == 'list' && $course_id): ?>
            <!-- Assignment List -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        Assignments (<?php echo $assignments->num_rows; ?> total)
                    </h3>
                </div>
                
                <?php if ($assignments->num_rows > 0): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                            <li>
                                <div class="px-4 py-4 sm:px-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0">
                                                <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                                    <i class="fas fa-file-alt text-indigo-600"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="flex items-center">
                                                    <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                                                    <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full <?php echo $assignment['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                        <?php echo $assignment['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </div>
                                                <p class="text-sm text-gray-500"><?php echo htmlspecialchars(substr($assignment['description'], 0, 100)) . '...'; ?></p>
                                                <div class="mt-2 flex items-center text-sm text-gray-500">
                                                    <span class="mr-4"><i class="fas fa-calendar mr-1"></i> Due: <?php echo formatDate($assignment['due_date']); ?> at <?php echo $assignment['due_time']; ?></span>
                                                    <span class="mr-4"><i class="fas fa-star mr-1"></i> <?php echo $assignment['max_points']; ?> points</span>
                                                    <span class="mr-4"><i class="fas fa-users mr-1"></i> <?php echo $assignment['submission_count']; ?> submissions</span>
                                                    <span><i class="fas fa-check mr-1"></i> <?php echo $assignment['graded_count']; ?> graded</span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <a href="assignment_submissions.php?course_id=<?php echo $course_id; ?>&assignment_id=<?php echo $assignment['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Submissions">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="?course_id=<?php echo $course_id; ?>&assignment_id=<?php echo $assignment['id']; ?>&action=edit" class="text-indigo-600 hover:text-indigo-900" title="Edit Assignment">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="?course_id=<?php echo $course_id; ?>&delete=<?php echo $assignment['id']; ?>" onclick="return confirm('Are you sure you want to delete this assignment?')" class="text-red-600 hover:text-red-900" title="Delete Assignment">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="px-4 py-8 text-center">
                        <i class="fas fa-file-alt text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No assignments found for this course</p>
                        <a href="?course_id=<?php echo $course_id; ?>&action=add" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <i class="fas fa-plus mr-2"></i>
                            Add First Assignment
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($action == 'add' || ($action == 'edit' && $current_assignment)): ?>
            <!-- Add/Edit Assignment Form -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <label for="title" class="block text-sm font-medium text-gray-700">Assignment Title *</label>
                                <input type="text" name="title" id="title" required 
                                       value="<?php echo htmlspecialchars($current_assignment['title'] ?? ''); ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <div class="sm:col-span-2">
                                <label for="description" class="block text-sm font-medium text-gray-700">Description *</label>
                                <textarea name="description" id="description" rows="3" required
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                          placeholder="Enter assignment description..."><?php echo htmlspecialchars($current_assignment['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="sm:col-span-2">
                                <label for="instructions" class="block text-sm font-medium text-gray-700">Instructions</label>
                                <textarea name="instructions" id="instructions" rows="4"
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                          placeholder="Enter detailed instructions for students..."><?php echo htmlspecialchars($current_assignment['instructions'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <label for="due_date" class="block text-sm font-medium text-gray-700">Due Date</label>
                                <input type="date" name="due_date" id="due_date" 
                                       value="<?php echo htmlspecialchars($current_assignment['due_date'] ?? ''); ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <label for="due_time" class="block text-sm font-medium text-gray-700">Due Time</label>
                                <input type="time" name="due_time" id="due_time" 
                                       value="<?php echo htmlspecialchars($current_assignment['due_time'] ?? '23:59'); ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <label for="max_points" class="block text-sm font-medium text-gray-700">Maximum Points</label>
                                <input type="number" name="max_points" id="max_points" min="1" 
                                       value="<?php echo htmlspecialchars($current_assignment['max_points'] ?? '100'); ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <div class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" 
                                       <?php echo ($current_assignment && $current_assignment['is_active']) ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                    Active Assignment
                                </label>
                            </div>
                            
                            <div class="sm:col-span-2">
                                <label for="attachment_url" class="block text-sm font-medium text-gray-700">Attachment URL</label>
                                <input type="url" name="attachment_url" id="attachment_url" 
                                       value="<?php echo htmlspecialchars($current_assignment['attachment_url'] ?? ''); ?>"
                                       placeholder="https://example.com/assignment.pdf"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <a href="?course_id=<?php echo $course_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" name="<?php echo $action == 'add' ? 'add_assignment' : 'edit_assignment'; ?>" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                <?php echo $action == 'add' ? 'Create Assignment' : 'Update Assignment'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 