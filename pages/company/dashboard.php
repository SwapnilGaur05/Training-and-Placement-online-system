<?php
session_start();
require_once '../../config/db_connect.php';

// Debug information
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    // Debug information
    error_log("Login check failed - User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'not set'));
    error_log("User Type: " . (isset($_SESSION['user_type']) ? $_SESSION['user_type'] : 'not set'));
    header('Location: ../login.php');
    exit;
}

// Get company information
$userId = $_SESSION['user_id'];
$companyQuery = "SELECT c.* FROM companies c WHERE c.user_id = ?";
$stmt = $conn->prepare($companyQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$companyResult = $stmt->get_result();

if ($companyResult->num_rows === 0) {
    // Debug information
    error_log("Company not found for user ID: " . $userId);
    header('Location: ../logout.php');
    exit;
}

$company = $companyResult->fetch_assoc();

// Get recent job postings
$jobsQuery = "SELECT j.* FROM job_postings j WHERE j.company_id = ? ORDER BY j.created_at DESC LIMIT 5";
$stmt = $conn->prepare($jobsQuery);
$stmt->bind_param("i", $company['id']);
$stmt->execute();
$jobsResult = $stmt->get_result();

// Get recent applications
$applicationsQuery = "SELECT a.*, j.title as job_title, s.name as student_name, s.department, s.year_of_passing 
                     FROM applications a 
                     JOIN job_postings j ON a.job_id = j.id 
                     JOIN students s ON a.student_id = s.id 
                     WHERE j.company_id = ? 
                     ORDER BY a.applied_date DESC LIMIT 10";
$stmt = $conn->prepare($applicationsQuery);
$stmt->bind_param("i", $company['id']);
$stmt->execute();
$applicationsResult = $stmt->get_result();

// Get upcoming events
$eventsQuery = "SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 3";
$eventsResult = $conn->query($eventsQuery);

// Set page title
$pageTitle = 'Dashboard';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Dashboard</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="post-job.php" class="btn btn-sm btn-primary">
            <i class="fas fa-plus me-1"></i> Post New Job
        </a>
    </div>
</div>

<!-- Company Profile Card -->
<div class="row mb-4">
    <div class="col-md-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-2 text-center">
                        <?php if (!empty($company['logo'])): ?>
                            <img src="../../<?php echo htmlspecialchars($company['logo']); ?>" alt="<?php echo htmlspecialchars($company['name']); ?>" class="img-fluid rounded-circle mb-3" style="max-width: 120px; height: 120px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 120px; height: 120px;">
                                <i class="fas fa-building fa-3x text-secondary"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-7">
                        <h4 class="mb-1"><?php echo htmlspecialchars($company['name']); ?></h4>
                        <p class="text-muted mb-2">
                            <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($company['contact_person']); ?>
                        </p>
                        <p class="text-muted mb-2">
                            <i class="fas fa-phone me-2"></i><?php echo isset($company['phone']) ? htmlspecialchars($company['phone']) : 'Not provided'; ?>
                        </p>
                        <?php if (!empty($company['website'])): ?>
                            <p class="text-muted mb-2">
                                <i class="fas fa-globe me-2"></i>
                                <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank">
                                    <?php echo htmlspecialchars($company['website']); ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-3 text-end">
                        <a href="profile.php" class="btn btn-primary">
                            <i class="fas fa-edit me-2"></i>Edit Profile
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-4">
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">Total Job Postings</h6>
                        <h2 class="card-title mb-0">
                            <?php
                            $jobsCountQuery = "SELECT COUNT(*) as total FROM job_postings WHERE company_id = ?";
                            $stmt = $conn->prepare($jobsCountQuery);
                            $stmt->bind_param("i", $company['id']);
                            $stmt->execute();
                            echo $stmt->get_result()->fetch_assoc()['total'];
                            ?>
                        </h2>
                    </div>
                    <div class="icon-shape bg-primary text-white rounded-3 p-3">
                        <i class="fas fa-briefcase"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">Total Applications</h6>
                        <h2 class="card-title mb-0">
                            <?php
                            $applicationsCountQuery = "SELECT COUNT(*) as total FROM applications a 
                                                     JOIN job_postings j ON a.job_id = j.id 
                                                     WHERE j.company_id = ?";
                            $stmt = $conn->prepare($applicationsCountQuery);
                            $stmt->bind_param("i", $company['id']);
                            $stmt->execute();
                            echo $stmt->get_result()->fetch_assoc()['total'];
                            ?>
                        </h2>
                    </div>
                    <div class="icon-shape bg-success text-white rounded-3 p-3">
                        <i class="fas fa-file-alt"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">Shortlisted</h6>
                        <h2 class="card-title mb-0">
                            <?php
                            $shortlistedQuery = "SELECT COUNT(*) as total FROM applications a 
                                               JOIN job_postings j ON a.job_id = j.id 
                                               WHERE j.company_id = ? AND a.status IN ('Shortlisted', 'Interview', 'Selected')";
                            $stmt = $conn->prepare($shortlistedQuery);
                            $stmt->bind_param("i", $company['id']);
                            $stmt->execute();
                            echo $stmt->get_result()->fetch_assoc()['total'];
                            ?>
                        </h2>
                    </div>
                    <div class="icon-shape bg-warning text-white rounded-3 p-3">
                        <i class="fas fa-user-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="card-subtitle mb-1 text-muted">Active Jobs</h6>
                        <h2 class="card-title mb-0">
                            <?php
                            $activeJobsQuery = "SELECT COUNT(*) as total FROM job_postings 
                                              WHERE company_id = ? AND deadline >= CURDATE()";
                            $stmt = $conn->prepare($activeJobsQuery);
                            $stmt->bind_param("i", $company['id']);
                            $stmt->execute();
                            echo $stmt->get_result()->fetch_assoc()['total'];
                            ?>
                        </h2>
                    </div>
                    <div class="icon-shape bg-info text-white rounded-3 p-3">
                        <i class="fas fa-calendar-check"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Job Postings -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Job Postings</h5>
        <a href="job-postings.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body">
        <?php if ($jobsResult && $jobsResult->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Type</th>
                            <th>Location</th>
                            <th>Posted Date</th>
                            <th>Deadline</th>
                            <th>Applications</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($job = $jobsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($job['title']); ?></td>
                                <td><span class="badge bg-<?php echo strtolower($job['job_type']) === 'full-time' ? 'primary' : (strtolower($job['job_type']) === 'part-time' ? 'warning' : 'info'); ?>"><?php echo $job['job_type']; ?></span></td>
                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($job['deadline'])); ?></td>
                                <td>
                                    <?php
                                    $appCountQuery = "SELECT COUNT(*) as count FROM applications WHERE job_id = ?";
                                    $stmt = $conn->prepare($appCountQuery);
                                    $stmt->bind_param("i", $job['id']);
                                    $stmt->execute();
                                    echo $stmt->get_result()->fetch_assoc()['count'];
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                <p class="text-muted">You haven't posted any jobs yet.</p>
                <a href="post-job.php" class="btn btn-primary">Post Your First Job</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Recent Applications -->
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Recent Applications</h5>
        <a href="applications.php" class="btn btn-sm btn-outline-primary">View All</a>
    </div>
    <div class="card-body">
        <?php if ($applicationsResult && $applicationsResult->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Student Name</th>
                            <th>Job Title</th>
                            <th>Department</th>
                            <th>Applied Date</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($app = $applicationsResult->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($app['student_name']); ?></td>
                                <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                <td><?php echo htmlspecialchars($app['department']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($app['applied_date'])); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $app['status'] === 'Applied' ? 'info' : 
                                            ($app['status'] === 'Shortlisted' ? 'warning' : 
                                            ($app['status'] === 'Interview' ? 'primary' : 
                                            ($app['status'] === 'Selected' ? 'success' : 
                                            ($app['status'] === 'Rejected' ? 'danger' : 'secondary')))); 
                                    ?>">
                                        <?php echo $app['status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view-application.php?id=<?php echo $app['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="view-resume.php?id=<?php echo $app['student_id']; ?>" class="btn btn-sm btn-outline-info" title="View Resume" target="_blank">
                                            <i class="fas fa-file-alt"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center py-4">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <p class="text-muted">No applications received yet.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?> 