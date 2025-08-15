<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$student_id = $_GET['id'] ?? null;
if (!$student_id) {
    header('Location: users.php');
    exit;
}

$page_title = 'Student Details - Admin';

// Get student information
$student_sql = "SELECT * FROM users WHERE id = ? AND user_type = 'student'";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("i", $student_id);
$student_stmt->execute();
$student = $student_stmt->get_result()->fetch_assoc();

if (!$student) {
    header('Location: users.php');
    exit;
}

// Get student's course enrollments
$enrollments_sql = "SELECT e.*, c.title as course_title, c.category, u.name as teacher_name,
                    (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
                    (SELECT COUNT(*) FROM student_progress WHERE student_id = e.student_id AND course_id = c.id AND lesson_completed = 1) as completed_lessons
                    FROM enrollments e
                    JOIN courses c ON e.course_id = c.id
                    LEFT JOIN users u ON c.teacher_id = u.id
                    WHERE e.student_id = ? AND e.course_id IS NOT NULL
                    ORDER BY e.enrollment_date DESC";
$enrollments_stmt = $conn->prepare($enrollments_sql);
$enrollments_stmt->bind_param("i", $student_id);
$enrollments_stmt->execute();
$enrollments = $enrollments_stmt->get_result();

// Get student's batch enrollments
$batch_enrollments_sql = "SELECT e.*, b.name as batch_name, b.start_date, b.end_date, b.status as batch_status,
                          (SELECT COUNT(*) FROM class_sessions WHERE class_id = b.id) as total_sessions,
                          (SELECT COUNT(*) FROM class_sessions WHERE class_id = b.id AND status = 'completed') as completed_sessions
                          FROM enrollments e
                          JOIN batches b ON e.batch_id = b.id
                          WHERE e.student_id = ? AND e.batch_id IS NOT NULL
                          ORDER BY e.enrollment_date DESC";
$batch_enrollments_stmt = $conn->prepare($batch_enrollments_sql);
$batch_enrollments_stmt->bind_param("i", $student_id);
$batch_enrollments_stmt->execute();
$batch_enrollments = $batch_enrollments_stmt->get_result();

// Get all available batches for assignment
$available_batches_sql = "SELECT id, name, start_date, end_date FROM batches WHERE status = 'active' ORDER BY name";
$available_batches = $conn->query($available_batches_sql);

// Handle batch assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_batch'])) {
    $batch_id = $_POST['batch_id'];
    
    // Check if student is already in this batch
    $check_sql = "SELECT id FROM enrollments WHERE student_id = ? AND batch_id = ? AND status = 'active'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $student_id, $batch_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows === 0) {
        // Assign student to batch
        $assign_sql = "INSERT INTO enrollments (student_id, batch_id, enrollment_date, status, progress) VALUES (?, ?, CURRENT_TIMESTAMP, 'active', 0)";
        $assign_stmt = $conn->prepare($assign_sql);
        $assign_stmt->bind_param("ii", $student_id, $batch_id);
        
        if ($assign_stmt->execute()) {
            $success_message = "Student assigned to batch successfully!";
            // Refresh the page to show updated data
            header("Location: student_details.php?id=$student_id&success=1");
            exit;
        } else {
            $error_message = "Failed to assign student to batch.";
        }
    } else {
        $error_message = "Student is already in this batch.";
    }
}

// Handle batch removal
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_batch'])) {
    $enrollment_id = $_POST['enrollment_id'];
    
    $remove_sql = "UPDATE enrollments SET status = 'dropped' WHERE id = ?";
    $remove_stmt = $conn->prepare($remove_sql);
    $remove_stmt->bind_param("i", $enrollment_id);
    
    if ($remove_stmt->execute()) {
        $success_message = "Student removed from batch successfully!";
        header("Location: student_details.php?id=$student_id&success=1");
        exit;
    } else {
        $error_message = "Failed to remove student from batch.";
    }
}

// Show success message if redirected with success parameter
if (isset($_GET['success'])) {
    $success_message = "Operation completed successfully!";
}
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Student Details</h1>
                <p class="mt-2 text-gray-600">View and manage student information</p>
            </div>
            <a href="users.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                <i class="fas fa-arrow-left mr-2"></i>
                Back to Users
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

    <!-- Student Information -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Student Information</h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Name</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student['name']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Email</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student['email']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phone</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student['phone'] ?? 'Not provided'); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Joined Date</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo formatDate($student['created_at']); ?></dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500">Address</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($student['address'] ?? 'Not provided'); ?></dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Course Enrollments -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Course Enrollments</h3>
        </div>
        <div class="border-t border-gray-200">
            <?php if ($enrollments->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($enrollment['course_title']); ?></h4>
                                    <p class="text-sm text-gray-600"><?php echo htmlspecialchars($enrollment['teacher_name'] ?? 'Unknown'); ?></p>
                                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i> Enrolled: <?php echo formatDate($enrollment['enrollment_date']); ?></span>
                                        <span><i class="fas fa-book mr-1"></i> Progress: <?php echo $enrollment['completed_lessons']; ?>/<?php echo $enrollment['total_lessons']; ?> lessons</span>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $enrollment['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo ucfirst($enrollment['status']); ?>
                                        </span>
                                    </div>
                                    <?php if ($enrollment['total_lessons'] > 0): ?>
                                        <div class="mt-2">
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo ($enrollment['completed_lessons'] / $enrollment['total_lessons']) * 100; ?>%"></div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo round(($enrollment['completed_lessons'] / $enrollment['total_lessons']) * 100); ?>% complete</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <p class="text-gray-500">No course enrollments found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Batch Enrollments -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <div class="flex items-center justify-between">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Batch Enrollments</h3>
                <button onclick="openBatchModal()" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>
                    Assign to Batch
                </button>
            </div>
        </div>
        <div class="border-t border-gray-200">
            <?php if ($batch_enrollments->num_rows > 0): ?>
                <ul class="divide-y divide-gray-200">
                    <?php while ($batch_enrollment = $batch_enrollments->fetch_assoc()): ?>
                        <li class="px-4 py-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($batch_enrollment['batch_name']); ?></h4>
                                    <div class="mt-2 flex items-center space-x-4 text-sm text-gray-500">
                                        <span><i class="fas fa-calendar mr-1"></i> Enrolled: <?php echo formatDate($batch_enrollment['enrollment_date']); ?></span>
                                        <span><i class="fas fa-play mr-1"></i> Sessions: <?php echo $batch_enrollment['completed_sessions']; ?>/<?php echo $batch_enrollment['total_sessions']; ?></span>
                                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $batch_enrollment['batch_status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo ucfirst($batch_enrollment['batch_status']); ?>
                                        </span>
                                    </div>
                                    <?php if ($batch_enrollment['total_sessions'] > 0): ?>
                                        <div class="mt-2">
                                            <div class="w-full bg-gray-200 rounded-full h-2">
                                                <div class="bg-green-600 h-2 rounded-full" style="width: <?php echo ($batch_enrollment['completed_sessions'] / $batch_enrollment['total_sessions']) * 100; ?>%"></div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo round(($batch_enrollment['completed_sessions'] / $batch_enrollment['total_sessions']) * 100); ?>% complete</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <form method="POST" action="" class="ml-4">
                                    <input type="hidden" name="enrollment_id" value="<?php echo $batch_enrollment['id']; ?>">
                                    <input type="hidden" name="remove_batch" value="1">
                                    <button type="submit" onclick="return confirm('Are you sure you want to remove this student from the batch?')" 
                                            class="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50">
                                        <i class="fas fa-times mr-2"></i>
                                        Remove
                                    </button>
                                </form>
                            </div>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="px-4 py-8 text-center">
                    <p class="text-gray-500">No batch enrollments found.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Batch Assignment Modal -->
<div id="batchModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Student to Batch</h3>
            <form method="POST" action="">
                <input type="hidden" name="assign_batch" value="1">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Student:</label>
                    <p class="text-gray-900 font-medium"><?php echo htmlspecialchars($student['name']); ?></p>
                </div>
                
                <div class="mb-4">
                    <label for="batch_id" class="block text-sm font-medium text-gray-700 mb-2">Select Batch:</label>
                    <select name="batch_id" id="batch_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose a batch...</option>
                        <?php while ($batch = $available_batches->fetch_assoc()): ?>
                            <option value="<?php echo $batch['id']; ?>"><?php echo htmlspecialchars($batch['name']); ?> (<?php echo formatDate($batch['start_date']); ?> - <?php echo formatDate($batch['end_date']); ?>)</option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeBatchModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Assign
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openBatchModal() {
    document.getElementById('batchModal').classList.remove('hidden');
}

function closeBatchModal() {
    document.getElementById('batchModal').classList.add('hidden');
}

// Close modal when clicking outside
document.getElementById('batchModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBatchModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 