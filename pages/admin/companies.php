<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

// Initialize search variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';

// Build the query
$query = "SELECT c.*, u.email as user_email,
          (SELECT COUNT(*) FROM job_postings j WHERE j.company_id = c.id) as job_count
          FROM companies c 
          JOIN users u ON c.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

// Add search filters
if (!empty($search)) {
    $query .= " AND (c.name LIKE ? OR c.contact_person LIKE ? OR c.contact_email LIKE ?)";
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

$pageTitle = 'Manage Companies';
include 'includes/header.php';
?>

<main class="admin-main">
    <div class="container">
        <div class="page-header">
            <h1>Manage Companies</h1>
            <div class="header-actions">
                <a href="add-company.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Company</a>
                <a href="import-companies.php" class="btn btn-secondary"><i class="fas fa-file-import"></i> Import Companies</a>
            </div>
        </div>

        <div class="filter-section">
            <form action="companies.php" method="get" class="filter-form">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, contact person, or email" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <select name="location" class="form-control">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($location === $loc) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if (!empty($search) || !empty($location)): ?>
                    <a href="companies.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Company Name</th>
                        <th>Location</th>
                        <th>Contact Person</th>
                        <th>Contact Email</th>
                        <th>Contact Phone</th>
                        <th>Jobs Posted</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($company = $result->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <?php echo htmlspecialchars($company['name']); ?>
                                    <?php if (!empty($company['website'])): ?>
                                        <a href="<?php echo htmlspecialchars($company['website']); ?>" target="_blank" class="text-muted">
                                            <i class="fas fa-external-link-alt small"></i>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($company['location']); ?></td>
                                <td><?php echo htmlspecialchars($company['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($company['contact_email']); ?></td>
                                <td><?php echo htmlspecialchars($company['contact_phone']); ?></td>
                                <td><?php echo $company['job_count']; ?></td>
                                <td class="actions">
                                    <a href="view-company.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit-company.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-sm btn-danger" onclick="deleteCompany(<?php echo $company['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                    <a href="company-jobs.php?id=<?php echo $company['id']; ?>" class="btn btn-sm btn-secondary" title="View Jobs"><i class="fas fa-briefcase"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No companies found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
function deleteCompany(companyId) {
    if (confirm('Are you sure you want to delete this company? This will also delete all associated job postings and applications. This action cannot be undone.')) {
        window.location.href = `delete-company.php?id=${companyId}`;
    }
}
</script> 