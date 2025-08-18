<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if (!$course_id) {
    header('Location: courses.php');
    exit();
}

// Verify teacher owns this course or has access
$verify_sql = "SELECT * FROM courses WHERE id = ? AND (teacher_id = ? OR id IN (
    SELECT original_course_id FROM teacher_course_customizations WHERE teacher_id = ?
))";
$verify_stmt = $conn->prepare($verify_sql);
$verify_stmt->bind_param("iii", $course_id, $teacher_id, $teacher_id);
$verify_stmt->execute();
$course = $verify_stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: courses.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_section'])) {
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $order_number = (int)$_POST['order_number'];
        
        $sql = "INSERT INTO course_sections (course_id, title, description, order_number) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("issi", $course_id, $title, $description, $order_number);
        
        if ($stmt->execute()) {
            $success_message = "Section added successfully!";
        } else {
            $error_message = "Error adding section: " . $stmt->error;
        }
    }
    
    if (isset($_POST['edit_section'])) {
        $section_id = (int)$_POST['section_id'];
        $title = sanitizeInput($_POST['title']);
        $description = sanitizeInput($_POST['description']);
        $order_number = (int)$_POST['order_number'];
        
        $sql = "UPDATE course_sections SET title = ?, description = ?, order_number = ? WHERE id = ? AND course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssiii", $title, $description, $order_number, $section_id, $course_id);
        
        if ($stmt->execute()) {
            $success_message = "Section updated successfully!";
        } else {
            $error_message = "Error updating section: " . $stmt->error;
        }
    }
    
    if (isset($_POST['delete_section'])) {
        $section_id = (int)$_POST['section_id'];
        
        // First delete associated lessons
        $delete_lessons = "DELETE FROM lessons WHERE section_id = ?";
        $stmt = $conn->prepare($delete_lessons);
        $stmt->bind_param("i", $section_id);
        $stmt->execute();
        
        // Then delete the section
        $sql = "DELETE FROM course_sections WHERE id = ? AND course_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $section_id, $course_id);
        
        if ($stmt->execute()) {
            $success_message = "Section deleted successfully!";
        } else {
            $error_message = "Error deleting section: " . $stmt->error;
        }
    }
}

// Get course sections with lesson counts
$sections_sql = "SELECT cs.*, COUNT(l.id) as lesson_count 
                 FROM course_sections cs 
                 LEFT JOIN lessons l ON cs.id = l.section_id 
                 WHERE cs.course_id = ? 
                 GROUP BY cs.id 
                 ORDER BY cs.order_number, cs.id";
$sections_stmt = $conn->prepare($sections_sql);
$sections_stmt->bind_param("i", $course_id);
$sections_stmt->execute();
$sections = $sections_stmt->get_result();

$page_title = 'Course Sections - ' . $course['title'];
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Course Sections</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($course['title']); ?></p>
            </div>
            <div class="space-x-3">
                <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Courses
                </a>
                <button onclick="openSectionModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-plus mr-2"></i>
                    Add Section
                </button>
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

    <!-- Sections List -->
    <div class="space-y-4">
        <?php if ($sections->num_rows > 0): ?>
            <?php while ($section = $sections->fetch_assoc()): ?>
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <div class="flex items-center">
                                    <span class="flex items-center justify-center w-8 h-8 bg-indigo-100 text-indigo-600 rounded-full text-sm font-medium mr-3">
                                        <?php echo $section['order_number']; ?>
                                    </span>
                                    <div>
                                        <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($section['title']); ?></h3>
                                        <?php if ($section['description']): ?>
                                            <p class="text-sm text-gray-600"><?php echo htmlspecialchars($section['description']); ?></p>
                                        <?php endif; ?>
                                        <p class="text-xs text-gray-500 mt-1">
                                            <i class="fas fa-book mr-1"></i>
                                            <?php echo $section['lesson_count']; ?> lesson(s)
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                <a href="section_lessons.php?section_id=<?php echo $section['id']; ?>&course_id=<?php echo $course_id; ?>" 
                                   class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                    <i class="fas fa-list mr-2"></i>
                                    Manage Lessons
                                </a>
                                <button onclick="editSection(<?php echo $section['id']; ?>, '<?php echo htmlspecialchars($section['title']); ?>', '<?php echo htmlspecialchars($section['description']); ?>', <?php echo $section['order_number']; ?>)" 
                                        class="inline-flex items-center px-3 py-2 border border-gray-300 text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                    <i class="fas fa-edit mr-2"></i>
                                    Edit
                                </button>
                                <button onclick="confirmDeleteSection(<?php echo $section['id']; ?>, '<?php echo htmlspecialchars($section['title']); ?>')" 
                                        class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-red-600 hover:bg-red-700">
                                    <i class="fas fa-trash mr-2"></i>
                                    Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="text-center py-12 bg-white rounded-lg shadow">
                <i class="fas fa-layer-group text-gray-400 text-6xl mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No Sections Yet</h3>
                <p class="text-gray-500 mb-4">Start organizing your course by adding sections.</p>
                <button onclick="openSectionModal()" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">
                    <i class="fas fa-plus mr-2"></i>
                    Add First Section
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add/Edit Section Modal -->
<div id="sectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 id="modal-title" class="text-lg font-medium text-gray-900 mb-4">Add Section</h3>
            <form method="POST" action="">
                <input type="hidden" name="section_id" id="modal_section_id">
                
                <div class="mb-4">
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">Section Title:</label>
                    <input type="text" name="title" id="modal_title_input" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div class="mb-4">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description:</label>
                    <textarea name="description" id="modal_description" rows="3" 
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="order_number" class="block text-sm font-medium text-gray-700 mb-2">Order:</label>
                    <input type="number" name="order_number" id="modal_order" min="1" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeSectionModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" name="add_section" id="submit-button"
                            class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 border border-transparent rounded-md hover:bg-indigo-700">
                        Add Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Confirm Delete</h3>
            <p class="text-gray-600 mb-4">Are you sure you want to delete the section "<span id="delete-section-name"></span>"? This will also delete all lessons in this section.</p>
            
            <form method="POST" action="">
                <input type="hidden" name="section_id" id="delete_section_id">
                <input type="hidden" name="delete_section" value="1">
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeDeleteModal()" 
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" 
                            class="px-4 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-md hover:bg-red-700">
                        Delete Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let isEditMode = false;

function openSectionModal(sectionId = null, title = '', description = '', orderNumber = null) {
    isEditMode = sectionId !== null;
    
    document.getElementById('modal-title').textContent = isEditMode ? 'Edit Section' : 'Add Section';
    document.getElementById('submit-button').textContent = isEditMode ? 'Update Section' : 'Add Section';
    document.getElementById('submit-button').name = isEditMode ? 'edit_section' : 'add_section';
    
    document.getElementById('modal_section_id').value = sectionId || '';
    document.getElementById('modal_title_input').value = title;
    document.getElementById('modal_description').value = description;
    document.getElementById('modal_order').value = orderNumber || (<?php echo $sections->num_rows; ?> + 1);
    
    document.getElementById('sectionModal').classList.remove('hidden');
}

function closeSectionModal() {
    document.getElementById('sectionModal').classList.add('hidden');
}

function editSection(sectionId, title, description, orderNumber) {
    openSectionModal(sectionId, title, description, orderNumber);
}

function confirmDeleteSection(sectionId, sectionName) {
    document.getElementById('delete_section_id').value = sectionId;
    document.getElementById('delete-section-name').textContent = sectionName;
    document.getElementById('deleteModal').classList.remove('hidden');
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
}

// Close modals when clicking outside
document.getElementById('sectionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSectionModal();
    }
});

document.getElementById('deleteModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?>