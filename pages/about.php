<?php
session_start();
require_once '../config/db_connect.php';

// Get some statistics for the about page
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM students) as total_students,
    (SELECT COUNT(*) FROM companies) as total_companies,
    (SELECT COUNT(*) FROM job_postings WHERE deadline >= CURDATE()) as active_jobs,
    (SELECT COUNT(*) FROM applications) as total_applications";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();

// Set page title
$pageTitle = 'About Us';
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
                   
                    
                    <li><a href="about.php" class="active">About</a></li>
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
            <div class="about-section">
                <div class="page-header text-center">
                    <h1>About TPOS</h1>
                    <p>Bridging the gap between talent and opportunity</p>
                </div>

                <!-- Statistics Section -->
                <div class="stats-section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-user-graduate"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['total_students']); ?></h3>
                                <p>Registered Students</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['total_companies']); ?></h3>
                                <p>Partner Companies</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['active_jobs']); ?></h3>
                                <p>Active Job Postings</p>
                            </div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-info">
                                <h3><?php echo number_format($stats['total_applications']); ?></h3>
                                <p>Job Applications</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Mission Section -->
                <div class="content-section">
                    <h2>Our Mission</h2>
                    <p>The Training and Placement Online System (TPOS) is dedicated to empowering students and facilitating their transition into successful professionals. We strive to create meaningful connections between academic excellence and industry opportunities.</p>
                </div>

                <!-- Features Section -->
                <div class="features-section">
                    <h2>What We Offer</h2>
                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-search"></i>
                            </div>
                            <h3>Job Search</h3>
                            <p>Access to a wide range of job opportunities from leading companies across various industries.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-handshake"></i>
                            </div>
                            <h3>Company Connections</h3>
                            <p>Direct interaction with top companies and opportunities to showcase your talents.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-calendar-check"></i>
                            </div>
                            <h3>Events & Training</h3>
                            <p>Regular recruitment drives, workshops, and training sessions for skill development.</p>
                        </div>
                        <div class="feature-card">
                            <div class="feature-icon">
                                <i class="fas fa-chart-line"></i>
                            </div>
                            <h3>Career Growth</h3>
                            <p>Resources and guidance for career development and professional growth.</p>
                        </div>
                    </div>
                </div>

                <!-- Process Section -->
                <div class="process-section">
                    <h2>How It Works</h2>
                    <div class="process-steps">
                        <div class="step">
                            <div class="step-number">1</div>
                            <h3>Register</h3>
                            <p>Create your account as a student or company to access our services.</p>
                        </div>
                        <div class="step">
                            <div class="step-number">2</div>
                            <h3>Complete Profile</h3>
                            <p>Add your details, skills, and preferences to stand out.</p>
                        </div>
                        <div class="step">
                            <div class="step-number">3</div>
                            <h3>Apply for Jobs</h3>
                            <p>Browse and apply for relevant opportunities.</p>
                        </div>
                        <div class="step">
                            <div class="step-number">4</div>
                            <h3>Get Hired</h3>
                            <p>Interview with companies and secure your dream job.</p>
                        </div>
                    </div>
                </div>

                <!-- Contact CTA Section -->
                <div class="contact-cta">
                    <h2>Ready to Get Started?</h2>
                    <p>Join our platform and take the first step towards your career success.</p>
                    <div class="cta-buttons">
                        <a href="register.php" class="btn btn-primary">Register Now</a>
                        <a href="contact.php" class="btn btn-secondary">Contact Us</a>
                    </div>
                </div>
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
                        <li><a href="contact.php">Contact</a></li>
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
</body>
</html> 