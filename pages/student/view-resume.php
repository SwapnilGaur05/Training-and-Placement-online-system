<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Get student ID from the request
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$studentId) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

// Fetch student information and resume path
$query = "SELECT s.*, u.email 
          FROM students s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.1 404 Not Found');
    exit('Student not found');
}

$student = $result->fetch_assoc();

// Security check: Only allow access to:
// 1. The student viewing their own resume
// 2. Admins
// 3. Companies that the student has applied to
$hasAccess = false;

if ($_SESSION['user_type'] === 'student' && $student['user_id'] === $_SESSION['user_id']) {
    $hasAccess = true;
} elseif ($_SESSION['user_type'] === 'admin') {
    $hasAccess = true;
} elseif ($_SESSION['user_type'] === 'company') {
    // Check if student has applied to any of this company's jobs
    $checkQuery = "SELECT 1 FROM applications a 
                   JOIN job_postings j ON a.job_id = j.id 
                   JOIN companies c ON j.company_id = c.id 
                   WHERE a.student_id = ? AND c.user_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $studentId, $_SESSION['user_id']);
    $stmt->execute();
    $hasAccess = $stmt->get_result()->num_rows > 0;
}

if (!$hasAccess) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Check if resume exists
if (empty($student['resume_path']) || !file_exists($student['resume_path'])) {
    header('HTTP/1.1 404 Not Found');
    exit('Resume not found');
}

// Get file information
$fileName = basename($student['resume_path']);
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Set appropriate content type
switch ($fileExt) {
    case 'pdf':
        $contentType = 'application/pdf';
        break;
    case 'doc':
        $contentType = 'application/msword';
        break;
    case 'docx':
        $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    default:
        $contentType = 'application/octet-stream';
}

// Output the file
header('Content-Type: ' . $contentType);
header('Content-Disposition: inline; filename="' . $student['name'] . '_resume.' . $fileExt . '"');
header('Cache-Control: public, max-age=0');

readfile($student['resume_path']);
exit;
?> 
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Get student ID from the request
$studentId = isset($_GET['id']) ? (int)$_GET['id'] : null;

if (!$studentId) {
    header('HTTP/1.1 400 Bad Request');
    exit('Invalid request');
}

// Fetch student information and resume path
$query = "SELECT s.*, u.email 
          FROM students s 
          JOIN users u ON s.user_id = u.id 
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('HTTP/1.1 404 Not Found');
    exit('Student not found');
}

$student = $result->fetch_assoc();

// Security check: Only allow access to:
// 1. The student viewing their own resume
// 2. Admins
// 3. Companies that the student has applied to
$hasAccess = false;

if ($_SESSION['user_type'] === 'student' && $student['user_id'] === $_SESSION['user_id']) {
    $hasAccess = true;
} elseif ($_SESSION['user_type'] === 'admin') {
    $hasAccess = true;
} elseif ($_SESSION['user_type'] === 'company') {
    // Check if student has applied to any of this company's jobs
    $checkQuery = "SELECT 1 FROM applications a 
                   JOIN job_postings j ON a.job_id = j.id 
                   JOIN companies c ON j.company_id = c.id 
                   WHERE a.student_id = ? AND c.user_id = ?";
    $stmt = $conn->prepare($checkQuery);
    $stmt->bind_param("ii", $studentId, $_SESSION['user_id']);
    $stmt->execute();
    $hasAccess = $stmt->get_result()->num_rows > 0;
}

if (!$hasAccess) {
    header('HTTP/1.1 403 Forbidden');
    exit('Access denied');
}

// Check if resume exists
if (empty($student['resume_path']) || !file_exists($student['resume_path'])) {
    header('HTTP/1.1 404 Not Found');
    exit('Resume not found');
}

// Get file information
$fileName = basename($student['resume_path']);
$fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

// Set appropriate content type
switch ($fileExt) {
    case 'pdf':
        $contentType = 'application/pdf';
        break;
    case 'doc':
        $contentType = 'application/msword';
        break;
    case 'docx':
        $contentType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    default:
        $contentType = 'application/octet-stream';
}

// Output the file
header('Content-Type: ' . $contentType);
header('Content-Disposition: inline; filename="' . $student['name'] . '_resume.' . $fileExt . '"');
header('Cache-Control: public, max-age=0');

readfile($student['resume_path']);
exit;
?> 