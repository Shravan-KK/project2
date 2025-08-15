<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$page_title = 'Assignments - Student';

// Handle assignment submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_assignment'])) {
    $assignment_id = (int)$_POST['assignment_id'];
    $submission_text = sanitizeInput($_POST['submission_text']);
    $submission_file = $_FILES['submission_file'] ?? null;
    
    $attachment_url = null;
    if ($submission_file && $submission_file['error'] == 0) {
        $upload_dir = '../uploads/assignments/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($submission_file['name'], PATHINFO_EXTENSION);
        $filename = 'submission_' . $student_id . '_' . $assignment_id . '_' . time() . '.' . $file_extension;
        $filepath = $upload_dir . $filename;
        
        if (move_uploaded_file($submission_file['tmp_name'], $filepath)) {
            $attachment_url = 'uploads/assignments/' . $filename;
        }
    }
    
    // Check if submission already exists
    $check_sql = "SELECT id FROM submissions WHERE student_id = ? AND assignment_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $student_id, $assignment_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result();
    
    if ($existing->num_rows > 0) {
        // Update existing submission
        $sql = "UPDATE submissions SET submission_text = ?, attachment_url = ?, submitted_time = NOW() WHERE student_id = ? AND assignment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssii", $submission_text, $attachment_url, $student_id, $assignment_id);
    } else {
        // Create new submission
        $sql = "INSERT INTO submissions (student_id, assignment_id, submission_text, attachment_url, submitted_time) VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("iiss", $student_id, $assignment_id, $submission_text, $attachment_url);
    }
    
    if ($stmt->execute()) {
        $success_message = "Assignment submitted successfully!";
    } else {
        $error_message = "Error submitting assignment.";
    }
}

// Get assignments for enrolled courses
$sql = "SELECT a.*, c.title as course_title, c.id as course_id, u.name as teacher_name,
        s.submission_text, s.attachment_url, s.submitted_time, s.points_earned, s.teacher_notes
        FROM assignments a 
        JOIN courses c ON a.course_id = c.id 
        JOIN enrollments e ON c.id = e.course_id 
        LEFT JOIN users u ON c.teacher_id = u.id
        LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
        WHERE e.student_id = ? AND e.status = 'active' AND a.is_active = 1
        ORDER BY a.due_date ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $student_id, $student_id);
$stmt->execute();
$assignments = $stmt->get_result();

// Get specific assignment for submission
$assignment = null;
if ($assignment_id && $action == 'submit') {
    $sql = "SELECT a.*, c.title as course_title, c.id as course_id, u.name as teacher_name,
            s.submission_text, s.attachment_url, s.submitted_time, s.points_earned, s.teacher_notes
            FROM assignments a 
            JOIN courses c ON a.course_id = c.id 
            JOIN enrollments e ON c.id = e.course_id 
            LEFT JOIN users u ON c.teacher_id = u.id
            LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
            WHERE a.id = ? AND e.student_id = ? AND e.status = 'active' AND a.is_active = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $student_id, $assignment_id, $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $assignment = $result->fetch_assoc();
}
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?php echo $action == 'submit' ? 'Submit Assignment' : 'Assignments'; ?>
                    </h1>
                    <p class="mt-2 text-gray-600">View and submit your course assignments</p>
                </div>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="mb-4 bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)): ?>
            <div class="mb-4 bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($action == 'submit' && $assignment): ?>
            <!-- Submit Assignment Form -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h2>
                            <p class="text-gray-600 mt-1">Course: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
                        </div>
                        <a href="assignments.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Back to Assignments
                        </a>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Assignment Details</h3>
                        <div class="bg-gray-50 p-4 rounded-md">
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                            <?php if ($assignment['instructions']): ?>
                                <div class="mt-4">
                                    <h4 class="font-medium text-gray-900">Instructions:</h4>
                                    <p class="text-gray-700 mt-1"><?php echo nl2br(htmlspecialchars($assignment['instructions'])); ?></p>
                                </div>
                            <?php endif; ?>
                            <div class="mt-4 grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-900">Due Date:</span>
                                    <span class="text-gray-700 ml-2"><?php echo formatDate($assignment['due_date']); ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900">Max Points:</span>
                                    <span class="text-gray-700 ml-2"><?php echo $assignment['max_points']; ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($assignment['submitted_time']): ?>
                        <div class="mb-6 bg-blue-50 border border-blue-200 rounded-md p-4">
                            <h3 class="text-lg font-medium text-blue-900 mb-2">Your Submission</h3>
                            <p class="text-blue-700 mb-2">Submitted on: <?php echo formatDate($assignment['submitted_time']); ?></p>
                            <?php if ($assignment['submission_text']): ?>
                                <div class="mb-2">
                                    <span class="font-medium text-blue-900">Your Answer:</span>
                                    <div class="mt-1 bg-white p-3 rounded border">
                                        <?php echo nl2br(htmlspecialchars($assignment['submission_text'])); ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($assignment['attachment_url']): ?>
                                <div class="mb-2">
                                    <span class="font-medium text-blue-900">Attachment:</span>
                                    <a href="../<?php echo $assignment['attachment_url']; ?>" target="_blank" class="ml-2 text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-download mr-1"></i>Download File
                                    </a>
                                </div>
                            <?php endif; ?>
                            <?php if ($assignment['points_earned'] !== null): ?>
                                <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded">
                                    <span class="font-medium text-green-900">Grade: <?php echo $assignment['points_earned']; ?>/<?php echo $assignment['max_points']; ?></span>
                                    <?php if ($assignment['teacher_notes']): ?>
                                        <div class="mt-2">
                                            <span class="font-medium text-green-900">Teacher Notes:</span>
                                            <p class="text-green-700 mt-1"><?php echo nl2br(htmlspecialchars($assignment['teacher_notes'])); ?></p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" enctype="multipart/form-data" class="space-y-6">
                        <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                        
                        <div>
                            <label for="submission_text" class="block text-sm font-medium text-gray-700">Your Answer</label>
                            <textarea id="submission_text" name="submission_text" rows="6" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500" placeholder="Enter your assignment answer here..."><?php echo htmlspecialchars($assignment['submission_text'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label for="submission_file" class="block text-sm font-medium text-gray-700">Attachment (Optional)</label>
                            <input type="file" id="submission_file" name="submission_file" class="mt-1 block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                            <p class="mt-1 text-sm text-gray-500">Upload a file to support your submission (PDF, DOC, etc.)</p>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit" name="submit_assignment" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                <i class="fas fa-paper-plane mr-2"></i>
                                <?php echo $assignment['submitted_time'] ? 'Update Submission' : 'Submit Assignment'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <!-- Assignments List -->
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Your Assignments (<?php echo $assignments->num_rows; ?> total)
                    </h3>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php if ($assignments->num_rows > 0): ?>
                        <?php while ($assignment = $assignments->fetch_assoc()): ?>
                            <div class="px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($assignment['title']); ?></h4>
                                            <div class="flex items-center space-x-2">
                                                <?php if ($assignment['submitted_time']): ?>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                        <i class="fas fa-check mr-1"></i> Submitted
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                                        <i class="fas fa-clock mr-1"></i> Pending
                                                    </span>
                                                <?php endif; ?>
                                                <?php if ($assignment['points_earned'] !== null): ?>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                        <i class="fas fa-star mr-1"></i> Graded
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($assignment['description'], 0, 150)) . '...'; ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-book mr-1"></i> <?php echo htmlspecialchars($assignment['course_title']); ?></span>
                                            <span class="ml-4"><i class="fas fa-user mr-1"></i> <?php echo htmlspecialchars($assignment['teacher_name'] ?? 'Unknown'); ?></span>
                                            <span class="ml-4"><i class="fas fa-calendar mr-1"></i> Due: <?php echo formatDate($assignment['due_date']); ?></span>
                                            <span class="ml-4"><i class="fas fa-star mr-1"></i> <?php echo $assignment['max_points']; ?> points</span>
                                        </div>
                                        <?php if ($assignment['submitted_time']): ?>
                                            <div class="mt-2 text-sm text-gray-500">
                                                <span><i class="fas fa-paper-plane mr-1"></i> Submitted: <?php echo formatDate($assignment['submitted_time']); ?></span>
                                                <?php if ($assignment['points_earned'] !== null): ?>
                                                    <span class="ml-4"><i class="fas fa-star mr-1"></i> Grade: <?php echo $assignment['points_earned']; ?>/<?php echo $assignment['max_points']; ?></span>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="ml-4">
                                        <a href="?action=submit&id=<?php echo $assignment['id']; ?>" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                            <i class="fas fa-edit mr-2"></i>
                                            <?php echo $assignment['submitted_time'] ? 'View/Edit' : 'Submit'; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="px-6 py-8 text-center">
                            <i class="fas fa-clipboard-list text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No assignments available.</p>
                            <p class="text-sm text-gray-400 mt-2">Check back later for new assignments.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>


<?php require_once '../includes/footer.php'; ?>