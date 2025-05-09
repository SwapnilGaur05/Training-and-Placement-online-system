<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: ../login.php');
    exit;
}

// Get company information
$userId = $_SESSION['user_id'];
$companyQuery = "SELECT c.* FROM companies c WHERE c.user_id = ?";
$stmt = $conn->prepare($companyQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

if (!$company) {
    header('Location: ../logout.php');
    exit;
}

$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $requirements = trim($_POST['requirements'] ?? '');
    $location = trim($_POST['location'] ?? '');
    $jobType = $_POST['job_type'] ?? '';
    $salary = trim($_POST['salary'] ?? '');
    $deadline = $_POST['deadline'] ?? '';

    // Validate form data
    if (empty($title)) {
        $errors[] = "Job title is required";
    }
    if (empty($description)) {
        $errors[] = "Job description is required";
    }
    if (empty($requirements)) {
        $errors[] = "Job requirements are required";
    }
    if (empty($location)) {
        $errors[] = "Job location is required";
    }
    if (empty($jobType)) {
        $errors[] = "Job type is required";
    }
    if (empty($deadline)) {
        $errors[] = "Application deadline is required";
    } elseif (strtotime($deadline) < strtotime(date('Y-m-d'))) {
        $errors[] = "Application deadline cannot be in the past";
    }

    // If no errors, insert the job posting
    if (empty($errors)) {
        $query = "INSERT INTO job_postings (company_id, title, description, requirements, location, job_type, salary, deadline, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("isssssss", 
            $company['id'],
            $title,
            $description,
            $requirements,
            $location,
            $jobType,
            $salary,
            $deadline
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Job posting created successfully!";
            header('Location: jobs.php');
            exit;
        } else {
            $errors[] = "Failed to create job posting. Please try again.";
        }
    }
}

// Set page title
$pageTitle = 'Post New Job';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Post New Job</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="jobs.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Jobs
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php foreach ($errors as $error): ?>
            <div><?php echo $error; ?></div>
        <?php endforeach; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Job Posting Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" class="needs-validation" novalidate>
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="title" name="title" placeholder="Job Title" 
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>" required>
                        <label for="title">Job Title</label>
                        <div class="invalid-feedback">Please provide a job title.</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="location" name="location" placeholder="Job Location"
                               value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>" required>
                        <label for="location">Location</label>
                        <div class="invalid-feedback">Please provide a job location.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <select class="form-select" id="job_type" name="job_type" required>
                            <option value="" disabled <?php echo !isset($_POST['job_type']) ? 'selected' : ''; ?>>Select job type</option>
                            <option value="Full-time" <?php echo isset($_POST['job_type']) && $_POST['job_type'] === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                            <option value="Part-time" <?php echo isset($_POST['job_type']) && $_POST['job_type'] === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                            <option value="Internship" <?php echo isset($_POST['job_type']) && $_POST['job_type'] === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                        </select>
                        <label for="job_type">Job Type</label>
                        <div class="invalid-feedback">Please select a job type.</div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="salary" name="salary" placeholder="Salary Range"
                               value="<?php echo isset($_POST['salary']) ? htmlspecialchars($_POST['salary']) : ''; ?>">
                        <label for="salary">Salary Range (Optional)</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-floating mb-3">
                        <input type="date" class="form-control" id="deadline" name="deadline" 
                               min="<?php echo date('Y-m-d'); ?>"
                               value="<?php echo isset($_POST['deadline']) ? $_POST['deadline'] : ''; ?>" required>
                        <label for="deadline">Application Deadline</label>
                        <div class="invalid-feedback">Please provide a valid application deadline.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="description" name="description" placeholder="Job Description" 
                                  style="height: 200px" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <label for="description">Job Description</label>
                        <div class="invalid-feedback">Please provide a job description.</div>
                    </div>
                </div>
                <div class="col-12">
                    <div class="form-floating mb-3">
                        <textarea class="form-control" id="requirements" name="requirements" placeholder="Job Requirements" 
                                  style="height: 200px" required><?php echo isset($_POST['requirements']) ? htmlspecialchars($_POST['requirements']) : ''; ?></textarea>
                        <label for="requirements">Job Requirements</label>
                        <div class="invalid-feedback">Please provide job requirements.</div>
                    </div>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Post Job
                    </button>
                    <a href="jobs.php" class="btn btn-secondary">
                        <i class="fas fa-times me-2"></i>Cancel
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
// Form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault()
                event.stopPropagation()
            }
            form.classList.add('was-validated')
        }, false)
    })
})()

// Set minimum date for deadline
document.getElementById('deadline').min = new Date().toISOString().split('T')[0];
</script>

<?php include 'includes/footer.php'; ?> 