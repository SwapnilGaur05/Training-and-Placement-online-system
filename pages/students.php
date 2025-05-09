<?php
session_start();
require_once '../config/db_connect.php';

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

// Set page title
$pageTitle = 'Students';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - Training and Placement Online System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="../index.php">
                    <h1>TPOS</h1>
                    <span>Training & Placement Online System</span>
                </a>
            </div>
            <nav>
                <ul class="main-menu">
                    <li><a href="../index.php">Home</a></li>
                    <li><a href="jobs.php">Jobs</a></li>
                    <li><a href="companies.php">Companies</a></li>
                    <li><a href="events.php">Events</a></li>
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php">Contact</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="dropdown-toggle">
                            <i class="fas fa-user-circle"></i>
                            <span>My Account</span>
                        </button>
                        <div class="dropdown-menu">
                            <?php if ($_SESSION['user_type'] == 'admin'): ?>
                                <a href="admin/dashboard.php">Admin Dashboard</a>
                            <?php elseif ($_SESSION['user_type'] == 'student'): ?>
                                <a href="student/dashboard.php">Student Dashboard</a>
                                <a href="student/profile.php">My Profile</a>
                                <a href="student/applications.php">My Applications</a>
                            <?php elseif ($_SESSION['user_type'] == 'company'): ?>
                                <a href="company/dashboard.php">Company Dashboard</a>
                                <a href="company/profile.php">Company Profile</a>
                                <a href="company/job-postings.php">Job Postings</a>
                            <?php endif; ?>
                            <a href="logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-login">Login</a>
                    <a href="register.php" class="btn btn-register">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </header>

    <main>
        <div class="container">
            <div class="page-header">
                <h1>Students Directory</h1>
                <p>Browse through our talented pool of students</p>
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

            <div class="students-grid">
                <?php if ($result && $result->num_rows > 0): ?>
                    <?php while ($student = $result->fetch_assoc()): ?>
                        <div class="student-card">
                            <div class="student-avatar">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="student-info">
                                <h3><?php echo htmlspecialchars($student['name']); ?></h3>
                                <p class="student-department"><i class="fas fa-graduation-cap"></i> <?php echo htmlspecialchars($student['department']); ?></p>
                                <p class="student-year"><i class="fas fa-calendar"></i> Batch of <?php echo htmlspecialchars($student['year_of_passing']); ?></p>
                                <?php if (!empty($student['cgpa'])): ?>
                                    <p class="student-cgpa"><i class="fas fa-star"></i> CGPA: <?php echo htmlspecialchars($student['cgpa']); ?></p>
                                <?php endif; ?>
                                <?php if (!empty($student['skills'])): ?>
                                    <div class="student-skills">
                                        <i class="fas fa-tools"></i>
                                        <?php
                                        $skills = explode(',', $student['skills']);
                                        foreach ($skills as $skill) {
                                            echo '<span class="skill-tag">' . htmlspecialchars(trim($skill)) . '</span>';
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                                <p class="application-count">
                                    <i class="fas fa-file-alt"></i> <?php echo $student['application_count']; ?> Application(s)
                                </p>
                            </div>
                            <?php if (isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'company' || $_SESSION['user_type'] === 'admin')): ?>
                                <div class="student-actions">
                                    <a href="view-student-profile.php?id=<?php echo $student['id']; ?>" class="btn btn-primary">View Profile</a>
                                    <?php if (!empty($student['resume_path'])): ?>
                                        <a href="<?php echo htmlspecialchars($student['resume_path']); ?>" class="btn btn-secondary" target="_blank">View Resume</a>
                                    <?php endif; ?>
                                    <?php if ($_SESSION['user_type'] === 'admin'): ?>
                                        <a href="admin/edit-student.php?id=<?php echo $student['id']; ?>" class="btn btn-warning">Edit Student</a>
                                        <button class="btn btn-danger" onclick="deleteStudent(<?php echo $student['id']; ?>)">Delete</button>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-data">
                        <i class="fas fa-user-graduate"></i>
                        <p>No students found</p>
                        <?php if (!empty($search) || !empty($department) || !empty($year)): ?>
                            <p>Try adjusting your search filters</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Education Street, City, Country</p>
                    <p><i class="fas fa-phone"></i> +1 234 567 890</p>
                    <p><i class="fas fa-envelope"></i> info@tpos.com</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="jobs.php">Browse Jobs</a></li>
                        <li><a href="companies.php">Companies</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="about.php">About</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Follow Us</h3>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Training and Placement Online System. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="../assets/js/script.js"></script>
    <script>
        function deleteStudent(studentId) {
            if (confirm('Are you sure you want to delete this student? This action cannot be undone.')) {
                window.location.href = `admin/delete-student.php?id=${studentId}`;
            }
        }
    </script>
</body>
</html> 