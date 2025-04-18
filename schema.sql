-- Create the database
CREATE DATABASE IF NOT EXISTS music_school_db;
USE music_school_db;

-- Users table: Stores user account information
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Students table: Stores enrolled student details
CREATE TABLE students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_name VARCHAR(100) NOT NULL,
    instrument VARCHAR(50) NOT NULL,
    age INT NOT NULL,
    contact_info VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    guardian_contact VARCHAR(255) NOT NULL, -- Added guardian_contact column
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- Assignments table: Stores teacher-class assignments
CREATE TABLE assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    teacher_name VARCHAR(100) NOT NULL,
    class_name VARCHAR(100) NOT NULL,
    instrument VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- Practice_logs table: Tracks student practice hours
CREATE TABLE practice_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id INT NOT NULL, -- Added student_id column
    student_name VARCHAR(100) NOT NULL,
    practice_hours DECIMAL(4,1) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE, -- Added foreign key for student_id
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- Lesson_schedules table: Manages lesson schedules
CREATE TABLE lesson_schedules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    student_id INT NOT NULL, -- Added student_id column
    student_name VARCHAR(100) NOT NULL,
    lesson_date DATE NOT NULL,
    lesson_time TIME NOT NULL,
    duration INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE, -- Added foreign key for student_id
    INDEX idx_user_id (user_id),
    INDEX idx_lesson_date (lesson_date)
) ENGINE=InnoDB;

-- Recitals table: Stores recital event details
CREATE TABLE recitals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    event_name VARCHAR(100) NOT NULL,
    recital_date DATE NOT NULL,
    location VARCHAR(255) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id),
    INDEX idx_recital_date (recital_date)
) ENGINE=InnoDB;

-- Collaboration_messages table: Stores collaboration messages
CREATE TABLE collaboration_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_name VARCHAR(100) NOT NULL,
    recipient_type VARCHAR(50) NOT NULL, -- Added recipient_type column
    message TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;

-- Feedback table: Stores user feedback
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    feedback_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_id (user_id)
) ENGINE=InnoDB;