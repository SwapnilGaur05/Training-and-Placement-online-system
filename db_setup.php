<?php
// Database setup script for TPOS system
// This script creates all the tables needed for the application

// Include database connection
require_once 'config/db_connect.php';

// Function to execute SQL and display result
function executeSql($conn, $sql, $tableName) {
    if ($conn->query($sql) === TRUE) {
        echo "<p style='color:green'>✓ Table '$tableName' created or already exists.</p>";
    } else {
        echo "<p style='color:red'>✗ Error creating table '$tableName': " . $conn->error . "</p>";
    }
}

echo "<h1>TPOS Database Setup</h1>";
echo "<p>Creating or verifying necessary tables...</p>";

// Create event_registrations table
$eventRegSql = "CREATE TABLE IF NOT EXISTS event_registrations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    event_id INT NOT NULL,
    student_id INT NOT NULL,
    company_id INT,
    registration_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    status ENUM('registered', 'attended', 'cancelled') NOT NULL DEFAULT 'registered',
    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
)";
executeSql($conn, $eventRegSql, "event_registrations");

// Create training_enrollments table (if missing)
$trainingEnrollSql = "CREATE TABLE IF NOT EXISTS training_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    training_program_id INT NOT NULL,
    student_id INT NOT NULL,
    enrollment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    completion_date DATETIME NULL,
    status ENUM('enrolled', 'completed', 'dropped') NOT NULL DEFAULT 'enrolled',
    feedback TEXT NULL,
    FOREIGN KEY (training_program_id) REFERENCES training_programs(id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
)";
executeSql($conn, $trainingEnrollSql, "training_enrollments");

echo "<p>Database setup complete!</p>";
echo "<p><a href='index.php'>Return to homepage</a></p>";

// Close the connection
$conn->close();
?> 