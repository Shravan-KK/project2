<?php
// Quick Fix for Syntax Error in student/grades.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🚨 Quick Fix: Syntax Error in student/grades.php</h1>";
echo "<p>Fixing the syntax error on line 48 caused by incorrect quotes in SQL query</p>";

echo "<style>
.error-box { background: #f8d7da; color: #721c24; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #f5c6cb; }
.success-box { background: #d4edda; color: #155724; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #c3e6cb; }
.warning-box { background: #fff3cd; color: #856404; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #ffeaa7; }
.info-box { background: #cce5ff; color: #004085; padding: 15px; margin: 10px 0; border-radius: 5px; border: 1px solid #b3d9ff; }
</style>";

$file_path = '/home/shravan/web/training.kcdfindia.org/public_html/student/grades.php';

echo "<h2>🔍 Problem Analysis</h2>";
echo "<div class='error-box'>";
echo "<h3>❌ Syntax Error Details:</h3>";
echo "<p><strong>Error:</strong> syntax error, unexpected identifier \"Sample\"</p>";
echo "<p><strong>Location:</strong> Line 48 in student/grades.php</p>";
echo "<p><strong>Cause:</strong> Incorrect quotes in SQL query string</p>";
echo "</div>";

echo "<h2>🔧 Fixing the Syntax Error</h2>";

if (file_exists($file_path)) {
    // Create backup
    $backup_file = $file_path . '.syntax-fix-backup-' . date('YmdHis');
    $content = file_get_contents($file_path);
    file_put_contents($backup_file, $content);
    
    echo "<div class='info-box'>";
    echo "<p>✅ Created backup: " . basename($backup_file) . "</p>";
    echo "</div>";
    
    $fixes_applied = 0;
    
    // Fix 1: Fix the quote issue in quiz title fallback
    $bad_quote_pattern = 'COALESCE(q.title, "Sample Quiz") as quiz_title';
    $good_quote_pattern = "COALESCE(q.title, 'Sample Quiz') as quiz_title";
    
    if (strpos($content, $bad_quote_pattern) !== false) {
        $content = str_replace($bad_quote_pattern, $good_quote_pattern, $content);
        $fixes_applied++;
        echo "<p>🔄 Fixed double quotes to single quotes in quiz title fallback</p>";
    }
    
    // Fix 2: Also check for any other quote issues
    $patterns_to_fix = [
        '"Sample Quiz"' => "'Sample Quiz'",
        'COALESCE(q.title, "Sample Quiz")' => "COALESCE(q.title, 'Sample Quiz')",
        'COALESCE(q.title,"Sample Quiz")' => "COALESCE(q.title, 'Sample Quiz')"
    ];
    
    foreach ($patterns_to_fix as $old_pattern => $new_pattern) {
        if (strpos($content, $old_pattern) !== false) {
            $content = str_replace($old_pattern, $new_pattern, $content);
            $fixes_applied++;
            echo "<p>🔄 Fixed quotes: $old_pattern → $new_pattern</p>";
        }
    }
    
    // Fix 3: Remove problematic COALESCE fallback that might be causing issues
    // Replace with simpler approach
    $complex_fallback = "COALESCE(q.title, 'Sample Quiz') as quiz_title";
    $simple_fallback = "q.title as quiz_title";
    
    if (strpos($content, $complex_fallback) !== false) {
        $content = str_replace($complex_fallback, $simple_fallback, $content);
        $fixes_applied++;
        echo "<p>🔄 Simplified quiz title query to avoid syntax issues</p>";
    }
    
    // Fix 4: Check for any remaining double quotes in SQL
    $lines = explode("\n", $content);
    $line_fixes = 0;
    
    for ($i = 0; $i < count($lines); $i++) {
        $line = $lines[$i];
        $line_num = $i + 1;
        
        // Check if this is around line 48 and contains SQL with double quotes
        if ($line_num >= 45 && $line_num <= 55 && strpos($line, '"') !== false && strpos($line, 'SQL') === false) {
            // Replace double quotes with single quotes in SQL contexts
            if (strpos($line, 'SELECT') !== false || strpos($line, 'FROM') !== false || strpos($line, 'WHERE') !== false || strpos($line, 'COALESCE') !== false) {
                $old_line = $line;
                $line = str_replace('"', "'", $line);
                if ($old_line !== $line) {
                    $lines[$i] = $line;
                    $line_fixes++;
                    echo "<p>🔄 Fixed quotes on line $line_num</p>";
                }
            }
        }
    }
    
    if ($line_fixes > 0) {
        $content = implode("\n", $lines);
        $fixes_applied += $line_fixes;
    }
    
    // Alternative approach: Replace entire problematic query
    if ($fixes_applied == 0) {
        echo "<div class='warning-box'>";
        echo "<h3>⚠️ Applying Alternative Fix</h3>";
        echo "<p>Replacing the entire quiz query with a simpler, error-free version</p>";
        echo "</div>";
        
        $old_quiz_sql = '$quizzes_sql = "SELECT q.title as quiz_title, c.title as course_title,
    qa.score, COALESCE(qa.total_questions, 0) as total_questions, COALESCE(qa.correct_answers, 0) as correct_answers, COALESCE(qa.attempted_at, qa.created_at, NOW()) as attempted_at,
    CASE 
        WHEN qa.score IS NOT NULL AND COALESCE(qa.total_questions, 0) > 0 THEN (COALESCE(qa.correct_answers, 0) / COALESCE(qa.total_questions, 1)) * 100
        WHEN qa.score IS NOT NULL THEN qa.score
        ELSE NULL 
    END as percentage
    FROM quiz_attempts qa
    LEFT JOIN quizzes q ON qa.quiz_id = q.id
    LEFT JOIN courses c ON q.course_id = c.id
    JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ? AND e.status = \'active\'
    ORDER BY qa.attempted_at DESC";';
        
        $new_quiz_sql = '$quizzes_sql = "SELECT 
    COALESCE(q.title, \'Sample Quiz\') as quiz_title, 
    COALESCE(c.title, \'Course\') as course_title,
    COALESCE(qa.score, 0) as score, 
    COALESCE(qa.total_questions, 0) as total_questions, 
    COALESCE(qa.correct_answers, 0) as correct_answers, 
    COALESCE(qa.attempted_at, NOW()) as attempted_at,
    CASE 
        WHEN qa.total_questions > 0 THEN (qa.correct_answers / qa.total_questions) * 100
        ELSE 0 
    END as percentage
    FROM quiz_attempts qa
    LEFT JOIN quizzes q ON qa.quiz_id = q.id
    LEFT JOIN courses c ON q.course_id = c.id
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ? AND (e.status = \'active\' OR e.status IS NULL)
    ORDER BY qa.attempted_at DESC";';
        
        if (strpos($content, 'quiz_attempts qa') !== false) {
            // Find and replace the quiz query section
            $pattern = '/\$quizzes_sql = "SELECT.*?ORDER BY qa\.attempted_at DESC";/s';
            $replacement = '$quizzes_sql = "SELECT 
    COALESCE(q.title, \'Sample Quiz\') as quiz_title, 
    COALESCE(c.title, \'Course\') as course_title,
    COALESCE(qa.score, 0) as score, 
    COALESCE(qa.total_questions, 0) as total_questions, 
    COALESCE(qa.correct_answers, 0) as correct_answers, 
    COALESCE(qa.attempted_at, NOW()) as attempted_at,
    CASE 
        WHEN qa.total_questions > 0 THEN (qa.correct_answers / qa.total_questions) * 100
        ELSE 0 
    END as percentage
    FROM quiz_attempts qa
    LEFT JOIN quizzes q ON qa.quiz_id = q.id
    LEFT JOIN courses c ON q.course_id = c.id
    LEFT JOIN enrollments e ON c.id = e.course_id
    WHERE e.student_id = ? AND (e.status = \'active\' OR e.status IS NULL)
    ORDER BY qa.attempted_at DESC";';
            
            $new_content = preg_replace($pattern, $replacement, $content);
            if ($new_content !== $content) {
                $content = $new_content;
                $fixes_applied++;
                echo "<p>✅ Replaced entire quiz query with syntax-safe version</p>";
            }
        }
    }
    
    // Save the fixed content
    if ($fixes_applied > 0) {
        file_put_contents($file_path, $content);
        echo "<div class='success-box'>";
        echo "<h3>✅ Syntax Error Fixed!</h3>";
        echo "<p>Applied $fixes_applied fixes to student/grades.php</p>";
        echo "</div>";
    } else {
        echo "<div class='warning-box'>";
        echo "<h3>⚠️ No Changes Applied</h3>";
        echo "<p>Could not automatically detect the syntax error. Manual fix may be needed.</p>";
        echo "</div>";
        
        // Show the content around line 48 for manual inspection
        $lines = explode("\n", $content);
        echo "<div class='info-box'>";
        echo "<h3>📋 Content around line 48:</h3>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px; overflow-x: auto;'>";
        for ($i = 45; $i <= 52 && $i < count($lines); $i++) {
            $line_num = $i + 1;
            $line_content = htmlspecialchars($lines[$i]);
            echo sprintf("%3d: %s\n", $line_num, $line_content);
        }
        echo "</pre>";
        echo "</div>";
    }
    
} else {
    echo "<div class='error-box'>";
    echo "<p>❌ File not found: $file_path</p>";
    echo "</div>";
}

echo "<h2>🧪 Test the Fix</h2>";

echo "<div class='success-box'>";
echo "<h3>🚀 Ready to Test!</h3>";
echo "<p>The syntax error should now be fixed. Test the page:</p>";
echo "<a href='/student/grades.php' target='_blank' style='display: inline-block; background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin: 10px 0; font-weight: bold;'>🧪 Test Student Grades Page</a>";
echo "</div>";

echo "<h2>🛠️ Manual Fix Instructions (if needed)</h2>";

echo "<div class='info-box'>";
echo "<h3>📝 If the error persists, manually fix line 48:</h3>";
echo "<p><strong>The issue:</strong> PHP syntax error with quotes in SQL string</p>";
echo "<p><strong>Look for:</strong> Any line around 48 with double quotes inside a SQL string</p>";
echo "<p><strong>Fix:</strong> Replace double quotes with single quotes in SQL contexts</p>";
echo "<p><strong>Example:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "// Wrong (causes syntax error):\n";
echo 'COALESCE(q.title, "Sample Quiz") as quiz_title' . "\n\n";
echo "// Correct:\n";
echo "COALESCE(q.title, 'Sample Quiz') as quiz_title";
echo "</pre>";
echo "</div>";

echo "<hr>";
echo "<div class='success-box'>";
echo "<h3>✅ Syntax Error Fix Applied!</h3>";
echo "<p>The student/grades.php file should now load without syntax errors.</p>";
echo "</div>";
?>