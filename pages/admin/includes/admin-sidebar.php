<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>

<div class="admin-sidebar">
    <div class="sidebar-header">
        <div class="logo-container">
            <i class="fas fa-graduation-cap"></i>
            <span>Admin Panel</span>
        </div>
        <button class="sidebar-toggle" id="sidebarToggle">
            <i class="fas fa-bars"></i>
        </button>
    </div>
    
    <div class="sidebar-content">
        <nav class="sidebar-nav">
            <div class="nav-section">
                <h5 class="nav-section-title">Main</h5>
                <ul class="nav-items">
                    <li class="nav-item <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">
                        <a href="dashboard.php">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h5 class="nav-section-title">Management</h5>
                <ul class="nav-items">
                    <li class="nav-item <?php echo $current_page === 'students.php' ? 'active' : ''; ?>">
                        <a href="students.php">
                            <i class="fas fa-user-graduate"></i>
                            <span>Students</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page === 'companies.php' ? 'active' : ''; ?>">
                        <a href="companies.php">
                            <i class="fas fa-building"></i>
                            <span>Companies</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page === 'jobs.php' ? 'active' : ''; ?>">
                        <a href="jobs.php">
                            <i class="fas fa-briefcase"></i>
                            <span>Jobs</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page === 'events.php' ? 'active' : ''; ?>">
                        <a href="events.php">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Events</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page === 'applications.php' ? 'active' : ''; ?>">
                        <a href="applications.php">
                            <i class="fas fa-file-alt"></i>
                            <span>Applications</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h5 class="nav-section-title">Content</h5>
                <ul class="nav-items">
                    <li class="nav-item <?php echo $current_page === 'announcements.php' ? 'active' : ''; ?>">
                        <a href="announcements.php">
                            <i class="fas fa-bullhorn"></i>
                            <span>Announcements</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page === 'trainings.php' ? 'active' : ''; ?>">
                        <a href="trainings.php">
                            <i class="fas fa-chalkboard-teacher"></i>
                            <span>Training Programs</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h5 class="nav-section-title">Reports</h5>
                <ul class="nav-items">
                    <li class="nav-item <?php echo $current_page === 'placement-stats.php' ? 'active' : ''; ?>">
                        <a href="placement-stats.php">
                            <i class="fas fa-chart-bar"></i>
                            <span>Placement Statistics</span>
                        </a>
                    </li>
                    <li class="nav-item <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>">
                        <a href="reports.php">
                            <i class="fas fa-file-pdf"></i>
                            <span>Generate Reports</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="nav-section">
                <h5 class="nav-section-title">Settings</h5>
                <ul class="nav-items">
                    <li class="nav-item <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>">
                        <a href="profile.php">
                            <i class="fas fa-user-cog"></i>
                            <span>Profile Settings</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </div>
        </nav>
    </div>
</div>

<style>
.admin-sidebar {
    width: 280px;
    height: 100vh;
    position: fixed;
    left: 0;
    top: 0;
    background: #ffffff;
    border-right: 1px solid #e9ecef;
    display: flex;
    flex-direction: column;
    transition: all 0.3s ease;
    z-index: 1000;
}

.sidebar-header {
    padding: 1.5rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.logo-container {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.25rem;
    font-weight: 600;
    color: #1a73e8;
}

.logo-container i {
    font-size: 1.5rem;
}

.sidebar-toggle {
    display: none;
    background: none;
    border: none;
    color: #6c757d;
    font-size: 1.25rem;
    cursor: pointer;
    padding: 0.5rem;
    border-radius: 0.5rem;
    transition: all 0.3s ease;
}

.sidebar-toggle:hover {
    background: #f8f9fa;
    color: #1a73e8;
}

.sidebar-content {
    flex: 1;
    overflow-y: auto;
    padding: 1.5rem 0;
}

.nav-section {
    margin-bottom: 2rem;
}

.nav-section-title {
    font-size: 0.75rem;
    font-weight: 600;
    text-transform: uppercase;
    color: #6c757d;
    padding: 0 1.5rem;
    margin-bottom: 0.75rem;
}

.nav-items {
    list-style: none;
    padding: 0;
    margin: 0;
}

.nav-item {
    margin-bottom: 0.25rem;
}

.nav-item a {
    display: flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    color: #495057;
    text-decoration: none;
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.nav-item a i {
    width: 1.5rem;
    font-size: 1.1rem;
    margin-right: 1rem;
    color: #6c757d;
    transition: all 0.3s ease;
}

.nav-item a:hover {
    background: #f8f9fa;
    color: #1a73e8;
}

.nav-item a:hover i {
    color: #1a73e8;
}

.nav-item.active a {
    background: #f8f9fa;
    color: #1a73e8;
    border-left-color: #1a73e8;
}

.nav-item.active a i {
    color: #1a73e8;
}

/* Responsive Design */
@media (max-width: 991px) {
    .admin-sidebar {
        transform: translateX(-100%);
    }

    .admin-sidebar.show {
        transform: translateX(0);
    }

    .sidebar-toggle {
        display: block;
    }

    .main-content {
        margin-left: 0 !important;
    }
}

/* Scrollbar Styling */
.sidebar-content::-webkit-scrollbar {
    width: 6px;
}

.sidebar-content::-webkit-scrollbar-track {
    background: #f8f9fa;
}

.sidebar-content::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 3px;
}

.sidebar-content::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.admin-sidebar');
    
    sidebarToggle.addEventListener('click', function() {
        sidebar.classList.toggle('show');
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function(event) {
        if (window.innerWidth <= 991) {
            const isClickInside = sidebar.contains(event.target) || 
                                sidebarToggle.contains(event.target);
            
            if (!isClickInside && sidebar.classList.contains('show')) {
                sidebar.classList.remove('show');
            }
        }
    });
});
</script> 