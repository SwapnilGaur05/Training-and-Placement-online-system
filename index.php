<?php
require_once 'includes/header.php';

// Get latest job postings
$jobsQuery = "SELECT j.*, c.name as company_name FROM job_postings j 
              JOIN companies c ON j.company_id = c.id 
              ORDER BY j.created_at DESC LIMIT 6";
$jobsResult = $conn->query($jobsQuery);

// Get upcoming events
$eventsQuery = "SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 3";
$eventsResult = $conn->query($eventsQuery);

// Get latest announcements
$announcementsQuery = "SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3";
$announcementsResult = $conn->query($announcementsQuery);

// Get statistics
$statsQuery = "SELECT 
    (SELECT COUNT(*) FROM job_postings WHERE deadline >= CURDATE()) as active_jobs,
    (SELECT COUNT(*) FROM companies) as total_companies,
    (SELECT COUNT(*) FROM students) as total_students,
    (SELECT COUNT(*) FROM applications WHERE status = 'Selected') as placements";
$statsResult = $conn->query($statsQuery);
$stats = $statsResult->fetch_assoc();
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-bg-animation"></div>
    <div class="container">
        <div class="hero-content">
            <h1 class="animate-fade-up">Your Career Journey Starts Here</h1>
            <p class="animate-fade-up delay-1">Connect with top companies, discover opportunities, and take the next step in your professional growth with our Training and Placement Online System.</p>
            <div class="hero-buttons animate-fade-up delay-2">
                <a href="pages/jobs.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Browse Jobs
                </a>
                <a href="pages/register.php" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-user-plus me-2"></i>Sign Up Now
                </a>
            </div>
        </div>
        <div class="hero-image animate-fade-left">
            <img src="assets/images/hero-illustration.svg" alt="Career Growth Illustration">
        </div>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="stats-grid">
            <div class="stat-card animate-fade-up">
                <div class="stat-icon">
                    <i class="fas fa-briefcase"></i>
                </div>
                <div class="stat-number"><?php echo $stats['active_jobs']; ?>+</div>
                <div class="stat-label">Active Jobs</div>
            </div>
            <div class="stat-card animate-fade-up delay-1">
                <div class="stat-icon">
                    <i class="fas fa-building"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_companies']; ?>+</div>
                <div class="stat-label">Partner Companies</div>
            </div>
            <div class="stat-card animate-fade-up delay-2">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-number"><?php echo $stats['total_students']; ?>+</div>
                <div class="stat-label">Registered Students</div>
            </div>
            <div class="stat-card animate-fade-up delay-3">
                <div class="stat-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="stat-number"><?php echo $stats['placements']; ?>+</div>
                <div class="stat-label">Successful Placements</div>
            </div>
        </div>
    </div>
</section>

<!-- Latest Jobs Section -->
<section class="jobs-section">
    <div class="container">
        <div class="section-header text-center">
            <h2 class="animate-fade-up">Latest Job Opportunities</h2>
            <p class="animate-fade-up delay-1">Explore the most recent job postings from our partner companies</p>
        </div>
        <div class="job-cards">
            <?php if ($jobsResult && $jobsResult->num_rows > 0): ?>
                <?php 
                $delay = 2;
                while ($job = $jobsResult->fetch_assoc()): 
                ?>
                    <div class="job-card animate-fade-up delay-<?php echo $delay; ?>">
                        <div class="job-card-header">
                            <div class="company-logo">
                                <i class="fas fa-building"></i>
                            </div>
                            <div class="job-title">
                                <h3><?php echo htmlspecialchars($job['title']); ?></h3>
                                <div class="company-name">
                                    <i class="fas fa-building-user me-1"></i>
                                    <?php echo htmlspecialchars($job['company_name']); ?>
                                </div>
                            </div>
                        </div>
                        <div class="job-details">
                            <div class="job-detail">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($job['location']); ?></span>
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
                        <div class="job-actions">
                            <a href="pages/view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary btn-sm">
                                <i class="fas fa-eye me-1"></i>View Details
                            </a>
                        </div>
                    </div>
                <?php 
                $delay++;
                endwhile; 
                ?>
            <?php else: ?>
                <div class="no-jobs text-center">
                    <i class="fas fa-briefcase fa-3x mb-3"></i>
                    <p>No job postings available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
        <div class="text-center mt-4">
            <a href="pages/jobs.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-list me-2"></i>View All Jobs
            </a>
        </div>
    </div>
</section>

<!-- Events Section -->
<section class="events-section">
    <div class="container">
        <div class="section-header text-center">
            <h2 class="animate-fade-up">Upcoming Events</h2>
            <p class="animate-fade-up delay-1">Stay updated with our latest training and placement events</p>
        </div>
        <div class="events-grid">
            <?php if ($eventsResult && $eventsResult->num_rows > 0): ?>
                <?php 
                $delay = 2;
                while ($event = $eventsResult->fetch_assoc()): 
                ?>
                    <div class="event-card animate-fade-up delay-<?php echo $delay; ?>">
                        <div class="event-date">
                            <div class="date">
                                <span class="day"><?php echo date('d', strtotime($event['event_date'])); ?></span>
                                <span class="month"><?php echo date('M', strtotime($event['event_date'])); ?></span>
                            </div>
                            <div class="time"><?php echo date('h:i A', strtotime($event['event_date'])); ?></div>
                        </div>
                        <div class="event-details">
                            <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                            <div class="event-info">
                                <span><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($event['location']); ?></span>
                            </div>
                            <p><?php echo substr(htmlspecialchars($event['description']), 0, 100) . '...'; ?></p>
                        </div>
                        <div class="event-actions">
                            <a href="pages/view-event.php?id=<?php echo $event['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-info-circle me-1"></i>Learn More
                            </a>
                        </div>
                    </div>
                <?php 
                $delay++;
                endwhile; 
                ?>
            <?php else: ?>
                <div class="no-events text-center">
                    <i class="fas fa-calendar fa-3x mb-3"></i>
                    <p>No upcoming events at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Announcements Section -->
<section class="announcements-section">
    <div class="container">
        <div class="section-header text-center">
            <h2 class="animate-fade-up">Latest Announcements</h2>
            <p class="animate-fade-up delay-1">Stay informed with important updates from the placement office</p>
        </div>
        <div class="announcements-timeline">
            <?php if ($announcementsResult && $announcementsResult->num_rows > 0): ?>
                <?php 
                $delay = 2;
                while ($announcement = $announcementsResult->fetch_assoc()): 
                ?>
                    <div class="announcement-item animate-fade-up delay-<?php echo $delay; ?>">
                        <div class="announcement-date">
                            <i class="fas fa-bullhorn"></i>
                            <span><?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></span>
                        </div>
                        <div class="announcement-content">
                            <h3><?php echo htmlspecialchars($announcement['title']); ?></h3>
                            <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                        </div>
                    </div>
                <?php 
                $delay++;
                endwhile; 
                ?>
            <?php else: ?>
                <div class="no-announcements text-center">
                    <i class="fas fa-bell-slash fa-3x mb-3"></i>
                    <p>No announcements available at the moment.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Call to Action Section -->
<section class="cta-section parallax">
    <div class="container">
        <div class="cta-content text-center">
            <h2 class="animate-fade-up">Ready to Start Your Career Journey?</h2>
            <p class="animate-fade-up delay-1">Join our platform today and connect with top companies looking for talent like you.</p>
            <div class="cta-buttons animate-fade-up delay-2">
                <a href="pages/register.php?type=student" class="btn btn-primary btn-lg me-3">
                    <i class="fas fa-user-graduate me-2"></i>Student Registration
                </a>
                <a href="pages/register.php?type=company" class="btn btn-outline-light btn-lg">
                    <i class="fas fa-building me-2"></i>Company Registration
                </a>
            </div>
        </div>
    </div>
</section>

<style>
/* Hero Section Styles */
.hero {
    position: relative;
    background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
    color: white;
    padding: 6rem 0;
    overflow: hidden;
}

.hero-bg-animation {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: url('assets/images/pattern.svg');
    opacity: 0.1;
    animation: slide 20s linear infinite;
}

.hero-content {
    max-width: 600px;
    position: relative;
    z-index: 1;
}

.hero h1 {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 1.5rem;
    line-height: 1.2;
}

.hero p {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

.hero-image {
    position: absolute;
    right: 0;
    top: 50%;
    transform: translateY(-50%);
    width: 45%;
    max-width: 600px;
}

/* Stats Section Styles */
.stats-section {
    background: white;
    padding: 4rem 0;
    margin-top: -4rem;
    border-radius: 2rem 2rem 0 0;
    position: relative;
    z-index: 2;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 2rem;
}

.stat-card {
    background: white;
    padding: 2rem;
    text-align: center;
    border-radius: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
}

.stat-icon {
    font-size: 2.5rem;
    color: #1a73e8;
    margin-bottom: 1rem;
}

.stat-number {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.stat-label {
    color: #6c757d;
    font-size: 1.1rem;
}

/* Jobs Section Styles */
.jobs-section {
    padding: 6rem 0;
    background: #f8f9fa;
}

.section-header {
    margin-bottom: 4rem;
}

.section-header h2 {
    font-size: 2.5rem;
    color: #2c3e50;
    margin-bottom: 1rem;
}

.section-header p {
    color: #6c757d;
    font-size: 1.1rem;
}

.job-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
    margin-bottom: 3rem;
}

.job-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.job-card:hover {
    transform: translateY(-5px);
}

/* Events Section Styles */
.events-section {
    padding: 6rem 0;
    background: white;
}

.events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 2rem;
}

.event-card {
    background: white;
    border-radius: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    overflow: hidden;
    transition: transform 0.3s ease;
}

.event-card:hover {
    transform: translateY(-5px);
}

.event-date {
    background: #1a73e8;
    color: white;
    padding: 1rem;
    text-align: center;
}

.event-date .day {
    font-size: 2rem;
    font-weight: 700;
}

.event-date .month {
    font-size: 1.1rem;
    text-transform: uppercase;
}

.event-details {
    padding: 1.5rem;
}

/* Announcements Section Styles */
.announcements-section {
    padding: 6rem 0;
    background: #f8f9fa;
}

.announcements-timeline {
    max-width: 800px;
    margin: 0 auto;
    position: relative;
}

.announcement-item {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.announcement-item:hover {
    transform: translateX(10px);
}

/* CTA Section Styles */
.cta-section {
    background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%),
                url('assets/images/cta-bg.jpg') center/cover;
    background-blend-mode: multiply;
    color: white;
    padding: 6rem 0;
    text-align: center;
}

.cta-content h2 {
    font-size: 2.5rem;
    margin-bottom: 1rem;
}

.cta-content p {
    font-size: 1.25rem;
    margin-bottom: 2rem;
    opacity: 0.9;
}

/* Animations */
.animate-fade-up {
    opacity: 0;
    transform: translateY(20px);
    animation: fadeUp 0.6s ease forwards;
}

.animate-fade-left {
    opacity: 0;
    transform: translateX(20px);
    animation: fadeLeft 0.6s ease forwards;
}

.delay-1 {
    animation-delay: 0.2s;
}

.delay-2 {
    animation-delay: 0.4s;
}

.delay-3 {
    animation-delay: 0.6s;
}

@keyframes fadeUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes fadeLeft {
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes slide {
    from {
        background-position: 0 0;
    }
    to {
        background-position: 100% 100%;
    }
}

/* Responsive Design */
@media (max-width: 991px) {
    .hero {
        padding: 4rem 0;
    }

    .hero h1 {
        font-size: 2.5rem;
    }

    .hero-image {
        display: none;
    }

    .hero-content {
        max-width: 100%;
        text-align: center;
    }

    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }

    .job-cards {
        grid-template-columns: 1fr;
    }

    .events-grid {
        grid-template-columns: 1fr;
    }

    .cta-buttons {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .cta-buttons .btn {
        width: 100%;
        margin: 0 !important;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 