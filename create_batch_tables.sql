-- Create Batch Assignment Tables
-- This script creates the missing tables for batch instructor and course assignments

-- Create batch_instructors table
CREATE TABLE IF NOT EXISTS batch_instructors (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    batch_id INT(11) NOT NULL,
    instructor_id INT(11) NOT NULL,
    role ENUM('lead', 'assistant', 'mentor') DEFAULT 'lead',
    assigned_date DATE DEFAULT (CURRENT_DATE),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_batch_id (batch_id),
    INDEX idx_instructor_id (instructor_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_batch_instructor_role (batch_id, instructor_id, role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create batch_courses table
CREATE TABLE IF NOT EXISTS batch_courses (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    batch_id INT(11) NOT NULL,
    course_id INT(11) NOT NULL,
    start_date DATE,
    end_date DATE,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_batch_id (batch_id),
    INDEX idx_course_id (course_id),
    INDEX idx_status (status),
    UNIQUE KEY unique_batch_course (batch_id, course_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create batches table if it doesn't exist
CREATE TABLE IF NOT EXISTS batches (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    max_students INT(11) DEFAULT 30,
    current_students INT(11) DEFAULT 0,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status),
    INDEX idx_dates (start_date, end_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add sample instructors
INSERT IGNORE INTO users (name, email, password, user_type, status) VALUES 
('Dr. Sarah Johnson', 'sarah.johnson@training.com', MD5('instructor123'), 'teacher', 'active'),
('Prof. Michael Chen', 'michael.chen@training.com', MD5('instructor123'), 'teacher', 'active'),
('Dr. Emily Rodriguez', 'emily.rodriguez@training.com', MD5('instructor123'), 'teacher', 'active'),
('Mr. David Wilson', 'david.wilson@training.com', MD5('instructor123'), 'teacher', 'active'),
('Ms. Lisa Thompson', 'lisa.thompson@training.com', MD5('instructor123'), 'teacher', 'active');

-- Add sample batches if none exist
INSERT IGNORE INTO batches (name, description, start_date, end_date, max_students) VALUES 
('Web Development Batch 2024', 'Comprehensive web development training', '2024-01-15', '2024-06-15', 25),
('Data Science Batch 2024', 'Python and machine learning focus', '2024-02-01', '2024-07-01', 20),
('Mobile App Development', 'React Native and Flutter training', '2024-03-01', '2024-08-01', 30);

-- Display success message
SELECT 'Database tables created successfully!' as message;