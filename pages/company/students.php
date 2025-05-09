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

// Initialize filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$department = isset($_GET['department']) ? $_GET['department'] : '';
$year = isset($_GET['year']) ? $_GET['year'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Base query to get students who have applied to company's jobs
$query = "SELECT DISTINCT s.*, 
          GROUP_CONCAT(DISTINCT a.status) as application_statuses,
          COUNT(DISTINCT a.id) as total_applications,
          MAX(a.applied_date) as last_application_date
          FROM students s
          INNER JOIN applications a ON s.id = a.student_id
          INNER JOIN job_postings j ON a.job_id = j.id
          WHERE j.company_id = ?";

$params = [$company['id']];
$types = "i";

// Add search filter
if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.roll_number LIKE ? OR s.department LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= "sss";
}

// Add department filter
if (!empty($department)) {
    $query .= " AND s.department = ?";
    $params[] = $department;
    $types .= "s";
}

// Add year filter
if (!empty($year)) {
    $query .= " AND s.year_of_passing = ?";
    $params[] = $year;
    $types .= "i";
}

// Add status filter
if (!empty($status)) {
    $query .= " AND a.status = ?";
    $params[] = $status;
    $types .= "s";
}

// Group by and order
$query .= " GROUP BY s.id ORDER BY last_application_date DESC";

// Get departments for filter
$deptQuery = "SELECT DISTINCT department FROM students";
$departments = $conn->query($deptQuery)->fetch_all(MYSQLI_ASSOC);

// Get years for filter
$yearQuery = "SELECT DISTINCT year_of_passing FROM students ORDER BY year_of_passing DESC";
$years = $conn->query($yearQuery)->fetch_all(MYSQLI_ASSOC);

// Prepare and execute the main query
$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Set page title
$pageTitle = 'Students';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Students</h1>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <div class="form-floating">
                    <input type="text" class="form-control" id="search" name="search" placeholder="Search" value="<?php echo htmlspecialchars($search); ?>">
                    <label for="search">Search</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="department" name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept['department']); ?>" <?php echo $department === $dept['department'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['department']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="department">Department</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="year" name="year">
                        <option value="">All Years</option>
                        <?php foreach ($years as $yr): ?>
                            <option value="<?php echo $yr['year_of_passing']; ?>" <?php echo $year == $yr['year_of_passing'] ? 'selected' : ''; ?>>
                                <?php echo $yr['year_of_passing']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <label for="year">Passing Year</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="status" name="status">
                        <option value="">All Status</option>
                        <option value="Applied" <?php echo $status === 'Applied' ? 'selected' : ''; ?>>Applied</option>
                        <option value="Shortlisted" <?php echo $status === 'Shortlisted' ? 'selected' : ''; ?>>Shortlisted</option>
                        <option value="Interview" <?php echo $status === 'Interview' ? 'selected' : ''; ?>>Interview</option>
                        <option value="Selected" <?php echo $status === 'Selected' ? 'selected' : ''; ?>>Selected</option>
                        <option value="Rejected" <?php echo $status === 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                    </select>
                    <label for="status">Application Status</label>
                </div>
            </div>
            <div class="col-md-3">
                <div class="d-flex gap-2 h-100 align-items-center">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>Filter
                    </button>
                    <a href="students.php" class="btn btn-secondary">
                        <i class="fas fa-undo me-2"></i>Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Students List -->
<div class="card">
    <div class="card-body">
        <?php if ($result->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Roll Number</th>
                            <th>Department</th>
                            <th>Year of Passing</th>
                            <th>CGPA</th>
                            <th>Applications</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($student = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['department']); ?></td>
                                <td><?php echo htmlspecialchars($student['year_of_passing']); ?></td>
                                <td><?php echo $student['cgpa'] ? number_format($student['cgpa'], 2) : 'N/A'; ?></td>
                                <td><?php echo $student['total_applications']; ?></td>
                                <td>
                                    <?php
                                    $statuses = explode(',', $student['application_statuses']);
                                    foreach ($statuses as $status) {
                                        $badgeClass = 'bg-secondary';
                                        switch ($status) {
                                            case 'Applied': $badgeClass = 'bg-info'; break;
                                            case 'Shortlisted': $badgeClass = 'bg-warning'; break;
                                            case 'Interview': $badgeClass = 'bg-primary'; break;
                                            case 'Selected': $badgeClass = 'bg-success'; break;
                                            case 'Rejected': $badgeClass = 'bg-danger'; break;
                                        }
                                        echo "<span class='badge $badgeClass me-1'>$status</span>";
                                    }
                                    ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="view-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-primary" title="View Details">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="view-resume.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-outline-info" title="View Resume" target="_blank">
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
                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                <p class="text-muted">No students found matching your criteria.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Auto-submit form when filters change
    document.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', () => {
            document.querySelector('form').submit();
        });
    });
</script>

<?php include 'includes/footer.php'; ?> 
 