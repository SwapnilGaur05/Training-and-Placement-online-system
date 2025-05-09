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
$studentQuery = "SELECT s.*, u.email 
                FROM students s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$studentResult = $stmt->get_result();

if ($studentResult->num_rows === 0) {
    header('Location: ../logout.php');
    exit;
}

$student = $studentResult->fetch_assoc();

// Handle profile update
$successMsg = $errorMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $name = trim($_POST['name']);
    $department = trim($_POST['department']);
    $yearOfPassing = (int)$_POST['year_of_passing'];
    $cgpa = floatval($_POST['cgpa']);
    $contact = trim($_POST['contact']);
    $about = trim($_POST['about']);
    $skills = trim($_POST['skills']);

    // Basic validation
    if (empty($name) || empty($department) || empty($yearOfPassing)) {
        $errorMsg = "Name, Department, and Year of Passing are required fields.";
    } elseif ($cgpa > 10.0 || $cgpa < 0.0) {
        $errorMsg = "CGPA must be between 0 and 10.";
    } else {
        // Handle resume upload if provided
        $resumePath = $student['resume_path']; // Keep existing path by default
        if (isset($_FILES['resume']) && $_FILES['resume']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['application/pdf'];
            $maxSize = 5 * 1024 * 1024; // 5MB

            if (!in_array($_FILES['resume']['type'], $allowedTypes)) {
                $errorMsg = "Only PDF files are allowed for resume.";
            } elseif ($_FILES['resume']['size'] > $maxSize) {
                $errorMsg = "Resume file size must be less than 5MB.";
            } else {
                $uploadDir = '../../uploads/resumes/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0777, true);
                }

                $fileName = $student['roll_number'] . '_' . time() . '.pdf';
                $uploadPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['resume']['tmp_name'], $uploadPath)) {
                    // Delete old resume if exists
                    if ($student['resume_path'] && file_exists($student['resume_path'])) {
                        unlink($student['resume_path']);
                    }
                    $resumePath = $uploadPath;
                } else {
                    $errorMsg = "Failed to upload resume. Please try again.";
                }
            }
        }

        if (empty($errorMsg)) {
            // Update student profile
            $updateQuery = "UPDATE students 
                          SET name = ?, department = ?, year_of_passing = ?, 
                              cgpa = ?, contact = ?, about = ?, skills = ?, 
                              resume_path = ?
                          WHERE user_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("ssiissssi", $name, $department, $yearOfPassing, 
                            $cgpa, $contact, $about, $skills, $resumePath, $userId);

            if ($stmt->execute()) {
                $successMsg = "Profile updated successfully!";
                // Refresh student data
                $stmt = $conn->prepare($studentQuery);
                $stmt->bind_param("i", $userId);
                $stmt->execute();
                $student = $stmt->get_result()->fetch_assoc();
            } else {
                $errorMsg = "Failed to update profile. Please try again.";
            }
        }
    }
}

// Set page title
$pageTitle = 'My Profile';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - TPOS</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/student.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">My Profile</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($student['resume_path']): ?>
                            <a href="<?php echo htmlspecialchars($student['resume_path']); ?>" class="btn btn-sm btn-outline-primary me-2" target="_blank">
                                <i class="fas fa-file-pdf me-1"></i> View Resume
                            </a>
                        <?php endif; ?>
                        <a href="applications.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list me-1"></i> My Applications
                        </a>
                    </div>
                </div>

                <?php if ($successMsg): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $successMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($errorMsg): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $errorMsg; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($student['email'] ?? ''); ?>" disabled>
                                    </div>

                                    <div class="mb-3">
                                        <label for="roll_number" class="form-label">Roll Number</label>
                                        <input type="text" class="form-control" id="roll_number" value="<?php echo htmlspecialchars($student['roll_number'] ?? ''); ?>" disabled>
                                    </div>

                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($student['name'] ?? ''); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department *</label>
                                        <input type="text" class="form-control" id="department" name="department" value="<?php echo htmlspecialchars($student['department'] ?? ''); ?>" required>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="year_of_passing" class="form-label">Year of Passing *</label>
                                            <input type="number" class="form-control" id="year_of_passing" name="year_of_passing" value="<?php echo htmlspecialchars($student['year_of_passing'] ?? ''); ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="cgpa" class="form-label">CGPA</label>
                                            <input type="number" class="form-control" id="cgpa" name="cgpa" step="0.01" min="0" max="10" value="<?php echo htmlspecialchars($student['cgpa'] ?? ''); ?>">
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="contact" class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" id="contact" name="contact" value="<?php echo htmlspecialchars($student['contact'] ?? ''); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="about" class="form-label">About Me</label>
                                        <textarea class="form-control" id="about" name="about" rows="4"><?php echo htmlspecialchars($student['about'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="skills" class="form-label">Skills</label>
                                        <textarea class="form-control" id="skills" name="skills" rows="3" placeholder="Enter your skills (e.g., HTML, CSS, JavaScript, PHP)"><?php echo htmlspecialchars($student['skills'] ?? ''); ?></textarea>
                                    </div>

                                    <div class="mb-3">
                                        <label for="resume" class="form-label">Resume (PDF only, max 5MB)</label>
                                        <input type="file" class="form-control" id="resume" name="resume" accept="application/pdf">
                                        <?php if ($student['resume_path']): ?>
                                            <small class="text-muted">Current resume: <a href="<?php echo htmlspecialchars($student['resume_path']); ?>" target="_blank">View</a></small>
                                        <?php endif; ?>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-1"></i> Save Changes
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-body">
                                <h5 class="card-title">Profile Completion</h5>
                                <?php
                                // Calculate profile completion percentage
                                $fields = ['name', 'department', 'year_of_passing', 'cgpa', 'contact', 'about', 'skills', 'resume_path'];
                                $completedFields = 0;
                                foreach ($fields as $field) {
                                    if (!empty($student[$field])) {
                                        $completedFields++;
                                    }
                                }
                                $completionPercentage = round(($completedFields / count($fields)) * 100);
                                ?>
                                <div class="progress mb-2">
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completionPercentage; ?>%" aria-valuenow="<?php echo $completionPercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <p class="mb-0"><?php echo $completionPercentage; ?>% Complete</p>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title">Profile Tips</h5>
                                <ul class="list-unstyled">
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Keep your information up to date</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Upload a professional resume</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> List your key skills</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Add a brief description about yourself</li>
                                    <li><i class="fas fa-check-circle text-success me-2"></i> Maintain accurate contact details</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JavaScript -->
    <script>
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        // Auto-hide alerts after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    </script>
</body>
</html> 