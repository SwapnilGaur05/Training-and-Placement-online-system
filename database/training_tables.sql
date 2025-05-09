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

-- Insert sample training programs
INSERT INTO `training_programs` (`title`, `description`, `prerequisites`, `syllabus`, `instructor`, `start_date`, `end_date`, `duration`, `location`, `status`) VALUES
('Web Development Bootcamp', 'Comprehensive web development training covering HTML, CSS, JavaScript, and PHP.', 'Basic computer knowledge', '1. HTML & CSS Basics\n2. JavaScript Fundamentals\n3. PHP Programming\n4. MySQL Database\n5. Project Development', 'John Smith', '2024-04-01', '2024-05-30', '2 Months', 'Computer Lab 1', 'active'),
('Data Science Fundamentals', 'Introduction to data science concepts, tools, and methodologies.', 'Basic mathematics and statistics', '1. Python Programming\n2. Data Analysis\n3. Machine Learning Basics\n4. Data Visualization\n5. Project Work', 'Sarah Johnson', '2024-04-15', '2024-06-15', '2 Months', 'Computer Lab 2', 'active'),
('Cloud Computing Workshop', 'Learn cloud computing concepts and AWS services.', 'Basic networking knowledge', '1. Cloud Basics\n2. AWS Services\n3. Cloud Security\n4. Deployment\n5. Best Practices', 'Michael Brown', '2024-05-01', '2024-06-30', '2 Months', 'Computer Lab 3', 'active'); 