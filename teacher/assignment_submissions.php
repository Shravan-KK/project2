<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireTeacher();

$teacher_id = $_SESSION['user_id'];
$assignment_id = isset($_GET['assignment_id']) ? (int)$_GET['assignment_id'] : 0;
$submission_id = isset($_GET['submission_id']) ? (int)$_GET['submission_id'] : 0;
$action = $_GET['action'] ?? 'list';
$page_title = 'Assignment Submissions - Teacher';

// Handle grading
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = (int)$_POST['submission_id'];
    $points_earned = (float)$_POST['points_earned'];
    $teacher_notes = sanitizeInput($_POST['teacher_notes']);
    
    // Verify teacher owns the assignment
    $assignment_check = "SELECT a.* FROM assignments a 
                        JOIN courses c ON a.course_id = c.id 
                        WHERE a.id = (SELECT assignment_id FROM submissions WHERE id = ?) AND c.teacher_id = ?";
    $assignment_stmt = $conn->prepare($assignment_check);
    $assignment_stmt->bind_param("ii", $submission_id, $teacher_id);
    $assignment_stmt->execute();
    $assignment = $assignment_stmt->get_result()->fetch_assoc();
    
    if ($assignment && $points_earned <= $assignment['max_points']) {
        $sql = "UPDATE submissions SET points_earned = ?, teacher_notes = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("dsi", $points_earned, $teacher_notes, $submission_id);
        
        if ($stmt->execute()) {
            $success_message = "Submission graded successfully!";
        } else {
            $error_message = "Error grading submission.";
        }
    } else {
        $error_message = "Invalid points or assignment.";
    }
}

// Get assignment details
$assignment = null;
if ($assignment_id) {
    $assignment_sql = "SELECT a.*, c.title as course_title 
                       FROM assignments a 
                       JOIN courses c ON a.course_id = c.id 
                       WHERE a.id = ? AND c.teacher_id = ?";
    $assignment_stmt = $conn->prepare($assignment_sql);
    $assignment_stmt->bind_param("ii", $assignment_id, $teacher_id);
    $assignment_stmt->execute();
    $assignment = $assignment_stmt->get_result()->fetch_assoc();
}

// Get submissions for the assignment
$submissions = null;
if ($assignment_id) {
    $submissions_sql = "SELECT s.*, u.name as student_name, u.email as student_email
                        FROM submissions s
                        JOIN users u ON s.student_id = u.id
                        WHERE s.assignment_id = ?
                        ORDER BY s.submitted_time DESC";
    $submissions_stmt = $conn->prepare($submissions_sql);
    $submissions_stmt->bind_param("i", $assignment_id);
    $submissions_stmt->execute();
    $submissions = $submissions_stmt->get_result();
}

// Get specific submission for grading
$submission = null;
if ($submission_id) {
    $submission_sql = "SELECT s.*, u.name as student_name, u.email as student_email, a.title as assignment_title, a.max_points
                       FROM submissions s
                       JOIN users u ON s.student_id = u.id
                       JOIN assignments a ON s.assignment_id = a.id
                       JOIN courses c ON a.course_id = c.id
                       WHERE s.id = ? AND c.teacher_id = ?";
    $submission_stmt = $conn->prepare($submission_sql);
    $submission_stmt->bind_param("ii", $submission_id, $teacher_id);
    $submission_stmt->execute();
    $submission = $submission_stmt->get_result()->fetch_assoc();
}
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <?php echo $action == 'grade' ? 'Grade Submission' : 'Assignment Submissions'; ?>
                    </h1>
                    <p class="mt-2 text-gray-600">
                        <?php echo $action == 'grade' ? 'Review and grade student submission' : 'View and manage assignment submissions'; ?>
                    </p>
                </div>
                <a href="assignments.php" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Back to Assignments
                </a>
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

        <?php if ($action == 'grade' && $submission): ?>
            <!-- Grade Submission Form -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-gray-900"><?php echo htmlspecialchars($submission['assignment_title']); ?></h2>
                            <p class="text-gray-600 mt-1">Student: <?php echo htmlspecialchars($submission['student_name']); ?></p>
                        </div>
                    </div>
                </div>
                
                <div class="px-6 py-4">
                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Assignment Details</h3>
                        <div class="bg-gray-50 p-4 rounded-md">
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-900">Max Points:</span>
                                    <span class="text-gray-700 ml-2"><?php echo $submission['max_points']; ?></span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-900">Submitted:</span>
                                    <span class="text-gray-700 ml-2"><?php echo formatDate($submission['submitted_time']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Student Submission</h3>
                        <?php if ($submission['submission_text']): ?>
                            <div class="bg-blue-50 p-4 rounded-md mb-4">
                                <h4 class="font-medium text-blue-900 mb-2">Answer:</h4>
                                <div class="text-blue-700">
                                    <?php echo nl2br(htmlspecialchars($submission['submission_text'])); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($submission['attachment_url']): ?>
                            <div class="bg-blue-50 p-4 rounded-md">
                                <h4 class="font-medium text-blue-900 mb-2">Attachment:</h4>
                                <a href="<?php echo htmlspecialchars($submission['attachment_url']); ?>" 
                                   target="_blank" 
                                   class="text-blue-600 hover:text-blue-800">
                                    <i class="fas fa-download mr-1"></i>Download File
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                        
                        <div>
                            <label for="points_earned" class="block text-sm font-medium text-gray-700">Points Earned *</label>
                            <input type="number" id="points_earned" name="points_earned" step="0.1" min="0" max="<?php echo $submission['max_points']; ?>" required
                                   value="<?php echo $submission['points_earned'] ?? ''; ?>"
                                   class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500">
                            <p class="mt-1 text-sm text-gray-500">Maximum points: <?php echo $submission['max_points']; ?></p>
                        </div>

                        <div>
                            <label for="teacher_notes" class="block text-sm font-medium text-gray-700">Teacher Notes</label>
                            <textarea id="teacher_notes" name="teacher_notes" rows="4"
                                      class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                      placeholder="Provide feedback and comments..."><?php echo htmlspecialchars($submission['teacher_notes'] ?? ''); ?></textarea>
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="?assignment_id=<?php echo $assignment_id; ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 shadow-sm text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                Cancel
                            </a>
                            <button type="submit" name="grade_submission"
                                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-star mr-2"></i>
                                <?php echo $submission['points_earned'] !== null ? 'Update Grade' : 'Grade Submission'; ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php elseif ($assignment): ?>
            <!-- Assignment Submissions List -->
            <div class="bg-white shadow rounded-lg mb-6">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <?php echo htmlspecialchars($assignment['title']); ?> - Submissions
                    </h3>
                    <p class="text-sm text-gray-600 mt-1">Course: <?php echo htmlspecialchars($assignment['course_title']); ?></p>
                </div>
                <div class="divide-y divide-gray-200">
                    <?php if ($submissions && $submissions->num_rows > 0): ?>
                        <?php while ($submission_item = $submissions->fetch_assoc()): ?>
                            <div class="px-6 py-4 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0">
                                            <div class="h-10 w-10 rounded-full bg-blue-100 flex items-center justify-center">
                                                <i class="fas fa-user text-blue-600"></i>
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            <div class="flex items-center">
                                                <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($submission_item['student_name']); ?></h4>
                                                <div class="ml-2 flex items-center space-x-2">
                                                    <?php if ($submission_item['points_earned'] !== null): ?>
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                            <i class="fas fa-check mr-1"></i> Graded
                                                        </span>
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                            <?php echo $submission_item['points_earned']; ?>/<?php echo $assignment['max_points']; ?> points
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                                            <i class="fas fa-clock mr-1"></i> Pending
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($submission_item['student_email']); ?></p>
                                            <div class="mt-2 flex items-center text-sm text-gray-500">
                                                <span><i class="fas fa-calendar mr-1"></i> Submitted: <?php echo formatDate($submission_item['submitted_time']); ?></span>
                                                <?php if ($submission_item['attachment_url']): ?>
                                                    <span class="ml-4"><i class="fas fa-paperclip mr-1"></i> Has attachment</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <a href="?assignment_id=<?php echo $assignment_id; ?>&submission_id=<?php echo $submission_item['id']; ?>&action=grade" class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                            <i class="fas fa-star mr-2"></i>
                                            <?php echo $submission_item['points_earned'] !== null ? 'Update Grade' : 'Grade'; ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="px-6 py-8 text-center">
                            <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                            <p class="text-gray-500">No submissions received yet.</p>
                            <p class="text-sm text-gray-400 mt-2">Student submissions will appear here once they submit their work.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-8 text-center">
                    <i class="fas fa-exclamation-triangle text-gray-400 text-4xl mb-4"></i>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">Assignment Not Found</h3>
                    <p class="text-gray-500">The requested assignment could not be found or you don't have permission to access it.</p>
                    <a href="assignments.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Assignments
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
