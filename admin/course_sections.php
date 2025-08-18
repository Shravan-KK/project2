<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    header('Location: courses.php');
    exit;
}

$page_title = 'Course Sections - Admin';

// Get course information
$course_sql = "SELECT c.*, u.name as teacher_name FROM courses c LEFT JOIN users u ON c.teacher_id = u.id WHERE c.id = ?";
$course_stmt = $conn->prepare($course_sql);
$course_stmt->bind_param("i", $course_id);
$course_stmt->execute();
$course = $course_stmt->get_result()->fetch_assoc();

if (!$course) {
    header('Location: courses.php');
    exit;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create_section':
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $order_number = (int)$_POST['order_number'];
                
                $sql = "INSERT INTO course_sections (course_id, title, description, order_number) VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("issi", $course_id, $title, $description, $order_number);
                
                if ($stmt->execute()) {
                    $success_message = "Section created successfully!";
                } else {
                    $error_message = "Error creating section: " . $stmt->error;
                }
                break;
                
            case 'create_lesson':
                $section_id = (int)$_POST['section_id'];
                $title = trim($_POST['title']);
                $description = trim($_POST['description']);
                $content = trim($_POST['content']);
                $order_number = (int)$_POST['order_number'];
                $duration = (int)$_POST['duration'];
                
                $sql = "INSERT INTO lessons (course_id, section_id, title, description, content, order_number, duration) VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iissiii", $course_id, $section_id, $title, $description, $content, $order_number, $duration);
                
                if ($stmt->execute()) {
                    $success_message = "Lesson created successfully!";
                } else {
                    $error_message = "Error creating lesson: " . $stmt->error;
                }
                break;
        }
    }
}

// Get course sections with lesson counts
$sections_sql = "SELECT s.*, 
                 COUNT(l.id) as lesson_count,
                 SUM(l.duration) as total_duration
                 FROM course_sections s
                 LEFT JOIN lessons l ON s.id = l.section_id
                 WHERE s.course_id = ?
                 GROUP BY s.id, s.title, s.description, s.order_number, s.status, s.created_at, s.updated_at
                 ORDER BY s.order_number ASC";
$sections_stmt = $conn->prepare($sections_sql);
$sections_stmt->bind_param("i", $course_id);
$sections_stmt->execute();
$sections = $sections_stmt->get_result();

// Get all sections for lesson creation dropdown
$all_sections_sql = "SELECT id, title FROM course_sections WHERE course_id = ? ORDER BY order_number ASC";
$all_sections_stmt = $conn->prepare($all_sections_sql);
$all_sections_stmt->bind_param("i", $course_id);
$all_sections_stmt->execute();
$all_sections = $all_sections_stmt->get_result();
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
            <div class="flex items-center space-x-3">
                <button onclick="openSectionModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-plus mr-2"></i>
                    Add Section
                </button>
                <button onclick="openLessonModal()" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <i class="fas fa-plus mr-2"></i>
                    Add Lesson
                </button>
                <a href="courses.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Courses
                </a>
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

    <!-- Course Information -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg mb-6">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Course Information</h3>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Title</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($course['title']); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Teacher</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo htmlspecialchars($course['teacher_name'] ?? 'Not assigned'); ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Total Sections</dt>
                    <dd class="mt-1 text-sm text-gray-900"><?php echo $sections->num_rows; ?></dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                    <dd class="mt-1 text-sm text-gray-900">
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $course['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($course['status']); ?>
                        </span>
                    </dd>
                </div>
            </dl>
        </div>
    </div>

    <!-- Sections List -->
    <div class="bg-white shadow overflow-hidden sm:rounded-md">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">
                Course Sections (<?php echo $sections->num_rows; ?> total)
            </h3>
        </div>
        
        <?php if ($sections->num_rows > 0): ?>
            <ul class="divide-y divide-gray-200">
                <?php while ($section = $sections->fetch_assoc()): ?>
                    <li>
                        <div class="px-4 py-4 sm:px-6">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center">
                                            <span class="text-sm font-medium text-indigo-600"><?php echo $section['order_number']; ?></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($section['title']); ?></h4>
                                            <div class="ml-2 flex items-center space-x-2">
                                                <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $section['status'] == 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                    <?php echo ucfirst($section['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($section['description'] ?? 'No description'); ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-book mr-1"></i> <?php echo $section['lesson_count']; ?> lessons</span>
                                            <span class="ml-4"><i class="fas fa-clock mr-1"></i> <?php echo $section['total_duration'] ?? 0; ?> minutes</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <a href="section_view.php?id=<?php echo $section['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Section">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="section_edit.php?id=<?php echo $section['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit Section">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="lessons.php?section_id=<?php echo $section['id']; ?>&course_id=<?php echo $course_id; ?>" class="text-green-600 hover:text-green-900" title="Manage Lessons">
                                        <i class="fas fa-cogs"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </li>
                <?php endwhile; ?>
            </ul>
        <?php else: ?>
            <div class="px-4 py-8 text-center">
                <i class="fas fa-folder-open text-gray-400 text-4xl mb-4"></i>
                <p class="text-gray-500">No sections created yet.</p>
                <p class="text-sm text-gray-400 mt-2">Create your first section to organize your course content.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Section Modal -->
<div id="sectionModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Section</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_section">
                
                <div class="mb-4">
                    <label for="section_title" class="block text-sm font-medium text-gray-700 mb-2">Section Title</label>
                    <input type="text" name="title" id="section_title" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="section_description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="section_description" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="section_order" class="block text-sm font-medium text-gray-700 mb-2">Order Number</label>
                    <input type="number" name="order_number" id="section_order" value="<?php echo $sections->num_rows + 1; ?>" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeSectionModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                        Create Section
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Create Lesson Modal -->
<div id="lessonModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Create New Lesson</h3>
            <form method="POST" action="">
                <input type="hidden" name="action" value="create_lesson">
                
                <div class="mb-4">
                    <label for="lesson_section" class="block text-sm font-medium text-gray-700 mb-2">Section</label>
                    <select name="section_id" id="lesson_section" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Choose a section...</option>
                        <?php while ($section = $all_sections->fetch_assoc()): ?>
                            <option value="<?php echo $section['id']; ?>"><?php echo htmlspecialchars($section['title']); ?></option>
                        <?php endwhile; ?>
                    </select>
                </div>
                
                <div class="mb-4">
                    <label for="lesson_title" class="block text-sm font-medium text-gray-700 mb-2">Lesson Title</label>
                    <input type="text" name="title" id="lesson_title" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div class="mb-4">
                    <label for="lesson_description" class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                    <textarea name="description" id="lesson_description" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mb-4">
                    <label for="lesson_content" class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                    <textarea name="content" id="lesson_content" rows="4" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="lesson_order" class="block text-sm font-medium text-gray-700 mb-2">Order</label>
                        <input type="number" name="order_number" id="lesson_order" value="1" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="lesson_duration" class="block text-sm font-medium text-gray-700 mb-2">Duration (min)</label>
                        <input type="number" name="duration" id="lesson_duration" value="30" min="1" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="flex items-center justify-end space-x-3">
                    <button type="button" onclick="closeLessonModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 border border-gray-300 rounded-md hover:bg-gray-200">
                        Cancel
                    </button>
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-green-600 border border-transparent rounded-md hover:bg-green-700">
                        Create Lesson
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openSectionModal() {
    document.getElementById('sectionModal').classList.remove('hidden');
}

function closeSectionModal() {
    document.getElementById('sectionModal').classList.add('hidden');
}

function openLessonModal() {
    document.getElementById('lessonModal').classList.remove('hidden');
}

function closeLessonModal() {
    document.getElementById('lessonModal').classList.add('hidden');
}

// Close modals when clicking outside
document.getElementById('sectionModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeSectionModal();
    }
});

document.getElementById('lessonModal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeLessonModal();
    }
});
</script>

<?php require_once '../includes/footer.php'; ?> 