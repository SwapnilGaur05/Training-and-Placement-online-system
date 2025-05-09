<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $prerequisites = isset($_POST['prerequisites']) ? trim($_POST['prerequisites']) : null;
    $syllabus = isset($_POST['syllabus']) ? trim($_POST['syllabus']) : null;
    $instructor = isset($_POST['instructor']) ? trim($_POST['instructor']) : null;
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $duration = trim($_POST['duration']);
    $location = trim($_POST['location']);
    $status = $_POST['status'];
    
    $errors = [];
    
    // Validate required fields
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    if (empty($start_date)) {
        $errors[] = "Start date is required";
    }
    if (empty($end_date)) {
        $errors[] = "End date is required";
    }
    if (empty($duration)) {
        $errors[] = "Duration is required";
    }
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    
    // Validate dates
    $start_timestamp = strtotime($start_date);
    $end_timestamp = strtotime($end_date);
    
    if ($start_timestamp === false) {
        $errors[] = "Invalid start date format";
    }
    if ($end_timestamp === false) {
        $errors[] = "Invalid end date format";
    }
    if ($start_timestamp && $end_timestamp && $start_timestamp > $end_timestamp) {
        $errors[] = "End date must be after start date";
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        $query = "INSERT INTO training_programs (title, description, prerequisites, syllabus, instructor, start_date, end_date, duration, location, status) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                 
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssssssssss", $title, $description, $prerequisites, $syllabus, $instructor, $start_date, $end_date, $duration, $location, $status);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Training program created successfully!";
            header("Location: training-programs.php");
            exit;
        } else {
            $errors[] = "Failed to create training program: " . $conn->error;
        }
    }
}

// Set page title
$pageTitle = 'Add Training Program';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Training Program - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Add Training Program</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="training-programs.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Programs
                        </a>
                    </div>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                                    <div class="invalid-feedback">
                                        Please enter a title for the program.
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="instructor" class="form-label">Instructor</label>
                                    <input type="text" class="form-control" id="instructor" name="instructor" value="<?php echo isset($_POST['instructor']) ? htmlspecialchars($_POST['instructor']) : ''; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Description <span class="text-danger">*</span></label>
                                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                <div class="invalid-feedback">
                                    Please provide a description.
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="prerequisites" class="form-label">Prerequisites</label>
                                    <textarea class="form-control" id="prerequisites" name="prerequisites" rows="3"><?php echo isset($_POST['prerequisites']) ? htmlspecialchars($_POST['prerequisites']) : ''; ?></textarea>
                                    <div class="form-text">Specify any prerequisites or requirements for students.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="syllabus" class="form-label">Syllabus</label>
                                    <textarea class="form-control" id="syllabus" name="syllabus" rows="3"><?php echo isset($_POST['syllabus']) ? htmlspecialchars($_POST['syllabus']) : ''; ?></textarea>
                                    <div class="form-text">Outline the program curriculum or topics to be covered.</div>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">Start Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo isset($_POST['start_date']) ? htmlspecialchars($_POST['start_date']) : ''; ?>" required>
                                    <div class="invalid-feedback">
                                        Please select a start date.
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">End Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo isset($_POST['end_date']) ? htmlspecialchars($_POST['end_date']) : ''; ?>" required>
                                    <div class="invalid-feedback">
                                        Please select an end date.
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="duration" class="form-label">Duration <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="duration" name="duration" placeholder="e.g. 2 Months" value="<?php echo isset($_POST['duration']) ? htmlspecialchars($_POST['duration']) : ''; ?>" required>
                                    <div class="invalid-feedback">
                                        Please specify the duration.
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo (isset($_POST['status']) && $_POST['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                                        <option value="inactive" <?php echo (isset($_POST['status']) && $_POST['status'] === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Please select a status.
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="location" name="location" placeholder="e.g. Computer Lab 1" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" required>
                                <div class="invalid-feedback">
                                    Please specify the location.
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="training-programs.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-plus-circle me-1"></i> Create Training Program
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Form validation script -->
    <script>
        (function () {
            'use strict'
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
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
        
        // Date validation
        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = new Date(document.getElementById('start_date').value);
            const endDate = new Date(this.value);
            
            if (startDate > endDate) {
                this.setCustomValidity('End date must be after start date');
            } else {
                this.setCustomValidity('');
            }
        });
        
        document.getElementById('start_date').addEventListener('change', function() {
            const endDateInput = document.getElementById('end_date');
            if (endDateInput.value) {
                const startDate = new Date(this.value);
                const endDate = new Date(endDateInput.value);
                
                if (startDate > endDate) {
                    endDateInput.setCustomValidity('End date must be after start date');
                } else {
                    endDateInput.setCustomValidity('');
                }
            }
        });
    </script>
</body>
</html> 