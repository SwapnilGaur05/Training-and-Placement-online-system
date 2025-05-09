<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Get admin information
$userId = $_SESSION['user_id'];
$adminQuery = "SELECT a.* FROM admins a WHERE a.user_id = ?";
$stmt = $conn->prepare($adminQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$adminResult = $stmt->get_result();

if ($adminResult->num_rows === 0) {
    // Admin profile not found
    header('Location: ../logout.php');
    exit;
}

$admin = $adminResult->fetch_assoc();

// Get statistics
// Count total students
$studentsCountQuery = "SELECT COUNT(*) as total FROM students";
$studentsCountResult = $conn->query($studentsCountQuery)->fetch_assoc();
$totalStudents = $studentsCountResult['total'];

// Count total companies
$companiesCountQuery = "SELECT COUNT(*) as total FROM companies";
$companiesCountResult = $conn->query($companiesCountQuery)->fetch_assoc();
$totalCompanies = $companiesCountResult['total'];

// Count total job postings
$jobsCountQuery = "SELECT COUNT(*) as total FROM job_postings";
$jobsCountResult = $conn->query($jobsCountQuery)->fetch_assoc();
$totalJobs = $jobsCountResult['total'];

// Count total applications
$applicationsCountQuery = "SELECT COUNT(*) as total FROM applications";
$applicationsCountResult = $conn->query($applicationsCountQuery)->fetch_assoc();
$totalApplications = $applicationsCountResult['total'];

// Count total training programs
$trainingCountQuery = "SELECT COUNT(*) as total FROM training_programs";
$trainingCountResult = $conn->query($trainingCountQuery)->fetch_assoc();
$totalTraining = $trainingCountResult['total'];

// Get recent job postings
$recentJobsQuery = "SELECT j.*, c.name as company_name 
                   FROM job_postings j 
                   JOIN companies c ON j.company_id = c.id 
                   ORDER BY j.created_at DESC LIMIT 5";
$recentJobsResult = $conn->query($recentJobsQuery);

// Get recent applications
$recentApplicationsQuery = "SELECT a.*, j.title as job_title, s.name as student_name, c.name as company_name 
                           FROM applications a 
                           JOIN job_postings j ON a.job_id = j.id 
                           JOIN students s ON a.student_id = s.id 
                           JOIN companies c ON j.company_id = c.id 
                           ORDER BY a.applied_date DESC LIMIT 5";
$recentApplicationsResult = $conn->query($recentApplicationsQuery);

// Get recent students
$recentStudentsQuery = "SELECT s.*, u.email 
                       FROM students s 
                       JOIN users u ON s.user_id = u.id 
                       ORDER BY u.created_at DESC LIMIT 5";
$recentStudentsResult = $conn->query($recentStudentsQuery);

// Get recent companies
$recentCompaniesQuery = "SELECT c.*, u.email 
                        FROM companies c 
                        JOIN users u ON c.user_id = u.id 
                        ORDER BY u.created_at DESC LIMIT 5";
$recentCompaniesResult = $conn->query($recentCompaniesQuery);

// Get recent training programs
$recentTrainingQuery = "SELECT * FROM training_programs ORDER BY created_at DESC LIMIT 5";
$recentTrainingResult = $conn->query($recentTrainingQuery);

// Set page title
$pageTitle = 'Admin Dashboard';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - TPOS</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/admin.css">
    <style>
        body {
            font-size: .875rem;
        }

        .feather {
            width: 16px;
            height: 16px;
            vertical-align: text-bottom;
        }

        /* Sidebar */
        .sidebar-sticky {
            position: relative;
            top: 0;
            height: calc(100vh - 48px);
            padding-top: .5rem;
            overflow-x: hidden;
            overflow-y: auto;
        }

        .navbar-brand {
            padding-top: .75rem;
            padding-bottom: .75rem;
            font-size: 1rem;
            background-color: rgba(0, 0, 0, .25);
            box-shadow: inset -1px 0 0 rgba(0, 0, 0, .25);
        }

        .navbar .navbar-toggler {
            top: .25rem;
            right: 1rem;
        }

        .navbar .form-control {
            padding: .75rem 1rem;
            border-width: 0;
            border-radius: 0;
        }

        .form-control-dark {
            color: #fff;
            background-color: rgba(255, 255, 255, .1);
            border-color: rgba(255, 255, 255, .1);
        }

        .form-control-dark:focus {
            border-color: transparent;
            box-shadow: 0 0 0 3px rgba(255, 255, 255, .25);
        }

        /* Content */
        [role="main"] {
            padding-top: 48px;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0 shadow">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="#">TPOS Admin</a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="w-100"></div>
        <ul class="navbar-nav px-3">
            <li class="nav-item text-nowrap">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Sign out</a>
            </li>
                </ul>
            </nav>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Print Report
                    </button>
                        </div>
                    </div>
                </div>

                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-primary text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalStudents; ?></h3>
                                        <div>Total Students</div>
                                    </div>
                                    <div>
                                        <i class="fas fa-user-graduate fa-2x"></i>
            </div>
            </div>
        </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="students.php">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                        </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-success text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalCompanies; ?></h3>
                                        <div>Total Companies</div>
                                    </div>
                                    <div>
                                        <i class="fas fa-building fa-2x"></i>
                </div>
                    </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="companies.php">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-info text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalJobs; ?></h3>
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
                        <div class="card bg-secondary text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalTraining; ?></h3>
                                        <div>Training Programs</div>
                                    </div>
                                    <div>
                                        <i class="fas fa-chalkboard-teacher fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer d-flex align-items-center justify-content-between">
                                <a class="small text-white stretched-link" href="training-programs.php">View Details</a>
                                <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="card bg-warning text-white mb-4">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1"><?php echo $totalApplications; ?></h3>
                                        <div>Applications</div>
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
                    </div>
                    
                <!-- Recent Job Postings -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Recent Job Postings</h5>
                        <a href="jobs.php" class="btn btn-sm btn-primary">View All</a>
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
                                                    <th>Posted On</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($job = $recentJobsResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($job['title']); ?></td>
                                                        <td><?php echo htmlspecialchars($job['company_name']); ?></td>
                                                        <td><?php echo htmlspecialchars($job['location']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo strtolower($job['job_type']) === 'full-time' ? 'primary' : (strtolower($job['job_type']) === 'part-time' ? 'warning' : 'info'); ?>">
                                                <?php echo htmlspecialchars($job['job_type']); ?>
                                            </span>
                                        </td>
                                                        <td><?php echo date('M d, Y', strtotime($job['created_at'])); ?></td>
                                                        <td>
                                            <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="edit-job.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" title="Delete" 
                                                    onclick="deleteJob(<?php echo $job['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
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
                                                    <th>Student</th>
                                                    <th>Job Title</th>
                                                    <th>Company</th>
                                                    <th>Applied On</th>
                                                    <th>Status</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php while ($application = $recentApplicationsResult->fetch_assoc()): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($application['student_name']); ?></td>
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
                                            <a href="view-application.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="update-application.php?id=<?php echo $application['id']; ?>" class="btn btn-sm btn-primary" title="Update Status">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                    </div>
                                    </div>

                <!-- Recent Training Programs -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-chalkboard-teacher me-2"></i>Recent Training Programs</h5>
                        <a href="training-programs.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Instructor</th>
                                        <th>Duration</th>
                                        <th>Start Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($training = $recentTrainingResult->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($training['title']); ?></td>
                                            <td><?php echo htmlspecialchars($training['instructor'] ?: 'Not assigned'); ?></td>
                                            <td><?php echo htmlspecialchars($training['duration']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($training['start_date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $training['status'] === 'active' ? 'success' : 
                                                        ($training['status'] === 'completed' ? 'secondary' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($training['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view-training.php?id=<?php echo $training['id']; ?>" class="btn btn-sm btn-info" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit-training.php?id=<?php echo $training['id']; ?>" class="btn btn-sm btn-primary" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-user-plus fa-3x mb-3 text-primary"></i>
                                <h5 class="card-title">Add Student</h5>
                                <p class="card-text">Register a new student in the system</p>
                                <a href="add-student.php" class="btn btn-primary">Add Student</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-building fa-3x mb-3 text-success"></i>
                                <h5 class="card-title">Add Company</h5>
                                <p class="card-text">Register a new company in the system</p>
                                <a href="add-company.php" class="btn btn-success">Add Company</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-briefcase fa-3x mb-3 text-info"></i>
                                <h5 class="card-title">Add Job</h5>
                                <p class="card-text">Post a new job opportunity</p>
                                <a href="add-job.php" class="btn btn-info">Add Job</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="card h-100">
                            <div class="card-body text-center">
                                <i class="fas fa-chalkboard-teacher fa-3x mb-3 text-secondary"></i>
                                <h5 class="card-title">Add Training</h5>
                                <p class="card-text">Create a new training program</p>
                                <a href="add-training.php" class="btn btn-secondary">Add Training</a>
                            </div>
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
        // Delete job function
        function deleteJob(jobId) {
            if (confirm('Are you sure you want to delete this job posting?')) {
                window.location.href = 'delete-job.php?id=' + jobId;
            }
        }

        // Initialize tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl)
        })

        // Add active class to current nav item
        document.addEventListener('DOMContentLoaded', function() {
            const currentPage = window.location.pathname.split('/').pop();
            const navLinks = document.querySelectorAll('.nav-link');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentPage) {
                    link.classList.add('active');
                }
            });
        });
    </script>
</body>
</html> 