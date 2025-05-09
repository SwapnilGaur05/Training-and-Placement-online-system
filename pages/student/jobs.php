<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit;
}

// Get student information
$userId = $_SESSION['user_id'];
$studentQuery = "SELECT s.* FROM students s WHERE s.user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$studentResult = $stmt->get_result();

if ($studentResult->num_rows === 0) {
    header('Location: ../logout.php');
    exit;
}

$student = $studentResult->fetch_assoc();

// Initialize filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$jobType = isset($_GET['job_type']) ? $_GET['job_type'] : '';
$location = isset($_GET['location']) ? $_GET['location'] : '';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 9;
$offset = ($page - 1) * $itemsPerPage;

// Base query for jobs
$query = "SELECT j.*, c.name as company_name, c.location as company_location, 
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as application_count,
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.student_id = ?) as has_applied
          FROM job_postings j
          JOIN companies c ON j.company_id = c.id
          WHERE j.deadline >= CURDATE()";

// Apply search filter
if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ? OR c.name LIKE ?)";
}

// Apply job type filter
if (!empty($jobType)) {
    $query .= " AND j.job_type = ?";
}

// Apply location filter
if (!empty($location)) {
    $query .= " AND j.location LIKE ?";
}

// Apply sorting
switch ($sortBy) {
    case 'salary_high':
        $query .= " ORDER BY j.salary DESC";
        break;
    case 'salary_low':
        $query .= " ORDER BY j.salary ASC";
        break;
    case 'deadline':
        $query .= " ORDER BY j.deadline ASC";
        break;
    case 'applications':
        $query .= " ORDER BY application_count DESC";
        break;
    case 'oldest':
        $query .= " ORDER BY j.created_at ASC";
        break;
    case 'newest':
    default:
        $query .= " ORDER BY j.created_at DESC";
        break;
}

// Add pagination
$query .= " LIMIT ? OFFSET ?";

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM job_postings j 
              JOIN companies c ON j.company_id = c.id 
              WHERE j.deadline >= CURDATE()";
              
// Add filters to count query
if (!empty($search)) {
    $countQuery .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ? OR c.name LIKE ?)";
}
if (!empty($jobType)) {
    $countQuery .= " AND j.job_type = ?";
}
if (!empty($location)) {
    $countQuery .= " AND j.location LIKE ?";
}

$stmt = $conn->prepare($countQuery);

// Bind parameters for count query
$paramTypes = ""; // Start with empty string as we're not using student_id in count query
$paramValues = array();

if (!empty($search)) {
    $paramTypes .= "ssss";
    $searchParam = "%$search%";
    array_push($paramValues, $searchParam, $searchParam, $searchParam, $searchParam);
}
if (!empty($jobType)) {
    $paramTypes .= "s";
    array_push($paramValues, $jobType);
}
if (!empty($location)) {
    $paramTypes .= "s";
    $locationParam = "%$location%";
    array_push($paramValues, $locationParam);
}

// Only bind parameters if there are any to bind
if (!empty($paramTypes)) {
    $stmt->bind_param($paramTypes, ...$paramValues);
}
$stmt->execute();
$totalCount = $stmt->get_result()->fetch_assoc()['total'];
$totalPages = ceil($totalCount / $itemsPerPage);

// Prepare and execute main query
$stmt = $conn->prepare($query);

// Bind parameters for main query
$mainParamTypes = "i"; // Start with student_id parameter
$mainParamValues = array($student['id']);

if (!empty($search)) {
    $mainParamTypes .= "ssss";
    $searchParam = "%$search%";
    array_push($mainParamValues, $searchParam, $searchParam, $searchParam, $searchParam);
}
if (!empty($jobType)) {
    $mainParamTypes .= "s";
    array_push($mainParamValues, $jobType);
}
if (!empty($location)) {
    $mainParamTypes .= "s";
    $locationParam = "%$location%";
    array_push($mainParamValues, $locationParam);
}

// Add LIMIT and OFFSET parameters
$mainParamTypes .= "ii";
array_push($mainParamValues, $itemsPerPage, $offset);

$stmt->bind_param($mainParamTypes, ...$mainParamValues);
$stmt->execute();
$result = $stmt->get_result();

// Get unique locations for filter
$locationQuery = "SELECT DISTINCT location FROM job_postings WHERE location IS NOT NULL ORDER BY location";
$locations = $conn->query($locationQuery)->fetch_all(MYSQLI_ASSOC);

// Set page title
$pageTitle = 'Available Jobs';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Jobs - TPOS</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/student.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <?php
                // Display success/error messages
                if (isset($_SESSION['success'])) {
                    echo '<div class="alert alert-success alert-dismissible fade show mt-3" role="alert">';
                    echo htmlspecialchars($_SESSION['success']);
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                    unset($_SESSION['success']);
                }
                if (isset($_SESSION['error'])) {
                    echo '<div class="alert alert-danger alert-dismissible fade show mt-3" role="alert">';
                    echo htmlspecialchars($_SESSION['error']);
                    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                    echo '</div>';
                    unset($_SESSION['error']);
                }
                ?>
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Available Jobs</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="applications.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list me-1"></i> My Applications
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="" class="row g-3" id="filterForm">
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" name="search" placeholder="Search jobs..." value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-select" name="job_type">
                                            <option value="">All Job Types</option>
                                            <option value="Full-time" <?php echo $jobType === 'Full-time' ? 'selected' : ''; ?>>Full-time</option>
                                            <option value="Part-time" <?php echo $jobType === 'Part-time' ? 'selected' : ''; ?>>Part-time</option>
                                            <option value="Internship" <?php echo $jobType === 'Internship' ? 'selected' : ''; ?>>Internship</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-select" name="location">
                                            <option value="">All Locations</option>
                                            <?php foreach ($locations as $loc): ?>
                                                <option value="<?php echo htmlspecialchars($loc['location']); ?>" 
                                                        <?php echo $location === $loc['location'] ? 'selected' : ''; ?>>
                                                    <?php echo htmlspecialchars($loc['location']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <select class="form-select" name="sort">
                                            <option value="newest" <?php echo $sortBy === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                            <option value="oldest" <?php echo $sortBy === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                            <option value="deadline" <?php echo $sortBy === 'deadline' ? 'selected' : ''; ?>>Application Deadline</option>
                                            <option value="salary_high" <?php echo $sortBy === 'salary_high' ? 'selected' : ''; ?>>Salary (High to Low)</option>
                                            <option value="salary_low" <?php echo $sortBy === 'salary_low' ? 'selected' : ''; ?>>Salary (Low to High)</option>
                                            <option value="applications" <?php echo $sortBy === 'applications' ? 'selected' : ''; ?>>Most Applications</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Jobs List -->
                <div class="row">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($job = $result->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($job['title']); ?></h5>
                                        <h6 class="card-subtitle mb-2 text-muted"><?php echo htmlspecialchars($job['company_name']); ?></h6>
                                        
                                        <div class="job-details mb-3">
                                            <p class="mb-2">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                <?php echo htmlspecialchars($job['location']); ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-briefcase me-2"></i>
                                                <span class="badge bg-<?php echo strtolower($job['job_type']) === 'full-time' ? 'primary' : (strtolower($job['job_type']) === 'part-time' ? 'warning' : 'info'); ?>">
                                                    <?php echo htmlspecialchars($job['job_type']); ?>
                                                </span>
                                            </p>
                                            <?php if ($job['salary']): ?>
                                                <p class="mb-2">
                                                    <i class="fas fa-money-bill-wave me-2"></i>
                                                    <?php echo htmlspecialchars($job['salary']); ?>
                                                </p>
                                            <?php endif; ?>
                                            <p class="mb-2">
                                                <i class="fas fa-clock me-2"></i>
                                                Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-users me-2"></i>
                                                <?php echo $job['application_count']; ?> applications
                                            </p>
                                        </div>
                                        
                                        <p class="card-text">
                                            <?php echo nl2br(htmlspecialchars(substr($job['description'], 0, 150)) . (strlen($job['description']) > 150 ? '...' : '')); ?>
                                        </p>

                                        <div class="d-grid gap-2">
                                            <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </a>
                                            <?php if ($job['has_applied']): ?>
                                                <button class="btn btn-secondary" disabled>
                                                    <i class="fas fa-check me-1"></i> Applied
                                                </button>
                                                <a href="view-resume.php?id=<?php echo $student['id']; ?>" class="btn btn-outline-info" target="_blank">
                                                    <i class="fas fa-file-alt me-1"></i> View Resume
                                                </a>
                                            <?php else: ?>
                                                <form action="apply-job.php" method="POST" class="d-grid">
                                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                    <button type="submit" class="btn btn-success">
                                                        <i class="fas fa-paper-plane me-1"></i> Apply Now
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>

                        <!-- Pagination -->
                        <?php if ($totalPages > 1): ?>
                            <div class="col-12">
                                <nav aria-label="Job listings pagination">
                                    <ul class="pagination justify-content-center">
                                        <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">Previous</a>
                                        </li>
                                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                            <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                                                <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                            </li>
                                        <?php endfor; ?>
                                        <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">Next</a>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                No jobs found matching your criteria.
                            </div>
                        </div>
                    <?php endif; ?>
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
        document.querySelectorAll('select[name="job_type"], select[name="location"], select[name="sort"]').forEach(select => {
            select.addEventListener('change', () => {
                select.closest('form').submit();
            });
        });
    </script>
</body>
</html> 
 