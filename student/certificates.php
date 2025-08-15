<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/header.php';

requireStudent();

$student_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? 'list';
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$page_title = 'Certificates - Student';

// Handle certificate download
if ($action == 'download' && $course_id) {
    // Verify student is enrolled and has completed the course
    $sql = "SELECT c.*, u.name as student_name, u.email as student_email,
            (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
            (SELECT COUNT(*) FROM student_progress WHERE student_id = ? AND course_id = c.id AND lesson_completed = 1) as completed_lessons
            FROM courses c
            JOIN enrollments e ON c.id = e.course_id
            JOIN users u ON e.student_id = u.id
            WHERE c.id = ? AND e.student_id = ? AND e.status = 'active'";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iii", $course_id, $course_id, $student_id);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    
    if ($course && $course['completed_lessons'] >= $course['total_lessons'] && $course['total_lessons'] > 0) {
        // Generate certificate
        header('Content-Type: text/html');
        header('Content-Disposition: attachment; filename="certificate_' . $course['id'] . '_' . $student_id . '.html"');
        
        $completion_date = date('F j, Y');
        $completion_percentage = round(($course['completed_lessons'] / $course['total_lessons']) * 100);
        
        echo '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Certificate of Completion</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f5f5f5; }
        .certificate { background: white; border: 3px solid #gold; padding: 40px; text-align: center; max-width: 800px; margin: 0 auto; box-shadow: 0 4px 8px rgba(0,0,0,0.1); }
        .header { border-bottom: 2px solid #gold; padding-bottom: 20px; margin-bottom: 30px; }
        .title { font-size: 36px; color: #2c3e50; margin-bottom: 10px; font-weight: bold; }
        .subtitle { font-size: 18px; color: #7f8c8d; }
        .content { font-size: 16px; line-height: 1.6; margin: 30px 0; }
        .student-name { font-size: 28px; color: #2c3e50; font-weight: bold; margin: 20px 0; }
        .course-name { font-size: 24px; color: #34495e; margin: 20px 0; }
        .details { font-size: 14px; color: #7f8c8d; margin: 20px 0; }
        .signature { margin-top: 50px; }
        .signature-line { border-top: 1px solid #333; width: 200px; margin: 10px auto; }
        .certificate-id { font-size: 12px; color: #bdc3c7; margin-top: 30px; }
    </style>
</head>
<body>
    <div class="certificate">
        <div class="header">
            <div class="title">Certificate of Completion</div>
            <div class="subtitle">Teaching Management System</div>
        </div>
        
        <div class="content">
            This is to certify that
        </div>
        
        <div class="student-name">' . htmlspecialchars($course['student_name']) . '</div>
        
        <div class="content">
            has successfully completed the course
        </div>
        
        <div class="course-name">' . htmlspecialchars($course['title']) . '</div>
        
        <div class="content">
            with a completion rate of ' . $completion_percentage . '%<br>
            All course requirements have been met and the student has demonstrated<br>
            proficiency in the course material.
        </div>
        
        <div class="details">
            <strong>Course Description:</strong> ' . htmlspecialchars($course['description']) . '<br>
            <strong>Category:</strong> ' . htmlspecialchars($course['category']) . '<br>
            <strong>Completion Date:</strong> ' . $completion_date . '<br>
            <strong>Lessons Completed:</strong> ' . $course['completed_lessons'] . ' of ' . $course['total_lessons'] . '
        </div>
        
        <div class="signature">
            <div class="signature-line"></div>
            <div>Course Instructor</div>
        </div>
        
        <div class="certificate-id">
            Certificate ID: CERT-' . str_pad($course['id'], 4, '0', STR_PAD_LEFT) . '-' . str_pad($student_id, 4, '0', STR_PAD_LEFT) . '<br>
            Generated on: ' . date('Y-m-d H:i:s') . '
        </div>
    </div>
</body>
</html>';
        exit;
    }
}

// Get eligible courses for certificates
$sql = "SELECT c.*, e.enrollment_date,
        (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
        (SELECT COUNT(*) FROM student_progress WHERE student_id = ? AND course_id = c.id AND lesson_completed = 1) as completed_lessons,
        CASE 
            WHEN (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) > 0 
            AND (SELECT COUNT(*) FROM student_progress WHERE student_id = ? AND course_id = c.id AND lesson_completed = 1) >= (SELECT COUNT(*) FROM lessons WHERE course_id = c.id)
            THEN 1 
            ELSE 0 
        END as is_eligible
        FROM enrollments e
        JOIN courses c ON e.course_id = c.id
        WHERE e.student_id = ? AND e.status = 'active'
        ORDER BY e.enrollment_date DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $student_id, $student_id, $student_id);
$stmt->execute();
$courses = $stmt->get_result();
?>

    <!-- Main Content -->
    <div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="px-4 py-6 sm:px-0">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">My Certificates</h1>
                    <p class="mt-2 text-gray-600">Download certificates for completed courses</p>
                </div>
            </div>
        </div>

        <!-- Certificates List -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    Course Certificates (<?php echo $courses->num_rows; ?> total)
                </h3>
            </div>
            <div class="divide-y divide-gray-200">
                <?php if ($courses->num_rows > 0): ?>
                    <?php while ($course = $courses->fetch_assoc()): ?>
                        <div class="px-6 py-4 hover:bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <div class="h-12 w-12 rounded-full <?php echo $course['is_eligible'] ? 'bg-green-100' : 'bg-gray-100'; ?> flex items-center justify-center">
                                            <i class="fas <?php echo $course['is_eligible'] ? 'fa-certificate text-green-600' : 'fa-clock text-gray-400'; ?> text-xl"></i>
                                        </div>
                                    </div>
                                    <div class="ml-4">
                                        <div class="flex items-center">
                                            <h4 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h4>
                                            <div class="ml-2 flex items-center space-x-2">
                                                <?php if ($course['is_eligible']): ?>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                        <i class="fas fa-check mr-1"></i> Eligible
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-800">
                                                        <i class="fas fa-clock mr-1"></i> In Progress
                                                    </span>
                                                <?php endif; ?>
                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                                    <?php echo htmlspecialchars($course['category']); ?>
                                                </span>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars(substr($course['description'] ?? '', 0, 150)) . '...'; ?></p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <span><i class="fas fa-calendar mr-1"></i> Enrolled: <?php echo formatDate($course['enrollment_date']); ?></span>
                                            <span class="ml-4"><i class="fas fa-book mr-1"></i> Progress: <?php echo $course['completed_lessons']; ?>/<?php echo $course['total_lessons']; ?> lessons</span>
                                            <?php if ($course['total_lessons'] > 0): ?>
                                                <span class="ml-4"><i class="fas fa-percentage mr-1"></i> <?php echo round(($course['completed_lessons'] / $course['total_lessons']) * 100); ?>% complete</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if ($course['total_lessons'] > 0): ?>
                                            <div class="mt-2">
                                                <div class="w-full bg-gray-200 rounded-full h-2">
                                                    <div class="<?php echo $course['is_eligible'] ? 'bg-green-600' : 'bg-blue-600'; ?> h-2 rounded-full" style="width: <?php echo ($course['completed_lessons'] / $course['total_lessons']) * 100; ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <?php if ($course['is_eligible']): ?>
                                        <a href="?action=download&course_id=<?php echo $course['id']; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                            <i class="fas fa-download mr-2"></i>
                                            Download Certificate
                                        </a>
                                    <?php else: ?>
                                        <div class="text-sm text-gray-500">
                                            <?php 
                                            $remaining = $course['total_lessons'] - $course['completed_lessons'];
                                            echo $remaining > 0 ? $remaining . ' lessons remaining' : 'Complete all lessons';
                                            ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="px-6 py-8 text-center">
                        <i class="fas fa-certificate text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">No courses enrolled yet.</p>
                        <p class="text-sm text-gray-400 mt-2">Complete courses to earn certificates.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Certificate Information -->
        <div class="mt-8 bg-blue-50 border border-blue-200 rounded-lg p-6">
            <h3 class="text-lg font-medium text-blue-900 mb-2">About Certificates</h3>
            <div class="text-sm text-blue-700 space-y-2">
                <p><i class="fas fa-info-circle mr-2"></i>Certificates are automatically generated when you complete all lessons in a course.</p>
                <p><i class="fas fa-download mr-2"></i>Download certificates as HTML files that can be printed or shared.</p>
                <p><i class="fas fa-check mr-2"></i>Each certificate includes your name, course details, completion date, and a unique certificate ID.</p>
                <p><i class="fas fa-clock mr-2"></i>Courses marked as "In Progress" will become eligible once all lessons are completed.</p>
            </div>
        </div>
    </div>


<?php require_once '../includes/footer.php'; ?>