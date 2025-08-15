<?php
// Script to add footer to all pages
$pages_to_update = [
    // Admin pages
    'admin/users.php',
    'admin/courses.php',
    'admin/enrollments.php',
    'admin/payments.php',
    'admin/reports.php',
    'admin/announcements.php',
    'admin/announcement_details.php',
    'admin/messages.php',
    'admin/assignments.php',
    'admin/lessons.php',
    
    // Teacher pages
    'teacher/dashboard.php',
    'teacher/courses.php',
    'teacher/students.php',
    'teacher/assignments.php',
    'teacher/assignment_submissions.php',
    'teacher/grades.php',
    'teacher/announcements.php',
    'teacher/messages.php',
    'teacher/course_content.php',
    
    // Student pages
    'student/dashboard.php',
    'student/courses.php',
    'student/assignments.php',
    'student/grades.php',
    'student/certificates.php',
    'student/announcements.php',
    'student/messages.php',
    'student/course_content.php'
];

foreach ($pages_to_update as $page) {
    if (file_exists($page)) {
        $content = file_get_contents($page);
        
        // Check if footer is already included
        if (strpos($content, 'require_once') !== false && strpos($content, 'footer.php') !== false) {
            echo "✓ Footer already exists in $page\n";
            continue;
        }
        
        // Check if page ends with </body></html>
        if (preg_match('/<\/body>\s*<\/html>\s*$/i', $content)) {
            // Replace </body></html> with footer include
            $new_content = preg_replace('/<\/body>\s*<\/html>\s*$/i', "\n\n<?php require_once '../includes/footer.php'; ?>", $content);
            
            if (file_put_contents($page, $new_content)) {
                echo "✓ Added footer to $page\n";
            } else {
                echo "✗ Failed to update $page\n";
            }
        } else {
            echo "⚠ Could not find </body></html> in $page\n";
        }
    } else {
        echo "✗ File not found: $page\n";
    }
}

echo "\nFooter addition script completed!\n";
?> 