<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Check if training ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: training-programs.php');
    exit;
}

$training_id = $_GET['id'];

// Get training program details
$query = "SELECT * FROM training_programs WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $training_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = "Training program not found.";
    header('Location: training-programs.php');
    exit;
}

$program = $result->fetch_assoc();

// Get enrollment statistics
$statsQuery = "SELECT 
                COUNT(*) as total_enrolled,
                SUM(CASE WHEN status = 'enrolled' THEN 1 ELSE 0 END) as active_enrolled,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN status = 'dropped' THEN 1 ELSE 0 END) as dropped
              FROM training_enrollments 
              WHERE training_program_id = ?";
$stmt = $conn->prepare($statsQuery);
$stmt->bind_param("i", $training_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Get enrolled students
$studentsQuery = "SELECT te.*, te.id as enrollment_id, s.name, s.roll_number, s.department, s.year_of_passing, s.cgpa, u.email
                 FROM training_enrollments te
                 JOIN students s ON te.student_id = s.id
                 JOIN users u ON s.user_id = u.id
                 WHERE te.training_program_id = ?
                 ORDER BY te.enrollment_date DESC";
$stmt = $conn->prepare($studentsQuery);
$stmt->bind_param("i", $training_id);
$stmt->execute();
$enrollments = $stmt->get_result();

// Handle student enrollment status update
if (isset($_POST['update_status'])) {
    $enrollment_id = $_POST['enrollment_id'];
    $new_status = $_POST['new_status'];
    $completion_date = ($new_status === 'completed') ? date('Y-m-d H:i:s') : null;
    
    $updateQuery = "UPDATE training_enrollments SET status = ?, completion_date = ? WHERE id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ssi", $new_status, $completion_date, $enrollment_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student enrollment status updated successfully.";
        header("Location: view-training.php?id={$training_id}");
        exit;
    } else {
        $_SESSION['error'] = "Failed to update enrollment status.";
    }
}

// Handle student removal from program
if (isset($_POST['remove_student'])) {
    $enrollment_id = $_POST['enrollment_id'];
    
    $deleteQuery = "DELETE FROM training_enrollments WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $enrollment_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Student removed from the program successfully.";
        header("Location: view-training.php?id={$training_id}");
        exit;
    } else {
        $_SESSION['error'] = "Failed to remove student from the program.";
    }
}

// Set page title
$pageTitle = $program['title'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($program['title']); ?> - Admin Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/admin.css">
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
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="training-programs.php">Training Programs</a></li>
                            <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($program['title']); ?></li>
                        </ol>
                    </nav>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="edit-training.php?id=<?php echo $program['id']; ?>" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="fas fa-edit me-1"></i> Edit Program
                        </a>
                        <a href="training-programs.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Programs
                        </a>
                    </div>
                </div>

                <div class="row">
                    <!-- Program Details -->
                    <div class="col-md-8">
                        <div class="card mb-4">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Program Details</h5>
                                <span class="badge bg-<?php 
                                    echo $program['status'] === 'active' ? 'success' : 
                                        ($program['status'] === 'completed' ? 'secondary' : 'warning'); 
                                ?>">
                                    <?php echo ucfirst($program['status']); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <h4 class="card-title"><?php echo htmlspecialchars($program['title']); ?></h4>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1">Instructor</p>
                                        <p><?php echo !empty($program['instructor']) ? htmlspecialchars($program['instructor']) : '<em>Not specified</em>'; ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1">Duration</p>
                                        <p><?php echo htmlspecialchars($program['duration']); ?></p>
                                    </div>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1">Start Date</p>
                                        <p><?php echo date('F d, Y', strtotime($program['start_date'])); ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <p class="text-muted mb-1">End Date</p>
                                        <p><?php echo date('F d, Y', strtotime($program['end_date'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <p class="text-muted mb-1">Location</p>
                                    <p><?php echo htmlspecialchars($program['location']); ?></p>
                                </div>
                                
                                <h5 class="mt-4">Description</h5>
                                <p><?php echo nl2br(htmlspecialchars($program['description'])); ?></p>
                                
                                <?php if (!empty($program['prerequisites'])): ?>
                                <h5 class="mt-4">Prerequisites</h5>
                                <p><?php echo nl2br(htmlspecialchars($program['prerequisites'])); ?></p>
                                <?php endif; ?>
                                
                                <?php if (!empty($program['syllabus'])): ?>
                                <h5 class="mt-4">Syllabus</h5>
                                <p><?php echo nl2br(htmlspecialchars($program['syllabus'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Enrollment Statistics -->
                    <div class="col-md-4">
                        <div class="card mb-4">
                            <div class="card-header">
                                <h5 class="mb-0">Enrollment Statistics</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Total Enrollments</span>
                                    <span class="badge bg-primary rounded-pill"><?php echo $stats['total_enrolled']; ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Active Students</span>
                                    <span class="badge bg-success rounded-pill"><?php echo $stats['active_enrolled']; ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span>Completed</span>
                                    <span class="badge bg-info rounded-pill"><?php echo $stats['completed']; ?></span>
                                </div>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <span>Dropped</span>
                                    <span class="badge bg-danger rounded-pill"><?php echo $stats['dropped']; ?></span>
                                </div>
                                
                                <?php if ($stats['total_enrolled'] > 0): ?>
                                <hr>
                                <h6>Completion Rate</h6>
                                <div class="progress mb-2" style="height: 10px;">
                                    <?php 
                                    $completion_rate = ($stats['total_enrolled'] > 0) ? round(($stats['completed'] / $stats['total_enrolled']) * 100) : 0;
                                    ?>
                                    <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $completion_rate; ?>%;" 
                                         aria-valuenow="<?php echo $completion_rate; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                                <small class="text-muted"><?php echo $completion_rate; ?>% complete</small>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer">
                                <small class="text-muted">Last updated: <?php echo date('M d, Y H:i', strtotime($program['updated_at'])); ?></small>
                            </div>
                        </div>
                        
                        <!-- Program Created Info -->
                        <div class="card mb-4">
                            <div class="card-body">
                                <h6 class="card-subtitle mb-2 text-muted">Program Created</h6>
                                <p class="card-text"><?php echo date('F d, Y', strtotime($program['created_at'])); ?></p>
                                
                                <?php if ($program['created_at'] !== $program['updated_at']): ?>
                                <h6 class="card-subtitle mb-2 text-muted">Last Updated</h6>
                                <p class="card-text"><?php echo date('F d, Y', strtotime($program['updated_at'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Enrolled Students -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Enrolled Students (<?php echo $stats['total_enrolled']; ?>)</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($enrollments->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Roll Number</th>
                                        <th>Department</th>
                                        <th>Enrolled On</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?php echo htmlspecialchars($enrollment['name']); ?></strong>
                                                <small class="d-block text-muted"><?php echo htmlspecialchars($enrollment['email']); ?></small>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars($enrollment['roll_number']); ?></td>
                                        <td><?php echo htmlspecialchars($enrollment['department']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $enrollment['status'] === 'enrolled' ? 'primary' : 
                                                    ($enrollment['status'] === 'completed' ? 'success' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($enrollment['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="../student.php?id=<?php echo $enrollment['student_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-user"></i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                    <i class="fas fa-cog"></i>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <form method="POST" action="">
                                                            <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                                                            <input type="hidden" name="new_status" value="enrolled">
                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                <i class="fas fa-user-check text-primary me-2"></i> Mark as Enrolled
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="">
                                                            <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                                                            <input type="hidden" name="new_status" value="completed">
                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                <i class="fas fa-check-circle text-success me-2"></i> Mark as Completed
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li>
                                                        <form method="POST" action="">
                                                            <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                                                            <input type="hidden" name="new_status" value="dropped">
                                                            <button type="submit" name="update_status" class="dropdown-item">
                                                                <i class="fas fa-user-times text-warning me-2"></i> Mark as Dropped
                                                            </button>
                                                        </form>
                                                    </li>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="POST" action="" onsubmit="return confirm('Are you sure you want to remove this student from the program?');">
                                                            <input type="hidden" name="enrollment_id" value="<?php echo $enrollment['enrollment_id']; ?>">
                                                            <button type="submit" name="remove_student" class="dropdown-item text-danger">
                                                                <i class="fas fa-trash-alt me-2"></i> Remove from Program
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            No students are currently enrolled in this program.
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 