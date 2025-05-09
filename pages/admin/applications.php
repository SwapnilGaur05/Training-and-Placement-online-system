<?php
session_start();
require_once '../../config/db.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get admin information
$userId = $_SESSION['user_id'];
$adminQuery = "SELECT a.* FROM admins a WHERE a.user_id = ?";
$stmt = $conn->prepare($adminQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$adminResult = $stmt->get_result();
if ($adminResult->num_rows === 0) {
    header('Location: ../logout.php');
    exit;
}
$admin = $adminResult->fetch_assoc();

// Handle application status update
if (isset($_POST['update_status']) && isset($_POST['application_id']) && isset($_POST['new_status'])) {
    $applicationId = (int)$_POST['application_id'];
    $newStatus = $_POST['new_status'];
    $validStatuses = ['Applied', 'Shortlisted', 'Interview', 'Selected', 'Rejected'];
    
    if (in_array($newStatus, $validStatuses)) {
        $updateQuery = "UPDATE applications SET status = ?, updated_at = NOW() WHERE id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("si", $newStatus, $applicationId);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Application status updated successfully to " . $newStatus;
        } else {
            $_SESSION['error'] = "Failed to update application status.";
        }
    } else {
        $_SESSION['error'] = "Invalid status selected.";
    }
    
    header('Location: applications.php');
    exit;
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_applications'])) {
    $action = $_POST['bulk_action'];
    $selectedApplications = $_POST['selected_applications'];
    
    if (in_array($action, ['Shortlisted', 'Interview', 'Selected', 'Rejected'])) {
        $updateQuery = "UPDATE applications SET status = ?, updated_at = NOW() WHERE id IN (" . 
                      str_repeat('?,', count($selectedApplications) - 1) . '?)';
        $types = str_repeat('i', count($selectedApplications));
        $params = array_merge([$action], $selectedApplications);
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('s' . $types, ...$params);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Successfully updated " . count($selectedApplications) . " applications.";
        } else {
            $_SESSION['error'] = "Failed to update applications.";
        }
        
        header('Location: applications.php');
        exit;
    }
}

// Handle filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$company = isset($_GET['company']) ? (int)$_GET['company'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Base query
$query = "SELECT a.*, 
          s.name as student_name, s.roll_number, s.department, s.year_of_passing, s.cgpa, s.resume_path,
          j.title as job_title, j.job_type, j.salary,
          c.name as company_name, c.location
          FROM applications a
          JOIN students s ON a.student_id = s.id
          JOIN job_postings j ON a.job_id = j.id
          JOIN companies c ON j.company_id = c.id
          WHERE 1=1";

$params = [];
$types = "";

// Add search condition
if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.roll_number LIKE ? OR j.title LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "ssss";
}

// Add company filter
if ($company > 0) {
    $query .= " AND c.id = ?";
    $params[] = $company;
    $types .= "i";
}

// Add status filter
if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Add department filter
if (!empty($department)) {
    $query .= " AND s.department = ?";
    $params[] = $department;
    $types .= "s";
}

// Add sorting
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY a.applied_date ASC";
        break;
    case 'student':
        $query .= " ORDER BY s.name ASC";
        break;
    case 'company':
        $query .= " ORDER BY c.name ASC";
        break;
    case 'cgpa':
        $query .= " ORDER BY s.cgpa DESC";
        break;
    default: // newest
        $query .= " ORDER BY a.applied_date DESC";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$applications = $stmt->get_result();

// Get companies for filter
$companiesQuery = "SELECT DISTINCT c.id, c.name 
                  FROM companies c 
                  JOIN job_postings j ON c.id = j.company_id 
                  JOIN applications a ON j.id = a.job_id 
                  ORDER BY c.name ASC";
$companies = $conn->query($companiesQuery);

// Get departments for filter
$departmentsQuery = "SELECT DISTINCT department FROM students ORDER BY department ASC";
$departments = $conn->query($departmentsQuery);

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total_applications,
    SUM(CASE WHEN status = 'Applied' THEN 1 ELSE 0 END) as new_applications,
    SUM(CASE WHEN status = 'Shortlisted' THEN 1 ELSE 0 END) as shortlisted,
    SUM(CASE WHEN status = 'Interview' THEN 1 ELSE 0 END) as interview,
    SUM(CASE WHEN status = 'Selected' THEN 1 ELSE 0 END) as selected,
    SUM(CASE WHEN status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM applications";
$stats = $conn->query($statsQuery)->fetch_assoc();

// Set page title
$pageTitle = 'Manage Applications';
include '../admin/includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Manage Applications</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="exportToExcel()">
                <i class="fas fa-download"></i> Export
            </button>
        </div>
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
    <div class="col-md-2">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total</h5>
                <h2 class="card-text"><?php echo $stats['total_applications']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-bg-info">
            <div class="card-body">
                <h5 class="card-title">New</h5>
                <h2 class="card-text"><?php echo $stats['new_applications']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-bg-warning">
            <div class="card-body">
                <h5 class="card-title">Shortlisted</h5>
                <h2 class="card-text"><?php echo $stats['shortlisted']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-bg-secondary">
            <div class="card-body">
                <h5 class="card-title">Interview</h5>
                <h2 class="card-text"><?php echo $stats['interview']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-bg-success">
            <div class="card-body">
                <h5 class="card-title">Selected</h5>
                <h2 class="card-text"><?php echo $stats['selected']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card text-bg-danger">
            <div class="card-body">
                <h5 class="card-title">Rejected</h5>
                <h2 class="card-text"><?php echo $stats['rejected']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <div class="form-floating">
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" placeholder="Search">
                    <label for="search">Search applications</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="company" name="company">
                        <option value="">All Companies</option>
                        <?php while ($row = $companies->fetch_assoc()): ?>
                            <option value="<?php echo $row['id']; ?>" 
                                    <?php echo $company == $row['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($row['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label for="company">Company</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="department" name="department">
                        <option value="">All Departments</option>
                        <?php while ($dept = $departments->fetch_assoc()): ?>
                            <option value="<?php echo $dept['department']; ?>" 
                                    <?php echo $department === $dept['department'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label for="department">Department</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="Applied" <?php echo $status === 'Applied' ? 'selected' : ''; ?>>New</option>
                        <option value="Shortlisted" <?php echo $status === 'Shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                        <option value="Interview" <?php echo $status === 'Interview' ? 'selected' : ''; ?>>Interview</option>
                        <option value="Selected" <?php echo $status === 'Selected' ? 'selected' : ''; ?>>Selected</option>
                        <option value="Rejected" <?php echo $status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <label for="status">Status</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="sort" name="sort">
                        <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                        <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                        <option value="student" <?php echo $sort === 'student' ? 'selected' : ''; ?>>Student Name</option>
                        <option value="company" <?php echo $sort === 'company' ? 'selected' : ''; ?>>Company</option>
                        <option value="cgpa" <?php echo $sort === 'cgpa' ? 'selected' : ''; ?>>CGPA</option>
                    </select>
                    <label for="sort">Sort By</label>
                </div>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-primary w-100 h-100">Filter</button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Actions -->
<div class="mb-3">
    <form method="POST" id="bulkActionForm">
        <div class="row">
            <div class="col-auto">
                <select class="form-select" name="bulk_action" required>
                    <option value="">Bulk Actions</option>
                    <option value="Shortlisted">Shortlist Selected</option>
                    <option value="Interview">Schedule Interview</option>
                    <option value="Selected">Select All</option>
                    <option value="Rejected">Reject All</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-secondary" id="applyBulkAction" disabled>
                    Apply
                </button>
            </div>
        </div>
    </form>
</div>

<!-- Applications List -->
<?php if ($applications->num_rows > 0): ?>
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead>
                    <tr>
                        <th>
                            <input type="checkbox" class="form-check-input" id="selectAll">
                        </th>
                        <th>Student</th>
                        <th>Company</th>
                        <th>Job</th>
                        <th>Applied Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($application = $applications->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <input type="checkbox" class="form-check-input application-checkbox" 
                                       name="selected_applications[]" form="bulkActionForm"
                                       value="<?php echo $application['id']; ?>">
                            </td>
                            <td>
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($application['student_name']); ?></h6>
                                    <small class="text-muted d-block">
                                        <?php echo htmlspecialchars($application['roll_number']); ?> |
                                        <?php echo htmlspecialchars($application['department']); ?> |
                                        CGPA: <?php echo number_format($application['cgpa'], 2); ?>
                                    </small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($application['company_name']); ?></h6>
                                    <small class="text-muted"><?php echo htmlspecialchars($application['location']); ?></small>
                                </div>
                            </td>
                            <td>
                                <div>
                                    <h6 class="mb-1"><?php echo htmlspecialchars($application['job_title']); ?></h6>
                                    <div>
                                        <span class="badge bg-<?php 
                                            echo $application['job_type'] === 'Full-time' ? 'primary' : 
                                                ($application['job_type'] === 'Part-time' ? 'warning' : 'info'); 
                                        ?>"><?php echo $application['job_type']; ?></span>
                                        <?php if (!empty($application['salary'])): ?>
                                            <small class="text-muted ms-2"><?php echo htmlspecialchars($application['salary']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($application['applied_date'])); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    echo $application['status'] === 'Applied' ? 'info' : 
                                        ($application['status'] === 'Shortlisted' ? 'warning' : 
                                        ($application['status'] === 'Interview' ? 'primary' : 
                                        ($application['status'] === 'Selected' ? 'success' : 'danger'))); 
                                ?>"><?php echo $application['status']; ?></span>
                            </td>
                            <td>
                                <div class="btn-group">
                                    <?php if (!empty($application['resume_path'])): ?>
                                        <a href="../student/resumes/<?php echo $application['resume_path']; ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank" title="View Resume">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            data-bs-toggle="dropdown" title="Update Status">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <?php if ($application['status'] !== 'Shortlisted'): ?>
                                        <li>
                                            <form method="POST">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="new_status" value="Shortlisted">
                                                <button type="submit" name="update_status" class="dropdown-item text-warning">
                                                    <i class="fas fa-user-check me-2"></i>Shortlist
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        <?php if ($application['status'] !== 'Interview'): ?>
                                        <li>
                                            <form method="POST">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="new_status" value="Interview">
                                                <button type="submit" name="update_status" class="dropdown-item text-primary">
                                                    <i class="fas fa-users me-2"></i>Schedule Interview
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        <?php if ($application['status'] !== 'Selected'): ?>
                                        <li>
                                            <form method="POST">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="new_status" value="Selected">
                                                <button type="submit" name="update_status" class="dropdown-item text-success">
                                                    <i class="fas fa-check-circle me-2"></i>Select
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        <?php if ($application['status'] !== 'Rejected'): ?>
                                        <li>
                                            <form method="POST">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="new_status" value="Rejected">
                                                <button type="submit" name="update_status" class="dropdown-item text-danger">
                                                    <i class="fas fa-times-circle me-2"></i>Reject
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <h4 class="text-muted mb-3">No Applications Found</h4>
            <p class="mb-0">There are currently no applications matching your criteria.</p>
        </div>
    </div>
<?php endif; ?>

<script>
// Handle select all checkbox
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.application-checkbox').forEach(checkbox => {
        checkbox.checked = this.checked;
    });
    updateBulkActionButton();
});

// Handle individual checkboxes
document.querySelectorAll('.application-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', updateBulkActionButton);
});

// Update bulk action button state
function updateBulkActionButton() {
    const checkedBoxes = document.querySelectorAll('.application-checkbox:checked');
    document.getElementById('applyBulkAction').disabled = checkedBoxes.length === 0;
}

// Auto-submit form when filters change
document.querySelectorAll('select:not([name="bulk_action"])').forEach(select => {
    select.addEventListener('change', () => {
        document.querySelector('form').submit();
    });
});

// Export to Excel function
function exportToExcel() {
    // Create a form with current filters
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'export-applications.php';
    
    // Add current filter values
    const filters = ['search', 'company', 'status', 'department', 'sort'];
    filters.forEach(filter => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = filter;
        input.value = document.getElementById(filter).value;
        form.appendChild(input);
    });
    
    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
}
</script>

<?php include '../admin/includes/footer.php'; ?> 