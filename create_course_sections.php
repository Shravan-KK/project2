<?php
// Enable error display for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Creating Enhanced Course Structure</h2>";

// Database configuration
$host = 'localhost';
$dbname = 'teaching_management';
$username = 'root';
$password = 'root';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("❌ Connection failed: " . $conn->connect_error);
}

echo "<p>✅ Connected to database: $dbname</p>";

// Create course_sections table
$sections_sql = "CREATE TABLE IF NOT EXISTS course_sections (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    course_id INT(11) NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    order_number INT(11) DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    INDEX idx_course_order (course_id, order_number)
)";

if ($conn->query($sections_sql) === TRUE) {
    echo "<p>✅ Course sections table created successfully</p>";
} else {
    echo "<p>⚠️ Course sections table already exists or error: " . $conn->error . "</p>";
}

// Update lessons table to include section_id and enhanced content
$update_lessons_sql = "ALTER TABLE lessons 
                       ADD COLUMN IF NOT EXISTS section_id INT(11) AFTER course_id,
                       ADD COLUMN IF NOT EXISTS description TEXT AFTER title,
                       ADD COLUMN IF NOT EXISTS content_type ENUM('text', 'video', 'image', 'mixed') DEFAULT 'text' AFTER description,
                       ADD COLUMN IF NOT EXISTS estimated_duration INT(11) DEFAULT 0 AFTER duration,
                       ADD COLUMN IF NOT EXISTS is_published BOOLEAN DEFAULT TRUE AFTER estimated_duration,
                       ADD INDEX IF NOT EXISTS idx_section_order (section_id, order_number)";

if ($conn->query($update_lessons_sql) === TRUE) {
    echo "<p>✅ Lessons table updated successfully</p>";
} else {
    echo "<p>⚠️ Lessons table update: " . $conn->error . "</p>";
}

// Create lesson_videos table for multiple video support
$lesson_videos_sql = "CREATE TABLE IF NOT EXISTS lesson_videos (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT(11) NOT NULL,
    title VARCHAR(200) NOT NULL,
    video_url VARCHAR(500) NOT NULL,
    video_type ENUM('youtube', 'vimeo', 'mp4', 'webm', 'other') DEFAULT 'mp4',
    duration INT(11) DEFAULT 0,
    order_number INT(11) DEFAULT 0,
    is_primary BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_lesson_order (lesson_id, order_number)
)";

if ($conn->query($lesson_videos_sql) === TRUE) {
    echo "<p>✅ Lesson videos table created successfully</p>";
} else {
    echo "<p>⚠️ Lesson videos table already exists or error: " . $conn->error . "</p>";
}

// Create lesson_images table for multiple image support
$lesson_images_sql = "CREATE TABLE IF NOT EXISTS lesson_images (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT(11) NOT NULL,
    title VARCHAR(200),
    image_url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(255),
    order_number INT(11) DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_lesson_order (lesson_id, order_number)
)";

if ($conn->query($lesson_images_sql) === TRUE) {
    echo "<p>✅ Lesson images table created successfully</p>";
} else {
    echo "<p>⚠️ Lesson images table already exists or error: " . $conn->error . "</p>";
}

// Create lesson_resources table for additional files
$lesson_resources_sql = "CREATE TABLE IF NOT EXISTS lesson_resources (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    lesson_id INT(11) NOT NULL,
    title VARCHAR(200) NOT NULL,
    file_url VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT(11),
    description TEXT,
    order_number INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    INDEX idx_lesson_order (lesson_id, order_number)
)";

if ($conn->query($lesson_resources_sql) === TRUE) {
    echo "<p>✅ Lesson resources table created successfully</p>";
} else {
    echo "<p>⚠️ Lesson resources table already exists or error: " . $conn->error . "</p>";
}

// Create lesson_progress table for detailed progress tracking
$lesson_progress_sql = "CREATE TABLE IF NOT EXISTS lesson_progress (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    student_id INT(11) NOT NULL,
    lesson_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL,
    section_id INT(11) NOT NULL,
    progress_percentage INT(3) DEFAULT 0,
    time_spent INT(11) DEFAULT 0,
    is_completed BOOLEAN DEFAULT FALSE,
    completed_at TIMESTAMP NULL,
    last_accessed TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    FOREIGN KEY (section_id) REFERENCES course_sections(id) ON DELETE CASCADE,
    UNIQUE KEY unique_student_lesson (student_id, lesson_id),
    INDEX idx_student_course (student_id, course_id),
    INDEX idx_section_progress (section_id, student_id)
)";

if ($conn->query($lesson_progress_sql) === TRUE) {
    echo "<p>✅ Lesson progress table created successfully</p>";
} else {
    echo "<p>⚠️ Lesson progress table already exists or error: " . $conn->error . "</p>";
}

// Insert sample sections and lessons for existing courses
echo "<h3>📚 Creating Sample Course Structure</h3>";

// Get existing courses
$courses_sql = "SELECT id, title FROM courses LIMIT 3";
$courses_result = $conn->query($courses_sql);

if ($courses_result->num_rows > 0) {
    while ($course = $courses_result->fetch_assoc()) {
        echo "<p>📖 Processing course: <strong>" . htmlspecialchars($course['title']) . "</strong></p>";
        
        // Create sample sections
        $section_titles = ['Introduction', 'Core Concepts', 'Advanced Topics', 'Practice & Assessment'];
        
        foreach ($section_titles as $index => $title) {
            $section_sql = "INSERT IGNORE INTO course_sections (course_id, title, description, order_number) VALUES (?, ?, ?, ?)";
            $section_stmt = $conn->prepare($section_sql);
            $section_desc = "This section covers " . strtolower($title) . " for the course.";
            $section_stmt->bind_param("issi", $course['id'], $title, $section_desc, $index + 1);
            
            if ($section_stmt->execute()) {
                $section_id = $conn->insert_id;
                echo "<p>  ✅ Section created: $title</p>";
                
                // Create sample lessons for each section
                $lesson_titles = ['Overview', 'Key Points', 'Examples', 'Summary'];
                
                foreach ($lesson_titles as $lesson_index => $lesson_title) {
                    $lesson_sql = "INSERT IGNORE INTO lessons (course_id, section_id, title, description, content, order_number) VALUES (?, ?, ?, ?, ?, ?)";
                    $lesson_stmt = $conn->prepare($lesson_sql);
                    $lesson_desc = "Learn about " . strtolower($lesson_title) . " in this lesson.";
                    $lesson_content = "This lesson provides comprehensive coverage of " . strtolower($lesson_title) . " with practical examples and exercises.";
                    $lesson_stmt->bind_param("iissii", $course['id'], $section_id, $lesson_title, $lesson_desc, $lesson_content, $lesson_index + 1);
                    
                    if ($lesson_stmt->execute()) {
                        echo "<p>    📝 Lesson created: $lesson_title</p>";
                    }
                }
            }
        }
    }
}

$conn->close();

echo "<hr>";
echo "<h3>🎉 Enhanced Course Structure Setup Complete!</h3>";
echo "<p>The system now supports:</p>";
echo "<ul>";
echo "<li>✅ <strong>Course Sections</strong> - Organize lessons into logical groups</li>";
echo "<li>✅ <strong>Enhanced Lessons</strong> - Rich content with descriptions</li>";
echo "<li>✅ <strong>Multiple Videos</strong> - Support for various video formats</li>";
echo "<li>✅ <strong>Multiple Images</strong> - Image galleries for lessons</li>";
echo "<li>✅ <strong>Additional Resources</strong> - File attachments and downloads</li>";
echo "<li>✅ <strong>Detailed Progress Tracking</strong> - Monitor student engagement</li>";
echo "</ul>";

echo "<p><a href='admin/courses.php' style='background: #007bff; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>→ Go to Admin Courses</a></p>";
echo "<p><a href='teacher/courses.php' style='background: #28a745; color: white; padding: 12px 20px; text-decoration: none; border-radius: 5px; font-size: 16px;'>→ Go to Teacher Courses</a></p>";
?> 