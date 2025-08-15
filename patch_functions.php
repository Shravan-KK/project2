<?php
// Patch file to add any missing functions
require_once 'includes/functions.php';

// Add missing functions that might be needed

if (!function_exists('generateRandomString')) {
    function generateRandomString($length = 10) {
        return substr(str_shuffle(str_repeat($x='0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', ceil($length/strlen($x)) )),1,$length);
    }
}

if (!function_exists('getStudentCourses')) {
    function getStudentCourses($conn, $student_id) {
        $sql = "SELECT c.*, e.enrollment_date, e.progress, e.status as enrollment_status 
                FROM courses c 
                JOIN enrollments e ON c.id = e.course_id 
                WHERE e.student_id = ? AND e.status = 'active'
                ORDER BY e.enrollment_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}

if (!function_exists('getTeacherStudents')) {
    function getTeacherStudents($conn, $teacher_id) {
        $sql = "SELECT DISTINCT u.*, c.title as course_title, e.enrollment_date 
                FROM users u 
                JOIN enrollments e ON u.id = e.student_id 
                JOIN courses c ON e.course_id = c.id 
                WHERE c.teacher_id = ? AND u.user_type = 'student' AND e.status = 'active'
                ORDER BY u.name";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}

if (!function_exists('getStudentGrades')) {
    function getStudentGrades($conn, $student_id) {
        $sql = "SELECT g.*, a.title as assignment_title, c.title as course_title, u.name as graded_by_name
                FROM grades g
                LEFT JOIN assignments a ON g.assignment_id = a.id
                LEFT JOIN courses c ON g.course_id = c.id
                LEFT JOIN users u ON g.graded_by = u.id
                WHERE g.student_id = ?
                ORDER BY g.graded_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}

if (!function_exists('getStudentCertificates')) {
    function getStudentCertificates($conn, $student_id) {
        $sql = "SELECT cert.*, c.title as course_title
                FROM certificates cert
                JOIN courses c ON cert.course_id = c.id
                WHERE cert.student_id = ?
                ORDER BY cert.issue_date DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}

if (!function_exists('getStudentClasses')) {
    function getStudentClasses($conn, $student_id) {
        $sql = "SELECT cl.*, c.title as course_title
                FROM classes cl
                JOIN courses c ON cl.course_id = c.id
                JOIN enrollments e ON c.id = e.course_id
                WHERE e.student_id = ? AND e.status = 'active'
                ORDER BY cl.class_date DESC, cl.start_time";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $student_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}

if (!function_exists('getAnnouncementsForUser')) {
    function getAnnouncementsForUser($conn, $user_type, $user_id = null) {
        $sql = "SELECT a.*, u.name as created_by_name 
                FROM announcements a 
                LEFT JOIN users u ON a.created_by = u.id 
                WHERE a.is_active = 1 
                AND (a.target_audience = 'all' OR a.target_audience = ?)
                ORDER BY a.created_at DESC";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $user_type);
        $stmt->execute();
        return $stmt->get_result();
    }
}

if (!function_exists('getUserAssignments')) {
    function getUserAssignments($conn, $user_id, $user_type) {
        if ($user_type == 'student') {
            $sql = "SELECT a.*, c.title as course_title, s.submitted_at, s.grade as submission_grade
                    FROM assignments a
                    JOIN courses c ON a.course_id = c.id
                    JOIN enrollments e ON c.id = e.course_id
                    LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
                    WHERE e.student_id = ? AND e.status = 'active'
                    ORDER BY a.due_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ii", $user_id, $user_id);
        } else {
            $sql = "SELECT a.*, c.title as course_title
                    FROM assignments a
                    JOIN courses c ON a.course_id = c.id
                    WHERE c.teacher_id = ?
                    ORDER BY a.due_date DESC";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
        }
        $stmt->execute();
        return $stmt->get_result();
    }
}

echo "✅ Function patches loaded successfully!";
?>