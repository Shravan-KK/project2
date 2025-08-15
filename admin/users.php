<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$page_title = 'User Management - Admin';

// Get all users with additional information
$sql = "SELECT u.*, 
        (SELECT COUNT(*) FROM enrollments WHERE student_id = u.id) as enrollment_count,
        (SELECT COUNT(*) FROM courses WHERE teacher_id = u.id) as course_count,
        (SELECT GROUP_CONCAT(DISTINCT b.name SEPARATOR ', ') FROM enrollments e 
         JOIN batches b ON e.batch_id = b.id WHERE e.student_id = u.id AND e.status = 'active') as batch_names
        FROM users u 
        ORDER BY u.user_type, u.created_at DESC";
$users = $conn->query($sql);

// Get all batches for assignment
$batches_sql = "SELECT id, name, status FROM batches WHERE status = 'active' ORDER BY name";
$batches = $conn->query($batches_sql);

// Handle batch assignment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_batch'])) {
    $student_id = $_POST['student_id'];
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
    } else {
        $error_message = "Failed to remove student from batch.";
    }
}
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
                <p class="mt-2 text-gray-600">Manage all users in the system</p>
            </div>
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

    <!-- Users List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                All Users (<?php echo $users->num_rows; ?> total)
            </h3>
        </div>
        
        <?php if ($users->num_rows > 0): ?>
            <ul class="divide-y divide-gray-200">
                <?php while ($user = $users->fetch_assoc()): ?>
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <i class="fas fa-user text-indigo-600"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></h4>
                                            <div class="ml-2 flex items-center space-x-2">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo ($user['status'] ?? 'active') == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo ucfirst($user['status'] ?? 'active'); ?>
                                                </span>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                    <?php echo $user['user_type'] == 'admin' ? 'bg-red-100 text-red-800' : 
                                                          ($user['user_type'] == 'teacher' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'); ?>">
                                                    <?php echo ucfirst($user['user_type']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($user['email']); ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <?php if ($user['user_type'] == 'student'): ?>
                                                <span><i class="fas fa-graduation-cap mr-1"></i> <?php echo $user['enrollment_count']; ?> enrollments</span>
                                                <?php if ($user['batch_names']): ?>
                                                    <span class="ml-4"><i class="fas fa-layer-group mr-1"></i> Batches: <?php echo htmlspecialchars($user['batch_names']); ?></span>
                                                <?php endif; ?>
                                            <?php elseif ($user['user_type'] == 'teacher'): ?>
                                                <span><i class="fas fa-chalkboard mr-1"></i> <?php echo $user['course_count']; ?> courses</span>
                                            <?php endif; ?>
                                            <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($user['created_at']); ?></span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Action Buttons -->
                                <div class="flex items-center space-x-2">
                                    <?php if ($user['user_type'] == 'student'): ?>
                                        <!-- Batch Assignment Button -->
                                        <button onclick="openBatchModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')" 
                                                class="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                                            <i class="fas fa-layer-group mr-2"></i>
                                            Assign Batch
                                        </button>
                                        
                                        <!-- View Student Details -->
                                        <a href="student_details.php?id=<?php echo $user['id']; ?>" 
                                           class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            <i class="fas fa-eye mr-2"></i>
                                            View Details
                                        </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <div class="px-4 py-8 text-center">
                <i class="fas fa-users text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No users found.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Batch Assignment Modal -->
<div id="batchModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Assign Student to Batch</h3>
            <form method="POST" action="">
                <input type="hidden" name="student_id" id="modal_student_id">
                <input type="hidden" name="assign_batch" value="1">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Student:</label>
                    <p id="modal_student_name" class="text-gray-900 font-medium"></p>
                </div>
                
                <div class="mb-4">
                    <label for="batch_id" class="block text-sm font-medium text-gray-700 mb-2">Select Batch:</label>
                    <select name="batch_id" id="batch_id" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose a batch...</option>
                        <?php while ($batch = $batches->fetch_assoc()): ?>
                            <option value="<?php echo $batch['id']; ?>"><?php echo htmlspecialchars($batch['name']); ?> (<?php echo ucfirst($batch['status']); ?>)</option>
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
function openBatchModal(studentId, studentName) {
    document.getElementById('modal_student_id').value = studentId;
    document.getElementById('modal_student_name').textContent = studentName;
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