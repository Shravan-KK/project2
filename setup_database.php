<?php
$host = 'localhost';
$username = 'root';
$password = 'root';

// Create connection without database
$conn = new mysqli($host, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database
$sql = "CREATE DATABASE IF NOT EXISTS teaching_management";
if ($conn->query($sql) === TRUE) {
    echo "Database created successfully<br>";
} else {
    echo "Error creating database: " . $conn->error . "<br>";
}

// Select the database
$conn->select_db('teaching_management');

// Create users table
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'teacher', 'student') NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Users table created successfully<br>";
} else {
    echo "Error creating users table: " . $conn->error . "<br>";
}

// Create courses table
$sql = "CREATE TABLE IF NOT EXISTS courses (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    teacher_id INT(11),
    category VARCHAR(100),
    price DECIMAL(10,2) DEFAULT 0.00,
    duration VARCHAR(50),
    level ENUM('beginner', 'intermediate', 'advanced') DEFAULT 'beginner',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES users(id) ON DELETE SET NULL
)";

if ($conn->query($sql) === TRUE) {
    echo "Courses table created successfully<br>";
} else {
    echo "Error creating courses table: " . $conn->error . "<br>";
}

// Create enrollments table
$sql = "CREATE TABLE IF NOT EXISTS enrollments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    student_id INT(11),
    course_id INT(11),
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
    progress INT(3) DEFAULT 0,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_id, course_id)
)";

if ($conn->query($sql) === TRUE) {
    echo "Enrollments table created successfully<br>";
} else {
    echo "Error creating enrollments table: " . $conn->error . "<br>";
}

// Create lessons table
$sql = "CREATE TABLE IF NOT EXISTS lessons (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    course_id INT(11),
    title VARCHAR(200) NOT NULL,
    content TEXT,
    video_url VARCHAR(500),
    duration INT(11) DEFAULT 0,
    order_number INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Lessons table created successfully<br>";
} else {
    echo "Error creating lessons table: " . $conn->error . "<br>";
}

// Create assignments table
$sql = "CREATE TABLE IF NOT EXISTS assignments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    course_id INT(11),
    title VARCHAR(200) NOT NULL,
    description TEXT,
    due_date DATE,
    total_points INT(11) DEFAULT 100,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Assignments table created successfully<br>";
} else {
    echo "Error creating assignments table: " . $conn->error . "<br>";
}

// Create submissions table
$sql = "CREATE TABLE IF NOT EXISTS submissions (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    assignment_id INT(11),
    student_id INT(11),
    content TEXT,
    file_path VARCHAR(500),
    submitted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    grade DECIMAL(5,2),
    feedback TEXT,
    FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Submissions table created successfully<br>";
} else {
    echo "Error creating submissions table: " . $conn->error . "<br>";
}

// Create messages table
$sql = "CREATE TABLE IF NOT EXISTS messages (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    sender_id INT(11),
    receiver_id INT(11),
    subject VARCHAR(200),
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Messages table created successfully<br>";
} else {
    echo "Error creating messages table: " . $conn->error . "<br>";
}

// Create payments table
$sql = "CREATE TABLE IF NOT EXISTS payments (
    id INT(11) AUTO_INCREMENT PRIMARY KEY,
    student_id INT(11),
    course_id INT(11),
    amount DECIMAL(10,2),
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    payment_method VARCHAR(50),
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    transaction_id VARCHAR(100),
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (course_id) REFERENCES courses(id) ON DELETE CASCADE
)";

if ($conn->query($sql) === TRUE) {
    echo "Payments table created successfully<br>";
} else {
    echo "Error creating payments table: " . $conn->error . "<br>";
}

// Insert default admin user
$admin_password = password_hash('admin123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (name, email, password, user_type) VALUES ('Admin User', 'admin@tms.com', '$admin_password', 'admin')";

if ($conn->query($sql) === TRUE) {
    echo "Default admin user created successfully<br>";
} else {
    echo "Error creating admin user: " . $conn->error . "<br>";
}

// Insert sample teacher
$teacher_password = password_hash('teacher123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (name, email, password, user_type) VALUES ('John Teacher', 'teacher@tms.com', '$teacher_password', 'teacher')";

if ($conn->query($sql) === TRUE) {
    echo "Sample teacher created successfully<br>";
} else {
    echo "Error creating teacher: " . $conn->error . "<br>";
}

// Insert sample student
$student_password = password_hash('student123', PASSWORD_DEFAULT);
$sql = "INSERT IGNORE INTO users (name, email, password, user_type) VALUES ('Jane Student', 'student@tms.com', '$student_password', 'student')";

if ($conn->query($sql) === TRUE) {
    echo "Sample student created successfully<br>";
} else {
    echo "Error creating student: " . $conn->error . "<br>";
}

$conn->close();
echo "<br>Database setup completed!<br>";
echo "<a href='index.php'>Go to Login Page</a>";
?> 