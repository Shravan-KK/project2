<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$section_id = $_GET['id'] ?? null;
if (!$section_id) {
    header('Location: courses.php');
    exit;
}

$page_title = 'Edit Section - Admin';

// Get section details with course information
$section_sql = "SELECT cs.*, c.title as course_title, c.id as course_id, u.name as teacher_name 
                FROM course_sections cs
                JOIN courses c ON cs.course_id = c.id
                LEFT JOIN users u ON c.teacher_id = u.id
                WHERE cs.id = ?";
$section_stmt = $conn->prepare($section_sql);
$section_stmt->bind_param("i", $section_id);
$section_stmt->execute();
$section = $section_stmt->get_result()->fetch_assoc();

if (!$section) {
    header('Location: courses.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && $_POST['action'] === 'update_section') {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $order_number = (int)$_POST['order_number'];
        
        $sql = "UPDATE course_sections SET title = ?, description = ?, order_number = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $title, $description, $order_number, $section_id);
        
        if ($stmt->execute()) {
            $success_message = "Section updated successfully!";
            // Refresh section data
            $section_stmt->execute();
            $section = $section_stmt->get_result()->fetch_assoc();
        } else {
            $error_message = "Error updating section: " . $stmt->error;
        }
    }
}
?>

<div class="max-w-4xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Edit Section</h1>
                <p class="mt-2 text-gray-600"><?php echo htmlspecialchars($section['title']); ?> - <?php echo htmlspecialchars($section['course_title']); ?></p>
            </div>
            <div class="flex items-center space-x-3">
                <a href="section_view.php?id=<?php echo $section_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-eye mr-2"></i>
                    View Section
                </a>
                <a href="course_sections.php?course_id=<?php echo $section['course_id']; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Sections
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

    <!-- Edit Section Form -->
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Section Details</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Update the section information below.</p>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <form method="POST" action="">
                <input type="hidden" name="action" value="update_section">
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- Course Information (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Course</label>
                        <div class="mt-1 p-3 bg-gray-100 border border-gray-300 rounded-md">
                            <div class="flex items-center">
                                <i class="fas fa-book text-gray-500 mr-2"></i>
                                <span class="text-gray-900"><?php echo htmlspecialchars($section['course_title']); ?></span>
                                <span class="ml-2 text-sm text-gray-500">by <?php echo htmlspecialchars($section['teacher_name'] ?? 'Unassigned'); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Section Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700">Section Title *</label>
                        <input type="text" 
                               id="title" 
                               name="title"
                               value="<?php echo htmlspecialchars($section['title']); ?>"
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                               placeholder="Enter section title">
                    </div>
                    
                    <!-- Section Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700">Description</label>
                        <textarea id="description" 
                                  name="description"
                                  rows="4"
                                  class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500"
                                  placeholder="Enter section description (optional)"><?php echo htmlspecialchars($section['description'] ?? ''); ?></textarea>
                        <p class="mt-2 text-sm text-gray-500">Provide a brief description of what this section covers.</p>
                    </div>
                    
                    <!-- Order Number -->
                    <div>
                        <label for="order_number" class="block text-sm font-medium text-gray-700">Order Number *</label>
                        <input type="number" 
                               id="order_number" 
                               name="order_number"
                               value="<?php echo $section['order_number']; ?>"
                               min="1"
                               required
                               class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <p class="mt-2 text-sm text-gray-500">This determines the order in which sections appear in the course.</p>
                    </div>
                    
                    <!-- Meta Information -->
                    <div class="bg-gray-50 p-4 rounded-md">
                        <h4 class="text-sm font-medium text-gray-900 mb-2">Section Information</h4>
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-500">Created:</span>
                                <span class="text-gray-900"><?php echo formatDate($section['created_at']); ?></span>
                            </div>
                            <div>
                                <span class="text-gray-500">Last Updated:</span>
                                <span class="text-gray-900"><?php echo formatDate($section['updated_at']); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Form Actions -->
                <div class="mt-6 flex items-center justify-end space-x-3">
                    <a href="section_view.php?id=<?php echo $section_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        <i class="fas fa-save mr-2"></i>
                        Update Section
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="mt-6 bg-white shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900">Quick Actions</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500">Manage this section's content and settings.</p>
        </div>
        <div class="border-t border-gray-200 px-4 py-5 sm:px-6">
            <div class="flex flex-wrap gap-3">
                <a href="lessons.php?section_id=<?php echo $section_id; ?>&course_id=<?php echo $section['course_id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                    <i class="fas fa-cogs mr-2"></i>
                    Manage Lessons
                </a>
                <a href="section_view.php?id=<?php echo $section_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-eye mr-2"></i>
                    View Section
                </a>
                <a href="course_sections.php?course_id=<?php echo $section['course_id']; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-folder-open mr-2"></i>
                    All Sections
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>