<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/navigation.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$message_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$error = '';
$success = '';

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['send_message'])) {
    $recipient_id = (int)$_POST['recipient_id'];
    $subject = sanitizeInput($_POST['subject']);
    $content = sanitizeInput($_POST['content']);
    
            if (empty($subject) || empty($content) || empty($recipient_id)) {
            $error = 'Please fill in all required fields';
        } else {
            $sql = "INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("iiss", $teacher_id, $recipient_id, $subject, $content);
        
        if ($stmt->execute()) {
            $success = 'Message sent successfully';
            $action = 'list';
        } else {
            $error = 'Failed to send message';
        }
    }
}

// Mark message as read
if ($message_id && $action == 'view') {
    $sql = "UPDATE messages SET is_read = TRUE WHERE id = ? AND receiver_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $message_id, $teacher_id);
    $stmt->execute();
}

// Get sent messages
$sql = "SELECT m.*, u.name as recipient_name, u.user_type as recipient_type
        FROM messages m
        JOIN users u ON m.receiver_id = u.id
        WHERE m.sender_id = ?
        ORDER BY m.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$sent_messages = $stmt->get_result();

// Get received messages
$sql = "SELECT m.*, u.name as sender_name, u.user_type as sender_type
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE m.receiver_id = ?
        ORDER BY m.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$received_messages = $stmt->get_result();

// Get specific message
$message = null;
if ($message_id && $action == 'view') {
    $sql = "SELECT m.*, 
            CASE 
                WHEN m.sender_id = ? THEN u.name
                ELSE u.name
            END as other_user_name,
            CASE 
                WHEN m.sender_id = ? THEN u.user_type
                ELSE u.user_type
            END as other_user_type
            FROM messages m
            JOIN users u ON (m.sender_id = u.id OR m.receiver_id = u.id)
            WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?) AND u.id != ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiiiii", $teacher_id, $teacher_id, $message_id, $teacher_id, $teacher_id, $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();
}

// Get students enrolled in teacher's courses for sending messages
$sql = "SELECT DISTINCT u.id, u.name, u.user_type 
        FROM users u
        JOIN enrollments e ON u.id = e.student_id
        JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = ? AND u.id != ? AND e.status = 'active'
        ORDER BY u.name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $teacher_id, $teacher_id);
$stmt->execute();
$students = $stmt->get_result();

// Get admins for sending messages
$sql = "SELECT id, name, user_type FROM users WHERE user_type = 'admin' AND id != ? ORDER BY name";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $teacher_id);
$stmt->execute();
$admins = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - Teacher</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php renderNavigation('teacher', 'messages'); ?>

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
                        <?php echo $action == 'compose' ? 'Compose Message' : ($action == 'view' ? 'View Message' : 'Messages'); ?>
                    </h1>
                    <p class="mt-2 text-gray-600">Send and receive messages with students and admins</p>
                </div>
                <?php if ($action == 'list'): ?>
                    <a href="?action=compose" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i>
                        Compose Message
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($action == 'compose'): ?>
            <!-- Compose Message Form -->
            <div class="bg-white shadow sm:rounded-lg">
                <div class="px-4 py-5 sm:p-6">
                    <form method="POST" class="space-y-6">
                        <div>
                            <label for="recipient_id" class="block text-sm font-medium text-gray-700">Recipient *</label>
                            <select name="recipient_id" id="recipient_id" required
                                    class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                                <option value="">Select Recipient</option>
                                <optgroup label="Students">
                                    <?php while ($student = $students->fetch_assoc()): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['name']); ?> (Student)
                                        </option>
                                    <?php endwhile; ?>
                                </optgroup>
                                <optgroup label="Administrators">
                                    <?php while ($admin = $admins->fetch_assoc()): ?>
                                        <option value="<?php echo $admin['id']; ?>">
                                            <?php echo htmlspecialchars($admin['name']); ?> (Admin)
                                        </option>
                                    <?php endwhile; ?>
                                </optgroup>
                            </select>
                        </div>
                        
                        <div>
                            <label for="subject" class="block text-sm font-medium text-gray-700">Subject *</label>
                            <input type="text" name="subject" id="subject" required
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm">
                        </div>
                        
                        <div>
                            <label for="content" class="block text-sm font-medium text-gray-700">Message *</label>
                            <textarea name="content" id="content" rows="8" required
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500 sm:text-sm"
                                      placeholder="Enter your message..."></textarea>
                        </div>
                        
                        <div class="flex justify-end space-x-3">
                            <a href="messages.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" name="send_message" 
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Send Message
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($action == 'view' && $message): ?>
            <!-- View Message -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($message['subject']); ?></h2>
                            <p class="text-gray-600 mt-1">
                                <?php echo $message['sender_id'] == $teacher_id ? 'To: ' : 'From: '; ?>
                                <?php echo htmlspecialchars($message['other_user_name']); ?> 
                                (<?php echo ucfirst($message['other_user_type']); ?>)
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
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Received Messages -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">
                            Received Messages (<?php echo $received_messages->num_rows; ?>)
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
                                                            New
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                        <?php echo ucfirst($msg['sender_type']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                                                                         <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . '...'; ?></p>
                                             <div class="mt-2 flex items-center text-sm text-gray-500">
                                                 <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($msg['sender_name']); ?></span>
                                                 <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($msg['created_at']); ?></span>
                                             </div>
                                        </div>
                                        <div class="ml-4">
                                            <a href="?action=view&id=<?php echo $msg['id']; ?>" class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="px-6 py-8 text-center">
                                <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                                <p class="text-gray-500">No received messages.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sent Messages -->
                <div class="bg-white shadow rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">
                            Sent Messages (<?php echo $sent_messages->num_rows; ?>)
                        </h3>
                    </div>
                    <div class="divide-y divide-gray-200">
                        <?php if ($sent_messages->num_rows > 0): ?>
                            <?php while ($msg = $sent_messages->fetch_assoc()): ?>
                                <div class="px-6 py-4 hover:bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between">
                                                <h4 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($msg['subject']); ?></h4>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                    <?php echo ucfirst($msg['recipient_type']); ?>
                                                </span>
                                            </div>
                                                                                         <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($msg['message'], 0, 100)) . '...'; ?></p>
                                             <div class="mt-2 flex items-center text-sm text-gray-500">
                                                 <span><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($msg['recipient_name']); ?></span>
                                                 <span class="ml-4"><i class="fas fa-calendar mr-1"></i> <?php echo formatDate($msg['created_at']); ?></span>
                                             </div>
                                        </div>
                                        <div class="ml-4">
                                            <a href="?action=view&id=<?php echo $msg['id']; ?>" class="text-green-600 hover:text-green-900">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="px-6 py-8 text-center">
                                <i class="fas fa-paper-plane text-gray-400 text-4xl mb-4"></i>
                                <p class="text-gray-500">No sent messages.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html> 