<?php
session_start();

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Include database connection
require_once '../../config/db_connect.php';

// Get student ID
$studentQuery = "SELECT id FROM students WHERE user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();
$student_id = $student['id'];

// Handle enrollment - PROCESS BEFORE INCLUDING HEADER
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $training_id = $_POST['training_id'];
    
    // Check if already enrolled
    $check_query = "SELECT id FROM training_enrollments WHERE training_program_id = ? AND student_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("ii", $training_id, $student_id);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    
    if (!$existing) {
        $enroll_query = "INSERT INTO training_enrollments (training_program_id, student_id, enrollment_date, status) 
                        VALUES (?, ?, NOW(), 'enrolled')";
        $enroll_stmt = $conn->prepare($enroll_query);
        $enroll_stmt->bind_param("ii", $training_id, $student_id);
        
        if ($enroll_stmt->execute()) {
            header("Location: trainings.php?success=1");
            exit();
        } else {
            $error = "Failed to enroll in the training program. Please try again.";
        }
    }
}

// Fetch all active training programs
$query = "SELECT tp.*, 
          COUNT(DISTINCT te.student_id) as enrolled_students,
          (SELECT COUNT(*) FROM training_enrollments WHERE training_program_id = tp.id AND student_id = ?) as is_enrolled
          FROM training_programs tp
          LEFT JOIN training_enrollments te ON tp.id = te.training_program_id
          WHERE tp.status = 'active'
          GROUP BY tp.id
          ORDER BY tp.start_date ASC";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$trainings = $stmt->get_result();

// Set the page title and include header AFTER all redirects
$pageTitle = "Training Programs";
include 'includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h3 mb-0">Training Programs</h2>
    </div>

    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        Successfully enrolled in the training program!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $error; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <?php if ($trainings && $trainings->num_rows > 0): ?>
            <?php while ($training = $trainings->fetch_assoc()): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">
                                    <?php echo htmlspecialchars($training['title']); ?>
                                </h5>
                                <?php if ($training['is_enrolled']): ?>
                                    <span class="badge bg-success rounded-pill">Enrolled</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="card-text text-muted mb-3">
                                <?php 
                                $desc = htmlspecialchars($training['description']);
                                echo strlen($desc) > 150 ? substr($desc, 0, 150) . '...' : $desc;
                                ?>
                            </p>
                            
                            <div class="training-info mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar me-2 text-primary"></i>
                                    <span>
                                        <?php 
                                        $start_date = new DateTime($training['start_date']);
                                        $end_date = new DateTime($training['end_date']);
                                        echo $start_date->format('M d, Y') . ' - ' . $end_date->format('M d, Y');
                                        ?>
                                    </span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-clock me-2 text-primary"></i>
                                    <span><?php echo htmlspecialchars($training['duration']); ?></span>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-users me-2 text-primary"></i>
                                    <span><?php echo $training['enrolled_students']; ?> students enrolled</span>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map-marker-alt me-2 text-primary"></i>
                                    <span><?php echo htmlspecialchars($training['location']); ?></span>
                                </div>
                            </div>

                            <?php if (!$training['is_enrolled']): ?>
                                <form method="POST" class="mt-3">
                                    <input type="hidden" name="training_id" value="<?php echo $training['id']; ?>">
                                    <button type="submit" name="enroll" class="btn btn-primary w-100">
                                        <i class="fas fa-plus-circle me-2"></i>Enroll Now
                                    </button>
                                </form>
                            <?php else: ?>
                                <a href="training-details.php?id=<?php echo $training['id']; ?>" 
                                   class="btn btn-outline-primary w-100">
                                    <i class="fas fa-eye me-2"></i>View Details
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    No active training programs available at the moment. Please check back later!
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}
.card-title {
    color: #2c3e50;
    font-weight: 600;
}
.badge {
    font-weight: 500;
    padding: 0.5em 1em;
}
.training-info {
    font-size: 0.9rem;
}
.training-info i {
    width: 20px;
}
.btn {
    padding: 0.5rem 1rem;
    font-weight: 500;
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