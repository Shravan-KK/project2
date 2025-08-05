<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$announcement_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_title = 'Announcements - Student';

// Mark announcement as read
if ($announcement_id && $action == 'view') {
    $sql = "INSERT IGNORE INTO announcement_reads (announcement_id, user_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $announcement_id, $student_id);
    $stmt->execute();
}

// Get announcements for students
$sql = "SELECT a.*, u.name as created_by_name,
        CASE WHEN ar.id IS NOT NULL THEN 1 ELSE 0 END as is_read
        FROM announcements a 
        LEFT JOIN users u ON a.created_by = u.id
        LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.user_id = ?
        WHERE a.target_audience IN ('students', 'both') AND a.is_active = 1
        ORDER BY a.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$announcements = $stmt->get_result();

// Get specific announcement
$announcement = null;
if ($announcement_id && $action == 'view') {
    $sql = "SELECT a.*, u.name as created_by_name
            FROM announcements a 
            LEFT JOIN users u ON a.created_by = u.id
            WHERE a.id = ? AND a.target_audience IN ('students', 'both') AND a.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $announcement = $result->fetch_assoc();
}
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?php echo $action == 'view' ? 'View Announcement' : 'Announcements'; ?>
                    </h1>
                    <p class="mt-2 text-gray-600">Stay updated with important announcements</p>
                </div>
            </div>
        </div>

        <?php if ($action == 'view' && $announcement): ?>
            <!-- View Announcement -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h2>
                            <p class="text-gray-600 mt-1">
                                By: <?php echo htmlspecialchars($announcement['created_by_name'] ?? 'System'); ?>
                            </p>
                        </div>
                        <a href="announcements.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Announcements
                        </a>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="mb-4 text-sm text-gray-500">
                        <span><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($announcement['created_at']); ?></span>
                    </div>
                    
                    <div class="prose max-w-none">
                        <div class="bg-gray-50 p-4 rounded-md">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Announcements List -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Recent Announcements (<?php echo $announcements->num_rows; ?> total)
                    </h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php if ($announcements->num_rows > 0): ?>
                        <?php while ($announcement = $announcements->fetch_assoc()): ?>
                            <div class="px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                                            <div class="flex items-center space-x-2">
                                                <?php if (!$announcement['is_read']): ?>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                        <i class="fas fa-exclamation mr-1"></i> New
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($announcement['content'], 0, 150)) . '...'; ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($announcement['created_by_name'] ?? 'System'); ?></span>
                                            <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($announcement['created_at']); ?></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <a href="?action=view&id=<?php echo $announcement['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            <i class="fas fa-eye mr-2"></i>
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="px-6 py-8 text-center">
                            <i class="fas fa-bullhorn text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No announcements available.</p>
                            <p class="text-sm text-gray-400 mt-2">Check back later for updates.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
