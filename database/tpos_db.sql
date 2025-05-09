-- Create database
CREATE DATABASE IF NOT EXISTS tpos_db;
USE tpos_db;

-- Users table (for authentication)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'student', 'company') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Admin table
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    contact VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Students table
CREATE TABLE IF NOT EXISTS students (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    roll_number VARCHAR(20) NOT NULL UNIQUE,
    department VARCHAR(50) NOT NULL,
    year_of_passing INT NOT NULL,
    cgpa DECIMAL(3,2),
    resume_path VARCHAR(255),
    contact VARCHAR(20),
    about TEXT,
    skills TEXT,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Companies table
CREATE TABLE IF NOT EXISTS companies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    website VARCHAR(255),
    location VARCHAR(100),
    contact_person VARCHAR(100),
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Job postings table
CREATE TABLE IF NOT EXISTS job_postings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    company_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT NOT NULL,
    location VARCHAR(100),
    job_type ENUM('Full-time', 'Part-time', 'Internship') NOT NULL,
    salary VARCHAR(50),
    deadline DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
);

-- Applications table
CREATE TABLE IF NOT EXISTS applications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    student_id INT NOT NULL,
    status ENUM('Applied', 'Shortlisted', 'Interview', 'Selected', 'Rejected') DEFAULT 'Applied',
    applied_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES job_postings(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    UNIQUE KEY (job_id, student_id)
);

-- Announcements table
CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    title VARCHAR(100) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
);

-- Events table
CREATE TABLE IF NOT EXISTS events (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    event_date DATETIME NOT NULL,
    location VARCHAR(100),
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE
);

-- Training Programs Table
CREATE TABLE IF NOT EXISTS `training_programs` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `duration` VARCHAR(50) NOT NULL,
    `venue` VARCHAR(255) NOT NULL,
    `trainer` VARCHAR(100) NOT NULL,
    `max_participants` INT NOT NULL,
    `status` ENUM('Scheduled', 'Open', 'Completed', 'Cancelled') NOT NULL DEFAULT 'Scheduled',
    `company_id` INT NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`company_id`) REFERENCES `companies`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Training Enrollments Table
CREATE TABLE IF NOT EXISTS `training_enrollments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `training_program_id` int(11) NOT NULL,
    `student_id` int(11) NOT NULL,
    `enrollment_date` datetime NOT NULL,
    `status` enum('enrolled','completed','dropped') NOT NULL DEFAULT 'enrolled',
    `completion_date` datetime DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
    `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
    PRIMARY KEY (`id`),
    UNIQUE KEY `unique_enrollment` (`training_program_id`, `student_id`),
    FOREIGN KEY (`training_program_id`) REFERENCES `training_programs` (`id`) ON DELETE CASCADE,
    FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert demo data
-- Admin user
INSERT INTO users (email, password, user_type) VALUES ('admin@tpos.com', '$2y$10$8WxhJz0q.Y9BKQsRwH1NqeUTLXEgNwCj3/F9Iw8yrLB3Zx.J9FYxe', 'admin'); -- Password: admin123
INSERT INTO admins (user_id, name, contact) VALUES (1, 'Admin User', '1234567890');

-- Student user
INSERT INTO users (email, password, user_type) VALUES ('student@example.com', '$2y$10$8WxhJz0q.Y9BKQsRwH1NqeUTLXEgNwCj3/F9Iw8yrLB3Zx.J9FYxe', 'student'); -- Password: student123
INSERT INTO students (user_id, name, roll_number, department, year_of_passing, cgpa, contact, about, skills) 
VALUES (2, 'John Doe', 'CS2023001', 'Computer Science', 2023, 8.5, '9876543210', 'Passionate about web development', 'HTML, CSS, JavaScript, PHP, MySQL');

-- Company user
INSERT INTO users (email, password, user_type) VALUES ('company@example.com', '$2y$10$8WxhJz0q.Y9BKQsRwH1NqeUTLXEgNwCj3/F9Iw8yrLB3Zx.J9FYxe', 'company'); -- Password: company123
INSERT INTO companies (user_id, name, description, website, location, contact_person, contact_email, contact_phone) 
VALUES (3, 'Tech Solutions Inc.', 'A leading technology company', 'https://techsolutions.example.com', 'New York', 'Jane Smith', 'jane@techsolutions.example.com', '5551234567');

-- Sample job posting
INSERT INTO job_postings (company_id, title, description, requirements, location, job_type, salary, deadline) 
VALUES (1, 'Web Developer', 'We are looking for a skilled web developer to join our team.', 'HTML, CSS, JavaScript, PHP, 2+ years experience', 'New York', 'Full-time', '$70,000 - $90,000', '2023-12-31');

-- Sample announcement
INSERT INTO announcements (admin_id, title, content) 
VALUES (1, 'Welcome to the new Training and Placement System', 'We are excited to launch our new online system for managing training and placement activities.');

-- Sample event
INSERT INTO events (title, description, event_date, location, created_by) 
VALUES ('Campus Recruitment Drive', 'Multiple companies will be visiting our campus for recruitment.', '2023-11-15 09:00:00', 'Main Auditorium', 1); 