<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Get student ID
$userId = $_SESSION['user_id'];
$studentQuery = "SELECT id FROM students WHERE user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$studentResult = $stmt->get_result();
$student = $studentResult->fetch_assoc();
$studentId = $student['id'];

// Initialize filters
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build the query
$query = "SELECT a.*, j.title as job_title, j.job_type, j.location, j.salary,
          c.name as company_name, c.website as company_website
          FROM applications a
          JOIN job_postings j ON a.job_id = j.id
          JOIN companies c ON j.company_id = c.id
          WHERE a.student_id = ?";

$params = [$studentId];
$types = "i";

// Add status filter
if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Add search filter
if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR c.name LIKE ? OR j.location LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

// Add sorting
$query .= " ORDER BY " . ($sort === 'oldest' ? 'a.applied_date ASC' : 
           ($sort === 'company' ? 'c.name ASC' : 
           ($sort === 'status' ? 'a.status ASC' : 'a.applied_date DESC')));

// Prepare and execute the query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Get application statistics
$statsQuery = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'Applied' THEN 1 ELSE 0 END) as applied,
    SUM(CASE WHEN status = 'Shortlisted' THEN 1 ELSE 0 END) as shortlisted,
    SUM(CASE WHEN status = 'Interview' THEN 1 ELSE 0 END) as interview,
    SUM(CASE WHEN status = 'Selected' THEN 1 ELSE 0 END) as selected,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM applications WHERE student_id = ?";
$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("i", $studentId);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Set page title
$pageTitle = 'My Applications';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">My Applications</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <a href="jobs.php" class="btn btn-primary">
                        <i class="fas fa-briefcase me-1"></i> Browse Jobs
                    </a>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row row-cols-1 row-cols-md-3 row-cols-xl-6 g-4 mb-4">
                <div class="col">
                    <div class="card text-white bg-primary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Total</h6>
                                    <h2 class="my-2"><?php echo $stats['total']; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-file-alt fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card text-white bg-info h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Applied</h6>
                                    <h2 class="my-2"><?php echo $stats['applied']; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-paper-plane fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card text-white bg-warning h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Shortlisted</h6>
                                    <h2 class="my-2"><?php echo $stats['shortlisted']; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-list-check fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card text-white bg-secondary h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Interview</h6>
                                    <h2 class="my-2"><?php echo $stats['interview']; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-users fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card text-white bg-success h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Selected</h6>
                                    <h2 class="my-2"><?php echo $stats['selected']; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-check-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col">
                    <div class="card text-white bg-danger h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="card-title mb-0">Rejected</h6>
                                    <h2 class="my-2"><?php echo $stats['rejected']; ?></h2>
                                </div>
                                <div>
                                    <i class="fas fa-times-circle fa-2x"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search and Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="applications.php" method="get" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search jobs or companies..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-filter"></i></span>
                                <select name="status" class="form-select" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="Applied" <?php echo $status === 'Applied' ? 'selected' : ''; ?>>Applied</option>
                                    <option value="Shortlisted" <?php echo $status === 'Shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                                    <option value="Interview" <?php echo $status === 'Interview' ? 'selected' : ''; ?>>Interview</option>
                                    <option value="Selected" <?php echo $status === 'Selected' ? 'selected' : ''; ?>>Selected</option>
                                    <option value="Rejected" <?php echo $status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-sort"></i></span>
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                    <option value="company" <?php echo $sort === 'company' ? 'selected' : ''; ?>>Company Name</option>
                                    <option value="status" <?php echo $sort === 'status' ? 'selected' : ''; ?>>Application Status</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <?php if (!empty($search) || !empty($status)): ?>
                                <a href="applications.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-times me-1"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Applications List -->
            <?php if ($result && $result->num_rows > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Job Title</th>
                                <th>Company</th>
                                <th>Type</th>
                                <th>Location</th>
                                <th>Applied Date</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($application = $result->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo htmlspecialchars($application['job_title']); ?></strong>
                                        <?php if ($application['salary']): ?>
                                            <br>
                                            <small class="text-muted">
                                                <i class="fas fa-money-bill-wave me-1"></i>
                                                <?php echo htmlspecialchars($application['salary']); ?>
                                            </small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($application['company_website'])): ?>
                                            <a href="<?php echo htmlspecialchars($application['company_website']); ?>" target="_blank">
                                                <?php echo htmlspecialchars($application['company_name']); ?>
                                            </a>
                                        <?php else: ?>
                                            <?php echo htmlspecialchars($application['company_name']); ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php echo strtolower($application['job_type']) === 'full-time' ? 'primary' : (strtolower($application['job_type']) === 'part-time' ? 'warning' : 'info'); ?>">
                                            <?php echo htmlspecialchars($application['job_type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($application['location']); ?>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($application['applied_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $application['status'] === 'Applied' ? 'info' : 
                                                ($application['status'] === 'Shortlisted' ? 'warning' : 
                                                ($application['status'] === 'Interview' ? 'primary' : 
                                                ($application['status'] === 'Selected' ? 'success' : 
                                                ($application['status'] === 'Rejected' ? 'danger' : 'secondary')))); 
                                        ?>">
                                            <?php echo htmlspecialchars($application['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('M d, Y', strtotime($application['updated_at'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view-job.php?id=<?php echo $application['job_id']; ?>" class="btn btn-sm btn-outline-primary" title="View Job Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="view-resume.php?id=<?php echo $studentId; ?>" class="btn btn-sm btn-outline-info" title="View Resume" target="_blank">
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
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php 
                    if (!empty($search) || !empty($status)) {
                        echo 'No applications found matching your criteria.';
                    } else {
                        echo 'You haven\'t applied to any jobs yet. <a href="jobs.php" class="alert-link">Browse available jobs</a>';
                    }
                    ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Auto-submit form when filters change
document.querySelectorAll('select[name="status"], select[name="sort"]').forEach(function(select) {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});
</script>

<?php
// Add success/error message display
if (isset($_SESSION['success'])): ?>
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
 