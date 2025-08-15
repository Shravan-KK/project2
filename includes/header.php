<?php
// Enable error display for development
$error_display_file = __DIR__ . '/../config/error_display.php';
if (file_exists($error_display_file)) {
    require_once $error_display_file;
} else {
    // Fallback error display settings
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
}

// Header file with navigation for all pages
if (!isset($_SESSION)) {
    session_start();
}

// Determine user type and current page
$user_type = '';
$current_page = '';

if (isset($_SESSION['user_type'])) {
    $user_type = $_SESSION['user_type'];
} elseif (isset($_SESSION['role'])) {
    $user_type = $_SESSION['role'];
}

// Determine current page from URL
$script_name = $_SERVER['SCRIPT_NAME'];
if (strpos($script_name, 'admin/') !== false) {
    $user_type = 'admin';
    if (strpos($script_name, 'dashboard.php') !== false) $current_page = 'dashboard';
    elseif (strpos($script_name, 'users.php') !== false) $current_page = 'users';
    elseif (strpos($script_name, 'courses.php') !== false) $current_page = 'courses';
    elseif (strpos($script_name, 'enrollments.php') !== false) $current_page = 'enrollments';
    elseif (strpos($script_name, 'payments.php') !== false) $current_page = 'payments';
    elseif (strpos($script_name, 'reports.php') !== false) $current_page = 'reports';
    elseif (strpos($script_name, 'announcements.php') !== false) $current_page = 'announcements';
    elseif (strpos($script_name, 'messages.php') !== false) $current_page = 'messages';
    elseif (strpos($script_name, 'assignments.php') !== false) $current_page = 'assignments';
    elseif (strpos($script_name, 'quizzes.php') !== false) $current_page = 'quizzes';
    elseif (strpos($script_name, 'lessons.php') !== false) $current_page = 'lessons';
    elseif (strpos($script_name, 'batches.php') !== false) $current_page = 'batches';
    elseif (strpos($script_name, 'batch_details.php') !== false) $current_page = 'batches';
    elseif (strpos($script_name, 'course_content.php') !== false) $current_page = 'course_content';
    elseif (strpos($script_name, 'announcement_details.php') !== false) $current_page = 'announcements';
} elseif (strpos($script_name, 'teacher/') !== false) {
    $user_type = 'teacher';
    if (strpos($script_name, 'dashboard.php') !== false) $current_page = 'dashboard';
    elseif (strpos($script_name, 'courses.php') !== false) $current_page = 'courses';
    elseif (strpos($script_name, 'students.php') !== false) $current_page = 'students';
    elseif (strpos($script_name, 'assignments.php') !== false) $current_page = 'assignments';
    elseif (strpos($script_name, 'grades.php') !== false) $current_page = 'grades';
    elseif (strpos($script_name, 'announcements.php') !== false) $current_page = 'announcements';
    elseif (strpos($script_name, 'messages.php') !== false) $current_page = 'messages';
    elseif (strpos($script_name, 'course_content.php') !== false) $current_page = 'course_content';
    elseif (strpos($script_name, 'assignment_submissions.php') !== false) $current_page = 'assignment_submissions';
} elseif (strpos($script_name, 'student/') !== false) {
    $user_type = 'student';
    if (strpos($script_name, 'dashboard.php') !== false) $current_page = 'dashboard';
    elseif (strpos($script_name, 'courses.php') !== false) $current_page = 'courses';
    elseif (strpos($script_name, 'announcements.php') !== false) $current_page = 'announcements';
    elseif (strpos($script_name, 'assignments.php') !== false) $current_page = 'assignments';
    elseif (strpos($script_name, 'grades.php') !== false) $current_page = 'grades';
    elseif (strpos($script_name, 'certificates.php') !== false) $current_page = 'certificates';
    elseif (strpos($script_name, 'messages.php') !== false) $current_page = 'messages';
    elseif (strpos($script_name, 'course_content.php') !== false) $current_page = 'course_content';
} else {
    $user_type = 'public';
    if (strpos($script_name, 'courses.php') !== false) $current_page = 'public_courses';
}

// Include navigation
require_once __DIR__ . '/navigation.php';

// Get navigation data
$nav = getNavigation($user_type, $current_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title ?? 'Teaching Management System'; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
    <body class="bg-gray-100 flex flex-col min-h-screen">
        <?php renderNavigation($user_type, $current_page); ?>
        <main class="flex-grow"> 