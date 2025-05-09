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

// Get companies for dropdown
$companies_query = "SELECT id, name FROM companies ORDER BY name ASC";
$companies_result = $conn->query($companies_query);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $company_id = $_POST['company_id'] ?? '';
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $requirements = $_POST['requirements'] ?? '';
    $location = $_POST['location'] ?? '';
    $job_type = $_POST['job_type'] ?? '';
    $salary = $_POST['salary'] ?? '';
    $deadline = $_POST['deadline'] ?? '';

    // Validate required fields
    if (empty($company_id) || empty($title) || empty($description) || empty($requirements) || empty($job_type) || empty($deadline)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            // Insert job posting
            $stmt = $conn->prepare("INSERT INTO job_postings (company_id, title, description, requirements, location, job_type, salary, deadline) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("isssssss", $company_id, $title, $description, $requirements, $location, $job_type, $salary, $deadline);
            
            if ($stmt->execute()) {
                $success_message = "Job posting added successfully!";
                // Clear form data
                $_POST = array();
            } else {
                $error_message = "Error adding job posting: " . $stmt->error;
            }
        } catch (Exception $e) {
            $error_message = "Error adding job posting: " . $e->getMessage();
        }
    }
}

// Set page title
$pageTitle = 'Add New Job Posting';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Job Posting - TPOS</title>
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
                    <h1 class="h2">Add New Job Posting</h1>
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
                            <div class="mb-3">
                                <label for="company_id" class="form-label">Company *</label>
                                <select class="form-select" id="company_id" name="company_id" required>
                                    <option value="">Select Company</option>
                                    <?php while ($company = $companies_result->fetch_assoc()): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php echo (isset($_POST['company_id']) && $_POST['company_id'] == $company['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($company['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="title" class="form-label">Job Title *</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo $_POST['title'] ?? ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Job Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $_POST['description'] ?? ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="requirements" class="form-label">Requirements *</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="4" required><?php echo $_POST['requirements'] ?? ''; ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" value="<?php echo $_POST['location'] ?? ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="job_type" class="form-label">Job Type *</label>
                                    <select class="form-select" id="job_type" name="job_type" required>
                                        <option value="">Select Job Type</option>
                                        <option value="Full-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                                        <option value="Part-time" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                                        <option value="Internship" <?php echo (isset($_POST['job_type']) && $_POST['job_type'] === 'Internship') ? 'selected' : ''; ?>>Internship</option>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="salary" class="form-label">Salary Range</label>
                                    <input type="text" class="form-control" id="salary" name="salary" value="<?php echo $_POST['salary'] ?? ''; ?>" placeholder="e.g., $50,000 - $70,000">
                                </div>
                                <div class="col-md-6">
                                    <label for="deadline" class="form-label">Application Deadline *</label>
                                    <input type="date" class="form-control" id="deadline" name="deadline" 
                                           value="<?php echo $_POST['deadline'] ?? ''; ?>" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-secondary me-md-2" onclick="window.location.href='jobs.php'">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Job Posting</button>
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