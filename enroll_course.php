<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
$course_id = $input['course_id'] ?? null;

if (!$course_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Course ID is required']);
    exit;
}

$student_id = $_SESSION['user_id'];

try {
    // Check if course exists and is active
    $course_sql = "SELECT id, title, status FROM courses WHERE id = ? AND status = 'active'";
    $course_stmt = $conn->prepare($course_sql);
    $course_stmt->bind_param("i", $course_id);
    $course_stmt->execute();
    $course_result = $course_stmt->get_result();
    
    if ($course_result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Course not found or inactive']);
        exit;
    }
    
    $course = $course_result->fetch_assoc();
    
    // Check if student is already enrolled
    $enroll_check_sql = "SELECT id, status FROM enrollments WHERE student_id = ? AND course_id = ?";
    $enroll_check_stmt = $conn->prepare($enroll_check_sql);
    $enroll_check_stmt->bind_param("ii", $student_id, $course_id);
    $enroll_check_stmt->execute();
    $enroll_check_result = $enroll_check_stmt->get_result();
    
    if ($enroll_check_result->num_rows > 0) {
        $enrollment = $enroll_check_result->fetch_assoc();
        if ($enrollment['status'] === 'active') {
            echo json_encode(['success' => false, 'message' => 'You are already enrolled in this course']);
        } else {
            // Reactivate enrollment
            $reactivate_sql = "UPDATE enrollments SET status = 'active', enrollment_date = CURRENT_TIMESTAMP WHERE id = ?";
            $reactivate_stmt = $conn->prepare($reactivate_sql);
            $reactivate_stmt->bind_param("i", $enrollment['id']);
            
            if ($reactivate_stmt->execute()) {
                echo json_encode(['success' => true, 'message' => 'Enrollment reactivated successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to reactivate enrollment']);
            }
        }
        exit;
    }
    
    // Create new enrollment
    $enroll_sql = "INSERT INTO enrollments (student_id, course_id, enrollment_date, status, progress) VALUES (?, ?, CURRENT_TIMESTAMP, 'active', 0)";
    $enroll_stmt = $conn->prepare($enroll_sql);
    $enroll_stmt->bind_param("ii", $student_id, $course_id);
    
    if ($enroll_stmt->execute()) {
        // Log the enrollment
        $enrollment_id = $conn->insert_id;
        
        // Create initial progress record for all lessons in the course
        $lessons_sql = "SELECT id FROM lessons WHERE course_id = ? ORDER BY order_number";
        $lessons_stmt = $conn->prepare($lessons_sql);
        $lessons_stmt->bind_param("i", $course_id);
        $lessons_stmt->execute();
        $lessons_result = $lessons_stmt->get_result();
        
        while ($lesson = $lessons_result->fetch_assoc()) {
            $progress_sql = "INSERT INTO student_progress (student_id, course_id, lesson_id, lesson_completed, completed_at) VALUES (?, ?, ?, 0, NULL)";
            $progress_stmt = $conn->prepare($progress_sql);
            $progress_stmt->bind_param("iii", $student_id, $course_id, $lesson['id']);
            $progress_stmt->execute();
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Successfully enrolled in ' . $course['title'],
            'enrollment_id' => $enrollment_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create enrollment']);
    }
    
} catch (Exception $e) {
    error_log("Enrollment error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during enrollment']);
}

$conn->close();
?> 