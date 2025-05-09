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
$query = "SELECT j.*, 
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as total_applications,
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'Applied') as new_applications,
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'Shortlisted') as shortlisted_applications,
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'Interview') as interview_applications,
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.status = 'Selected') as selected_applications
          FROM job_postings j 
          WHERE j.id = ? AND j.company_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $jobId, $company['id']);
$stmt->execute();
$job = $stmt->get_result()->fetch_assoc();

if (!$job) {
    $_SESSION['error'] = "Job posting not found or access denied.";
    header('Location: jobs.php');
    exit;
}

// Get recent applications for this job
$applicationsQuery = "SELECT a.*, s.name as student_name, s.department, s.year_of_passing, s.cgpa 
                     FROM applications a 
                     JOIN students s ON a.student_id = s.id 
                     WHERE a.job_id = ? 
                     ORDER BY a.applied_date DESC 
                     LIMIT 5";
$stmt = $conn->prepare($applicationsQuery);
$stmt->bind_param("i", $jobId);
$stmt->execute();
$recentApplications = $stmt->get_result();

// Handle job deletion
if (isset($_POST['delete_job']) && isset($_POST['job_id'])) {
    $jobId = $_POST['job_id'];
    
    // Verify the job belongs to this company
    $deleteCheck = "SELECT id FROM job_postings WHERE id = ? AND company_id = ?";
    $stmt = $conn->prepare($deleteCheck);
    $stmt->bind_param("ii", $jobId, $company['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Delete related applications first
        $deleteApps = "DELETE FROM applications WHERE job_id = ?";
        $stmt = $conn->prepare($deleteApps);
        $stmt->bind_param("i", $jobId);
        $stmt->execute();
        
        // Delete the job posting
        $deleteJob = "DELETE FROM job_postings WHERE id = ?";
        $stmt = $conn->prepare($deleteJob);
        $stmt->bind_param("i", $jobId);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Job posting has been deleted successfully.";
            header('Location: jobs.php');
            exit;
        } else {
            $_SESSION['error'] = "Failed to delete job posting.";
        }
    } else {
        $_SESSION['error'] = "Access denied or job not found.";
    }
}

// Set page title
$pageTitle = 'View Job';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?php echo htmlspecialchars($job['title']); ?></h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary me-2">
            <i class="fas fa-edit me-2"></i>Edit Job
        </a>
        <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteJobModal">
            <i class="fas fa-trash-alt me-2"></i>Delete Job
        </button>
        <a href="jobs.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left me-2"></i>Back to Jobs
        </a>
    </div>
</div>

<!-- Job Details -->
<div class="row">
    <!-- Main Job Information -->
    <div class="col-md-8">
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <span class="badge bg-<?php 
                            echo $job['job_type'] === 'Full-time' ? 'primary' : 
                                ($job['job_type'] === 'Part-time' ? 'warning' : 'info'); 
                        ?> mb-2"><?php echo $job['job_type']; ?></span>
                        <h4 class="card-title mb-1"><?php echo htmlspecialchars($job['title']); ?></h4>
                        <p class="text-muted mb-0">
                            <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($job['location']); ?>
                            <?php if (!empty($job['salary'])): ?>
                                <span class="mx-2">|</span>
                                <i class="fas fa-money-bill-alt me-2"></i><?php echo htmlspecialchars($job['salary']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="text-end">
                        <p class="text-muted mb-1">Posted on <?php echo date('M d, Y', strtotime($job['created_at'])); ?></p>
                        <p class="<?php echo strtotime($job['deadline']) < time() ? 'text-danger' : 'text-success'; ?> mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?>
                        </p>
                    </div>
                </div>

                <h5 class="card-subtitle mb-3">Job Description</h5>
                <div class="mb-4">
                    <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                </div>

                <h5 class="card-subtitle mb-3">Requirements</h5>
                <div>
                    <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Sidebar with Statistics -->
    <div class="col-md-4">
        <!-- Application Statistics -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Application Statistics</h5>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>Total Applications</div>
                    <div class="h4 mb-0"><?php echo $job['total_applications']; ?></div>
                </div>
                <hr>
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-muted">New</div>
                        <div class="badge bg-info"><?php echo $job['new_applications']; ?></div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-info" style="width: <?php echo $job['total_applications'] > 0 ? ($job['new_applications'] / $job['total_applications'] * 100) : 0; ?>%"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-muted">Shortlisted</div>
                        <div class="badge bg-warning"><?php echo $job['shortlisted_applications']; ?></div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-warning" style="width: <?php echo $job['total_applications'] > 0 ? ($job['shortlisted_applications'] / $job['total_applications'] * 100) : 0; ?>%"></div>
                    </div>
                </div>
                <div class="mb-2">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-muted">Interview</div>
                        <div class="badge bg-primary"><?php echo $job['interview_applications']; ?></div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-primary" style="width: <?php echo $job['total_applications'] > 0 ? ($job['interview_applications'] / $job['total_applications'] * 100) : 0; ?>%"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <div class="text-muted">Selected</div>
                        <div class="badge bg-success"><?php echo $job['selected_applications']; ?></div>
                    </div>
                    <div class="progress" style="height: 5px;">
                        <div class="progress-bar bg-success" style="width: <?php echo $job['total_applications'] > 0 ? ($job['selected_applications'] / $job['total_applications'] * 100) : 0; ?>%"></div>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center">
                <a href="view-applications.php?job_id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-users me-2"></i>View All Applications
                </a>
            </div>
        </div>

        <!-- Recent Applications -->
        <?php if ($recentApplications->num_rows > 0): ?>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">Recent Applications</h5>
                <a href="view-applications.php?job_id=<?php echo $job['id']; ?>" class="btn btn-sm btn-link">View All</a>
            </div>
            <div class="list-group list-group-flush">
                <?php while ($application = $recentApplications->fetch_assoc()): ?>
                    <div class="list-group-item">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($application['student_name']); ?></h6>
                                <small class="text-muted">
                                    <?php echo htmlspecialchars($application['department']); ?> | 
                                    CGPA: <?php echo number_format($application['cgpa'], 2); ?> |
                                    <?php echo htmlspecialchars($application['year_of_passing']); ?>
                                </small>
                            </div>
                            <span class="badge bg-<?php 
                                echo $application['status'] === 'Applied' ? 'info' : 
                                    ($application['status'] === 'Shortlisted' ? 'warning' : 
                                    ($application['status'] === 'Interview' ? 'primary' : 
                                    ($application['status'] === 'Selected' ? 'success' : 'danger'))); 
                            ?>"><?php echo $application['status']; ?></span>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted">
                                Applied <?php echo date('M d, Y', strtotime($application['applied_date'])); ?>
                            </small>
                            <a href="view-application.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-outline-primary float-end">
                                View Details
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Delete Confirmation Modal at the end of the file before footer -->
<div class="modal fade" id="deleteJobModal" tabindex="-1" aria-labelledby="deleteJobModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteJobModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this job posting? This action cannot be undone.</p>
                <p class="text-danger"><strong>Warning:</strong> All applications for this job will also be deleted.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" class="d-inline">
                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                    <button type="submit" name="delete_job" class="btn btn-danger">Delete Job</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 