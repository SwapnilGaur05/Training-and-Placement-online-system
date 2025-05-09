<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// Get student information
$userId = $_SESSION['user_id'];
$studentQuery = "SELECT s.* FROM students s WHERE s.user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$studentResult = $stmt->get_result();

if ($studentResult->num_rows === 0) {
    header('Location: ../logout.php');
    exit;
}

$student = $studentResult->fetch_assoc();

// Check if job_id is provided
if (!isset($_POST['job_id'])) {
    $_SESSION['error'] = "Invalid request. Please try again.";
    header('Location: jobs.php');
    exit;
}

$jobId = (int)$_POST['job_id'];

// Check if job exists and deadline hasn't passed
$jobQuery = "SELECT * FROM job_postings WHERE id = ? AND deadline >= CURDATE()";
$stmt = $conn->prepare($jobQuery);
$stmt->bind_param("i", $jobId);
$stmt->execute();
$jobResult = $stmt->get_result();

if ($jobResult->num_rows === 0) {
    $_SESSION['error'] = "This job posting is no longer available or has expired.";
    header('Location: jobs.php');
    exit;
}

$job = $jobResult->fetch_assoc();

// Check if student has already applied
$checkQuery = "SELECT * FROM applications WHERE student_id = ? AND job_id = ?";
$stmt = $conn->prepare($checkQuery);
$stmt->bind_param("ii", $student['id'], $jobId);
$stmt->execute();

if ($stmt->get_result()->num_rows > 0) {
    $_SESSION['error'] = "You have already applied for this position.";
    header('Location: jobs.php');
    exit;
}

// Check if student has uploaded a resume
if (empty($student['resume_path'])) {
    $_SESSION['error'] = "Please upload your resume before applying for jobs.";
    header('Location: myprofile.php');
    exit;
}

// Insert application
$status = 'Applied'; // Changed from 'Pending' to match the ENUM values in the database
$applicationDate = date('Y-m-d H:i:s');

$insertQuery = "INSERT INTO applications (student_id, job_id, status, applied_date) VALUES (?, ?, ?, ?)";
$stmt = $conn->prepare($insertQuery);
$stmt->bind_param("iiss", $student['id'], $jobId, $status, $applicationDate);

if ($stmt->execute()) {
    $_SESSION['success'] = "Your application has been submitted successfully!";
} else {
    $_SESSION['error'] = "There was an error submitting your application. Please try again.";
}

header('Location: jobs.php');
exit;
?> 