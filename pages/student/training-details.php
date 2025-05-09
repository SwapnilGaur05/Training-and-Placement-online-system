<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once '../../config/db_connect.php';

// Check if training ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: trainings.php');
    exit();
}

$training_id = $_GET['id'];

// Get student ID
$studentQuery = "SELECT id FROM students WHERE user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_id = $student['id'];

// Fetch training program details
$query = "SELECT tp.*,
          (SELECT COUNT(*) FROM training_enrollments WHERE training_program_id = tp.id) as total_enrolled,
          (SELECT COUNT(*) FROM training_enrollments WHERE training_program_id = tp.id AND student_id = ?) as is_enrolled,
          (SELECT status FROM training_enrollments WHERE training_program_id = tp.id AND student_id = ?) as enrollment_status,
          (SELECT enrollment_date FROM training_enrollments WHERE training_program_id = tp.id AND student_id = ?) as enrollment_date
          FROM training_programs tp
          WHERE tp.id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param("iiii", $student_id, $student_id, $student_id, $training_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: trainings.php');
    exit();
}

$training = $result->fetch_assoc();
$pageTitle = $training['title'];
include 'includes/header.php';

// Handle enrollment/unenrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['enroll'])) {
        // Check if already enrolled
        if (!$training['is_enrolled']) {
            $enroll_query = "INSERT INTO training_enrollments (training_program_id, student_id, enrollment_date, status) 
                          VALUES (?, ?, NOW(), 'enrolled')";
            $enroll_stmt = $conn->prepare($enroll_query);
            $enroll_stmt->bind_param("ii", $training_id, $student_id);
            
            if ($enroll_stmt->execute()) {
                header("Location: training-details.php?id={$training_id}&success=enrolled");
                exit();
            } else {
                $error = "Failed to enroll in the training program. Please try again.";
            }
        }
    } elseif (isset($_POST['unenroll'])) {
        // Can only unenroll if currently enrolled
        if ($training['is_enrolled']) {
            $unenroll_query = "UPDATE training_enrollments SET status = 'dropped' WHERE training_program_id = ? AND student_id = ?";
            $unenroll_stmt = $conn->prepare($unenroll_query);
            $unenroll_stmt->bind_param("ii", $training_id, $student_id);
            
            if ($unenroll_stmt->execute()) {
                header("Location: training-details.php?id={$training_id}&success=unenrolled");
                exit();
            } else {
                $error = "Failed to unenroll from the training program. Please try again.";
            }
        }
    }
}
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="trainings.php">Training Programs</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo htmlspecialchars($training['title']); ?></li>
            </ol>
        </nav>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php if ($_GET['success'] === 'enrolled'): ?>
            Successfully enrolled in the training program!
        <?php elseif ($_GET['success'] === 'unenrolled'): ?>
            Successfully unenrolled from the training program.
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h1 class="card-title h3 mb-4"><?php echo htmlspecialchars($training['title']); ?></h1>
                    
                    <div class="training-status mb-4">
                        <span class="badge bg-<?php echo $training['status'] === 'active' ? 'success' : ($training['status'] === 'completed' ? 'secondary' : 'warning'); ?> rounded-pill">
                            <?php echo ucfirst($training['status']); ?>
                        </span>
                        <?php if ($training['is_enrolled']): ?>
                            <span class="badge bg-primary rounded-pill ms-2">
                                <?php echo ucfirst($training['enrollment_status']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="training-description mb-4">
                        <h5 class="mb-3">Description</h5>
                        <p><?php echo nl2br(htmlspecialchars($training['description'])); ?></p>
                    </div>
                    
                    <?php if (!empty($training['prerequisites'])): ?>
                    <div class="training-prerequisites mb-4">
                        <h5 class="mb-3">Prerequisites</h5>
                        <p><?php echo nl2br(htmlspecialchars($training['prerequisites'])); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($training['syllabus'])): ?>
                    <div class="training-syllabus mb-4">
                        <h5 class="mb-3">Syllabus</h5>
                        <p><?php echo nl2br(htmlspecialchars($training['syllabus'])); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h5 class="card-title mb-4">Training Details</h5>
                    
                    <ul class="list-unstyled training-info">
                        <?php if (!empty($training['instructor'])): ?>
                        <li class="mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-tie me-3 text-primary"></i>
                                <div>
                                    <div class="text-muted small">Instructor</div>
                                    <div><?php echo htmlspecialchars($training['instructor']); ?></div>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <li class="mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-calendar-alt me-3 text-primary"></i>
                                <div>
                                    <div class="text-muted small">Duration</div>
                                    <div>
                                        <?php 
                                        $start_date = new DateTime($training['start_date']);
                                        $end_date = new DateTime($training['end_date']);
                                        echo $start_date->format('M d, Y') . ' - ' . $end_date->format('M d, Y');
                                        ?>
                                        <div class="text-muted small">(<?php echo htmlspecialchars($training['duration']); ?>)</div>
                                    </div>
                                </div>
                            </div>
                        </li>
                        
                        <li class="mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-map-marker-alt me-3 text-primary"></i>
                                <div>
                                    <div class="text-muted small">Location</div>
                                    <div><?php echo htmlspecialchars($training['location']); ?></div>
                                </div>
                            </div>
                        </li>
                        
                        <li class="mb-3">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-users me-3 text-primary"></i>
                                <div>
                                    <div class="text-muted small">Enrollment</div>
                                    <div><?php echo $training['total_enrolled']; ?> students enrolled</div>
                                </div>
                            </div>
                        </li>
                        
                        <?php if ($training['is_enrolled']): ?>
                        <li>
                            <div class="d-flex align-items-center">
                                <i class="fas fa-clock me-3 text-primary"></i>
                                <div>
                                    <div class="text-muted small">Enrolled On</div>
                                    <div>
                                        <?php 
                                        $enrolled_date = new DateTime($training['enrollment_date']);
                                        echo $enrolled_date->format('M d, Y'); 
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                        <?php endif; ?>
                    </ul>
                    
                    <div class="mt-4">
                        <?php if (!$training['is_enrolled'] && $training['status'] === 'active'): ?>
                            <form method="POST">
                                <button type="submit" name="enroll" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-plus-circle me-2"></i>Enroll in This Program
                                </button>
                            </form>
                        <?php elseif ($training['is_enrolled'] && $training['enrollment_status'] === 'enrolled'): ?>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to unenroll from this training program?');">
                                <button type="submit" name="unenroll" class="btn btn-outline-danger w-100 mb-2">
                                    <i class="fas fa-times-circle me-2"></i>Unenroll
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <a href="trainings.php" class="btn btn-outline-secondary w-100">
                            <i class="fas fa-arrow-left me-2"></i>Back to Training Programs
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.training-info i {
    font-size: 1.25rem;
    width: 20px;
    text-align: center;
}
.badge {
    font-weight: 500;
    padding: 0.5em 1em;
}
.card-title {
    color: #2c3e50;
    font-weight: 600;
}
.breadcrumb a {
    color: #1a73e8;
    text-decoration: none;
}
.breadcrumb a:hover {
    text-decoration: underline;
}
.btn-primary {
    background-color: #1a73e8;
    border-color: #1a73e8;
}
.btn-primary:hover {
    background-color: #1557b0;
    border-color: #1557b0;
}
.btn-outline-primary {
    color: #1a73e8;
    border-color: #1a73e8;
}
.btn-outline-primary:hover {
    background-color: #1a73e8;
    border-color: #1a73e8;
}
</style>

<?php include 'includes/footer.php'; ?> 