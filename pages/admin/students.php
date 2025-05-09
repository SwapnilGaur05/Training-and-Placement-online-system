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
$department = isset($_GET['department']) ? trim($_GET['department']) : '';
$year = isset($_GET['year']) ? trim($_GET['year']) : '';

// Build the query
$query = "SELECT s.*, u.email, 
          (SELECT COUNT(*) FROM applications a WHERE a.student_id = s.id) as application_count 
          FROM students s 
          JOIN users u ON s.user_id = u.id 
          WHERE 1=1";

$params = [];
$types = "";

// Add search filters
if (!empty($search)) {
    $query .= " AND (s.name LIKE ? OR s.roll_number LIKE ? OR u.email LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "sss";
}

if (!empty($department)) {
    $query .= " AND s.department = ?";
    $params[] = $department;
    $types .= "s";
}

if (!empty($year)) {
    $query .= " AND s.year_of_passing = ?";
    $params[] = $year;
    $types .= "i";
}

// Add order by
$query .= " ORDER BY s.name ASC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get distinct departments for filter dropdown
$departmentsQuery = "SELECT DISTINCT department FROM students WHERE department IS NOT NULL AND department != '' ORDER BY department";
$departmentsResult = $conn->query($departmentsQuery);
$departments = [];
if ($departmentsResult && $departmentsResult->num_rows > 0) {
    while ($row = $departmentsResult->fetch_assoc()) {
        $departments[] = $row['department'];
    }
}

// Get distinct years for filter dropdown
$yearsQuery = "SELECT DISTINCT year_of_passing FROM students WHERE year_of_passing IS NOT NULL ORDER BY year_of_passing DESC";
$yearsResult = $conn->query($yearsQuery);
$years = [];
if ($yearsResult && $yearsResult->num_rows > 0) {
    while ($row = $yearsResult->fetch_assoc()) {
        $years[] = $row['year_of_passing'];
    }
}

$pageTitle = 'Manage Students';
include 'includes/header.php';
?>

<main class="admin-main">
    <div class="container">
        <div class="page-header">
            <h1>Manage Students</h1>
            <div class="header-actions">
                <a href="add-student.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Student</a>
                <a href="import-students.php" class="btn btn-secondary"><i class="fas fa-file-import"></i> Import Students</a>
            </div>
        </div>

        <div class="filter-section">
            <form action="students.php" method="get" class="filter-form">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by name, roll number, or email" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <select name="department" class="form-control">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>" <?php echo ($department === $dept) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select name="year" class="form-control">
                        <option value="">All Years</option>
                        <?php foreach ($years as $yr): ?>
                            <option value="<?php echo $yr; ?>" <?php echo ($year == $yr) ? 'selected' : ''; ?>>
                                <?php echo $yr; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if (!empty($search) || !empty($department) || !empty($year)): ?>
                    <a href="students.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Roll Number</th>
                        <th>Email</th>
                        <th>Department</th>
                        <th>Year</th>
                        <th>CGPA</th>
                        <th>Applications</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($student = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                <td><?php echo htmlspecialchars($student['roll_number']); ?></td>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                <td><?php echo htmlspecialchars($student['department']); ?></td>
                                <td><?php echo htmlspecialchars($student['year_of_passing']); ?></td>
                                <td><?php echo htmlspecialchars($student['cgpa']); ?></td>
                                <td><?php echo $student['application_count']; ?></td>
                                <td class="actions">
                                    <a href="view-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit-student.php?id=<?php echo $student['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-sm btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                    <?php if (!empty($student['resume_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($student['resume_path']); ?>" class="btn btn-sm btn-secondary" target="_blank" title="View Resume"><i class="fas fa-file-pdf"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center">No students found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
function deleteStudent(studentId) {
    if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
        window.location.href = `delete-student.php?id=${studentId}`;
    }
}
</script> 