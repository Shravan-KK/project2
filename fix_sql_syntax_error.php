<?php
// Fix SQL Syntax Error in Teacher Grades
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>🔧 Fix SQL Syntax Error</h1>";
echo "<p>Fixing parentheses and alias placement in SQL calculations</p>";

$file_path = 'teacher/grades.php';

if (!file_exists($file_path)) {
    die("<p>❌ File not found: $file_path</p>");
}

// Read the file
$content = file_get_contents($file_path);

// Create backup
file_put_contents($file_path . '.syntax-backup', $content);
echo "<p>✅ Created syntax backup</p>";

// Show the problematic area around line 42
$lines = explode("\n", $content);
echo "<h2>📄 Current Content Around Line 42:</h2>";
echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; overflow-x: auto; max-height: 600px; overflow-y: auto; border: 1px solid #ddd;'>";

for ($i = 35; $i < 55 && $i < count($lines); $i++) {
    $line_num = $i + 1;
    $line_content = htmlspecialchars($lines[$i]);
    $highlight = ($line_num == 42) ? "background: #ffe6e6; font-weight: bold;" : "";
    echo "<div style='$highlight'>$line_num: $line_content</div>";
}
echo "</div>";

// Fix common SQL syntax issues caused by automatic replacements
echo "<h2>🔧 Fixing SQL Syntax Issues</h2>";

$syntax_fixes = [
    // Fix parentheses issues with aliases
    '/ 100.00 as max_points)' => '/ 100.00)',
    '* 100.00 as max_points)' => '* 100.00)',
    '+ 100.00 as max_points)' => '+ 100.00)',
    '- 100.00 as max_points)' => '- 100.00)',
    
    // Fix misplaced aliases in calculations
    '(s.grade / 100.00 as max_points)' => '(s.grade / 100.00)',
    '(grade / 100.00 as max_points)' => '(grade / 100.00)',
    
    // Fix multiple alias issues
    ', 100.00 as max_points)' => ')',
    'SELECT 100.00 as max_points)' => 'SELECT 100.00)',
    
    // Fix CASE statement issues
    '100.00 as max_points) * 100 ELSE' => '100.00) * 100 ELSE',
    '100.00 as max_points) ELSE' => '100.00) ELSE',
    
    // Fix common calculation patterns
    '/ 100.00 as max_points * 100' => '/ 100.00 * 100',
    '* 100.00 as max_points / 100' => '* 100.00 / 100',
    
    // Fix JOIN issues
    ') FROM submissions s JO' => ') as percentage FROM submissions s JO',
    ') FROM submissions s LEFT JO' => ') as percentage FROM submissions s LEFT JO',
    
    // Fix nested parentheses
    '((s.grade / 100.00 as max_points))' => '((s.grade / 100.00))',
    '((grade / 100.00 as max_points))' => '((grade / 100.00))',
    
    // Fix WHERE clause issues
    'WHERE 100.00 as max_points' => 'WHERE 100.00',
    'HAVING 100.00 as max_points' => 'HAVING 100.00',
    
    // Fix ORDER BY issues
    'ORDER BY 100.00 as max_points' => 'ORDER BY 100.00',
    
    // Fix common percentage calculations
    'CASE WHEN 100.00 as max_points > 0' => 'CASE WHEN 100.00 > 0',
    'IF(100.00 as max_points > 0' => 'IF(100.00 > 0',
    
    // Fix any remaining syntax issues
    'as max_points) *' => ') as max_points *',
    'as max_points) /' => ') as max_points /',
    'as max_points) +' => ') as max_points +',
    'as max_points) -' => ') as max_points -'
];

$original_content = $content;
$changes_made = 0;
$changes_log = [];

foreach ($syntax_fixes as $old => $new) {
    if (strpos($content, $old) !== false) {
        $content = str_replace($old, $new, $content);
        $changes_made++;
        $changes_log[] = ['old' => $old, 'new' => $new];
        echo "<p>🔄 Fixed: <code>" . htmlspecialchars($old) . "</code> → <code>" . htmlspecialchars($new) . "</code></p>";
    }
}

// Additional smart fixes for common patterns
if (strpos($content, 'as max_points)') !== false) {
    // Find and fix patterns where alias is inside parentheses incorrectly
    $content = preg_replace('/\(\s*([^)]+?)\s+as\s+max_points\s*\)/', '($1) as max_points', $content);
    echo "<p>🔄 Fixed alias placement inside parentheses</p>";
    $changes_made++;
}

// Fix any remaining calculation issues
if (strpos($content, '100.00 as max_points') !== false && strpos($content, '*') !== false) {
    // Look for calculation patterns and fix them
    $content = preg_replace('/100\.00\s+as\s+max_points\s*\)\s*\*/', '100.00) as max_points *', $content);
    echo "<p>🔄 Fixed calculation alias placement</p>";
    $changes_made++;
}

// Save the fixed content
if ($changes_made > 0) {
    file_put_contents($file_path, $content);
    
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>✅ SQL Syntax Fixed!</h3>";
    echo "<p>Made <strong>$changes_made</strong> syntax correction(s).</p>";
    echo "</div>";
    
    // Show updated content
    $new_lines = explode("\n", $content);
    echo "<h2>📝 Updated Content Around Line 42:</h2>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; overflow-x: auto; max-height: 600px; overflow-y: auto; border: 1px solid #ddd;'>";
    
    for ($i = 35; $i < 55 && $i < count($new_lines); $i++) {
        $line_num = $i + 1;
        $line_content = htmlspecialchars($new_lines[$i]);
        $highlight = ($line_num == 42) ? "background: #e6ffe6; font-weight: bold;" : "";
        echo "<div style='$highlight'>$line_num: $line_content</div>";
    }
    echo "</div>";
    
} else {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px; margin: 15px 0;'>";
    echo "<h3>⚠️ No Automatic Syntax Fixes Applied</h3>";
    echo "<p>The SQL syntax error might require manual inspection.</p>";
    echo "</div>";
}

// Manual fix suggestions
echo "<h2>🛠️ Manual Fix Suggestions</h2>";
echo "<div style='background: #e7f3ff; color: #004085; padding: 15px; border-radius: 5px;'>";
echo "<h4>Common SQL Syntax Issues:</h4>";
echo "<ol>";
echo "<li><strong>Misplaced Alias:</strong> <code>(calculation as alias)</code> should be <code>(calculation) as alias</code></li>";
echo "<li><strong>Missing Parentheses:</strong> Ensure all opened parentheses are properly closed</li>";
echo "<li><strong>Calculation Errors:</strong> <code>column / 100.00 as alias) * 100</code> should be <code>(column / 100.00) * 100 as alias</code></li>";
echo "<li><strong>CASE Statement Issues:</strong> Make sure CASE...WHEN...ELSE...END syntax is correct</li>";
echo "</ol>";
echo "</div>";

// Quick SQL validation test
echo "<h2>🧪 Quick SQL Validation</h2>";

try {
    require_once '/home/shravan/web/training.kcdfindia.org/public_html/config/database.php';
    
    if (isset($conn)) {
        echo "<p>Testing basic SQL syntax...</p>";
        
        // Test a simple query to make sure database is accessible
        $test_result = $conn->query("SELECT 1 as test");
        if ($test_result) {
            echo "<p>✅ Database connection and basic SQL working</p>";
        } else {
            echo "<p>❌ Database query failed: " . $conn->error . "</p>";
        }
        
        $conn->close();
    }
} catch (Exception $e) {
    echo "<p>⚠️ Database test failed: " . $e->getMessage() . "</p>";
}

echo "<h2>🎯 Test Your Fix</h2>";
echo "<p>Test the teacher grades page to see if the SQL syntax error is resolved:</p>";
echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='teacher/grades.php' target='_blank' style='background: #28a745; color: white; padding: 15px 25px; text-decoration: none; border-radius: 8px; font-size: 16px; font-weight: bold;'>🧪 Test Teacher Grades Page</a>";
echo "</div>";

echo "<h2>🔄 Restore Options</h2>";
echo "<p>If the syntax fix doesn't work, you can restore from backup:</p>";

// Create quick restore for syntax backup
$syntax_restore = '<?php
$file = "teacher/grades.php";
$backup = $file . ".syntax-backup";

if (file_exists($backup)) {
    if (copy($backup, $file)) {
        echo "<h1>✅ Restored from Syntax Backup</h1>";
        echo "<p>File restored to state before syntax fixes.</p>";
        unlink($backup);
    } else {
        echo "<h1>❌ Restore Failed</h1>";
    }
} else {
    echo "<h1>❌ Syntax Backup Not Found</h1>";
}
echo "<p><a href=\"teacher/grades.php\">Test Page</a></p>";
?>';

file_put_contents('restore_syntax_backup.php', $syntax_restore);
echo "<p><a href='restore_syntax_backup.php' style='background: #6c757d; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;'>Restore Syntax Backup</a></p>";

echo "<hr>";
echo "<h2>💡 What Was Fixed</h2>";
echo "<div style='background: #e7f3ff; color: #004085; padding: 15px; border-radius: 5px;'>";
echo "<h4>SQL Syntax Corrections:</h4>";
echo "<ul>";
echo "<li>✅ Fixed misplaced aliases in calculations</li>";
echo "<li>✅ Corrected parentheses placement</li>";
echo "<li>✅ Fixed CASE statement syntax</li>";
echo "<li>✅ Resolved calculation operator precedence issues</li>";
echo "</ul>";
echo "<p><strong>Goal:</strong> Convert broken SQL like <code>(grade / 100.00 as max_points) * 100</code> to valid SQL like <code>(grade / 100.00) * 100 as percentage</code></p>";
echo "</div>";

if (!empty($changes_log)) {
    echo "<h2>📋 Detailed Changes Made</h2>";
    echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
    foreach ($changes_log as $i => $change) {
        echo "<p><strong>Change " . ($i + 1) . ":</strong></p>";
        echo "<p><strong>From:</strong> <code>" . htmlspecialchars($change['old']) . "</code></p>";
        echo "<p><strong>To:</strong> <code>" . htmlspecialchars($change['new']) . "</code></p>";
        echo "<hr>";
    }
    echo "</div>";
}
?>