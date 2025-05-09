<?php
session_start();
require_once '../../config/db_connect.php';

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

// Check if job ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Job ID not provided.";
    header('Location: jobs.php');
    exit;
}

// Get job details
$jobId = $_GET['id'];
$query = "SELECT * FROM job_postings WHERE id = ? AND company_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $jobId, $company['id']);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    $_SESSION['error'] = "Job posting not found or access denied.";
    header('Location: jobs.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $location = trim($_POST['location']);
    $job_type = $_POST['job_type'];
    $salary = trim($_POST['salary']);
    $deadline = $_POST['deadline'];
    
    $errors = [];
    
    // Validation
    if (empty($title)) {
        $errors[] = "Job title is required.";
    }
    if (empty($description)) {
        $errors[] = "Job description is required.";
    }
    if (empty($requirements)) {
        $errors[] = "Job requirements are required.";
    }
    if (empty($location)) {
        $errors[] = "Job location is required.";
    }
    if (empty($deadline)) {
        $errors[] = "Application deadline is required.";
    } elseif (strtotime($deadline) < time()) {
        $errors[] = "Application deadline must be a future date.";
    }
    
    if (empty($errors)) {
        $updateQuery = "UPDATE job_postings SET 
                       title = ?, 
                       description = ?, 
                       requirements = ?, 
                       location = ?, 
                       job_type = ?, 
                       salary = ?, 
                       deadline = ?,
                       updated_at = CURRENT_TIMESTAMP 
                       WHERE id = ? AND company_id = ?";
                       
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssssii", 
            $title, 
            $description, 
            $requirements, 
            $location, 
            $job_type, 
            $salary, 
            $deadline, 
            $jobId, 
            $company['id']
        );
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Job posting updated successfully.";
            header("Location: view-job.php?id=" . $jobId);
            exit;
        } else {
            $errors[] = "Failed to update job posting. Please try again.";
        }
    }
}

// Set page title
$pageTitle = 'Edit Job';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Job Posting</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Job Details
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php foreach ($errors as $error): ?>
        <div><?php echo $error; ?></div>
    <?php endforeach; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Edit Job Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="title" class="form-label">Job Title</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo htmlspecialchars($job['title']); ?>" required>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" 
                           value="<?php echo htmlspecialchars($job['location']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="job_type" class="form-label">Job Type</label>
                    <select class="form-select" id="job_type" name="job_type" required>
                        <option value="Full-time" <?php echo $job['job_type'] === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                        <option value="Part-time" <?php echo $job['job_type'] === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                        <option value="Internship" <?php echo $job['job_type'] === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="salary" class="form-label">Salary (Optional)</label>
                    <input type="text" class="form-control" id="salary" name="salary" 
                           value="<?php echo htmlspecialchars($job['salary']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="deadline" class="form-label">Application Deadline</label>
                    <input type="date" class="form-control" id="deadline" name="deadline" 
                           value="<?php echo date('Y-m-d', strtotime($job['deadline'])); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Job Description</label>
                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($job['description']); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="requirements" class="form-label">Requirements</label>
                <textarea class="form-control" id="requirements" name="requirements" rows="5" required><?php echo htmlspecialchars($job['requirements']); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Job</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 
session_start();
require_once '../../config/db_connect.php';

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

// Check if job ID is provided
if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Job ID not provided.";
    header('Location: jobs.php');
    exit;
}

// Get job details
$jobId = $_GET['id'];
$query = "SELECT * FROM job_postings WHERE id = ? AND company_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $jobId, $company['id']);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    $_SESSION['error'] = "Job posting not found or access denied.";
    header('Location: jobs.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $requirements = trim($_POST['requirements']);
    $location = trim($_POST['location']);
    $job_type = $_POST['job_type'];
    $salary = trim($_POST['salary']);
    $deadline = $_POST['deadline'];
    
    $errors = [];
    
    // Validation
    if (empty($title)) {
        $errors[] = "Job title is required.";
    }
    if (empty($description)) {
        $errors[] = "Job description is required.";
    }
    if (empty($requirements)) {
        $errors[] = "Job requirements are required.";
    }
    if (empty($location)) {
        $errors[] = "Job location is required.";
    }
    if (empty($deadline)) {
        $errors[] = "Application deadline is required.";
    } elseif (strtotime($deadline) < time()) {
        $errors[] = "Application deadline must be a future date.";
    }
    
    if (empty($errors)) {
        $updateQuery = "UPDATE job_postings SET 
                       title = ?, 
                       description = ?, 
                       requirements = ?, 
                       location = ?, 
                       job_type = ?, 
                       salary = ?, 
                       deadline = ?,
                       updated_at = CURRENT_TIMESTAMP 
                       WHERE id = ? AND company_id = ?";
                       
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sssssssii", 
            $title, 
            $description, 
            $requirements, 
            $location, 
            $job_type, 
            $salary, 
            $deadline, 
            $jobId, 
            $company['id']
        );
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Job posting updated successfully.";
            header("Location: view-job.php?id=" . $jobId);
            exit;
        } else {
            $errors[] = "Failed to update job posting. Please try again.";
        }
    }
}

// Set page title
$pageTitle = 'Edit Job';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Edit Job Posting</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Job Details
        </a>
    </div>
</div>

<?php if (!empty($errors)): ?>
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <?php foreach ($errors as $error): ?>
        <div><?php echo $error; ?></div>
    <?php endforeach; ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Edit Job Form -->
<div class="card">
    <div class="card-body">
        <form method="POST" action="">
            <div class="mb-3">
                <label for="title" class="form-label">Job Title</label>
                <input type="text" class="form-control" id="title" name="title" 
                       value="<?php echo htmlspecialchars($job['title']); ?>" required>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="location" class="form-label">Location</label>
                    <input type="text" class="form-control" id="location" name="location" 
                           value="<?php echo htmlspecialchars($job['location']); ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="job_type" class="form-label">Job Type</label>
                    <select class="form-select" id="job_type" name="job_type" required>
                        <option value="Full-time" <?php echo $job['job_type'] === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                        <option value="Part-time" <?php echo $job['job_type'] === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                        <option value="Internship" <?php echo $job['job_type'] === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                    </select>
                </div>
            </div>
            
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="salary" class="form-label">Salary (Optional)</label>
                    <input type="text" class="form-control" id="salary" name="salary" 
                           value="<?php echo htmlspecialchars($job['salary']); ?>">
                </div>
                <div class="col-md-6">
                    <label for="deadline" class="form-label">Application Deadline</label>
                    <input type="date" class="form-control" id="deadline" name="deadline" 
                           value="<?php echo date('Y-m-d', strtotime($job['deadline'])); ?>" required>
                </div>
            </div>
            
            <div class="mb-3">
                <label for="description" class="form-label">Job Description</label>
                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($job['description']); ?></textarea>
            </div>
            
            <div class="mb-3">
                <label for="requirements" class="form-label">Requirements</label>
                <textarea class="form-control" id="requirements" name="requirements" rows="5" required><?php echo htmlspecialchars($job['requirements']); ?></textarea>
            </div>
            
            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-secondary me-md-2">Cancel</a>
                <button type="submit" class="btn btn-primary">Update Job</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 