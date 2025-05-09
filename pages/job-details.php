<?php
session_start();
require_once '../config/db_connect.php';

// Check if job ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: jobs.php');
    exit;
}

$jobId = $_GET['id'];

// Get job details
$jobQuery = "SELECT j.*, c.name as company_name, c.description as company_description, 
             c.website as company_website, c.location as company_location 
             FROM job_postings j 
             JOIN companies c ON j.company_id = c.id 
             WHERE j.id = ?";
$stmt = $conn->prepare($jobQuery);
$stmt->bind_param("i", $jobId);
$stmt->execute();
$result = $stmt->get_result();

// Check if job exists
if ($result->num_rows === 0) {
    header('Location: jobs.php');
    exit;
}

$job = $result->fetch_assoc();

// Check if user has already applied for this job
$hasApplied = false;
$applicationStatus = '';

if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'student') {
    // Get student ID
    $studentQuery = "SELECT id FROM students WHERE user_id = ?";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $studentResult = $stmt->get_result();
    
    if ($studentResult->num_rows > 0) {
        $student = $studentResult->fetch_assoc();
        $studentId = $student['id'];
        
        // Check if already applied
        $applicationQuery = "SELECT status FROM applications WHERE job_id = ? AND student_id = ?";
        $stmt = $conn->prepare($applicationQuery);
        $stmt->bind_param("ii", $jobId, $studentId);
        $stmt->execute();
        $applicationResult = $stmt->get_result();
        
        if ($applicationResult->num_rows > 0) {
            $hasApplied = true;
            $application = $applicationResult->fetch_assoc();
            $applicationStatus = $application['status'];
        }
    }
}

// Process job application
$applicationMessage = '';
$applicationError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['apply']) && isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'student') {
    // Get student ID
    $studentQuery = "SELECT id, resume_path FROM students WHERE user_id = ?";
    $stmt = $conn->prepare($studentQuery);
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $studentResult = $stmt->get_result();
    
    if ($studentResult->num_rows > 0) {
        $student = $studentResult->fetch_assoc();
        $studentId = $student['id'];
        
        // Check if student has uploaded a resume
        if (empty($student['resume_path'])) {
            $applicationError = 'Please upload your resume in your profile before applying for jobs.';
        } else {
            // Check if already applied
            $applicationQuery = "SELECT id FROM applications WHERE job_id = ? AND student_id = ?";
            $stmt = $conn->prepare($applicationQuery);
            $stmt->bind_param("ii", $jobId, $studentId);
            $stmt->execute();
            $applicationResult = $stmt->get_result();
            
            if ($applicationResult->num_rows > 0) {
                $applicationError = 'You have already applied for this job.';
            } else {
                // Insert application
                $stmt = $conn->prepare("INSERT INTO applications (job_id, student_id, status) VALUES (?, ?, 'Applied')");
                $stmt->bind_param("ii", $jobId, $studentId);
                
                if ($stmt->execute()) {
                    $hasApplied = true;
                    $applicationStatus = 'Applied';
                    $applicationMessage = 'Your application has been submitted successfully.';
                } else {
                    $applicationError = 'Failed to submit your application. Please try again later.';
                }
            }
        }
    } else {
        $applicationError = 'Student profile not found.';
    }
}

// Set page title
$pageTitle = $job['title'] . ' - Job Details';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($job['title']); ?> - Training and Placement Online System</title>
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
                    <li><a href="jobs.php" class="active">Jobs</a></li>
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
            <div class="mobile-menu-toggle">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>
    <main>
        <div class="container">
            <div class="job-details-page">
                <div class="back-link">
                    <a href="jobs.php"><i class="fas fa-arrow-left"></i> Back to Jobs</a>
                </div>
                
                <?php if (!empty($applicationMessage)): ?>
                    <div class="alert alert-success">
                        <?php echo $applicationMessage; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($applicationError)): ?>
                    <div class="alert alert-danger">
                        <?php echo $applicationError; ?>
                    </div>
                <?php endif; ?>
                
                <div class="job-header">
                    <div class="job-title-section">
                        <h1><?php echo htmlspecialchars($job['title']); ?></h1>
                        <div class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></div>
                        <div class="job-meta">
                            <span class="job-type"><?php echo htmlspecialchars($job['job_type']); ?></span>
                            <span class="job-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($job['location']); ?></span>
                            <span class="job-date"><i class="fas fa-calendar-alt"></i> Posted on <?php echo date('M d, Y', strtotime($job['created_at'])); ?></span>
                        </div>
                    </div>
                    <div class="job-actions">
                        <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'student'): ?>
                            <?php if ($hasApplied): ?>
                                <div class="application-status">
                                    <span class="status-label">Application Status:</span>
                                    <span class="status-value"><?php echo htmlspecialchars($applicationStatus); ?></span>
                                </div>
                            <?php else: ?>
                                <form action="job-details.php?id=<?php echo $jobId; ?>" method="post">
                                    <button type="submit" name="apply" class="btn btn-primary">Apply Now</button>
                                </form>
                            <?php endif; ?>
                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                            <a href="login.php" class="btn btn-primary">Login to Apply</a>
                        <?php endif; ?>
                        <button class="btn btn-outline share-btn"><i class="fas fa-share-alt"></i> Share</button>
                    </div>
                </div>
                
                <div class="job-content">
                    <div class="job-main-content">
                        <div class="job-section">
                            <h2>Job Description</h2>
                            <div class="job-description">
                                <?php echo nl2br(htmlspecialchars($job['description'])); ?>
                            </div>
                        </div>
                        
                        <div class="job-section">
                            <h2>Requirements</h2>
                            <div class="job-requirements">
                                <?php echo nl2br(htmlspecialchars($job['requirements'])); ?>
                            </div>
                        </div>
                        
                        <div class="job-section">
                            <h2>About the Company</h2>
                            <div class="company-description">
                                <p><?php echo nl2br(htmlspecialchars($job['company_description'])); ?></p>
                                <?php if (!empty($job['company_website'])): ?>
                                    <p><strong>Website:</strong> <a href="<?php echo htmlspecialchars($job['company_website']); ?>" target="_blank"><?php echo htmlspecialchars($job['company_website']); ?></a></p>
                                <?php endif; ?>
                                <p><strong>Location:</strong> <?php echo htmlspecialchars($job['company_location']); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="job-sidebar">
                        <div class="job-summary">
                            <h3>Job Summary</h3>
                            <ul>
                                <li>
                                    <span class="summary-label"><i class="fas fa-money-bill-wave"></i> Salary:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($job['salary']); ?></span>
                                </li>
                                <li>
                                    <span class="summary-label"><i class="fas fa-map-marker-alt"></i> Location:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($job['location']); ?></span>
                                </li>
                                <li>
                                    <span class="summary-label"><i class="fas fa-briefcase"></i> Job Type:</span>
                                    <span class="summary-value"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                </li>
                                <li>
                                    <span class="summary-label"><i class="fas fa-calendar-alt"></i> Application Deadline:</span>
                                    <span class="summary-value"><?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                                </li>
                            </ul>
                        </div>
                        
                        <div class="apply-box">
                            <h3>Apply for this Job</h3>
                            <?php if (isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'student'): ?>
                                <?php if ($hasApplied): ?>
                                    <div class="already-applied">
                                        <i class="fas fa-check-circle"></i>
                                        <p>You have already applied for this job.</p>
                                        <p>Current Status: <strong><?php echo htmlspecialchars($applicationStatus); ?></strong></p>
                                    </div>
                                <?php else: ?>
                                    <form action="job-details.php?id=<?php echo $jobId; ?>" method="post">
                                        <p>Ready to apply for this position? Click the button below to submit your application.</p>
                                        <button type="submit" name="apply" class="btn btn-primary btn-block">Apply Now</button>
                                    </form>
                                <?php endif; ?>
                            <?php elseif (!isset($_SESSION['user_id'])): ?>
                                <p>Please login to apply for this job.</p>
                                <a href="login.php" class="btn btn-primary btn-block">Login to Apply</a>
                            <?php else: ?>
                                <p>Only students can apply for jobs.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="related-jobs">
                    <h2>Similar Jobs</h2>
                    <div class="related-jobs-container">
                        <?php
                        // Get related jobs
                        $relatedJobsQuery = "SELECT j.id, j.title, j.location, j.job_type, c.name as company_name 
                                            FROM job_postings j 
                                            JOIN companies c ON j.company_id = c.id 
                                            WHERE j.id != ? AND (j.job_type = ? OR j.location = ?) 
                                            ORDER BY j.created_at DESC LIMIT 3";
                        $stmt = $conn->prepare($relatedJobsQuery);
                        $stmt->bind_param("iss", $jobId, $job['job_type'], $job['location']);
                        $stmt->execute();
                        $relatedJobsResult = $stmt->get_result();
                        
                        if ($relatedJobsResult && $relatedJobsResult->num_rows > 0):
                            while ($relatedJob = $relatedJobsResult->fetch_assoc()):
                        ?>
                            <div class="related-job-card">
                                <h3><a href="job-details.php?id=<?php echo $relatedJob['id']; ?>"><?php echo htmlspecialchars($relatedJob['title']); ?></a></h3>
                                <div class="company-name"><?php echo htmlspecialchars($relatedJob['company_name']); ?></div>
                                <div class="job-meta">
                                    <span class="job-location"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($relatedJob['location']); ?></span>
                                    <span class="job-type"><?php echo htmlspecialchars($relatedJob['job_type']); ?></span>
                                </div>
                            </div>
                        <?php
                            endwhile;
                        else:
                        ?>
                            <p>No similar jobs found at the moment.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section about">
                    <h3>About TPOS</h3>
                    <p>Training and Placement Online System is a comprehensive platform connecting students, companies, and placement officers to streamline the recruitment process.</p>
                    <div class="social-links">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
                <div class="footer-section links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="jobs.php">Jobs</a></li>
                        <li><a href="companies.php">Companies</a></li>
                        <li><a href="events.php">Events</a></li>
                        <li><a href="about.php">About</a></li>
                        <li><a href="contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-section contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-map-marker-alt"></i> 123 Education Street, Campus Area</p>
                    <p><i class="fas fa-phone"></i> +1 234 567 8900</p>
                    <p><i class="fas fa-envelope"></i> info@tpos.edu</p>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; <?php echo date('Y'); ?> Training and Placement Online System. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script src="../assets/js/main.js"></script>
    <script>
        // Share functionality
        document.querySelector('.share-btn').addEventListener('click', function() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($job['title']); ?>',
                    text: 'Check out this job: <?php echo addslashes($job['title']); ?> at <?php echo addslashes($job['company_name']); ?>',
                    url: window.location.href
                })
                .catch(console.error);
            } else {
                // Fallback for browsers that don't support the Web Share API
                prompt('Copy this link to share:', window.location.href);
            }
        });
    </script>
</body>
</html> 