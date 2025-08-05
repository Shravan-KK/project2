<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/navigation.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Mark message as read
if ($message_id && $action == 'view') {
    $sql = "UPDATE messages SET is_read = TRUE WHERE id = ? AND receiver_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $message_id, $student_id);
    $stmt->execute();
}

// Get received messages
$sql = "SELECT m.*, u.name as sender_name, u.user_type as sender_type
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ?
        ORDER BY m.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$received_messages = $stmt->get_result();

// Get specific message
$message = null;
if ($message_id && $action == 'view') {
    $sql = "SELECT m.*, u.name as sender_name, u.user_type as sender_type
            FROM messages m
            JOIN users u ON m.sender_id = u.id
            WHERE m.id = ? AND m.receiver_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $message_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Student</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php renderNavigation('student', 'messages'); ?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?php echo $action == 'view' ? 'View Message' : 'Messages'; ?>
                    </h1>
                    <p class="mt-2 text-gray-600">View messages from your teachers and administrators</p>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-sm text-gray-500">
                        <i class="fas fa-envelope mr-1"></i>
                        <?php echo $received_messages->num_rows; ?> messages
                    </span>
                </div>
            </div>
        </div>

        <?php if ($action == 'view' && $message): ?>
            <!-- View Message -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($message['subject']); ?></h2>
                            <p class="text-gray-600 mt-1">
                                From: <?php echo htmlspecialchars($message['sender_name']); ?> 
                                (<?php echo ucfirst($message['sender_type']); ?>)
                            </p>
                        </div>
                        <a href="messages.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Messages
                        </a>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="mb-4 text-sm text-gray-500">
                        <span><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($message['created_at']); ?></span>
                    </div>
                    
                    <div class="prose max-w-none">
                        <div class="bg-gray-50 p-4 rounded-md">
                            <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Messages List -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Received Messages (<?php echo $received_messages->num_rows; ?> total)
                    </h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php if ($received_messages->num_rows > 0): ?>
                        <?php while ($msg = $received_messages->fetch_assoc()): ?>
                            <div class="px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($msg['subject']); ?></h4>
                                            <div class="flex items-center space-x-2">
                                                <?php if (!$msg['is_read']): ?>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                        <i class="fas fa-exclamation mr-1"></i> New
                                                    </span>
                                                <?php endif; ?>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                    <?php echo ucfirst($msg['sender_type']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($msg['message'], 0, 150)) . '...'; ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($msg['sender_name']); ?></span>
                                            <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($msg['created_at']); ?></span>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <a href="?action=view&id=<?php echo $msg['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            <i class="fas fa-eye mr-2"></i>
                                            View
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="px-6 py-8 text-center">
                            <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No messages received yet.</p>
                            <p class="text-sm text-gray-400 mt-2">Messages from teachers and administrators will appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 