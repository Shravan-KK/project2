<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: batches.php');
    exit();
}

$batch_id = (int)$_GET['id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'assign_course':
                $course_id = (int)$_POST['course_id'];
                $start_date = $_POST['start_date'];
                $end_date = $_POST['end_date'];
                
                $sql = "INSERT INTO batch_courses (batch_id, course_id, start_date, end_date) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE start_date = ?, end_date = ?, status = 'active'";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iissss", $batch_id, $course_id, $start_date, $end_date, $start_date, $end_date);
                
                if ($stmt->execute()) {
                    $success_message = "Course assigned to batch successfully!";
                } else {
                    $error_message = "Error assigning course: " . $stmt->error;
                }
                break;
                
            case 'assign_instructor':
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
        }
    }
}

// Get batch details
$batch_sql = "SELECT * FROM batches WHERE id = ?";
$batch_stmt = $conn->prepare($batch_sql);
$batch_stmt->bind_param("i", $batch_id);
$batch_stmt->execute();
$batch = $batch_stmt->get_result()->fetch_assoc();

if (!$batch) {
    header('Location: batches.php');
    exit();
}

$page_title = 'Batch Details - ' . $batch['name'];

// Get batch students
$students_sql = "SELECT u.*, e.enrollment_date, e.progress 
                FROM enrollments e 
                JOIN users u ON e.student_id = u.id 
                WHERE e.batch_id = ? AND e.status = 'active' 
                ORDER BY u.name";
$students_stmt = $conn->prepare($students_sql);
$students_stmt->bind_param("i", $batch_id);
$students_stmt->execute();
$students = $students_stmt->get_result();

// Get batch courses
$courses_sql = "SELECT c.*, bc.start_date, bc.end_date, bc.status as assignment_status, bc.id as assignment_id,
                (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id AND batch_id = ? AND status = 'active') as enrolled_students
                FROM batch_courses bc 
                JOIN courses c ON bc.course_id = c.id 
                WHERE bc.batch_id = ? 
                ORDER BY bc.start_date";
$courses_stmt = $conn->prepare($courses_sql);
$courses_stmt->bind_param("ii", $batch_id, $batch_id);
$courses_stmt->execute();
$batch_courses = $courses_stmt->get_result();

// Get batch instructors
$instructors_sql = "SELECT u.*, bi.role, bi.assigned_date, bi.status as assignment_status, bi.id as assignment_id
                   FROM batch_instructors bi 
                   JOIN users u ON bi.instructor_id = u.id 
                   WHERE bi.batch_id = ? AND bi.status = 'active'
                   ORDER BY bi.role, u.name";
$instructors_stmt = $conn->prepare($instructors_sql);
$instructors_stmt->bind_param("i", $batch_id);
$instructors_stmt->execute();
$batch_instructors = $instructors_stmt->get_result();

// Get all available courses for assignment
$available_courses_sql = "SELECT c.* FROM courses c 
                         WHERE c.status = 'active' 
                         AND c.id NOT IN (
                             SELECT bc.course_id FROM batch_courses bc 
                             WHERE bc.batch_id = ? AND bc.status = 'active'
                         )
                         ORDER BY c.title";
$available_courses_stmt = $conn->prepare($available_courses_sql);
$available_courses_stmt->bind_param("i", $batch_id);
$available_courses_stmt->execute();
$available_courses = $available_courses_stmt->get_result();

// Get all available instructors for assignment
$available_instructors_sql = "SELECT u.* FROM users u 
                             WHERE u.user_type = 'teacher' AND u.status = 'active'
                             ORDER BY u.name";
$available_instructors_stmt = $conn->prepare($available_instructors_sql);
$available_instructors_stmt->execute();
$available_instructors = $available_instructors_stmt->get_result();

// Get batch statistics
$stats_sql = "SELECT 
    COUNT(DISTINCT e.student_id) as total_students,
    AVG(e.progress) as avg_progress,
    COUNT(DISTINCT e.course_id) as total_courses,
    (SELECT COUNT(*) FROM submissions s 
     JOIN enrollments e2 ON s.student_id = e2.student_id 
     WHERE e2.batch_id = ?) as total_submissions
    FROM enrollments e 
    WHERE e.batch_id = ? AND e.status = 'active'";
$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("ii", $batch_id, $batch_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();
?>

<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="px-4 py-6 sm:px-0">
        <!-- Header -->
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900"><?php echo htmlspecialchars($batch['name']); ?></h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($batch['description']); ?></p>
            </div>
            <div class="flex space-x-3">
                <a href="batches.php" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400">
                    <i class="fas fa-arrow-left mr-2"></i>Back to Batches
                </a>
                <button onclick="openAssignModal('instructor')" class="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700">
                    <i class="fas fa-user-plus mr-2"></i>Assign Instructor
                </button>
                <button onclick="openAssignModal('course')" class="bg-purple-600 text-white px-4 py-2 rounded-md hover:bg-purple-700">
                    <i class="fas fa-book-plus mr-2"></i>Assign Course
                </button>
                <a href="batches.php?edit=<?php echo $batch_id; ?>" class="bg-indigo-600 text-white px-4 py-2 rounded-md hover:bg-indigo-700">
                    <i class="fas fa-edit mr-2"></i>Edit Batch
                </a>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <!-- Batch Status -->
        <div class="mb-6">
            <span class="px-3 py-1 text-sm font-medium rounded-full 
                <?php echo $batch['status'] === 'active' ? 'bg-green-100 text-green-800' : 
                        ($batch['status'] === 'completed' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800'); ?>">
                <?php echo ucfirst($batch['status']); ?>
            </span>
        </div>

        <!-- Batch Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-users text-indigo-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Total Students</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_students'] ?: 0; ?>/<?php echo $batch['max_students']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-chart-line text-green-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Avg Progress</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo round($stats['avg_progress'] ?: 0, 1); ?>%</dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-book text-blue-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Courses</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_courses'] ?: 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-file-alt text-purple-600 text-2xl"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium text-gray-500 truncate">Submissions</dt>
                                <dd class="text-lg font-medium text-gray-900"><?php echo $stats['total_submissions'] ?: 0; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Batch Information -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium text-gray-900 mb-4">Batch Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Start Date</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $batch['start_date']; ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">End Date</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $batch['end_date']; ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Maximum Students</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo $batch['max_students']; ?></dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">Created</dt>
                        <dd class="mt-1 text-sm text-gray-900"><?php echo date('M j, Y', strtotime($batch['created_at'])); ?></dd>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabs -->
        <div class="mb-6">
            <nav class="flex space-x-8">
                <button onclick="showTab('students')" class="tab-button active py-2 px-1 border-b-2 border-indigo-500 font-medium text-sm text-indigo-600">
                    Students (<?php echo $students->num_rows; ?>)
                </button>
                <button onclick="showTab('instructors')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                    Instructors (<?php echo $batch_instructors->num_rows; ?>)
                </button>
                <button onclick="showTab('courses')" class="tab-button py-2 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                    Courses (<?php echo $batch_courses->num_rows; ?>)
                </button>
            </nav>
        </div>

        <!-- Students Tab -->
        <div id="students-tab" class="tab-content">
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Enrolled Students</h3>
                </div>
                <?php if ($students->num_rows > 0): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php while ($student = $students->fetch_assoc()): ?>
                            <li class="px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($student['name']); ?></h4>
                                            <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                Student
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($student['email']); ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> Enrolled: <?php echo date('M j, Y', strtotime($student['enrollment_date'])); ?></span>
                                            <span class="ml-4"><i class="fas fa-chart-line mr-1"></i> Progress: <?php echo $student['progress'] ?: 0; ?>%</span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="../admin/users.php?view=<?php echo $student['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="px-4 py-8 text-center">
                        <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No students enrolled in this batch.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Instructors Tab -->
        <div id="instructors-tab" class="tab-content hidden">
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Assigned Instructors</h3>
                </div>
                <?php if ($batch_instructors->num_rows > 0): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php while ($instructor = $batch_instructors->fetch_assoc()): ?>
                            <li class="px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($instructor['name']); ?></h4>
                                            <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full 
                                                <?php 
                                                    echo $instructor['role'] === 'lead' ? 'bg-blue-100 text-blue-800' : 
                                                        ($instructor['role'] === 'assistant' ? 'bg-green-100 text-green-800' : 'bg-purple-100 text-purple-800'); 
                                                ?>">
                                                <?php echo ucfirst($instructor['role']); ?> Instructor
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($instructor['email']); ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> Assigned: <?php echo date('M j, Y', strtotime($instructor['assigned_date'])); ?></span>
                                            <span class="ml-4"><i class="fas fa-user-tag mr-1"></i> Role: <?php echo ucfirst($instructor['role']); ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="../admin/users.php?view=<?php echo $instructor['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Profile">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" action="batches.php" class="inline" onsubmit="return confirm('Remove this instructor from the batch?')">
                                            <input type="hidden" name="action" value="remove_instructor">
                                            <input type="hidden" name="assignment_id" value="<?php echo $instructor['assignment_id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Remove Instructor">
                                                <i class="fas fa-user-minus"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="px-4 py-8 text-center">
                        <i class="fas fa-chalkboard-teacher text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No instructors assigned to this batch.</p>
                        <p class="text-sm text-gray-400 mt-2">Go back to batch management to assign instructors.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Courses Tab -->
        <div id="courses-tab" class="tab-content hidden">
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Assigned Courses</h3>
                </div>
                <?php if ($batch_courses->num_rows > 0): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php while ($course = $batch_courses->fetch_assoc()): ?>
                            <li class="px-4 py-4">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h4>
                                            <span class="ml-2 px-2 py-1 text-xs font-medium rounded-full 
                                                <?php echo $course['assignment_status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo ucfirst($course['assignment_status']); ?>
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600"><?php echo htmlspecialchars($course['description']); ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> <?php echo $course['start_date']; ?> - <?php echo $course['end_date']; ?></span>
                                            <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $course['enrolled_students']; ?> students</span>
                                            <span class="ml-4"><i class="fas fa-dollar-sign mr-1"></i> $<?php echo $course['price']; ?></span>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="courses.php?view=<?php echo $course['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Course">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Remove this course from the batch?')">
                                            <input type="hidden" name="action" value="remove_course">
                                            <input type="hidden" name="assignment_id" value="<?php echo $course['assignment_id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Remove Course">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="px-4 py-8 text-center">
                        <i class="fas fa-book text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No courses assigned to this batch.</p>
                    </div>
                <?php endif; ?>
            </div>
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
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Select Instructor</label>
                    <select name="instructor_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Choose an instructor...</option>
                        <?php if ($available_instructors): ?>
                            <?php $available_instructors->data_seek(0); ?>
                            <?php while ($instructor = $available_instructors->fetch_assoc()): ?>
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
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700">Select Course</label>
                    <select name="course_id" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">Choose a course...</option>
                        <?php if ($available_courses): ?>
                            <?php $available_courses->data_seek(0); ?>
                            <?php while ($course = $available_courses->fetch_assoc()): ?>
                                <option value="<?php echo $course['id']; ?>">
                                    <?php echo htmlspecialchars($course['title']); ?> - $<?php echo $course['price']; ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course Start Date</label>
                        <input type="date" name="start_date" value="<?php echo $batch['start_date']; ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course End Date</label>
                        <input type="date" name="end_date" value="<?php echo $batch['end_date']; ?>" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
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
function showTab(tabName) {
    // Hide all tab contents
    const tabContents = document.querySelectorAll('.tab-content');
    tabContents.forEach(content => content.classList.add('hidden'));
    
    // Remove active class from all tab buttons
    const tabButtons = document.querySelectorAll('.tab-button');
    tabButtons.forEach(button => {
        button.classList.remove('border-indigo-500', 'text-indigo-600');
        button.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Show selected tab content
    document.getElementById(tabName + '-tab').classList.remove('hidden');
    
    // Add active class to selected tab button
    event.target.classList.remove('border-transparent', 'text-gray-500');
    event.target.classList.add('border-indigo-500', 'text-indigo-600');
}

function openAssignModal(type) {
    if (type === 'instructor') {
        document.getElementById('assignInstructorModal').classList.remove('hidden');
    } else if (type === 'course') {
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
    
    if (event.target === instructorModal) {
        instructorModal.classList.add('hidden');
    }
    if (event.target === courseModal) {
        courseModal.classList.add('hidden');
    }
}
</script>

<?php require_once '../includes/footer.php'; ?> 