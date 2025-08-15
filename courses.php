<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Set default navigation variables for public pages
$nav_vars = [
    'bg_color' => 'bg-gray-600',
    'hover_color' => 'hover:text-white',
    'active_color' => 'text-white',
    'inactive_color' => 'text-gray-200'
];

require_once 'includes/header.php';

$page_title = 'Available Courses';

// Get all active courses with teacher information
$sql = "SELECT c.*, u.name as teacher_name, 
        (SELECT COUNT(*) FROM lessons WHERE course_id = c.id) as total_lessons,
        (SELECT COUNT(*) FROM enrollments WHERE course_id = c.id AND status = 'active') as enrolled_students
        FROM courses c 
        LEFT JOIN users u ON c.teacher_id = u.id 
        WHERE c.status = 'active' 
        ORDER BY c.created_at DESC";
$courses = $conn->query($sql);

// Check if user is logged in and is a student
$is_student = isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'student';
$student_id = $is_student ? $_SESSION['user_id'] : null;

// Get student's enrolled courses if logged in
$enrolled_course_ids = [];
if ($is_student) {
    $enroll_sql = "SELECT course_id FROM enrollments WHERE student_id = ? AND status = 'active'";
    $enroll_stmt = $conn->prepare($enroll_sql);
    $enroll_stmt->bind_param("i", $student_id);
    $enroll_stmt->execute();
    $enroll_result = $enroll_stmt->get_result();
    while ($row = $enroll_result->fetch_assoc()) {
        $enrolled_course_ids[] = $row['course_id'];
    }
}
?>

<!-- Main Content -->
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <!-- Page Header -->
    <div class="px-4 py-6 sm:px-0">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Available Courses</h1>
                <p class="mt-2 text-gray-600">Browse and enroll in courses to start learning</p>
            </div>
            <?php if ($is_student): ?>
                <a href="student/courses.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                    <i class="fas fa-graduation-cap mr-2"></i>
                    My Enrolled Courses
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Courses Grid -->
    <?php if ($courses->num_rows > 0): ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($course = $courses->fetch_assoc()): ?>
                <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow duration-300">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-xl font-semibold text-gray-900"><?php echo htmlspecialchars($course['title']); ?></h3>
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                <?php echo ucfirst($course['level']); ?>
                            </span>
                        </div>
                        
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars(substr($course['description'], 0, 120)) . '...'; ?></p>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-user mr-2"></i>
                                <span><?php echo htmlspecialchars($course['teacher_name'] ?? 'Unknown'); ?></span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-book mr-2"></i>
                                <span><?php echo $course['total_lessons']; ?> lessons</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-users mr-2"></i>
                                <span><?php echo $course['enrolled_students']; ?> students enrolled</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-clock mr-2"></i>
                                <span><?php echo htmlspecialchars($course['duration'] ?? 'Self-paced'); ?></span>
                            </div>
                            <?php if ($course['price'] > 0): ?>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-dollar-sign mr-2"></i>
                                    <span>$<?php echo number_format($course['price'], 2); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">
                                <?php echo htmlspecialchars($course['category']); ?>
                            </span>
                            
                            <?php if ($is_student): ?>
                                <?php if (in_array($course['id'], $enrolled_course_ids)): ?>
                                    <span class="px-3 py-2 text-sm font-medium text-green-700 bg-green-100 rounded-md">
                                        <i class="fas fa-check mr-1"></i> Enrolled
                                    </span>
                                <?php else: ?>
                                    <button onclick="enrollInCourse(<?php echo $course['id']; ?>)" 
                                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                        <i class="fas fa-plus mr-2"></i>
                                        Enroll Now
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="index.php" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700">
                                    <i class="fas fa-sign-in-alt mr-2"></i>
                                    Login to Enroll
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-12">
            <i class="fas fa-graduation-cap text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">No courses available</h3>
            <p class="text-gray-500">Check back later for new courses.</p>
        </div>
    <?php endif; ?>
</div>

<!-- Enrollment Modal -->
<div id="enrollmentModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
        <div class="mt-3 text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                <i class="fas fa-check text-green-600 text-xl"></i>
            </div>
            <h3 class="text-lg font-medium text-gray-900 mt-4">Enrollment Successful!</h3>
            <div class="mt-2 px-7 py-3">
                <p class="text-sm text-gray-500">You have been successfully enrolled in the course.</p>
            </div>
            <div class="items-center px-4 py-3">
                <button onclick="closeEnrollmentModal()" class="px-4 py-2 bg-blue-600 text-white text-base font-medium rounded-md w-full shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    Continue
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function enrollInCourse(courseId) {
    // Send enrollment request
    fetch('enroll_course.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            course_id: courseId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('enrollmentModal').classList.remove('hidden');
            // Reload page after a short delay
            setTimeout(() => {
                location.reload();
            }, 2000);
        } else {
            alert('Enrollment failed: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred during enrollment.');
    });
}

function closeEnrollmentModal() {
    document.getElementById('enrollmentModal').classList.add('hidden');
}
</script>

<?php require_once 'includes/footer.php'; ?>
