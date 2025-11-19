-- Updated schema with all necessary tables and fields for comprehensive exam system
CREATE DATABASE IF NOT EXISTS school_management;
USE school_management;

-- Users table for authentication
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'HOI', 'DHOI', 'DoS_Exams_Teacher', 'Social_Affairs_Teacher', 'Finance_Teacher', 'Teacher', 'Student') NOT NULL,
    profile_image VARCHAR(255) DEFAULT 'default-avatar.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Students table
CREATE TABLE students (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    student_id VARCHAR(20) UNIQUE NOT NULL,
    admission_number VARCHAR(10) UNIQUE,
    grade ENUM('7', '8', '9') NOT NULL,
    stream VARCHAR(50),
    admission_date DATE NOT NULL,
    admission_year INT NOT NULL,
    status ENUM('Active', 'Promoted', 'Graduated') DEFAULT 'Active',
    parent_name VARCHAR(100),
    parent_phone VARCHAR(20),
    parent_email VARCHAR(100),
    address TEXT,
    date_of_birth DATE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Exams table
CREATE TABLE exams (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_name VARCHAR(100) NOT NULL,
    exam_type VARCHAR(100) NOT NULL,
    grade ENUM('7', '8', '9') NOT NULL,
    total_marks INT NOT NULL DEFAULT 10,
    exam_date DATE NOT NULL,
    is_school_wide BOOLEAN DEFAULT TRUE,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Exam subjects table
CREATE TABLE exam_subjects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    UNIQUE KEY unique_exam_subject (exam_id, subject)
);

-- Exam results table with subject tracking
CREATE TABLE exam_results (
    id INT PRIMARY KEY AUTO_INCREMENT,
    exam_id INT NOT NULL,
    student_id INT NOT NULL,
    subject VARCHAR(100) NOT NULL,
    marks_obtained DECIMAL(5,2) NOT NULL,
    remarks TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_exam_student_subject (exam_id, student_id, subject)
);

-- Fee types table
CREATE TABLE fee_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    fee_name VARCHAR(100) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    grade ENUM('7', '8', '9', 'All') DEFAULT 'All',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Fee payments table
CREATE TABLE fee_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    fee_type_id INT NOT NULL,
    amount_paid DECIMAL(10,2) NOT NULL,
    payment_date DATE NOT NULL,
    term ENUM('1', '2', '3', 'Full Year') DEFAULT 'Full Year',
    payment_method ENUM('Cash', 'M-Pesa', 'Bank Transfer', 'Card', 'Other') NOT NULL,
    receipt_number VARCHAR(50) UNIQUE,
    remarks TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (fee_type_id) REFERENCES fee_types(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Clubs table
CREATE TABLE clubs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    club_name VARCHAR(100) NOT NULL,
    description TEXT,
    club_image VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Club members table
CREATE TABLE club_members (
    id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT NOT NULL,
    student_id INT NOT NULL,
    joined_date DATE NOT NULL,
    role ENUM('Member', 'Leader', 'Vice-Leader') DEFAULT 'Member',
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY unique_club_member (club_id, student_id)
);

-- Club posts table
CREATE TABLE club_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    club_id INT NOT NULL,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (club_id) REFERENCES clubs(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Post comments table
CREATE TABLE post_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES club_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Post likes table
CREATE TABLE post_likes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES club_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_post_like (post_id, user_id)
);

-- Notes/Study materials table
CREATE TABLE notes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    subject VARCHAR(50) NOT NULL,
    grade ENUM('7', '8', '9', 'All') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_type VARCHAR(50),
    description TEXT,
    uploaded_by INT,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

-- Report cards table
CREATE TABLE report_cards (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    academic_year VARCHAR(20) NOT NULL,
    term ENUM('1', '2', '3') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    generated_by INT,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (generated_by) REFERENCES users(id)
);

-- News posts table
CREATE TABLE news_posts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    title VARCHAR(200),
    content TEXT,
    media_type ENUM('text', 'image', 'video', 'link') DEFAULT 'text',
    media_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- News comments table
CREATE TABLE news_comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    news_id INT NOT NULL,
    user_id INT NOT NULL,
    comment TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (news_id) REFERENCES news_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Projects table for student projects
CREATE TABLE projects (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_id INT NOT NULL,
    project_title VARCHAR(200) NOT NULL,
    project_description TEXT,
    media_type ENUM('text', 'image', 'document', 'video', 'mixed') DEFAULT 'text',
    media_url VARCHAR(500),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password, full_name, role) 
VALUES ('admin', 'admin@school.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'Admin')
ON DUPLICATE KEY UPDATE username = username;

-- Insert sample fee types
INSERT INTO fee_types (fee_name, amount, grade) VALUES
('Tuition Fee', 5000.00, 'All'),
('Library Fee', 500.00, 'All'),
('Sports Fee', 300.00, 'All'),
('Lab Fee', 800.00, 'All')
ON DUPLICATE KEY UPDATE fee_name = fee_name;
