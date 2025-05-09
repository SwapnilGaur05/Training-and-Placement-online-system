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

// Handle job deletion
if (isset($_POST['delete_job']) && isset($_POST['job_id'])) {
    $jobId = $_POST['job_id'];
    $deleteQuery = "DELETE FROM job_postings WHERE id = ? AND company_id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("ii", $jobId, $company['id']);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Job posting deleted successfully.";
    } else {
        $_SESSION['error'] = "Failed to delete job posting.";
    }
    header('Location: jobs.php');
    exit;
}

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$jobType = isset($_GET['job_type']) ? $_GET['job_type'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Base query
$query = "SELECT j.*, 
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as total_applications 
          FROM job_postings j 
          WHERE j.company_id = ?";
$params = [$company['id']];
$types = "i";

// Add search condition
if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.location LIKE ? OR j.description LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= "sss";
}

// Add job type filter
if (!empty($jobType)) {
    $query .= " AND j.job_type = ?";
    $params[] = $jobType;
    $types .= "s";
}

// Add status filter
if ($status === 'active') {
    $query .= " AND j.deadline >= CURRENT_DATE()";
} elseif ($status === 'expired') {
    $query .= " AND j.deadline < CURRENT_DATE()";
}

// Add sorting
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY j.created_at ASC";
        break;
    case 'deadline':
        $query .= " ORDER BY j.deadline ASC";
        break;
    case 'applications':
        $query .= " ORDER BY total_applications DESC";
        break;
    default: // newest
        $query .= " ORDER BY j.created_at DESC";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$jobs = $stmt->get_result();

// Get total counts for statistics
$statsQuery = "SELECT 
    COUNT(*) as total_jobs,
    SUM(CASE WHEN deadline >= CURRENT_DATE() THEN 1 ELSE 0 END) as active_jobs,
    SUM(CASE WHEN deadline < CURRENT_DATE() THEN 1 ELSE 0 END) as expired_jobs,
    (SELECT COUNT(*) FROM applications a WHERE a.job_id IN (SELECT id FROM job_postings WHERE company_id = ?)) as total_applications
    FROM job_postings 
    WHERE company_id = ?";
$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("ii", $company['id'], $company['id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Set page title
$pageTitle = 'Job Postings';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Job Postings</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <a href="post-job.php" class="btn btn-primary">
            <i class="fas fa-plus me-2"></i>Post New Job
        </a>
    </div>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Jobs</h5>
                <h2 class="card-text"><?php echo $stats['total_jobs']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <h5 class="card-title">Active Jobs</h5>
                <h2 class="card-text"><?php echo $stats['active_jobs']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <h5 class="card-title">Expired Jobs</h5>
                <h2 class="card-text"><?php echo $stats['expired_jobs']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-info">
            <div class="card-body">
                <h5 class="card-title">Total Applications</h5>
                <h2 class="card-text"><?php echo $stats['total_applications']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="form-floating">
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search jobs" value="<?php echo htmlspecialchars($search); ?>">
                    <label for="search">Search jobs</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="job_type" name="job_type">
                        <option value="">All Types</option>
                        <option value="Full-time" <?php echo $jobType === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                        <option value="Part-time" <?php echo $jobType === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                        <option value="Internship" <?php echo $jobType === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                    </select>
                    <label for="job_type">Job Type</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                        <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                    </select>
                    <label for="status">Status</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="sort" name="sort">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="deadline" <?php echo $sort === 'deadline' ? 'selected' : ''; ?>>Deadline</option>
                        <option value="applications" <?php echo $sort === 'applications' ? 'selected' : ''; ?>>Most Applications</option>
                    </select>
                    <label for="sort">Sort By</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 h-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Jobs List -->
<?php if ($jobs->num_rows > 0): ?>
    <div class="row">
        <?php while ($job = $jobs->fetch_assoc()): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-<?php 
                                    echo $job['job_type'] === 'Full-time' ? 'primary' : 
                                        ($job['job_type'] === 'Part-time' ? 'warning' : 'info'); 
                                ?> mb-2"><?php echo $job['job_type']; ?></span>
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($job['title']); ?></h5>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars($job['location']); ?>
                                </p>
                            </div>
                            <span class="badge bg-<?php echo strtotime($job['deadline']) < time() ? 'danger' : 'success'; ?>">
                                <?php echo strtotime($job['deadline']) < time() ? 'Expired' : 'Active'; ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-2"></i>Posted: <?php echo date('M d, Y', strtotime($job['created_at'])); ?>
                            </small>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-clock me-2"></i>Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?>
                            </small>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-users me-2"></i>Applications: <?php echo $job['total_applications']; ?>
                            </small>
                        </div>
                        
                        <div class="d-flex justify-content-end">
                            <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary me-2">
                                <i class="fas fa-eye me-2"></i>View Details
                            </a>
                            <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-edit me-2"></i>Edit
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <h4 class="text-muted mb-3">No Job Postings Found</h4>
            <p class="mb-3">Start by posting your first job opening.</p>
            <a href="post-job.php" class="btn btn-primary">
                <i class="fas fa-plus me-2"></i>Post New Job
            </a>
        </div>
    </div>
<?php endif; ?>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the job posting "<span id="jobTitle"></span>"?</p>
                <p class="text-danger">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <form method="POST">
                    <input type="hidden" name="job_id" id="deleteJobId">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="delete_job" class="btn btn-danger">Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Auto-submit form when filters change
    document.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', () => {
            document.querySelector('form').submit();
        });
    });

    // Delete confirmation
    function confirmDelete(jobId, jobTitle) {
        document.getElementById('deleteJobId').value = jobId;
        document.getElementById('jobTitle').textContent = jobTitle;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
</script>

<?php include 'includes/footer.php'; ?> 