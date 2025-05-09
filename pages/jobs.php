<?php
session_start();
require_once '../config/db_connect.php';

// Initialize filter variables
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$jobType = isset($_GET['job_type']) ? $_GET['job_type'] : '';

// Build the query
$query = "SELECT j.*, c.name as company_name FROM job_postings j 
          JOIN companies c ON j.company_id = c.id 
          WHERE 1=1";

$params = [];
$types = "";

// Add filters to query if provided
if (!empty($keyword)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ?)";
    $keyword = "%$keyword%";
    $params[] = $keyword;
    $params[] = $keyword;
    $params[] = $keyword;
    $types .= "sss";
}

if (!empty($location)) {
    $query .= " AND j.location LIKE ?";
    $location = "%$location%";
    $params[] = $location;
    $types .= "s";
}

if (!empty($jobType)) {
    $query .= " AND j.job_type = ?";
    $params[] = $jobType;
    $types .= "s";
}

// Add order by
$query .= " ORDER BY j.created_at DESC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get distinct locations for filter dropdown
$locationsQuery = "SELECT DISTINCT location FROM job_postings ORDER BY location";
$locationsResult = $conn->query($locationsQuery);
$locations = [];
if ($locationsResult && $locationsResult->num_rows > 0) {
    while ($row = $locationsResult->fetch_assoc()) {
        $locations[] = $row['location'];
    }
}

// Set page title
$pageTitle = 'Job Listings';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Listings - Training and Placement Online System</title>
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
            <div class="page-header">
                <h1>Job Listings</h1>
                <p>Explore job opportunities from our partner companies</p>
            </div>
            
            <div class="job-filter-section">
                <form id="job-filter-form" action="jobs.php" method="get">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label for="keyword">Keyword</label>
                            <input type="text" id="keyword" name="keyword" class="form-control" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Job title, skills, or company">
                        </div>
                        
                        <div class="filter-group">
                            <label for="location">Location</label>
                            <select id="location" name="location" class="form-control">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($location == $loc) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label for="job_type">Job Type</label>
                            <select id="job_type" name="job_type" class="form-control">
                                <option value="">All Types</option>
                                <option value="Full-time" <?php echo ($jobType == 'Full-time') ? 'selected' : ''; ?>>Full-time</option>
                                <option value="Part-time" <?php echo ($jobType == 'Part-time') ? 'selected' : ''; ?>>Part-time</option>
                                <option value="Internship" <?php echo ($jobType == 'Internship') ? 'selected' : ''; ?>>Internship</option>
                            </select>
                        </div>
                        
                        <div class="filter-actions">
                            <button type="submit" class="btn">Search</button>
                            <button type="button" class="btn reset-filters">Reset</button>
                        </div>
                    </div>
                </form>
            </div>
            
            <div class="job-results">
                <div class="job-count">
                    <p>Found <strong><?php echo $result->num_rows; ?></strong> job listings</p>
                </div>
                
                <div class="job-listings">
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($job = $result->fetch_assoc()): ?>
                            <div class="job-card">
                                <div class="job-card-header">
                                    <div class="company-logo">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="job-title">
                                        <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                        <div class="company-name"><?php echo htmlspecialchars($job['company_name']); ?></div>
                                    </div>
                                </div>
                                <div class="job-details">
                                    <div class="job-detail">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span class="job-location"><?php echo htmlspecialchars($job['location']); ?></span>
                                    </div>
                                    <div class="job-detail">
                                        <i class="fas fa-money-bill-wave"></i>
                                        <span><?php echo htmlspecialchars($job['salary']); ?></span>
                                    </div>
                                    <div class="job-detail">
                                        <i class="fas fa-calendar-alt"></i>
                                        <span>Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></span>
                                    </div>
                                </div>
                                <div class="job-description">
                                    <p><?php echo substr(htmlspecialchars($job['description']), 0, 150) . '...'; ?></p>
                                </div>
                                <div class="job-actions">
                                    <a href="job-details.php?id=<?php echo $job['id']; ?>" class="btn">View Details</a>
                                    <span class="job-type"><?php echo htmlspecialchars($job['job_type']); ?></span>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-jobs">
                            <p>No job listings found matching your criteria. Please try different search terms or browse all jobs.</p>
                        </div>
                    <?php endif; ?>
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
        // Reset filters
        document.querySelector('.reset-filters').addEventListener('click', function() {
            document.getElementById('keyword').value = '';
            document.getElementById('location').value = '';
            document.getElementById('job_type').value = '';
            document.getElementById('job-filter-form').submit();
        });
    </script>
</body>
</html> 