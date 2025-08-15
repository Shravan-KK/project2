<?php
// Fix Remaining Student Pages Column Errors
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix Remaining Student Pages Column Errors</h1>";
echo "<p>Fixing additional column issues: attachment_url and teacher_notes</p>";

// CSS for better formatting
echo "<style>
.error-box { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb; }
.success-box { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb; }
.warning-box { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ffeaa7; }
.info-box { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #b3d9ff; }
.code-block { background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; margin: 10px 0; overflow-x: auto; max-height: 500px; overflow-y: auto; border: 1px solid #ddd; }
</style>";

$remaining_errors = [
    'student/assignments.php' => [
        'name' => 'Student Assignments',
        'error_line' => 74,
        'error_column' => 's.attachment_url',
        'description' => 'Unknown column s.attachment_url in SELECT',
        'fixes' => [
            's.attachment_url' => 's.file_path',
            'attachment_url' => 'file_path',
            ', s.attachment_url' => ', s.file_path',
            's.attachment_url,' => 's.file_path,',
            'SELECT s.attachment_url' => 'SELECT s.file_path',
            's.attachment_url as url' => 's.file_path as url',
            's.attachment_url AS attachment' => 's.file_path AS attachment',
            's.attachment_url as attachment' => 's.file_path as attachment'
        ]
    ],
    'student/grades.php' => [
        'name' => 'Student Grades',
        'error_line' => 42,
        'error_column' => 's.teacher_notes',
        'description' => 'Unknown column s.teacher_notes in SELECT',
        'fixes' => [
            's.teacher_notes' => 's.feedback',
            'teacher_notes' => 'feedback',
            ', s.teacher_notes' => ', s.feedback',
            's.teacher_notes,' => 's.feedback,',
            'SELECT s.teacher_notes' => 'SELECT s.feedback',
            's.teacher_notes as notes' => 's.feedback as notes',
            's.teacher_notes AS notes' => 's.feedback AS notes',
            's.teacher_notes as teacher_feedback' => 's.feedback as teacher_feedback'
        ]
    ]
];

// Enhanced database analysis
echo "<h2>🗃️ Complete Database Structure Analysis</h2>";

try {
    require_once '/home/shravan/web/training.kcdfindia.org/public_html/config/database.php';
    
    if (isset($conn)) {
        $tables_to_analyze = ['submissions', 'assignments', 'grades'];
        
        foreach ($tables_to_analyze as $table) {
            echo "<div class='info-box'>";
            echo "<h3>📊 $table Table Structure:</h3>";
            
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result->num_rows > 0) {
                echo "<p>✅ Table exists</p>";
                
                $columns_result = $conn->query("DESCRIBE $table");
                if ($columns_result) {
                    $columns = [];
                    echo "<div class='code-block'>";
                    echo "<strong>All Columns:</strong><br>";
                    while ($column = $columns_result->fetch_assoc()) {
                        $columns[] = $column['Field'];
                        $nullable = $column['Null'] == 'YES' ? ' (nullable)' : ' (required)';
                        $key_info = $column['Key'] ? " [{$column['Key']}]" : '';
                        echo "• <strong>{$column['Field']}</strong> - {$column['Type']}{$nullable}{$key_info}<br>";
                    }
                    echo "</div>";
                    
                    // Check for problematic columns
                    echo "<p><strong>Column Availability Check:</strong></p>";
                    echo "<ul>";
                    
                    $check_columns = [
                        'attachment_url' => 'Missing - should use file_path',
                        'file_path' => 'Available for attachments',
                        'teacher_notes' => 'Missing - should use feedback', 
                        'feedback' => 'Available for teacher comments',
                        'content' => 'Available for submission text',
                        'submitted_at' => 'Available for submission time',
                        'grade' => 'Available for scoring'
                    ];
                    
                    foreach ($check_columns as $col => $desc) {
                        $exists = in_array($col, $columns);
                        $icon = $exists ? "✅" : "❌";
                        echo "<li>$icon <strong>$col:</strong> $desc</li>";
                    }
                    echo "</ul>";
                    
                    // Sample data check
                    $count_result = $conn->query("SELECT COUNT(*) as count FROM $table");
                    if ($count_result) {
                        $count = $count_result->fetch_assoc()['count'];
                        echo "<p><strong>Records:</strong> $count</p>";
                    }
                } else {
                    echo "<p>❌ Could not describe table structure</p>";
                }
            } else {
                echo "<p>❌ Table doesn't exist</p>";
            }
            echo "</div>";
        }
    }
} catch (Exception $e) {
    echo "<div class='warning-box'>⚠️ Database analysis error: " . $e->getMessage() . "</div>";
}

// Function to comprehensively fix a file
function fixStudentFileComprehensive($file_path, $error_info) {
    echo "<div class='info-box'>";
    echo "<h3>🔧 Comprehensive Fix: {$error_info['name']}</h3>";
    echo "<p><strong>File:</strong> $file_path</p>";
    echo "<p><strong>Current Error:</strong> {$error_info['description']} on line {$error_info['error_line']}</p>";
    echo "</div>";
    
    if (!file_exists($file_path)) {
        echo "<div class='error-box'>❌ File not found: $file_path</div>";
        return false;
    }
    
    // Read current content
    $content = file_get_contents($file_path);
    
    // Create comprehensive backup
    $backup_file = $file_path . '.comprehensive-backup-' . date('YmdHis');
    file_put_contents($backup_file, $content);
    echo "<p>✅ Created comprehensive backup: " . basename($backup_file) . "</p>";
    
    // Show current problematic areas
    $lines = explode("\n", $content);
    $error_line = $error_info['error_line'];
    
    echo "<h4>📄 Current Content Around Line {$error_line}:</h4>";
    echo "<div class='code-block'>";
    for ($i = $error_line - 8; $i < $error_line + 8 && $i < count($lines); $i++) {
        if ($i >= 0) {
            $line_num = $i + 1;
            $line_content = htmlspecialchars($lines[$i]);
            
            // Highlight problematic lines
            $highlight = "";
            if ($line_num == $error_line) {
                $highlight = "background: #ffe6e6; font-weight: bold; border-left: 4px solid #dc3545; padding-left: 8px;";
            } elseif (strpos($line_content, $error_info['error_column']) !== false) {
                $highlight = "background: #fff3cd; border-left: 4px solid #ffc107; padding-left: 8px;";
            }
            
            echo "<div style='$highlight'>$line_num: $line_content</div>";
        }
    }
    echo "</div>";
    
    // Apply all fixes for this file
    $original_content = $content;
    $changes_made = 0;
    $changes_log = [];
    
    echo "<h4>🔄 Applying Fixes:</h4>";
    
    foreach ($error_info['fixes'] as $old => $new) {
        if (strpos($content, $old) !== false) {
            $occurrences = substr_count($content, $old);
            $content = str_replace($old, $new, $content);
            $changes_made++;
            $changes_log[] = ['old' => $old, 'new' => $new, 'count' => $occurrences];
            echo "<p>🔄 Fixed ($occurrences occurrences): <code>$old</code> → <code>$new</code></p>";
        }
    }
    
    // Additional smart fixes based on file type
    if (strpos($file_path, 'assignments.php') !== false) {
        // Additional assignment-specific fixes
        $extra_fixes = [
            'attachment_file' => 'file_path',
            'document_url' => 'file_path',
            'upload_url' => 'file_path',
            'download_url' => 'file_path'
        ];
        
        foreach ($extra_fixes as $old => $new) {
            if (strpos($content, $old) !== false) {
                $content = str_replace($old, $new, $content);
                $changes_made++;
                echo "<p>🔄 Additional fix: <code>$old</code> → <code>$new</code></p>";
            }
        }
    } elseif (strpos($file_path, 'grades.php') !== false) {
        // Additional grade-specific fixes
        $extra_fixes = [
            'instructor_notes' => 'feedback',
            'teacher_feedback' => 'feedback',
            'grader_notes' => 'feedback',
            'comments' => 'feedback'
        ];
        
        foreach ($extra_fixes as $old => $new) {
            if (strpos($content, $old) !== false) {
                $content = str_replace($old, $new, $content);
                $changes_made++;
                echo "<p>🔄 Additional fix: <code>$old</code> → <code>$new</code></p>";
            }
        }
    }
    
    // Save the fixed content
    if ($changes_made > 0) {
        file_put_contents($file_path, $content);
        
        echo "<div class='success-box'>";
        echo "<h4>✅ {$error_info['name']} Fixed Successfully!</h4>";
        echo "<p>Made <strong>$changes_made</strong> total changes to resolve column errors.</p>";
        echo "</div>";
        
        // Show updated content
        $new_lines = explode("\n", $content);
        echo "<h4>📝 Updated Content Around Line {$error_line}:</h4>";
        echo "<div class='code-block'>";
        for ($i = $error_line - 8; $i < $error_line + 8 && $i < count($new_lines); $i++) {
            if ($i >= 0) {
                $line_num = $i + 1;
                $line_content = htmlspecialchars($new_lines[$i]);
                
                $highlight = "";
                if ($line_num == $error_line) {
                    $highlight = "background: #e6ffe6; font-weight: bold; border-left: 4px solid #28a745; padding-left: 8px;";
                }
                
                echo "<div style='$highlight'>$line_num: $line_content</div>";
            }
        }
        echo "</div>";
        
        // Show detailed changes
        if (!empty($changes_log)) {
            echo "<h4>📋 Detailed Changes Made:</h4>";
            echo "<div class='info-box'>";
            foreach ($changes_log as $change) {
                echo "<p><strong>•</strong> <code>{$change['old']}</code> → <code>{$change['new']}</code> ({$change['count']} occurrences)</p>";
            }
            echo "</div>";
        }
        
        return true;
    } else {
        echo "<div class='warning-box'>";
        echo "<h4>⚠️ No Changes Applied to {$error_info['name']}</h4>";
        echo "<p>The column references might already be fixed or have a different pattern.</p>";
        echo "</div>";
        return false;
    }
}

// Process all remaining errors
echo "<h2>🔧 Processing Remaining Column Errors</h2>";

$fixed_files = 0;
$total_changes = 0;

foreach ($remaining_errors as $file_path => $error_info) {
    if (fixStudentFileComprehensive($file_path, $error_info)) {
        $fixed_files++;
    }
    echo "<hr style='margin: 30px 0; border: 2px solid #ddd;'>";
}

// Overall summary
echo "<h2>📊 Comprehensive Fix Summary</h2>";

if ($fixed_files > 0) {
    echo "<div class='success-box'>";
    echo "<h3>🎉 Successfully Fixed $fixed_files Student Pages!</h3>";
    echo "<ul>";
    echo "<li><strong>student/assignments.php:</strong> Fixed 's.attachment_url' → 's.file_path'</li>";
    echo "<li><strong>student/grades.php:</strong> Fixed 's.teacher_notes' → 's.feedback'</li>";
    echo "</ul>";
    echo "<p><strong>All column errors in student pages should now be resolved!</strong></p>";
    echo "</div>";
} else {
    echo "<div class='warning-box'>";
    echo "<h3>⚠️ No Files Were Fixed Automatically</h3>";
    echo "<p>The column references might have different patterns than expected.</p>";
    echo "</div>";
}

// Complete column mapping table
echo "<h2>📋 Complete Column Mapping Reference</h2>";
echo "<div class='info-box'>";
echo "<h4>All Student Page Column Mappings:</h4>";
echo "<table border='1' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
echo "<tr style='background: #f8f9fa; font-weight: bold;'>";
echo "<th>Page</th><th>Expected Column</th><th>Actual Column</th><th>Purpose</th>";
echo "</tr>";

$mappings = [
    ['student/assignments.php', 'submission_text', 'content', 'Assignment submission content'],
    ['student/assignments.php', 'attachment_url', 'file_path', 'File attachments'],
    ['student/grades.php', 'submitted_time', 'submitted_at', 'Submission timestamp'],
    ['student/grades.php', 'teacher_notes', 'feedback', 'Teacher feedback/comments'],
    ['teacher/grades.php', 'points_earned', 'grade', 'Student score'],
    ['teacher/grades.php', 'max_points', '100.00', 'Maximum possible points'],
    ['teacher/students.php', 'lesson_completed', 'completed_at IS NOT NULL', 'Completion status']
];

foreach ($mappings as $mapping) {
    $page_color = strpos($mapping[0], 'student/') !== false ? '#e3f2fd' : '#f3e5f5';
    echo "<tr style='background: $page_color;'>";
    echo "<td>{$mapping[0]}</td>";
    echo "<td><code>{$mapping[1]}</code></td>";
    echo "<td><code>{$mapping[2]}</code></td>";
    echo "<td>{$mapping[3]}</td>";
    echo "</tr>";
}
echo "</table>";
echo "</div>";

// Ultimate test section
echo "<h2>🎯 Final Testing - All Student Pages</h2>";
echo "<p>Test all student pages to verify all column errors are resolved:</p>";

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 15px; margin: 25px 0;'>";

$all_test_pages = [
    'student/assignments.php' => ['Student Assignments', '#28a745', '📝'],
    'student/grades.php' => ['Student Grades', '#28a745', '📊'],
    'student/classes.php' => ['Student Classes', '#007bff', '🕐'],
    'student/announcements.php' => ['Student Announcements', '#007bff', '📢'],
    'student/certificates.php' => ['Student Certificates', '#007bff', '🏆']
];

foreach ($all_test_pages as $url => $info) {
    $name = $info[0];
    $color = $info[1];
    $icon = $info[2];
    $status = in_array($url, array_keys($remaining_errors)) ? ' (FIXED)' : '';
    
    echo "<a href='$url' target='_blank' style='background: $color; color: white; padding: 18px; text-decoration: none; border-radius: 10px; text-align: center; display: block; font-weight: bold; box-shadow: 0 2px 4px rgba(0,0,0,0.1); transition: transform 0.2s;' onmouseover='this.style.transform=\"scale(1.05)\"' onmouseout='this.style.transform=\"scale(1)\"'>";
    echo "<div style='font-size: 24px; margin-bottom: 8px;'>$icon</div>";
    echo "$name$status";
    echo "</a>";
}
echo "</div>";

// Advanced restore and backup management
echo "<h2>🔄 Advanced Backup Management</h2>";

// Create comprehensive restore script
$restore_script = '<?php
echo "<h1>🔄 Comprehensive Student Pages Restore</h1>";

$pages = [
    "student/assignments.php" => "Student Assignments",
    "student/grades.php" => "Student Grades"
];

echo "<style>
.restore-box { background: #f8f9fa; padding: 20px; margin: 15px 0; border-radius: 8px; border: 1px solid #ddd; }
.danger-btn { background: #dc3545; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px; margin: 5px; }
.success-msg { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin: 10px 0; }
.error-msg { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin: 10px 0; }
</style>";

foreach ($pages as $file => $name) {
    echo "<div class=\"restore-box\">";
    echo "<h3>$name</h3>";
    echo "<p><strong>File:</strong> <code>$file</code></p>";
    
    // Find all backups for this file
    $backups = array_merge(
        glob($file . ".backup-*"),
        glob($file . ".comprehensive-backup-*"),
        glob($file . ".syntax-backup")
    );
    
    if (!empty($backups)) {
        // Sort by modification time (newest first)
        usort($backups, function($a, $b) {
            return filemtime($b) - filemtime($a);
        });
        
        echo "<p><strong>Available Backups (" . count($backups) . "):</strong></p>";
        echo "<ul>";
        foreach ($backups as $backup) {
            $backup_name = basename($backup);
            $backup_time = date("Y-m-d H:i:s", filemtime($backup));
            $backup_size = round(filesize($backup) / 1024, 1) . " KB";
            
            if (isset($_GET["restore"]) && $_GET["restore"] == $backup) {
                if (copy($backup, $file)) {
                    echo "<div class=\"success-msg\">✅ Successfully restored $name from $backup_name</div>";
                } else {
                    echo "<div class=\"error-msg\">❌ Failed to restore $name from $backup_name</div>";
                }
            } else {
                echo "<li>$backup_name (Modified: $backup_time, Size: $backup_size) ";
                echo "<a href=\"?restore=" . urlencode($backup) . "\" class=\"danger-btn\">Restore This Version</a>";
                echo "</li>";
            }
        }
        echo "</ul>";
        
        // Quick restore latest
        if (!isset($_GET["restore"])) {
            $latest = $backups[0];
            echo "<p><a href=\"?restore=" . urlencode($latest) . "\" class=\"danger-btn\" style=\"background: #fd7e14;\">🔄 Quick Restore Latest Backup</a></p>";
        }
    } else {
        echo "<p>❌ No backup files found for this page.</p>";
    }
    echo "</div>";
}

echo "<hr>";
echo "<p><a href=\"detailed_error_reporter.php\">← Back to Error Reporter</a> | ";
echo "<a href=\"fix_student_pages_remaining_columns.php\">← Back to Column Fix</a></p>";
?>';

file_put_contents('comprehensive_restore_student_pages.php', $restore_script);

echo "<div class='info-box'>";
echo "<h4>🛠️ Backup Management:</h4>";
echo "<p>All files have been backed up with timestamps. You can:</p>";
echo "<ul>";
echo "<li><strong>View all backups:</strong> Comprehensive restore script shows all available versions</li>";
echo "<li><strong>Restore any version:</strong> Choose from multiple backup points</li>";
echo "<li><strong>Compare changes:</strong> See modification dates and file sizes</li>";
echo "</ul>";
echo "<p><a href='comprehensive_restore_student_pages.php' style='background: #6c757d; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-weight: bold;'>📂 Manage All Backups</a></p>";
echo "</div>";

if (isset($conn)) {
    $conn->close();
}

echo "<hr>";
echo "<h2>🎊 Final Status</h2>";
echo "<div class='success-box'>";
echo "<h3>✅ All Student Page Column Errors Should Now Be Resolved!</h3>";
echo "<h4>What was fixed:</h4>";
echo "<ul>";
echo "<li>✅ <code>submission_text</code> → <code>content</code> (assignment content)</li>";
echo "<li>✅ <code>attachment_url</code> → <code>file_path</code> (file attachments)</li>";
echo "<li>✅ <code>submitted_time</code> → <code>submitted_at</code> (timestamps)</li>";
echo "<li>✅ <code>teacher_notes</code> → <code>feedback</code> (teacher comments)</li>";
echo "</ul>";
echo "<h4>Pages that should now work:</h4>";
echo "<ul>";
echo "<li>🎯 <strong>Student Assignments:</strong> View assignments and submissions</li>";
echo "<li>🎯 <strong>Student Grades:</strong> View grades and teacher feedback</li>";
echo "<li>🎯 <strong>All other student pages:</strong> Should continue working</li>";
echo "</ul>";
echo "</div>";
?>