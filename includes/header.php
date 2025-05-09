<?php
session_start();
require_once 'config/db_connect.php';

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
$userType = $isLoggedIn ? $_SESSION['user_type'] : '';

// Get current page for active menu highlighting
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Training and Placement Online System</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <a href="index.php">
                    <h1>TPOS</h1>
                    <span>Training & Placement Online System</span>
                </a>
            </div>
            <nav>
                <ul class="main-menu">
                    <li><a href="index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">Home</a></li>
                    <li><a href="pages/jobs.php" class="<?php echo ($currentPage == 'jobs.php') ? 'active' : ''; ?>">Jobs</a></li>
                    
                    
                    <li><a href="pages/about.php" class="<?php echo ($currentPage == 'about.php') ? 'active' : ''; ?>">About</a></li>
                    <li><a href="pages/contact.php" class="<?php echo ($currentPage == 'contact.php') ? 'active' : ''; ?>">Contact</a></li>
                </ul>
            </nav>
            <div class="user-actions">
                <?php if ($isLoggedIn): ?>
                    <div class="dropdown">
                        <button class="dropdown-toggle">
                            <i class="fas fa-user-circle"></i>
                            <span>My Account</span>
                        </button>
                        <div class="dropdown-menu">
                            <?php if ($userType == 'admin'): ?>
                                <a href="pages/admin/dashboard.php">Admin Dashboard</a>
                            <?php elseif ($userType == 'student'): ?>
                                <a href="pages/student/dashboard.php">Student Dashboard</a>
                                <a href="pages/student/profile.php">My Profile</a>
                                <a href="pages/student/applications.php">My Applications</a>
                            <?php elseif ($userType == 'company'): ?>
                                <a href="pages/company/dashboard.php">Company Dashboard</a>
                                <a href="pages/company/profile.php">Company Profile</a>
                                <a href="pages/company/job-postings.php">Job Postings</a>
                            <?php endif; ?>
                            <a href="pages/logout.php">Logout</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="pages/login.php" class="btn btn-login">Login</a>
                    <a href="pages/register.php" class="btn btn-register">Register</a>
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