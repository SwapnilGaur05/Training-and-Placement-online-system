<?php
// Get current page name
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-dark sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'students.php' ? 'active' : ''; ?>" href="students.php">
                    <i class="fas fa-user-graduate me-2"></i>
                    Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'companies.php' ? 'active' : ''; ?>" href="companies.php">
                    <i class="fas fa-building me-2"></i>
                    Companies
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'jobs.php' ? 'active' : ''; ?>" href="jobs.php">
                    <i class="fas fa-briefcase me-2"></i>
                    Jobs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'training-programs.php' ? 'active' : ''; ?>" href="training-programs.php">
                    <i class="fas fa-chalkboard-teacher me-2"></i>
                    Training Programs
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>" href="events.php">
                    <i class="fas fa-calendar-alt me-2"></i>
                    Events
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'applications.php' ? 'active' : ''; ?>" href="applications.php">
                    <i class="fas fa-file-alt me-2"></i>
                    Applications
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Management</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'add-student.php' ? 'active' : ''; ?>" href="add-student.php">
                    <i class="fas fa-user-plus me-2"></i>
                    Add Student
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'add-company.php' ? 'active' : ''; ?>" href="add-company.php">
                    <i class="fas fa-plus-circle me-2"></i>
                    Add Company
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'add-job.php' ? 'active' : ''; ?>" href="add-job.php">
                    <i class="fas fa-plus-square me-2"></i>
                    Add Job
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'add-training.php' ? 'active' : ''; ?>" href="add-training.php">
                    <i class="fas fa-plus me-2"></i>
                    Add Training
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo $currentPage === 'add-event.php' ? 'active' : ''; ?>" href="add-event.php">
                    <i class="fas fa-calendar-plus me-2"></i>
                    Add Event
                </a>
            </li>
        </ul>

        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Reports</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-chart-bar me-2"></i>
                    Placement Statistics
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-file-pdf me-2"></i>
                    Generate Reports
                </a>
            </li>
        </ul>
    </div>
</nav>

<style>
.sidebar {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    z-index: 100;
    padding: 48px 0 0;
    box-shadow: inset -1px 0 0 rgba(0, 0, 0, .1);
}

.sidebar .nav-link {
    font-weight: 500;
    color: #fff;
    padding: .5rem 1rem;
}

.sidebar .nav-link:hover {
    color: #007bff;
}

.sidebar .nav-link.active {
    color: #007bff;
}

.sidebar-heading {
    font-size: .75rem;
    text-transform: uppercase;
    color: #6c757d !important;
}

@media (max-width: 767.98px) {
    .sidebar {
        top: 5rem;
    }
}
</style> 
 