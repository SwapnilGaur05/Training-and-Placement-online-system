<?php
session_start();
require_once '../config/db_connect.php';

// Initialize variables
$success = '';
$error = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all required fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        // Insert into contact_messages table (you'll need to create this table)
        $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, subject, message, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssss", $name, $email, $subject, $message);
        
        if ($stmt->execute()) {
            $success = 'Thank you for your message. We will get back to you soon!';
            // Clear form data
            $name = $email = $subject = $message = '';
        } else {
            $error = 'Sorry, there was an error sending your message. Please try again later.';
        }
        
        $stmt->close();
    }
}

// Set page title
$pageTitle = 'Contact Us';
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
                    
                    
                    <li><a href="about.php">About</a></li>
                    <li><a href="contact.php" class="active">Contact</a></li>
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
            <div class="contact-section">
                <div class="page-header text-center">
                    <h1>Contact Us</h1>
                    <p>Get in touch with our team for any queries or assistance</p>
                </div>

                <div class="contact-content">
                    <!-- Contact Information -->
                    <div class="contact-info">
                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <h3>Visit Us</h3>
                            <p>Training & Placement Office</p>
                            <p>123 Education Street</p>
                            <p>City, Country - 12345</p>
                        </div>

                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-clock"></i>
                            </div>
                            <h3>Office Hours</h3>
                            <p>Monday - Friday</p>
                            <p>9:00 AM - 5:00 PM</p>
                            <p>Closed on Weekends & Holidays</p>
                        </div>

                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-phone-alt"></i>
                            </div>
                            <h3>Call Us</h3>
                            <p>Phone: +1 234 567 890</p>
                            <p>Mobile: +1 234 567 891</p>
                            <p>Fax: +1 234 567 892</p>
                        </div>

                        <div class="info-card">
                            <div class="info-icon">
                                <i class="fas fa-envelope"></i>
                            </div>
                            <h3>Email Us</h3>
                            <p>info@tpos.com</p>
                            <p>support@tpos.com</p>
                            <p>careers@tpos.com</p>
                        </div>
                    </div>

                    <!-- Contact Form -->
                    <div class="contact-form-container">
                        <div class="form-header">
                            <h2>Send us a Message</h2>
                            <p>Fill out the form below and we'll get back to you as soon as possible.</p>
                        </div>

                        <?php if (!empty($success)): ?>
                            <div class="alert alert-success">
                                <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($error)): ?>
                            <div class="alert alert-danger">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form action="contact.php" method="post" class="contact-form">
                            <div class="form-group">
                                <label for="name">Full Name *</label>
                                <input type="text" id="name" name="name" class="form-control" value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="email">Email Address *</label>
                                <input type="email" id="email" name="email" class="form-control" value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="subject">Subject *</label>
                                <input type="text" id="subject" name="subject" class="form-control" value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>" required>
                            </div>

                            <div class="form-group">
                                <label for="message">Message *</label>
                                <textarea id="message" name="message" class="form-control" rows="5" required><?php echo isset($message) ? htmlspecialchars($message) : ''; ?></textarea>
                            </div>

                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>

                <!-- Map Section -->
                <div class="map-section">
                    <h2>Our Location</h2>
                    <div class="map-container">
                        <!-- Replace with your actual Google Maps embed code -->
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3024.2219901290355!2d-74.00369368400567!3d40.71277937933185!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x89c25a23e28c1191%3A0x49f75d3281df052a!2s150%20Park%20Row%2C%20New%20York%2C%20NY%2010007%2C%20USA!5e0!3m2!1sen!2s!4v1628090679222!5m2!1sen!2s"
                            width="100%"
                            height="450"
                            style="border:0;"
                            allowfullscreen=""
                            loading="lazy">
                        </iframe>
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
                        <li><a href="about.php">About</a></li>
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