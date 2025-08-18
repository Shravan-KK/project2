<?php
require_once 'config/database.php';

echo "<h1>Setting up Enhanced Course Structure</h1>";

try {
    // Create course_sections table
    $create_sections = "CREATE TABLE IF NOT EXISTS course_sections (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        course_id INT(11) NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        order_number INT(11) DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_course_id (course_id),
        INDEX idx_order (order_number),
        FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($create_sections)) {
        echo "<p style='color: green;'>✓ Course sections table created</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating course sections table: " . $conn->error . "</p>";
    }

    // Update lessons table to include section_id and enhanced media support
    $check_lessons_columns = $conn->query("SHOW COLUMNS FROM lessons");
    $existing_columns = [];
    while ($row = $check_lessons_columns->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }

    // Add section_id column if not exists
    if (!in_array('section_id', $existing_columns)) {
        $add_section_id = "ALTER TABLE lessons ADD COLUMN section_id INT(11) NULL AFTER course_id";
        if ($conn->query($add_section_id)) {
            echo "<p style='color: green;'>✓ Added section_id to lessons table</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Could not add section_id: " . $conn->error . "</p>";
        }
    }

    // Add rich content column if not exists
    if (!in_array('rich_content', $existing_columns)) {
        $add_rich_content = "ALTER TABLE lessons ADD COLUMN rich_content LONGTEXT NULL AFTER content";
        if ($conn->query($add_rich_content)) {
            echo "<p style='color: green;'>✓ Added rich_content to lessons table</p>";
        } else {
            echo "<p style='color: orange;'>⚠ Could not add rich_content: " . $conn->error . "</p>";
        }
    }

    // Create lesson_media table for multiple video/image uploads
    $create_media = "CREATE TABLE IF NOT EXISTS lesson_media (
        id INT(11) AUTO_INCREMENT PRIMARY KEY,
        lesson_id INT(11) NOT NULL,
        media_type ENUM('video', 'image', 'document') NOT NULL,
        file_name VARCHAR(255) NOT NULL,
        file_path VARCHAR(500) NOT NULL,
        file_url VARCHAR(500),
        file_size INT(11),
        mime_type VARCHAR(100),
        title VARCHAR(255),
        description TEXT,
        order_number INT(11) DEFAULT 0,
        status ENUM('active', 'inactive') DEFAULT 'active',
        uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_lesson_id (lesson_id),
        INDEX idx_media_type (media_type),
        INDEX idx_order (order_number),
        FOREIGN KEY (lesson_id) REFERENCES lessons(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    if ($conn->query($create_media)) {
        echo "<p style='color: green;'>✓ Lesson media table created</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating lesson media table: " . $conn->error . "</p>";
    }

    // Create uploads directory if it doesn't exist
    $upload_dirs = [
        'uploads/',
        'uploads/lessons/',
        'uploads/lessons/videos/',
        'uploads/lessons/images/',
        'uploads/lessons/documents/'
    ];

    foreach ($upload_dirs as $dir) {
        if (!file_exists($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p style='color: green;'>✓ Created directory: $dir</p>";
            } else {
                echo "<p style='color: orange;'>⚠ Could not create directory: $dir</p>";
            }
        } else {
            echo "<p style='color: orange;'>⚠ Directory already exists: $dir</p>";
        }
    }

    // Add sample sections and lessons if courses exist
    $courses_check = $conn->query("SELECT id, title FROM courses LIMIT 3");
    if ($courses_check->num_rows > 0) {
        echo "<h3>Adding Sample Sections and Lessons</h3>";
        
        while ($course = $courses_check->fetch_assoc()) {
            // Check if sections already exist for this course
            $sections_check = $conn->query("SELECT COUNT(*) as count FROM course_sections WHERE course_id = {$course['id']}");
            $sections_count = $sections_check->fetch_assoc()['count'];
            
            if ($sections_count == 0) {
                // Add sample sections
                $sample_sections = [
                    ['title' => 'Introduction', 'description' => 'Getting started with the course'],
                    ['title' => 'Core Concepts', 'description' => 'Main learning objectives'],
                    ['title' => 'Practical Applications', 'description' => 'Hands-on exercises and examples'],
                    ['title' => 'Assessment', 'description' => 'Tests and evaluations']
                ];
                
                foreach ($sample_sections as $index => $section) {
                    $insert_section = "INSERT INTO course_sections (course_id, title, description, order_number) VALUES (?, ?, ?, ?)";
                    $stmt = $conn->prepare($insert_section);
                    $stmt->bind_param("issi", $course['id'], $section['title'], $section['description'], $index + 1);
                    
                    if ($stmt->execute()) {
                        $section_id = $conn->insert_id;
                        echo "<p style='color: green;'>✓ Added section '{$section['title']}' to course '{$course['title']}'</p>";
                        
                        // Add sample lessons to each section
                        $sample_lessons = [
                            ['title' => 'Lesson 1: Overview', 'content' => 'Introduction to the topic'],
                            ['title' => 'Lesson 2: Deep Dive', 'content' => 'Detailed exploration of concepts']
                        ];
                        
                        foreach ($sample_lessons as $lesson_index => $lesson) {
                            $insert_lesson = "INSERT INTO lessons (course_id, section_id, title, content, order_number) VALUES (?, ?, ?, ?, ?)";
                            $lesson_stmt = $conn->prepare($insert_lesson);
                            $lesson_stmt->bind_param("iissi", $course['id'], $section_id, $lesson['title'], $lesson['content'], $lesson_index + 1);
                            
                            if ($lesson_stmt->execute()) {
                                echo "<p style='color: green;'>  ✓ Added lesson '{$lesson['title']}'</p>";
                            }
                        }
                    }
                }
            }
        }
    }

    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 20px 0;'>";
    echo "<h3>✅ Course Structure Setup Complete!</h3>";
    echo "<p>Enhanced course structure with sections and lessons is now ready. Features include:</p>";
    echo "<ul>";
    echo "<li>✓ Course Sections for organizing content</li>";
    echo "<li>✓ Enhanced Lessons with rich text support</li>";
    echo "<li>✓ Multiple media upload support (videos, images, documents)</li>";
    echo "<li>✓ Proper file organization and upload directories</li>";
    echo "<li>✓ Sample data for testing</li>";
    echo "</ul>";
    echo "<p><a href='admin/courses.php'>Manage Courses (Admin)</a> | <a href='teacher/courses.php'>My Courses (Teacher)</a></p>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border: 1px solid #f5c6cb; border-radius: 5px;'>";
    echo "<h3>❌ Error occurred:</h3>";
    echo "<p>" . $e->getMessage() . "</p>";
    echo "</div>";
}

$conn->close();
?>