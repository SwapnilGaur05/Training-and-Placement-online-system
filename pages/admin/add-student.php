<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $roll_number = $_POST['roll_number'] ?? '';
    $department = $_POST['department'] ?? '';
    $year_of_passing = $_POST['year_of_passing'] ?? '';
    $cgpa = $_POST['cgpa'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $about = $_POST['about'] ?? '';
    $skills = $_POST['skills'] ?? '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($password) || empty($roll_number) || empty($department) || empty($year_of_passing)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // First, create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_stmt = $conn->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, 'student')");
            $user_stmt->bind_param("ss", $email, $hashed_password);
            $user_stmt->execute();
            $user_id = $conn->insert_id;

            // Then, create student profile
            $student_stmt = $conn->prepare("INSERT INTO students (user_id, name, roll_number, department, year_of_passing, cgpa, contact, about, skills) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $student_stmt->bind_param("isssidsss", $user_id, $name, $roll_number, $department, $year_of_passing, $cgpa, $contact, $about, $skills);
            $student_stmt->execute();

            // If everything is successful, commit the transaction
            $conn->commit();
            $success_message = "Student added successfully!";

            // Clear form data
            $_POST = array();

        } catch (Exception $e) {
            // If there's an error, rollback the transaction
            $conn->rollback();
            $error_message = "Error adding student: " . $e->getMessage();
        }
    }
}

// Get list of departments for dropdown
$departments = ['Computer Science', 'Information Technology', 'Electronics', 'Mechanical', 'Civil', 'Electrical'];

// Set page title
$pageTitle = 'Add New Student';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Student - TPOS</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
          

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Add New Student</h1>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $_POST['name'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="roll_number" class="form-label">Roll Number *</label>
                                    <input type="text" class="form-control" id="roll_number" name="roll_number" value="<?php echo $_POST['roll_number'] ?? ''; ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="department" class="form-label">Department *</label>
                                    <select class="form-select" id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept; ?>" <?php echo (isset($_POST['department']) && $_POST['department'] === $dept) ? 'selected' : ''; ?>>
                                                <?php echo $dept; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="year_of_passing" class="form-label">Year of Passing *</label>
                                    <select class="form-select" id="year_of_passing" name="year_of_passing" required>
                                        <option value="">Select Year</option>
                                        <?php 
                                        $current_year = date('Y');
                                        for ($year = $current_year; $year <= $current_year + 4; $year++): 
                                        ?>
                                            <option value="<?php echo $year; ?>" <?php echo (isset($_POST['year_of_passing']) && $_POST['year_of_passing'] == $year) ? 'selected' : ''; ?>>
                                                <?php echo $year; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="cgpa" class="form-label">CGPA</label>
                                    <input type="number" class="form-control" id="cgpa" name="cgpa" step="0.01" min="0" max="10" value="<?php echo $_POST['cgpa'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="contact" class="form-label">Contact Number</label>
                                    <input type="tel" class="form-control" id="contact" name="contact" value="<?php echo $_POST['contact'] ?? ''; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="about" class="form-label">About</label>
                                <textarea class="form-control" id="about" name="about" rows="3"><?php echo $_POST['about'] ?? ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="skills" class="form-label">Skills</label>
                                <textarea class="form-control" id="skills" name="skills" rows="2" placeholder="Enter skills separated by commas"><?php echo $_POST['skills'] ?? ''; ?></textarea>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-secondary me-md-2" onclick="window.location.href='students.php'">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Student</button>
                            </div>
                        </form>
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
    </script>
</body>
</html> 