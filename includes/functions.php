<?php
// Helper functions for the teaching management system

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
}

function isTeacher() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'teacher';
}

function isStudent() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'student';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ../index.php');
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

function requireTeacher() {
    requireLogin();
    if (!isTeacher() && !isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

function requireStudent() {
    requireLogin();
    if (!isStudent() && !isAdmin()) {
        header('Location: ../index.php');
        exit();
    }
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function formatDate($date) {
    return date('F j, Y', strtotime($date));
}

function formatDateTime($datetime) {
    return date('F j, Y g:i A', strtotime($datetime));
}

function formatDuration($minutes) {
    if (!$minutes || $minutes <= 0) {
        return 'N/A';
    }
    
    $hours = floor($minutes / 60);
    $mins = $minutes % 60;
    
    if ($hours > 0) {
        return $hours . 'h ' . $mins . 'm';
    } else {
        return $mins . 'm';
    }
}

function getCourseById($conn, $course_id) {
    $sql = "SELECT c.*, u.name as teacher_name FROM courses c 
            LEFT JOIN users u ON c.teacher_id = u.id 
            WHERE c.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getUserById($conn, $user_id) {
    $sql = "SELECT * FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getEnrolledCourses($conn, $student_id) {
    $sql = "SELECT c.*, e.enrollment_date, e.progress, u.name as teacher_name 
            FROM enrollments e 
            JOIN courses c ON e.course_id = c.id 
            LEFT JOIN users u ON c.teacher_id = u.id 
            WHERE e.student_id = ? AND e.status = 'active' 
            ORDER BY e.enrollment_date DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $student_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getTeacherCourses($conn, $teacher_id) {
    $sql = "SELECT c.*, COUNT(e.id) as enrolled_students 
            FROM courses c 
            LEFT JOIN enrollments e ON c.id = e.course_id AND e.status = 'active'
            WHERE c.teacher_id = ? 
            GROUP BY c.id 
            ORDER BY c.created_at DESC";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $teacher_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getUnreadMessages($conn, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    return $row['count'];
}

function getDashboardStats($conn, $user_type, $user_id = null) {
    $stats = [];
    
    if ($user_type == 'admin') {
        // Total users
        $sql = "SELECT COUNT(*) as total FROM users";
        $result = $conn->query($sql);
        $stats['total_users'] = $result->fetch_assoc()['total'];
        
        // Total courses
        $sql = "SELECT COUNT(*) as total FROM courses";
        $result = $conn->query($sql);
        $stats['total_courses'] = $result->fetch_assoc()['total'];
        
        // Total enrollments
        $sql = "SELECT COUNT(*) as total FROM enrollments";
        $result = $conn->query($sql);
        $stats['total_enrollments'] = $result->fetch_assoc()['total'];
        
        // Total revenue
        $sql = "SELECT SUM(amount) as total FROM payments WHERE status = 'completed'";
        $result = $conn->query($sql);
        $stats['total_revenue'] = $result->fetch_assoc()['total'] ?? 0;
        
    } elseif ($user_type == 'teacher') {
        // Teacher's courses
        $sql = "SELECT COUNT(*) as total FROM courses WHERE teacher_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['total_courses'] = $stmt->get_result()->fetch_assoc()['total'];
        
        // Teacher's students
        $sql = "SELECT COUNT(DISTINCT e.student_id) as total FROM enrollments e 
                JOIN courses c ON e.course_id = c.id 
                WHERE c.teacher_id = ? AND e.status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['total_students'] = $stmt->get_result()->fetch_assoc()['total'];
        
    } elseif ($user_type == 'student') {
        // Student's enrolled courses
        $sql = "SELECT COUNT(*) as total FROM enrollments WHERE student_id = ? AND status = 'active'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['enrolled_courses'] = $stmt->get_result()->fetch_assoc()['total'];
        
        // Student's completed courses
        $sql = "SELECT COUNT(*) as total FROM enrollments WHERE student_id = ? AND status = 'completed'";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stats['completed_courses'] = $stmt->get_result()->fetch_assoc()['total'];
    }
    
    return $stats;
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function uploadFile($file, $target_dir = 'uploads/') {
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $new_filename = generateRandomString() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return $target_file;
    }
    return false;
}
?> 