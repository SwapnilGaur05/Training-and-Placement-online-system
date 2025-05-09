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
    // Student profile not found
    header('Location: ../logout.php');
    exit;
}

$student = $studentResult->fetch_assoc();

// Get total applications count
$applicationsCountQuery = "SELECT COUNT(*) as total FROM applications WHERE student_id = ?";
$stmt = $conn->prepare($applicationsCountQuery);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$applicationsCount = $stmt->get_result()->fetch_assoc()['total'];

// Get shortlisted applications count
$shortlistedQuery = "SELECT COUNT(*) as total FROM applications WHERE student_id = ? AND status IN ('Shortlisted', 'Interview', 'Selected')";
$stmt = $conn->prepare($shortlistedQuery);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$shortlistedCount = $stmt->get_result()->fetch_assoc()['total'];

// Get active jobs count
$activeJobsQuery = "SELECT COUNT(*) as total FROM job_postings WHERE deadline >= CURDATE()";
$activeJobsCount = $conn->query($activeJobsQuery)->fetch_assoc()['total'];

// Get upcoming events count
$eventsQuery = "SELECT COUNT(*) as total FROM events WHERE event_date >= CURDATE()";
$eventsCount = $conn->query($eventsQuery)->fetch_assoc()['total'];

// Get recent applications
$recentApplicationsQuery = "SELECT a.*, j.title as job_title, j.location, j.job_type, c.name as company_name 
                     FROM applications a 
                     JOIN job_postings j ON a.job_id = j.id 
                     JOIN companies c ON j.company_id = c.id 
                     WHERE a.student_id = ? 
                     ORDER BY a.applied_date DESC LIMIT 5";
$stmt = $conn->prepare($recentApplicationsQuery);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$recentApplicationsResult = $stmt->get_result();

// Get recommended jobs
$recommendedJobsQuery = "SELECT j.*, c.name as company_name 
                        FROM job_postings j 
                        JOIN companies c ON j.company_id = c.id 
                        WHERE j.deadline >= CURDATE() 
                        AND (j.description LIKE ? OR j.requirements LIKE ?) 
                        ORDER BY j.created_at DESC LIMIT 5";
$departmentKeyword = "%{$student['department']}%";
$stmt = $conn->prepare($recommendedJobsQuery);
$stmt->bind_param("ss", $departmentKeyword, $departmentKeyword);
$stmt->execute();
$recommendedJobsResult = $stmt->get_result();

// Get upcoming events
$upcomingEventsQuery = "SELECT * FROM events 
                       WHERE event_date >= CURDATE() 
                       ORDER BY event_date ASC LIMIT 3";
$upcomingEventsResult = $conn->query($upcomingEventsQuery);

// Set page title
$pageTitle = 'Student Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - TPOS</title>
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Student Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="resume.php" class="btn btn-sm btn-outline-primary me-2">
                            <i class="fas fa-file-pdf"></i> View Resume
                        </a>
                        <a href="myprofile.php" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-user-edit"></i> Edit Profile
                        </a>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-primary text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $applicationsCount; ?></h3>
                                        <div>Total Applications</div>
                                    </div>
                                    <div>
                                        <i class="fas fa-file-alt fa-2x"></i>
            </div>
            </div>
        </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="applications.php">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-success text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $shortlistedCount; ?></h3>
                                        <div>Shortlisted</div>
                </div>
                                    <div>
                                        <i class="fas fa-check-circle fa-2x"></i>
                            </div>
                        </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="applications.php?status=shortlisted">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-info text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $activeJobsCount; ?></h3>
                                        <div>Active Jobs</div>
                            </div>
                                    <div>
                                        <i class="fas fa-briefcase fa-2x"></i>
                            </div>
                        </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="jobs.php">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-warning text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $eventsCount; ?></h3>
                                        <div>Upcoming Events</div>
                                    </div>
                                    <div>
                                        <i class="fas fa-calendar-alt fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="events.php">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                                                    </div>
                                                </div>
                                                </div>

                <!-- Recent Applications -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-file-alt me-2"></i>Recent Applications</h5>
                        <a href="applications.php" class="btn btn-sm btn-primary">View All</a>
                                            </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Company</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentApplicationsResult->num_rows > 0): ?>
                                        <?php while ($application = $recentApplicationsResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($application['job_title']); ?></td>
                                                <td><?php echo htmlspecialchars($application['company_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($application['applied_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo strtolower($application['status']) === 'selected' ? 'success' : 
                                                            (strtolower($application['status']) === 'rejected' ? 'danger' : 
                                                            (strtolower($application['status']) === 'interview' ? 'warning' : 'info')); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($application['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view-application.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No applications found</td>
                                        </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        </div>
                    </div>
                    
                <!-- Recommended Jobs -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Recommended Jobs</h5>
                        <a href="jobs.php" class="btn btn-sm btn-primary">View All Jobs</a>
                            </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Company</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recommendedJobsResult->num_rows > 0): ?>
                                        <?php while ($job = $recommendedJobsResult->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($job['title']); ?></td>
                                                <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                                <td><?php echo htmlspecialchars($job['location']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo strtolower($job['job_type']) === 'full-time' ? 'primary' : (strtolower($job['job_type']) === 'part-time' ? 'warning' : 'info'); ?>">
                                                        <?php echo htmlspecialchars($job['job_type']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="apply-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-paper-plane"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center">No recommended jobs found</td>
                                        </tr>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                            </div>
                                                </div>

                <!-- Upcoming Events -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Events</h5>
                        <a href="events.php" class="btn btn-sm btn-primary">View All Events</a>
                                                </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if ($upcomingEventsResult->num_rows > 0): ?>
                                <?php while ($event = $upcomingEventsResult->fetch_assoc()): ?>
                                    <div class="col-md-4 mb-3">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                                                <p class="card-text">
                                                    <i class="fas fa-calendar me-2"></i>
                                                    <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                                </p>
                                                <p class="card-text">
                                                    <i class="fas fa-clock me-2"></i>
                                                    <?php echo date('h:i A', strtotime($event['event_date'])); ?>
                                                </p>
                                                <p class="card-text">
                                                    <i class="fas fa-map-marker-alt me-2"></i>
                                                    <?php echo htmlspecialchars($event['location']); ?>
                                                </p>
                                                <a href="view-event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-primary">
                                                    View Details
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                                <?php else: ?>
                                <div class="col-12">
                                    <p class="text-center">No upcoming events found</p>
                                    </div>
                                <?php endif; ?>
                        </div>
                    </div>
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
        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        });
    </script>
</body>
</html> 