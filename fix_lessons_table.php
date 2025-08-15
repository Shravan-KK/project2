<?php
require_once 'config/database.php';

// Add missing fields to lessons table
$alter_queries = [
    "ALTER TABLE lessons ADD COLUMN IF NOT EXISTS description TEXT AFTER title",
    "ALTER TABLE lessons ADD COLUMN IF NOT EXISTS image_url VARCHAR(500) AFTER video_url",
    "ALTER TABLE lessons ADD COLUMN IF NOT EXISTS attachment_url VARCHAR(500) AFTER image_url",
    "ALTER TABLE lessons ADD COLUMN IF NOT EXISTS videos TEXT AFTER attachment_url",
    "ALTER TABLE lessons ADD COLUMN IF NOT EXISTS images TEXT AFTER videos"
];

echo "<h2>Fixing Lessons Table</h2>";

foreach ($alter_queries as $query) {
    if ($conn->query($query) === TRUE) {
        echo "✓ " . $query . "<br>";
    } else {
        echo "✗ Error: " . $conn->error . " for query: " . $query . "<br>";
    }
}

echo "<br><strong>Lessons table has been updated successfully!</strong><br>";
echo "<a href='admin/lessons.php'>Go back to Lessons page</a>";
?> 