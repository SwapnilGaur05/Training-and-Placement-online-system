<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Initialize search variables
$search = isset($_GET['search']) ? $_GET['search'] : '';
$company = isset($_GET['company']) ? $_GET['company'] : '';
$job_type = isset($_GET['job_type']) ? $_GET['job_type'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Base query
$query = "SELECT j.*, c.name as company_name, 
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as application_count 
          FROM job_postings j 
          LEFT JOIN companies c ON j.company_id = c.id 
          WHERE 1=1";
$params = [];
$types = "";

// Add search conditions
if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam, $searchParam]);
    $types .= "ssss";
}

if (!empty($company)) {
    $query .= " AND c.id = ?";
    $params[] = $company;
    $types .= "i";
}

if (!empty($job_type)) {
    $query .= " AND j.job_type = ?";
    $params[] = $job_type;
    $types .= "s";
}

if (!empty($location)) {
    $query .= " AND j.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

if (!empty($status)) {
    if ($status === 'active') {
        $query .= " AND j.deadline >= CURDATE()";
    } else if ($status === 'expired') {
        $query .= " AND j.deadline < CURDATE()";
    }
}

// Add sorting
$query .= " ORDER BY j.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get all companies for filter dropdown
$companiesQuery = "SELECT id, name FROM companies ORDER BY name ASC";
$companiesResult = $conn->query($companiesQuery);

// Get distinct locations for filter dropdown
$locationsQuery = "SELECT DISTINCT location FROM job_postings WHERE location IS NOT NULL AND location != '' ORDER BY location ASC";
$locationsResult = $conn->query($locationsQuery);

// Set page title
$pageTitle = 'Manage Job Postings';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - TPOS</title>
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
                    <h1 class="h2">Manage Job Postings</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add-job.php" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add New Job
                        </a>
                    </div>
                </div>

                <!-- Search and Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="text" class="form-control" name="search" placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <select name="company" class="form-select">
                                    <option value="">All Companies</option>
                                    <?php while ($company = $companiesResult->fetch_assoc()): ?>
                                        <option value="<?php echo $company['id']; ?>" <?php echo isset($_GET['company']) && $_GET['company'] == $company['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($company['name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="job_type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="Full-time" <?php echo $job_type === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                    <option value="Part-time" <?php echo $job_type === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                    <option value="Internship" <?php echo $job_type === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="location" class="form-select">
                                    <option value="">All Locations</option>
                                    <?php while ($loc = $locationsResult->fetch_assoc()): ?>
                                        <option value="<?php echo $loc['location']; ?>" <?php echo $location === $loc['location'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($loc['location']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="expired" <?php echo $status === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Jobs List -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Company</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Applications</th>
                                        <th>Deadline</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($result->num_rows > 0): ?>
                                        <?php while ($job = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($job['title']); ?></td>
                                                <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo strtolower($job['job_type']) === 'full-time' ? 'primary' : (strtolower($job['job_type']) === 'part-time' ? 'warning' : 'info'); ?>">
                                                        <?php echo htmlspecialchars($job['job_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary">
                                                        <?php echo $job['application_count']; ?> applications
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($job['deadline'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $isExpired = strtotime($job['deadline']) < time();
                                                    $statusClass = $isExpired ? 'danger' : 'success';
                                                    $statusText = $isExpired ? 'Expired' : 'Active';
                                                    ?>
                                                    <span class="badge bg-<?php echo $statusClass; ?>">
                                                        <?php echo $statusText; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-danger" title="Delete" 
                                                                onclick="deleteJob(<?php echo $job['id']; ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No job postings found</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
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
        // Auto-submit form when filters change
        document.querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => {
                select.closest('form').submit();
            });
        });

        // Delete job function
        function deleteJob(jobId) {
            if (confirm('Are you sure you want to delete this job posting? This action cannot be undone.')) {
                window.location.href = 'delete-job.php?id=' + jobId;
            }
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })
    </script>
</body>
</html> 