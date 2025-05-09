<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Student Dashboard';
}

// Get student information
$student_query = "SELECT s.* FROM students s 
                 JOIN users u ON s.user_id = u.id 
                 WHERE u.id = ?";
$stmt = $conn->prepare($student_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?> - TPOS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/student.css">
    <style>
        .navbar {
            background-color: #ffffff !important;
            border-bottom: 1px solid #e9ecef;
        }
        .navbar-brand {
            color: #1a73e8 !important;
            font-weight: 600;
        }
        .nav-link {
            color: #495057 !important;
        }
        .nav-link:hover {
            color: #1a73e8 !important;
        }
        .nav-link.active {
            color: #1a73e8 !important;
            background-color: #f8f9fa !important;
            border-left: 3px solid #1a73e8;
        }
        .sidebar {
            background-color: #ffffff !important;
            border-right: 1px solid #e9ecef;
        }
        .student-info {
            padding: 1.5rem;
            border-bottom: 1px solid #e9ecef;
        }
        .student-info h6 {
            color: #2c3e50;
            font-weight: 600;
        }
        .sidebar-heading {
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            color: #6c757d;
        }
        .nav-item i {
            width: 1.5rem;
            color: #6c757d;
            transition: all 0.3s ease;
        }
        .nav-link:hover i {
            color: #1a73e8;
        }
        .nav-link.active i {
            color: #1a73e8;
        }
        .badge-new {
            font-size: 0.7rem;
            padding: 0.25rem 0.5rem;
            border-radius: 2rem;
            background-color: #1a73e8;
            color: white;
        }
    </style>
</head>
<body>
    <header class="navbar navbar-light sticky-top flex-md-nowrap p-0 shadow-sm">
        <a class="navbar-brand col-md-3 col-lg-2 me-0 px-3" href="dashboard.php">
            <i class="fas fa-graduation-cap me-2"></i>TPOS
        </a>
        <button class="navbar-toggler position-absolute d-md-none collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu" aria-controls="sidebarMenu" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="w-100"></div>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap">
                <a class="nav-link px-3" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Sign out
                </a>
            </div>
        </div>
    </header>

    <div class="container-fluid">
        <div class="row">
            <nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="student-info">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-circle fa-2x me-2 text-primary"></i>
                            <div>
                                <h6 class="mb-0"><?php echo htmlspecialchars($student['name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($student['roll_number']); ?></small>
                            </div>
                        </div>
                    </div>

                    <ul class="nav flex-column mt-3">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i>
                                Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'active' : ''; ?>" href="jobs.php">
                                <i class="fas fa-briefcase me-2"></i>
                                Available Jobs
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'applications.php' ? 'active' : ''; ?>" href="applications.php">
                                <i class="fas fa-file-alt me-2"></i>
                                My Applications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'trainings.php' ? 'active' : ''; ?>" href="trainings.php">
                                <i class="fas fa-chalkboard-teacher me-2"></i>
                                Training Programs
                                <span class="badge badge-new ms-2">New</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'events.php' ? 'active' : ''; ?>" href="events.php">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Events
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1">
                        <span>Profile</span>
                    </h6>
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'myprofile.php' ? 'active' : ''; ?>" href="myprofile.php">
                                <i class="fas fa-user me-2"></i>
                                My Profile
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'resume.php' ? 'active' : ''; ?>" href="resume.php">
                                <i class="fas fa-file-pdf me-2"></i>
                                Resume
                            </a>
                        </li>
                    </ul>

                    <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1">
                        <span>Quick Links</span>
                    </h6>
                    <ul class="nav flex-column mb-2">
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'announcements.php' ? 'active' : ''; ?>" href="announcements.php">
                                <i class="fas fa-bullhorn me-2"></i>
                                Announcements
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'companies.php' ? 'active' : ''; ?>" href="companies.php">
                                <i class="fas fa-building me-2"></i>
                                Companies
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
</body>
</html> 