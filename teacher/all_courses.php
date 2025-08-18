<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$page_title = 'All Available Courses - Teacher';

// Create teacher_course_customizations table if it doesn't exist
$create_customizations = "CREATE TABLE IF NOT EXISTS teacher_course_customizations (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    teacher_id INT(11) NOT NULL,
    original_course_id INT(11) NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    custom_content TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_teacher_id (teacher_id),
    INDEX idx_original_course_id (original_course_id),
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (original_course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_teacher_course (teacher_id, original_course_id)
)";
$conn->query($create_customizations);

// Handle course actions
$action = $_GET['action'] ?? '';
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['customize_course'])) {
        $original_course_id = (int)$_POST['original_course_id'];
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $custom_content = sanitizeInput($_POST['custom_content']);
        
        // Insert or update customization
        $sql = "INSERT INTO teacher_course_customizations (teacher_id, original_course_id, title, description, custom_content) 
                VALUES (?, ?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                title = VALUES(title), 
                description = VALUES(description), 
                custom_content = VALUES(custom_content),
                updated_at = CURRENT_TIMESTAMP";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iisss", $teacher_id, $original_course_id, $title, $description, $custom_content);
        
        if ($stmt->execute()) {
            $success_message = "Course customization saved successfully!";
        } else {
            $error_message = "Error saving customization: " . $stmt->error;
        }
    }
    
    if (isset($_POST['add_to_batch'])) {
        $batch_id = (int)$_POST['batch_id'];
        $course_id = (int)$_POST['course_id'];
        
        // Add course to batch
        $sql = "INSERT IGNORE INTO batch_courses (batch_id, course_id, status) VALUES (?, ?, 'active')";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $batch_id, $course_id);
        
        if ($stmt->execute()) {
            $success_message = "Course added to batch successfully!";
        } else {
            $error_message = "Error adding course to batch: " . $stmt->error;
        }
    }
}

// Get all courses with teacher's customizations
$sql = "SELECT c.id, c.title, c.description, c.teacher_id, c.price, c.status, c.created_at, c.updated_at,
        u.name as original_teacher_name,
        tcc.id as customization_id,
        tcc.title as custom_title,
        tcc.description as custom_description,
        tcc.custom_content,
        tcc.status as custom_status,
        COUNT(DISTINCT l.id) as lesson_count
        FROM courses c 
        LEFT JOIN users u ON c.teacher_id = u.id
        LEFT JOIN teacher_course_customizations tcc ON c.id = tcc.original_course_id AND tcc.teacher_id = ?
        LEFT JOIN lessons l ON c.id = l.course_id
        WHERE c.status = 'active'
        GROUP BY c.id, c.title, c.description, c.teacher_id, c.price, c.status, c.created_at, c.updated_at,
                 u.name, tcc.id, tcc.title, tcc.description, tcc.custom_content, tcc.status
        ORDER BY c.title";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$courses = $stmt->get_result();

// Get teacher's batches for dropdown
$batches_sql = "SELECT DISTINCT b.* FROM batches b 
                LEFT JOIN batch_instructors bi ON b.id = bi.batch_id 
                WHERE (bi.instructor_id = ? AND bi.status = 'active') OR b.id IN (
                    SELECT DISTINCT batch_id FROM enrollments WHERE student_id IN (
                        SELECT id FROM users WHERE user_type = 'student'
                    )
                )
                AND b.status = 'active'
                ORDER BY b.name";
$batches_stmt = $conn->prepare($batches_sql);
$batches_stmt->bind_param("i", $teacher_id);
$batches_stmt->execute();
$batches = $batches_stmt->get_result();
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">All Available Courses</h1>
                <p class="mt-2 text-gray-600">View, customize, and add courses to your batches</p>
            </div>
            <div>
                <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    My Courses
                </a>
            </div>
        </div>
    </div>

    <!-- Success/Error Messages -->
    <?php if ($success_message): ?>
        <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($success_message); ?>
        </div>
    <?php endif; ?>
    
    <?php if ($error_message): ?>
        <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <?php echo htmlspecialchars($error_message); ?>
        </div>
    <?php endif; ?>

    <!-- Courses Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php while ($course = $courses->fetch_assoc()): ?>
            <div class="bg-white overflow-hidden shadow rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            <?php echo htmlspecialchars($course['custom_title'] ?: $course['title']); ?>
                            <?php if ($course['customization_id']): ?>
                                <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    Customized
                                </span>
                            <?php endif; ?>
                        </h3>
                        <?php if ($course['teacher_id'] == $teacher_id): ?>
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Your Course
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="text-sm text-gray-600 mb-4">
                        <?php echo htmlspecialchars($course['custom_description'] ?: $course['description'] ?: 'No description available'); ?>
                    </p>
                    
                    <div class="text-sm text-gray-500 mb-4">
                        <p><i class="fas fa-user mr-1"></i> Original Teacher: <?php echo htmlspecialchars($course['original_teacher_name'] ?: 'Unknown'); ?></p>
                        <p><i class="fas fa-book mr-1"></i> Lessons: <?php echo $course['lesson_count']; ?></p>
                        <?php if ($course['price'] > 0): ?>
                            <p><i class="fas fa-rupee-sign mr-1"></i> Price: ₹<?php echo number_format($course['price'], 2); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="space-y-2">
                        <!-- View Course Content -->
                        <a href="course_content_view.php?id=<?php echo $course['id']; ?>" 
                           class="block w-full text-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            <i class="fas fa-eye mr-2"></i>
                            View Content
                        </a>
                        
                        <!-- Customize Course -->
                        <button onclick="openCustomizeModal(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['title']); ?>', '<?php echo htmlspecialchars($course['description'] ?: ''); ?>', '<?php echo htmlspecialchars($course['custom_content'] ?: ''); ?>')" 
                                class="block w-full text-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-edit mr-2"></i>
                            <?php echo $course['customization_id'] ? 'Edit Customization' : 'Customize Course'; ?>
                        </button>
                        
                        <!-- Add to Batch -->
                        <button onclick="openBatchModal(<?php echo $course['id']; ?>, '<?php echo htmlspecialchars($course['title']); ?>')" 
                                class="block w-full text-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                            <i class="fas fa-plus mr-2"></i>
                            Add to Batch
                        </button>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
    
    <?php if ($courses->num_rows == 0): ?>
        <div class="text-center py-12">
            <i class="fas fa-book-open text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No Courses Available</h3>
            <p class="text-gray-500">There are no active courses in the system.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Customize Course Modal -->
<div id="customizeModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-2xl shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Customize Course</h3>
            <form method="POST" action="">
                <input type="hidden" name="original_course_id" id="modal_course_id">
                <input type="hidden" name="customize_course" value="1">
                
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Custom Title:</label>
                    <input type="text" name="title" id="modal_title" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Custom Description:</label>
                    <textarea name="description" id="modal_description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="custom_content" class="block text-sm font-medium text-gray-700 mb-2">Additional Content/Notes:</label>
                    <textarea name="custom_content" id="modal_custom_content" rows="5" 
                              placeholder="Add your custom teaching notes, modifications, or additional content here..." 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeCustomizeModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Save Customization
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add to Batch Modal -->
<div id="batchModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Add Course to Batch</h3>
            <form method="POST" action="">
                <input type="hidden" name="course_id" id="batch_modal_course_id">
                <input type="hidden" name="add_to_batch" value="1">
                
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Course:</label>
                    <p id="batch_modal_course_name" class="text-gray-900 font-medium"></p>
                </div>
                
                <div class="mb-4">
                    <label for="batch_id" class="block text-sm font-medium text-gray-700 mb-2">Select Batch:</label>
                    <select name="batch_id" id="batch_id" required 
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose a batch...</option>
                        <?php
                        $batches->data_seek(0); // Reset result pointer
                        while ($batch = $batches->fetch_assoc()): ?>
                            <option value="<?php echo $batch['id']; ?>">
                                <?php echo htmlspecialchars($batch['name']); ?> 
                                (<?php echo $batch['current_students']; ?>/<?php echo $batch['max_students']; ?> students)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeBatchModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                        Add to Batch
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openCustomizeModal(courseId, title, description, customContent) {
    document.getElementById('modal_course_id').value = courseId;
    document.getElementById('modal_title').value = title;
    document.getElementById('modal_description').value = description;
    document.getElementById('modal_custom_content').value = customContent;
    document.getElementById('customizeModal').classList.remove('hidden');
}

function closeCustomizeModal() {
    document.getElementById('customizeModal').classList.add('hidden');
}

function openBatchModal(courseId, courseName) {
    document.getElementById('batch_modal_course_id').value = courseId;
    document.getElementById('batch_modal_course_name').textContent = courseName;
    document.getElementById('batchModal').classList.remove('hidden');
}

function closeBatchModal() {
    document.getElementById('batchModal').classList.add('hidden');
}

// Close modals when clicking outside
document.getElementById('customizeModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeCustomizeModal();
    }
});

document.getElementById('batchModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeBatchModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>