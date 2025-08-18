<?php
// Shared navigation component for consistent menu across all pages
function getNavigation($user_type, $current_page = '') {
    $nav_items = [];
    
    switch ($user_type) {
        case 'admin':
            $nav_items = [
                'dashboard' => ['url' => 'dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
                'users' => ['url' => 'users.php', 'icon' => 'fas fa-users', 'text' => 'Users'],
                'courses' => ['url' => 'courses.php', 'icon' => 'fas fa-book', 'text' => 'Courses'],
                'batches' => ['url' => 'batches.php', 'icon' => 'fas fa-layer-group', 'text' => 'Batches'],
                'enrollments' => ['url' => 'enrollments.php', 'icon' => 'fas fa-user-graduate', 'text' => 'Enrollments'],
                'payments' => ['url' => 'payments.php', 'icon' => 'fas fa-credit-card', 'text' => 'Payments'],
                'reports' => ['url' => 'reports.php', 'icon' => 'fas fa-chart-bar', 'text' => 'Reports'],
                'announcements' => ['url' => 'announcements.php', 'icon' => 'fas fa-bullhorn', 'text' => 'Announcements'],
                'messages' => ['url' => 'messages.php', 'icon' => 'fas fa-envelope', 'text' => 'Messages']
            ];
            $bg_color = 'bg-indigo-600';
            $hover_color = 'hover:text-white';
            $active_color = 'text-white';
            $inactive_color = 'text-indigo-200';
            break;
            
        case 'teacher':
            $nav_items = [
                'dashboard' => ['url' => 'dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
                'courses' => ['url' => 'courses.php', 'icon' => 'fas fa-book', 'text' => 'My Courses'],
                'all_courses' => ['url' => 'all_courses.php', 'icon' => 'fas fa-search', 'text' => 'All Courses'],
                'batches' => ['url' => 'batches.php', 'icon' => 'fas fa-layer-group', 'text' => 'My Batches'],
                'students' => ['url' => 'students.php', 'icon' => 'fas fa-user-graduate', 'text' => 'Students'],
                'assignments' => ['url' => 'assignments.php', 'icon' => 'fas fa-file-alt', 'text' => 'Assignments'],
                'grades' => ['url' => 'grades.php', 'icon' => 'fas fa-graduation-cap', 'text' => 'Grades'],
                'announcements' => ['url' => 'announcements.php', 'icon' => 'fas fa-bullhorn', 'text' => 'Announcements'],
                'messages' => ['url' => 'messages.php', 'icon' => 'fas fa-envelope', 'text' => 'Messages']
            ];
            $bg_color = 'bg-green-600';
            $hover_color = 'hover:text-white';
            $active_color = 'text-white';
            $inactive_color = 'text-green-200';
            break;
            
        case 'student':
            $nav_items = [
                'dashboard' => ['url' => 'dashboard.php', 'icon' => 'fas fa-tachometer-alt', 'text' => 'Dashboard'],
                'browse_courses' => ['url' => '../courses.php', 'icon' => 'fas fa-search', 'text' => 'Browse Courses'],
                'courses' => ['url' => 'courses.php', 'icon' => 'fas fa-book', 'text' => 'My Courses'],
                'batches' => ['url' => 'batches.php', 'icon' => 'fas fa-layer-group', 'text' => 'My Batches'],
                'classes' => ['url' => 'classes.php', 'icon' => 'fas fa-chalkboard-teacher', 'text' => 'My Classes'],
                'announcements' => ['url' => 'announcements.php', 'icon' => 'fas fa-bullhorn', 'text' => 'Announcements'],
                'assignments' => ['url' => 'assignments.php', 'icon' => 'fas fa-file-alt', 'text' => 'Assignments'],
                'grades' => ['url' => 'grades.php', 'icon' => 'fas fa-graduation-cap', 'text' => 'Grades'],
                'certificates' => ['url' => 'certificates.php', 'icon' => 'fas fa-certificate', 'text' => 'Certificates'],
                'messages' => ['url' => 'messages.php', 'icon' => 'fas fa-envelope', 'text' => 'Messages']
            ];
            $bg_color = 'bg-blue-600';
            $hover_color = 'hover:text-white';
            $active_color = 'text-white';
            $inactive_color = 'text-blue-200';
            break;
            
        default:
            // Default navigation for public pages
            $nav_items = [
                'home' => ['url' => 'index.php', 'icon' => 'fas fa-home', 'text' => 'Home'],
                'courses' => ['url' => 'courses.php', 'icon' => 'fas fa-book', 'text' => 'Browse Courses'],
                'login' => ['url' => 'index.php', 'icon' => 'fas fa-sign-in-alt', 'text' => 'Login']
            ];
            $bg_color = 'bg-gray-600';
            $hover_color = 'hover:text-white';
            $active_color = 'text-white';
            $inactive_color = 'text-gray-200';
            break;
    }
    
    return [
        'items' => $nav_items,
        'bg_color' => $bg_color,
        'hover_color' => $hover_color,
        'active_color' => $active_color,
        'inactive_color' => $inactive_color
    ];
}

function renderNavigation($user_type, $current_page = '') {
    $nav = getNavigation($user_type, $current_page);
    $user_name = $_SESSION['name'] ?? 'User';
    $user_type_icon = $user_type === 'admin' ? 'fas fa-user-shield' : ($user_type === 'teacher' ? 'fas fa-chalkboard-teacher' : 'fas fa-user-graduate');
    
    // Get unread message count
    $unread_messages = 0;
    if (isset($_SESSION['user_id'])) {
        global $conn;
        $sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = FALSE";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $unread_messages = $result->fetch_assoc()['count'];
    }
    
    echo '<nav class="' . $nav['bg_color'] . '">';
    echo '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';
    echo '<div class="flex items-center justify-between h-16">';
    
    // Logo and main navigation
    echo '<div class="flex items-center">';
    echo '<div class="flex-shrink-0">';
    echo '<i class="' . $user_type_icon . ' text-white text-2xl"></i>';
    echo '</div>';
    echo '<div class="hidden md:block">';
    echo '<div class="ml-10 flex items-baseline space-x-4">';
    
    foreach ($nav['items'] as $key => $item) {
        $is_active = ($current_page === $key);
        $text_color = $is_active ? $nav['active_color'] : $nav['inactive_color'];
        
        echo '<a href="' . $item['url'] . '" class="' . $text_color . ' ' . $nav['hover_color'] . ' px-3 py-2 rounded-md text-sm font-medium">';
        echo '<i class="' . $item['icon'] . ' mr-2"></i>';
        echo $item['text'];
        if ($key === 'messages' && $unread_messages > 0) {
            echo '<span class="bg-red-500 text-white text-xs rounded-full px-2 py-1 ml-1">' . $unread_messages . '</span>';
        }
        echo '</a>';
    }
    
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    // User info and logout
    echo '<div class="flex items-center">';
    echo '<span class="text-white text-sm">' . htmlspecialchars($user_name) . '</span>';
    echo '<a href="../logout.php" class="' . $nav['inactive_color'] . ' ' . $nav['hover_color'] . ' ml-4 text-sm">';
    echo '<i class="fas fa-sign-out-alt"></i> Logout';
    echo '</a>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
    echo '</nav>';
}
?> 