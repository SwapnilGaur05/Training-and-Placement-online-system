<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Handle program status update
if (isset($_POST['update_status'])) {
    $program_id = $_POST['program_id'];
    $new_status = $_POST['new_status'];
    
    $update_query = "UPDATE training_programs SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $new_status, $program_id);
    
    if ($stmt->execute()) {
        $_SESSION['success'] = "Training program status updated successfully.";
    } else {
        $_SESSION['error'] = "Failed to update training program status.";
    }
    
    // Redirect to prevent form resubmission
    header("Location: training-programs.php");
    exit;
}

// Handle program deletion
if (isset($_POST['delete_program'])) {
    $program_id = $_POST['program_id'];
    
    // First check if there are enrollments
    $check_query = "SELECT COUNT(*) as count FROM training_enrollments WHERE training_program_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $program_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $enrollments = $result->fetch_assoc()['count'];
    
    if ($enrollments > 0) {
        $_SESSION['error'] = "Cannot delete program with active enrollments. Please unenroll students first or change the program status to 'inactive'.";
    } else {
        $delete_query = "DELETE FROM training_programs WHERE id = ?";
        $stmt = $conn->prepare($delete_query);
        $stmt->bind_param("i", $program_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Training program deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete training program.";
        }
    }
    
    // Redirect to prevent form resubmission
    header("Location: training-programs.php");
    exit;
}

// Initialize filters and pagination
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$itemsPerPage = 10;
$offset = ($page - 1) * $itemsPerPage;

// Base query for training programs with enrollment counts
$query = "SELECT tp.*, 
          (SELECT COUNT(*) FROM training_enrollments WHERE training_program_id = tp.id) as enrolled_count,
          (SELECT COUNT(*) FROM training_enrollments WHERE training_program_id = tp.id AND status = 'completed') as completed_count
          FROM training_programs tp
          WHERE 1=1";

// Apply search filter
if (!empty($search)) {
    $query .= " AND (tp.title LIKE ? OR tp.description LIKE ? OR tp.instructor LIKE ?)";
}

// Apply status filter
if (!empty($status)) {
    $query .= " AND tp.status = ?";
}

// Apply sorting
$query .= " ORDER BY tp.start_date DESC";

// Add pagination
$query .= " LIMIT ? OFFSET ?";

// Get total count for pagination
$countQuery = "SELECT COUNT(*) as total FROM training_programs tp WHERE 1=1";

if (!empty($search)) {
    $countQuery .= " AND (tp.title LIKE ? OR tp.description LIKE ? OR tp.instructor LIKE ?)";
}

if (!empty($status)) {
    $countQuery .= " AND tp.status = ?";
}

$stmt = $conn->prepare($countQuery);

// Bind parameters for count query
$paramTypes = "";
$paramValues = array();

if (!empty($search)) {
    $paramTypes .= "sss";
    $searchParam = "%$search%";
    array_push($paramValues, $searchParam, $searchParam, $searchParam);
}

if (!empty($status)) {
    $paramTypes .= "s";
    array_push($paramValues, $status);
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
$mainParamTypes = "";
$mainParamValues = array();

if (!empty($search)) {
    $mainParamTypes .= "sss";
    $searchParam = "%$search%";
    array_push($mainParamValues, $searchParam, $searchParam, $searchParam);
}

if (!empty($status)) {
    $mainParamTypes .= "s";
    array_push($mainParamValues, $status);
}

// Add LIMIT and OFFSET parameters
$mainParamTypes .= "ii";
array_push($mainParamValues, $itemsPerPage, $offset);

$stmt->bind_param($mainParamTypes, ...$mainParamValues);
$stmt->execute();
$result = $stmt->get_result();

// Set page title
$pageTitle = 'Training Programs Management';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training Programs - Admin Dashboard</title>
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
                    <h1 class="h2">Training Programs Management</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="add-training.php" class="btn btn-sm btn-primary">
                            <i class="fas fa-plus me-1"></i> Add New Program
                        </a>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="" class="row g-3">
                                    <div class="col-md-6">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" name="search" placeholder="Search by title, description or instructor..." value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <select class="form-select" name="status" onchange="this.form.submit()">
                                            <option value="">All Statuses</option>
                                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Programs Table -->
                <div class="card mb-4">
                    <div class="card-body">
                        <?php if ($result->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Instructor</th>
                                            <th>Duration</th>
                                            <th>Status</th>
                                            <th>Enrollments</th>
                                            <th>Completion</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($program = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($program['title']); ?></strong>
                                                    <small class="d-block text-muted">
                                                        <?php 
                                                        $start_date = new DateTime($program['start_date']);
                                                        $end_date = new DateTime($program['end_date']);
                                                        echo $start_date->format('M d, Y') . ' - ' . $end_date->format('M d, Y'); 
                                                        ?>
                                                    </small>
                                                </td>
                                                <td><?php echo htmlspecialchars($program['instructor']); ?></td>
                                                <td><?php echo htmlspecialchars($program['duration']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $program['status'] === 'active' ? 'success' : 
                                                            ($program['status'] === 'completed' ? 'secondary' : 'warning'); 
                                                    ?>">
                                                        <?php echo ucfirst($program['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php echo $program['enrolled_count']; ?> students
                                                </td>
                                                <td>
                                                    <?php 
                                                    if ($program['enrolled_count'] > 0) {
                                                        $completion_rate = round(($program['completed_count'] / $program['enrolled_count']) * 100);
                                                        echo '<div class="progress" style="height: 8px;">
                                                            <div class="progress-bar" role="progressbar" style="width: '.$completion_rate.'%;" 
                                                                aria-valuenow="'.$completion_rate.'" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>';
                                                        echo '<small>'.$completion_rate.'% ('.$program['completed_count'].'/'.$program['enrolled_count'].')</small>';
                                                    } else {
                                                        echo '<small class="text-muted">No enrollments</small>';
                                                    }
                                                    ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="view-training.php?id=<?php echo $program['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit-training.php?id=<?php echo $program['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <button type="button" class="btn btn-sm btn-outline-info dropdown-toggle" data-bs-toggle="dropdown">
                                                            <i class="fas fa-cog"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li>
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="active">
                                                                    <button type="submit" name="update_status" class="dropdown-item">
                                                                        <i class="fas fa-check-circle text-success me-2"></i> Mark as Active
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="inactive">
                                                                    <button type="submit" name="update_status" class="dropdown-item">
                                                                        <i class="fas fa-pause-circle text-warning me-2"></i> Mark as Inactive
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li>
                                                                <form method="POST" action="">
                                                                    <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                                                                    <input type="hidden" name="new_status" value="completed">
                                                                    <button type="submit" name="update_status" class="dropdown-item">
                                                                        <i class="fas fa-flag-checkered text-secondary me-2"></i> Mark as Completed
                                                                    </button>
                                                                </form>
                                                            </li>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li>
                                                                <form method="POST" action="" onsubmit="return confirm('Are you sure you want to delete this training program? This action cannot be undone.');">
                                                                    <input type="hidden" name="program_id" value="<?php echo $program['id']; ?>">
                                                                    <button type="submit" name="delete_program" class="dropdown-item text-danger">
                                                                        <i class="fas fa-trash-alt me-2"></i> Delete Program
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

                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <nav aria-label="Training programs pagination">
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
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                No training programs found. <a href="add-training.php" class="alert-link">Create your first training program</a>.
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