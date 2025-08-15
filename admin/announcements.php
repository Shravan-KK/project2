<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireAdmin();

$action = $_GET['action'] ?? 'list';
$announcement_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Handle announcement form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_announcement']) || isset($_POST['edit_announcement'])) {
        $title = sanitizeInput($_POST['title']);
        $content = sanitizeInput($_POST['content']);
        $target_audience = sanitizeInput($_POST['target_audience']);
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($title) || empty($content)) {
            $error = 'Please fill in all required fields';
        } else {
            if (isset($_POST['add_announcement'])) {
                // Add new announcement
                $sql = "INSERT INTO announcements (title, content, target_audience, is_active, created_by) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssis", $title, $content, $target_audience, $is_active, $_SESSION['user_id']);
                
                if ($stmt->execute()) {
                    $success = 'Announcement added successfully';
                    $action = 'list';
                } else {
                    $error = 'Failed to add announcement';
                }
            } else {
                // Edit existing announcement
                $sql = "UPDATE announcements SET title = ?, content = ?, target_audience = ?, is_active = ? WHERE id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssii", $title, $content, $target_audience, $is_active, $announcement_id);
                
                if ($stmt->execute()) {
                    $success = 'Announcement updated successfully';
                    $action = 'list';
                } else {
                    $error = 'Failed to update announcement';
                }
            }
        }
    }
}

// Get announcement for editing
$edit_announcement = null;
if ($action == 'edit' && $announcement_id) {
    $sql = "SELECT * FROM announcements WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_announcement = $result->fetch_assoc();
    
    if (!$edit_announcement) {
        $error = 'Announcement not found';
        $action = 'list';
    }
}

// Get all announcements with read counts
$sql = "SELECT a.*, u.name as created_by_name,
        COUNT(ar.id) as read_count,
        CASE 
            WHEN a.target_audience = 'students' THEN (SELECT COUNT(*) FROM users WHERE user_type = 'student')
            WHEN a.target_audience = 'teachers' THEN (SELECT COUNT(*) FROM users WHERE user_type = 'teacher')
            WHEN a.target_audience = 'both' THEN (SELECT COUNT(*) FROM users WHERE user_type IN ('student', 'teacher'))
        END as total_recipients
        FROM announcements a 
        LEFT JOIN users u ON a.created_by = u.id
        LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id
        GROUP BY a.id 
        ORDER BY a.created_at DESC";
$announcements = $conn->query($sql);
?>

<?php $page_title = 'Announcements - Admin'; ?>

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
                        <?php echo $action == 'add' ? 'Create New Announcement' : ($action == 'edit' ? 'Edit Announcement' : 'Announcements'); ?>
                    </h1>
                    <p class="mt-2 text-gray-600">Manage system announcements for students and teachers</p>
                </div>
                <?php if ($action == 'list'): ?>
                    <a href="?action=add" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                        <i class="fas fa-plus mr-2"></i>
                        Create Announcement
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($action == 'list'): ?>
            <!-- Announcements List -->
            <div class="bg-white shadow overflow-hidden sm:rounded-md">
                <div class="px-4 py-5 sm:px-6">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">
                        System Announcements (<?php echo $announcements->num_rows; ?> total)
                    </h3>
                </div>
                
                <?php if ($announcements->num_rows > 0): ?>
                    <ul class="divide-y divide-gray-200">
                        <?php while ($announcement = $announcements->fetch_assoc()): ?>
                            <li>
                                <div class="px-4 py-4 sm:px-6">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                                <div class="flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full <?php echo $announcement['is_active'] ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                        <?php echo $announcement['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                                        <?php echo $announcement['target_audience'] == 'students' ? 'bg-blue-100 text-blue-800' : 
                                                              ($announcement['target_audience'] == 'teachers' ? 'bg-purple-100 text-purple-800' : 'bg-indigo-100 text-indigo-800'); ?>">
                                                        <?php echo ucfirst($announcement['target_audience']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($announcement['content'], 0, 150)) . '...'; ?></p>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <span><i class="fas fa-user mr-1"></i> By <?php echo htmlspecialchars($announcement['created_by_name'] ?? 'Unknown'); ?></span>
                                                <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($announcement['created_at']); ?></span>
                                                <span class="ml-4"><i class="fas fa-eye mr-1"></i> <?php echo $announcement['read_count']; ?>/<?php echo $announcement['total_recipients']; ?> read</span>
                                            </div>
                                        </div>
                                        <div class="flex items-center space-x-2 ml-4">
                                            <a href="?action=edit&id=<?php echo $announcement['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="Edit Announcement">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="announcement_details.php?id=<?php echo $announcement['id']; ?>" class="text-blue-600 hover:text-blue-900" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        <?php endwhile; ?>
                    </ul>
                <?php else: ?>
                    <div class="px-4 py-8 text-center">
                        <i class="fas fa-bullhorn text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No announcements created yet.</p>
                        <a href="?action=add" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                            Create Your First Announcement
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Add/Edit Announcement Form -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <form method="POST" class="space-y-6">
                        <div class="grid grid-cols-1 gap-6">
                            <div>
                                <label for="title" class="block text-sm font-medium text-gray-700">Announcement Title *</label>
                                <input type="text" name="title" id="title" required 
                                       value="<?php echo htmlspecialchars($edit_announcement['title'] ?? ''); ?>"
                                       class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                            </div>
                            
                            <div>
                                <label for="target_audience" class="block text-sm font-medium text-gray-700">Target Audience *</label>
                                <select name="target_audience" id="target_audience" required
                                        class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="">Select Target Audience</option>
                                    <option value="students" <?php echo ($edit_announcement && $edit_announcement['target_audience'] == 'students') ? 'selected' : ''; ?>>Students Only</option>
                                    <option value="teachers" <?php echo ($edit_announcement && $edit_announcement['target_audience'] == 'teachers') ? 'selected' : ''; ?>>Teachers Only</option>
                                    <option value="both" <?php echo ($edit_announcement && $edit_announcement['target_audience'] == 'both') ? 'selected' : ''; ?>>Both Students and Teachers</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="content" class="block text-sm font-medium text-gray-700">Announcement Content *</label>
                                <textarea name="content" id="content" rows="8" required
                                          class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                          placeholder="Enter your announcement content..."><?php echo htmlspecialchars($edit_announcement['content'] ?? ''); ?></textarea>
                            </div>
                            
                            <div>
                                <div class="flex items-center">
                                    <input type="checkbox" name="is_active" id="is_active" 
                                           <?php echo ($edit_announcement && $edit_announcement['is_active']) ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                    <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                        Active Announcement
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <a href="announcements.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" name="<?php echo $action == 'add' ? 'add_announcement' : 'edit_announcement'; ?>" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700">
                                <?php echo $action == 'add' ? 'Create Announcement' : 'Update Announcement'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>


<?php require_once '../includes/footer.php'; ?>