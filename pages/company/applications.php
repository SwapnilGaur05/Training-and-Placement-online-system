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

// Handle application status update
if (isset($_POST['update_status']) && isset($_POST['application_id']) && isset($_POST['new_status'])) {
    $applicationId = (int)$_POST['application_id'];
    $newStatus = $_POST['new_status'];
    $validStatuses = ['Applied', 'Shortlisted', 'Interview', 'Selected', 'Rejected'];
    
    if (in_array($newStatus, $validStatuses)) {
        // Only allow updating applications for jobs posted by this company
        $updateQuery = "UPDATE applications a 
                      JOIN job_postings j ON a.job_id = j.id 
                      SET a.status = ? 
                      WHERE a.id = ? AND j.company_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("sii", $newStatus, $applicationId, $company['id']);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Application status updated successfully to " . $newStatus;
        } else {
            $_SESSION['error'] = "Failed to update application status: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = "Invalid status selected.";
    }
    
    // Redirect to maintain any existing query parameters
    $redirect = 'applications.php';
    if (!empty($_SERVER['QUERY_STRING'])) {
        $redirect .= '?' . $_SERVER['QUERY_STRING'];
    }
    header("Location: $redirect");
    exit;
}

// Handle bulk actions
if (isset($_POST['bulk_action']) && isset($_POST['selected_applications'])) {
    $action = $_POST['bulk_action'];
    $selectedApplications = $_POST['selected_applications'];
    
    if (in_array($action, ['Shortlisted', 'Interview', 'Selected', 'Rejected'])) {
        // Only update applications for jobs posted by this company
        $updateQuery = "UPDATE applications a 
                       JOIN job_postings j ON a.job_id = j.id 
                       SET a.status = ?, a.updated_at = NOW() 
                       WHERE a.id IN (" . str_repeat('?,', count($selectedApplications) - 1) . '?) 
                       AND j.company_id = ?';
        
        $types = str_repeat('i', count($selectedApplications));
        $params = array_merge([$action], $selectedApplications, [$company['id']]);
        
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param('s' . $types . 'i', ...$params);
        
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
$jobId = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Base query for this company's applications
$query = "SELECT a.*, 
          s.name as student_name, s.roll_number, s.department, s.year_of_passing, s.cgpa, s.resume_path,
          j.title as job_title, j.job_type, j.salary
          FROM applications a
          JOIN students s ON a.student_id = s.id
          JOIN job_postings j ON a.job_id = j.id
          WHERE j.company_id = ?";

$params = [$company['id']];
$types = "i";

// Add search condition
if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.roll_number LIKE ? OR j.title LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= "sss";
}

// Add job filter
if ($jobId > 0) {
    $query .= " AND j.id = ?";
    $params[] = $jobId;
    $types .= "i";
}

// Add status filter
if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
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
    case 'cgpa':
        $query .= " ORDER BY s.cgpa DESC";
        break;
    default: // newest
        $query .= " ORDER BY a.applied_date DESC";
}

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$applications = $stmt->get_result();

// Get company's jobs for filter
$jobsQuery = "SELECT id, title FROM job_postings WHERE company_id = ? ORDER BY title ASC";
$stmt = $conn->prepare($jobsQuery);
$stmt->bind_param("i", $company['id']);
$stmt->execute();
$jobs = $stmt->get_result();

// Get statistics for this company
$statsQuery = "SELECT 
    COUNT(*) as total_applications,
    SUM(CASE WHEN a.status = 'Applied' THEN 1 ELSE 0 END) as new_applications,
    SUM(CASE WHEN a.status = 'Shortlisted' THEN 1 ELSE 0 END) as shortlisted,
    SUM(CASE WHEN a.status = 'Interview' THEN 1 ELSE 0 END) as interview,
    SUM(CASE WHEN a.status = 'Selected' THEN 1 ELSE 0 END) as selected,
    SUM(CASE WHEN a.status = 'Rejected' THEN 1 ELSE 0 END) as rejected
    FROM applications a
    JOIN job_postings j ON a.job_id = j.id
    WHERE j.company_id = ?";
$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("i", $company['id']);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Set page title
$pageTitle = 'Manage Applications';
include '../company/includes/header.php';
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
            <div class="col-md-4">
                <div class="form-floating">
                    <input type="text" class="form-control" id="search" name="search" 
                           value="<?php echo htmlspecialchars($search); ?>" placeholder="Search">
                    <label for="search">Search applications</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-floating">
                    <select class="form-select" id="job_id" name="job_id">
                        <option value="">All Jobs</option>
                        <?php while ($job = $jobs->fetch_assoc()): ?>
                            <option value="<?php echo $job['id']; ?>" 
                                    <?php echo $jobId == $job['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($job['title']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <label for="job_id">Job</label>
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
                                        <a href="../../<?php echo $application['resume_path']; ?>" 
                                           class="btn btn-sm btn-primary" target="_blank" title="View Resume">
                                            <i class="fas fa-file-pdf me-1"></i> View Resume
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled>
                                            <i class="fas fa-file-pdf me-1"></i> No Resume
                                        </button>
                                    <?php endif; ?>
                                    <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" 
                                            data-bs-toggle="dropdown" aria-expanded="false" title="Update Status">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <?php if ($application['status'] !== 'Shortlisted'): ?>
                                        <li>
                                            <form method="POST" class="status-update-form">
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
                                            <form method="POST" class="status-update-form">
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
                                            <form method="POST" class="status-update-form">
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
                                            <form method="POST" class="status-update-form">
                                                <input type="hidden" name="application_id" value="<?php echo $application['id']; ?>">
                                                <input type="hidden" name="new_status" value="Rejected">
                                                <button type="submit" name="update_status" class="dropdown-item text-danger">
                                                    <i class="fas fa-times-circle me-2"></i>Reject
                                                </button>
                                            </form>
                                        </li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <a href="view-application.php?id=<?php echo $application['id']; ?>" 
                                               class="dropdown-item">
                                                <i class="fas fa-eye me-2"></i>View Details
                                            </a>
                                        </li>
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
            <p class="mb-3">There are currently no applications matching your criteria.</p>
        </div>
    </div>
<?php endif; ?>

<!-- Add this script to initialize dropdowns and handle checkboxes -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Ensure Bootstrap dropdowns are working
        var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
        var dropdownList = dropdownElementList.map(function (dropdownToggleEl) {
            return new bootstrap.Dropdown(dropdownToggleEl);
        });

        // Handle "Select All" checkbox
        const selectAllCheckbox = document.getElementById('selectAll');
        const applicationCheckboxes = document.querySelectorAll('.application-checkbox');
        const applyBulkButton = document.getElementById('applyBulkAction');
        
        selectAllCheckbox.addEventListener('change', function() {
            applicationCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateBulkActionButton();
        });
        
        applicationCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateBulkActionButton();
                
                // Update "Select All" checkbox state
                let allChecked = true;
                applicationCheckboxes.forEach(cb => {
                    if (!cb.checked) allChecked = false;
                });
                selectAllCheckbox.checked = allChecked;
            });
        });
        
        function updateBulkActionButton() {
            let checkedCount = 0;
            applicationCheckboxes.forEach(checkbox => {
                if (checkbox.checked) checkedCount++;
            });
            
            applyBulkButton.disabled = checkedCount === 0;
            if (checkedCount > 0) {
                applyBulkButton.textContent = `Apply (${checkedCount})`;
            } else {
                applyBulkButton.textContent = 'Apply';
            }
        }
        
        // Add confirmation for status changes
        const statusForms = document.querySelectorAll('.status-update-form');
        statusForms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevent default form submission
                const newStatus = form.querySelector('input[name="new_status"]').value;
                
                if (confirm(`Are you sure you want to change the application status to "${newStatus}"?`)) {
                    // If confirmed, manually submit the form
                    const formData = new FormData(form);
                    
                    // Create a new hidden form to submit the data
                    const submitForm = document.createElement('form');
                    submitForm.method = 'POST';
                    submitForm.style.display = 'none';
                    
                    // Add all form data
                    for (const [key, value] of formData.entries()) {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = key;
                        input.value = value;
                        submitForm.appendChild(input);
                    }
                    
                    // Add the update_status field
                    const updateStatusInput = document.createElement('input');
                    updateStatusInput.type = 'hidden';
                    updateStatusInput.name = 'update_status';
                    updateStatusInput.value = '1';
                    submitForm.appendChild(updateStatusInput);
                    
                    // Submit the form
                    document.body.appendChild(submitForm);
                    submitForm.submit();
                }
            });
        });
        
        // Fix for dropdown items in Bootstrap 5
        document.querySelectorAll('.dropdown-menu form').forEach(form => {
            form.addEventListener('click', function(e) {
                e.stopPropagation(); // Prevent dropdown from closing when clicking form elements
            });
        });
        
        // Auto-submit filter forms when filter options change
        document.querySelectorAll('select:not([name="bulk_action"])').forEach(select => {
            select.addEventListener('change', function() {
                select.closest('form').submit();
            });
        });
    });
    
    // Handle export to Excel function
    function exportToExcel() {
        // Create a form and submit it to a dedicated export endpoint
        const exportForm = document.createElement('form');
        exportForm.method = 'POST';
        exportForm.action = 'export-applications.php';
        
        // Add current filters to the export
        const urlParams = new URLSearchParams(window.location.search);
        for (const [key, value] of urlParams) {
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = key;
            hiddenField.value = value;
            exportForm.appendChild(hiddenField);
        }
        
        document.body.appendChild(exportForm);
        exportForm.submit();
        document.body.removeChild(exportForm);
    }
</script>

<?php include '../company/includes/footer.php'; ?> 