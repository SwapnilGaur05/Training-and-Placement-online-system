<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Initialize search variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

// Build the query
$query = "SELECT c.*, 
          (SELECT COUNT(*) FROM job_postings j WHERE j.company_id = c.id AND j.deadline >= CURDATE()) as active_jobs,
          (SELECT COUNT(*) FROM job_postings j WHERE j.company_id = c.id) as total_jobs
          FROM companies c 
          WHERE 1=1";

$params = [];
$types = "";

// Add search filters
if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.description LIKE ? OR c.location LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if (!empty($location)) {
    $query .= " AND c.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

// Add order by
$query .= " ORDER BY c.name ASC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get distinct locations for filter dropdown
$locationsQuery = "SELECT DISTINCT location FROM companies WHERE location IS NOT NULL AND location != '' ORDER BY location";
$locationsResult = $conn->query($locationsQuery);
$locations = [];
if ($locationsResult && $locationsResult->num_rows > 0) {
    while ($row = $locationsResult->fetch_assoc()) {
        $locations[] = $row['location'];
    }
}

// Set page title
$pageTitle = 'Companies';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Companies</h1>
            </div>

            <!-- Search and Filter Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="companies.php" method="get" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search companies..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                <select name="location" class="form-select">
                                    <option value="">All Locations</option>
                                    <?php foreach ($locations as $loc): ?>
                                        <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($location === $loc) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($loc); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="fas fa-filter me-1"></i> Filter
                            </button>
                            <?php if (!empty($search) || !empty($location)): ?>
                                <a href="companies.php" class="btn btn-secondary">
                                    <i class="fas fa-times me-1"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Companies Grid -->
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($company = $result->fetch_assoc()): ?>
                        <div class="col">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <?php echo htmlspecialchars($company['name']); ?>
                                        <?php if (!empty($company['website'])): ?>
                                            <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="text-muted ms-2">
                                                <i class="fas fa-external-link-alt small"></i>
                                            </a>
                                        <?php endif; ?>
                                    </h5>
                                    <p class="card-text text-muted mb-2">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($company['location']); ?>
                                    </p>
                                    <p class="card-text">
                                        <?php 
                                        $description = $company['description'];
                                        echo htmlspecialchars(strlen($description) > 150 ? substr($description, 0, 147) . '...' : $description); 
                                        ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center mt-3">
                                        <div>
                                            <span class="badge bg-primary me-2">
                                                <i class="fas fa-briefcase me-1"></i>
                                                <?php echo $company['active_jobs']; ?> Active Jobs
                                            </span>
                                            <span class="badge bg-secondary">
                                                <i class="fas fa-history me-1"></i>
                                                <?php echo $company['total_jobs']; ?> Total Jobs
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer bg-transparent">
                                    <div class="d-grid gap-2">
                                        <a href="company-details.php?id=<?php echo $company['id']; ?>" class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-info-circle me-1"></i> View Details
                                        </a>
                                        <a href="jobs.php?company=<?php echo $company['id']; ?>" class="btn btn-outline-secondary btn-sm">
                                            <i class="fas fa-briefcase me-1"></i> View Jobs
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-12">
                        <div class="alert alert-info text-center">
                            <i class="fas fa-info-circle me-2"></i> No companies found matching your criteria.
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Auto-submit form when location changes
document.querySelector('select[name="location"]').addEventListener('change', function() {
    this.form.submit();
});
</script> 
 