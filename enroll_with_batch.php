<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: index.php');
    exit();
}

$student_id = $_SESSION['user_id'];
$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

// Handle enrollment submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $course_id = (int)$_POST['course_id'];
    $batch_id = isset($_POST['batch_id']) && !empty($_POST['batch_id']) ? (int)$_POST['batch_id'] : null;
    
    // Check if already enrolled
    $check_sql = "SELECT id FROM enrollments WHERE student_id = ? AND course_id = ? AND status = 'active'";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $student_id, $course_id);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        $error_message = "You are already enrolled in this course.";
    } else {
        // Check if batch is full
        if ($batch_id) {
            $batch_check_sql = "SELECT b.max_students, COUNT(e.id) as current_students 
                               FROM batches b 
                               LEFT JOIN enrollments e ON b.id = e.batch_id AND e.status = 'active'
                               WHERE b.id = ? 
                               GROUP BY b.id";
            $batch_check_stmt = $conn->prepare($batch_check_sql);
            $batch_check_stmt->bind_param("i", $batch_id);
            $batch_check_stmt->execute();
            $batch_info = $batch_check_stmt->get_result()->fetch_assoc();
            
            if ($batch_info && $batch_info['current_students'] >= $batch_info['max_students']) {
                $error_message = "This batch is full. Please select another batch.";
            }
        }
        
        if (!isset($error_message)) {
            // Enroll the student
            $enroll_sql = "INSERT INTO enrollments (student_id, course_id, batch_id, status) VALUES (?, ?, ?, 'active')";
            $enroll_stmt = $conn->prepare($enroll_sql);
            $enroll_stmt->bind_param("iii", $student_id, $course_id, $batch_id);
            
            if ($enroll_stmt->execute()) {
                $success_message = "Successfully enrolled in the course!";
                // Redirect to student dashboard after a short delay
                header("Refresh: 2; URL=student/dashboard.php");
            } else {
                $error_message = "Error enrolling in the course.";
            }
        }
    }
}

// Get course details
$course = null;
if ($course_id) {
    $course_sql = "SELECT c.*, u.name as teacher_name 
                   FROM courses c 
                   LEFT JOIN users u ON c.teacher_id = u.id 
                   WHERE c.id = ? AND c.status = 'active'";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bind_param("i", $course_id);
    $course_stmt->execute();
    $course = $course_stmt->get_result()->fetch_assoc();
    
    if (!$course) {
        header('Location: student/courses.php');
        exit();
    }
}

// Get available batches for this course
$batches_sql = "SELECT b.*, 
                COUNT(e.id) as enrolled_students,
                CASE WHEN COUNT(e.id) >= b.max_students THEN 'full' ELSE 'available' END as availability
                FROM batch_courses bc 
                JOIN batches b ON bc.batch_id = b.id 
                LEFT JOIN enrollments e ON b.id = e.batch_id AND e.status = 'active'
                WHERE bc.course_id = ? AND bc.status = 'active' AND b.status = 'active'
                GROUP BY b.id 
                ORDER BY b.start_date ASC";
$batches_stmt = $conn->prepare($batches_sql);
$batches_stmt->bind_param("i", $course_id);
$batches_stmt->execute();
$available_batches = $batches_stmt->get_result();

$page_title = 'Enroll in Course';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - Teaching Management System</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Header -->
        <nav class="bg-blue-600">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <i class="fas fa-user-graduate text-white text-2xl"></i>
                        </div>
                        <div class="hidden md:block">
                            <div class="ml-10 flex items-baseline space-x-4">
                                <a href="student/dashboard.php" class="text-blue-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                                    <i class="fas fa-tachometer-alt mr-2"></i>Dashboard
                                </a>
                                <a href="student/courses.php" class="text-blue-200 hover:text-white px-3 py-2 rounded-md text-sm font-medium">
                                    <i class="fas fa-book mr-2"></i>My Courses
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center">
                        <span class="text-white text-sm">Welcome, <?php echo htmlspecialchars($_SESSION['name']); ?></span>
                        <a href="logout.php" class="ml-4 text-blue-200 hover:text-white">
                            <i class="fas fa-sign-out-alt"></i>
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
            <div class="bg-white shadow rounded-lg">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h1 class="text-2xl font-bold text-gray-900">Enroll in Course</h1>
                </div>

                <?php if (isset($success_message)): ?>
                    <div class="m-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error_message)): ?>
                    <div class="m-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($course): ?>
                    <div class="p-6">
                        <!-- Course Information -->
                        <div class="mb-8">
                            <h2 class="text-xl font-semibold text-gray-900 mb-4"><?php echo htmlspecialchars($course['title']); ?></h2>
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($course['description']); ?></p>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-chalkboard-teacher text-blue-600 mr-3"></i>
                                        <div>
                                            <p class="text-sm text-gray-500">Instructor</p>
                                            <p class="font-medium"><?php echo htmlspecialchars($course['teacher_name']); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-dollar-sign text-green-600 mr-3"></i>
                                        <div>
                                            <p class="text-sm text-gray-500">Price</p>
                                            <p class="font-medium">$<?php echo number_format($course['price'], 2); ?></p>
                                        </div>
                                    </div>
                                </div>
                                <div class="bg-gray-50 p-4 rounded-lg">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock text-purple-600 mr-3"></i>
                                        <div>
                                            <p class="text-sm text-gray-500">Duration</p>
                                            <p class="font-medium"><?php echo htmlspecialchars($course['duration']); ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Enrollment Form -->
                        <form method="POST" class="space-y-6">
                            <input type="hidden" name="course_id" value="<?php echo $course_id; ?>">
                            
                            <!-- Batch Selection -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Select Batch (Optional)
                                </label>
                                <p class="text-sm text-gray-500 mb-4">
                                    Choose a specific batch to join, or leave empty to enroll without a batch assignment.
                                </p>
                                
                                <?php if ($available_batches->num_rows > 0): ?>
                                    <div class="space-y-3">
                                        <div class="flex items-center">
                                            <input type="radio" name="batch_id" value="" id="no_batch" class="mr-3">
                                            <label for="no_batch" class="text-sm text-gray-700">
                                                <span class="font-medium">No Batch Assignment</span>
                                                <span class="text-gray-500"> - Enroll without joining a specific batch</span>
                                            </label>
                                        </div>
                                        
                                        <?php while ($batch = $available_batches->fetch_assoc()): ?>
                                            <div class="border rounded-lg p-4 <?php echo $batch['availability'] === 'full' ? 'bg-gray-50' : 'bg-white'; ?>">
                                                <div class="flex items-center">
                                                    <input type="radio" name="batch_id" value="<?php echo $batch['id']; ?>" 
                                                           id="batch_<?php echo $batch['id']; ?>" 
                                                           <?php echo $batch['availability'] === 'full' ? 'disabled' : ''; ?>
                                                           class="mr-3">
                                                    <label for="batch_<?php echo $batch['id']; ?>" class="flex-1">
                                                        <div class="flex justify-between items-start">
                                                            <div>
                                                                <span class="font-medium text-gray-900"><?php echo htmlspecialchars($batch['name']); ?></span>
                                                                <p class="text-sm text-gray-600"><?php echo htmlspecialchars($batch['description']); ?></p>
                                                                <div class="mt-2 flex items-center text-sm text-gray-500">
                                                                    <span><i class="fas fa-calendar mr-1"></i> <?php echo $batch['start_date']; ?> - <?php echo $batch['end_date']; ?></span>
                                                                    <span class="ml-4"><i class="fas fa-users mr-1"></i> <?php echo $batch['enrolled_students']; ?>/<?php echo $batch['max_students']; ?> students</span>
                                                                </div>
                                                            </div>
                                                            <?php if ($batch['availability'] === 'full'): ?>
                                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800">
                                                                    Full
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                                                    Available
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </label>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="bg-gray-50 p-4 rounded-lg">
                                        <p class="text-sm text-gray-600">
                                            No batches are currently available for this course. You can still enroll without a batch assignment.
                                        </p>
                                        <input type="radio" name="batch_id" value="" id="no_batch" class="mt-3 mr-2" checked>
                                        <label for="no_batch" class="text-sm text-gray-700">Enroll without batch assignment</label>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Terms and Conditions -->
                            <div class="bg-gray-50 p-4 rounded-lg">
                                <div class="flex items-start">
                                    <input type="checkbox" name="agree_terms" id="agree_terms" required class="mt-1 mr-3">
                                    <label for="agree_terms" class="text-sm text-gray-700">
                                        I agree to the course terms and conditions. I understand that enrollment fees are non-refundable and I will have access to the course content for the duration specified.
                                    </label>
                                </div>
                            </div>

                            <!-- Submit Button -->
                            <div class="flex justify-end space-x-4">
                                <a href="student/courses.php" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-md hover:bg-gray-400">
                                    Cancel
                                </a>
                                <button type="submit" name="enroll" class="bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700">
                                    <i class="fas fa-user-plus mr-2"></i>Enroll Now
                                </button>
                            </div>
                        </form>
                    </div>
                <?php else: ?>
                    <div class="p-6 text-center">
                        <i class="fas fa-exclamation-triangle text-gray-400 text-4xl mb-4"></i>
                        <p class="text-gray-500">Course not found or not available for enrollment.</p>
                        <a href="student/courses.php" class="mt-4 inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                            Back to Courses
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html> 