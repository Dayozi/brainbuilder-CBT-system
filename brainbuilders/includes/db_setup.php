<?php
// includes/db_setup.php
require_once 'config.php';

// SQL to create tables
$sql = [
    "CREATE TABLE IF NOT EXISTS classes (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(50) NOT NULL,
        section VARCHAR(50) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role ENUM('admin', 'teacher', 'student') NOT NULL,
        class_id INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (class_id) REFERENCES classes(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS subjects (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    
    "CREATE TABLE IF NOT EXISTS exams (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(100) NOT NULL,
        subject_id INT NOT NULL,
        class_id INT NOT NULL,
        duration INT NOT NULL COMMENT 'Duration in minutes',
        total_questions INT NOT NULL,
        pass_mark INT NOT NULL,
        available_from DATETIME NOT NULL,
        available_to DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (subject_id) REFERENCES subjects(id),
        FOREIGN KEY (class_id) REFERENCES classes(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        exam_id INT NOT NULL,
        question_type ENUM('multiple_choice', 'true_false', 'fill_blank') NOT NULL,
        question_text TEXT NOT NULL,
        options JSON DEFAULT NULL,
        correct_answer TEXT NOT NULL,
        marks INT NOT NULL DEFAULT 1,
        FOREIGN KEY (exam_id) REFERENCES exams(id)
    )",
    
    "CREATE TABLE IF NOT EXISTS exam_results (
        id INT AUTO_INCREMENT PRIMARY KEY,
        student_id INT NOT NULL,
        exam_id INT NOT NULL,
        score INT NOT NULL,
        total_questions INT NOT NULL,
        percentage DECIMAL(5,2) NOT NULL,
        started_at DATETIME NOT NULL,
        submitted_at DATETIME NOT NULL,
        FOREIGN KEY (student_id) REFERENCES users(id),
        FOREIGN KEY (exam_id) REFERENCES exams(id)
    )"
];

foreach ($sql as $query) {
    if (!$conn->query($query)) {
        die("Error creating table: " . $conn->error);
    }
}

// Insert sample classes if they don't exist
$classes = [
    ['JSS 1A', 'Junior Secondary'],
    ['JSS 1B', 'Junior Secondary'],
    ['JSS 2A', 'Junior Secondary'],
    ['SSS 1A', 'Senior Secondary'],
    ['SSS 2B', 'Senior Secondary']
];

foreach ($classes as $class) {
    $check = $conn->prepare("SELECT id FROM classes WHERE name = ?");
    $check->bind_param("s", $class[0]);
    $check->execute();
    $check->store_result();
    
    if ($check->num_rows == 0) {
        $insert = $conn->prepare("INSERT INTO classes (name, section) VALUES (?, ?)");
        $insert->bind_param("ss", $class[0], $class[1]);
        $insert->execute();
    }
}

echo "Database setup completed successfully!";
?>