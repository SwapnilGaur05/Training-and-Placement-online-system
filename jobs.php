<?php
require_once 'includes/header.php';

// Get filter parameters
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$location = $_GET['location'] ?? '';
$type = $_GET['type'] ?? '';

// Base query
$query = "SELECT j.*, c.name as company_name, 
          (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) as application_count 
          FROM job_postings j 
          JOIN companies c ON j.company_id = c.id 
          WHERE j.deadline >= CURDATE()";

// Apply filters
if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR c.name LIKE ?)";
}
if (!empty($category)) {
    $query .= " AND j.category = ?";
}
if (!empty($location)) {
    $query .= " AND j.location = ?";
}
if (!empty($type)) {
    $query .= " AND j.job_type = ?";
}

$query .= " ORDER BY j.created_at DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);

if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
} elseif (!empty($category)) {
    $stmt->bind_param("s", $category);
} elseif (!empty($location)) {
    $stmt->bind_param("s", $location);
} elseif (!empty($type)) {
    $stmt->bind_param("s", $type);
}

$stmt->execute();
$result = $stmt->get_result();

// Get unique categories, locations, and job types for filters
$categoriesQuery = "SELECT DISTINCT category FROM job_postings WHERE category IS NOT NULL";
$locationsQuery = "SELECT DISTINCT location FROM job_postings WHERE location IS NOT NULL";
$typesQuery = "SELECT DISTINCT job_type FROM job_postings WHERE job_type IS NOT NULL";

$categories = $conn->query($categoriesQuery)->fetch_all(MYSQLI_ASSOC);
$locations = $conn->query($locationsQuery)->fetch_all(MYSQLI_ASSOC);
$types = $conn->query($typesQuery)->fetch_all(MYSQLI_ASSOC);
?>

<!-- Jobs Hero Section -->
<section class="jobs-hero">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6 animate-fade-up">
                <h1 class="display-4 fw-bold mb-4">Find Your Dream Job</h1>
                <p class="lead mb-4">Explore hundreds of job opportunities from top companies and take the next step in your career.</p>
            </div>
            <div class="col-lg-6 animate-fade-left">
                <div class="search-box">
                    <form action="" method="GET" class="job-search-form">
                        <div class="input-group">
                            <input type="text" class="form-control" name="search" placeholder="Search jobs, companies..." value="<?php echo htmlspecialchars($search); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Jobs Content Section -->
<section class="jobs-content">
    <div class="container">
        <div class="row">
            <!-- Filters Sidebar -->
            <div class="col-lg-3">
                <div class="filters-card animate-fade-right">
                    <h3 class="filters-title">Filters</h3>
                    <form action="" method="GET" id="filtersForm">
                        <!-- Category Filter -->
                        <div class="filter-group">
                            <h4>Category</h4>
                            <select class="form-select" name="category" onchange="this.form.submit()">
                                <option value="">All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                            <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($cat['category']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Location Filter -->
                        <div class="filter-group">
                            <h4>Location</h4>
                            <select class="form-select" name="location" onchange="this.form.submit()">
                                <option value="">All Locations</option>
                                <?php foreach ($locations as $loc): ?>
                                    <option value="<?php echo htmlspecialchars($loc['location']); ?>"
                                            <?php echo $location === $loc['location'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($loc['location']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Job Type Filter -->
                        <div class="filter-group">
                            <h4>Job Type</h4>
                            <select class="form-select" name="type" onchange="this.form.submit()">
                                <option value="">All Types</option>
                                <?php foreach ($types as $t): ?>
                                    <option value="<?php echo htmlspecialchars($t['job_type']); ?>"
                                            <?php echo $type === $t['job_type'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($t['job_type']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Clear Filters -->
                        <div class="filter-group">
                            <a href="jobs.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-undo me-2"></i>Clear Filters
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Jobs List -->
            <div class="col-lg-9">
                <div class="jobs-list">
                    <?php if ($result->num_rows > 0): ?>
                        <?php 
                        $delay = 0;
                        while ($job = $result->fetch_assoc()): 
                        ?>
                            <div class="job-card animate-fade-up" style="animation-delay: <?php echo $delay; ?>s">
                                <div class="job-card-header">
                                    <div class="company-logo">
                                        <i class="fas fa-building"></i>
                                    </div>
                                    <div class="job-info">
                                        <h2 class="job-title"><?php echo htmlspecialchars($job['title']); ?></h2>
                                        <div class="company-name">
                                            <i class="fas fa-building-user me-1"></i>
                                            <?php echo htmlspecialchars($job['company_name']); ?>
                                        </div>
                                    </div>
                                    <div class="job-type-badge <?php echo strtolower($job['job_type']); ?>">
                                        <?php echo htmlspecialchars($job['job_type']); ?>
                                    </div>
                                </div>
                                <div class="job-card-body">
                                    <div class="job-details">
                                        <div class="job-detail">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?php echo htmlspecialchars($job['location']); ?></span>
                                        </div>
                                        <div class="job-detail">
                                            <i class="fas fa-briefcase"></i>
                                            <span><?php echo htmlspecialchars($job['category']); ?></span>
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
                                    <p class="job-description">
                                        <?php echo substr(htmlspecialchars($job['description']), 0, 200) . '...'; ?>
                                    </p>
                                </div>
                                <div class="job-card-footer">
                                    <div class="applications-count">
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo $job['application_count']; ?> applications
                                    </div>
                                    <a href="view-job.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">
                                        <i class="fas fa-eye me-1"></i>View Details
                                    </a>
                                </div>
                            </div>
                        <?php 
                        $delay += 0.1;
                        endwhile; 
                        ?>
                    <?php else: ?>
                        <div class="no-jobs-found animate-fade-up">
                            <i class="fas fa-search fa-3x mb-3"></i>
                            <h3>No Jobs Found</h3>
                            <p>Try adjusting your search criteria or removing filters</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
/* Jobs Hero Section */
.jobs-hero {
    background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
    color: white;
    padding: 6rem 0;
    position: relative;
    overflow: hidden;
}

.jobs-hero::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('assets/images/pattern.svg');
    opacity: 0.1;
    animation: slide 20s linear infinite;
}

.search-box {
    background: rgba(255, 255, 255, 0.1);
    padding: 2rem;
    border-radius: 1rem;
    backdrop-filter: blur(10px);
}

.job-search-form .form-control {
    border: none;
    padding: 1rem;
    font-size: 1.1rem;
    border-radius: 0.5rem;
}

/* Jobs Content Section */
.jobs-content {
    padding: 4rem 0;
    background: #f8f9fa;
}

/* Filters Card */
.filters-card {
    background: white;
    padding: 1.5rem;
    border-radius: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    position: sticky;
    top: 2rem;
}

.filters-title {
    font-size: 1.25rem;
    margin-bottom: 1.5rem;
    color: #2c3e50;
}

.filter-group {
    margin-bottom: 1.5rem;
}

.filter-group h4 {
    font-size: 1rem;
    color: #6c757d;
    margin-bottom: 0.5rem;
}

/* Job Cards */
.job-card {
    background: white;
    border-radius: 1rem;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
}

.job-card:hover {
    transform: translateY(-5px);
}

.job-card-header {
    display: flex;
    align-items: center;
    margin-bottom: 1.5rem;
}

.company-logo {
    width: 4rem;
    height: 4rem;
    background: #e3f2fd;
    border-radius: 1rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
}

.company-logo i {
    font-size: 2rem;
    color: #1a73e8;
}

.job-info {
    flex: 1;
}

.job-title {
    font-size: 1.25rem;
    margin-bottom: 0.25rem;
    color: #2c3e50;
}

.company-name {
    color: #6c757d;
    font-size: 0.9rem;
}

.job-type-badge {
    padding: 0.5rem 1rem;
    border-radius: 2rem;
    font-size: 0.9rem;
    font-weight: 500;
}

.job-type-badge.full-time {
    background: #e3f2fd;
    color: #1a73e8;
}

.job-type-badge.part-time {
    background: #fff3e0;
    color: #f57c00;
}

.job-type-badge.internship {
    background: #e8f5e9;
    color: #43a047;
}

.job-card-body {
    margin-bottom: 1.5rem;
}

.job-details {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-bottom: 1rem;
}

.job-detail {
    display: flex;
    align-items: center;
    color: #6c757d;
}

.job-detail i {
    margin-right: 0.5rem;
    color: #1a73e8;
}

.job-description {
    color: #6c757d;
    margin: 0;
    line-height: 1.6;
}

.job-card-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 1rem;
    border-top: 1px solid #e9ecef;
}

.applications-count {
    color: #6c757d;
    font-size: 0.9rem;
}

/* No Jobs Found */
.no-jobs-found {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 1rem;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
}

.no-jobs-found i {
    color: #1a73e8;
    opacity: 0.5;
}

.no-jobs-found h3 {
    color: #2c3e50;
    margin-bottom: 0.5rem;
}

.no-jobs-found p {
    color: #6c757d;
    margin: 0;
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

.animate-fade-right {
    opacity: 0;
    transform: translateX(-20px);
    animation: fadeRight 0.6s ease forwards;
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

@keyframes fadeRight {
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
    .jobs-hero {
        padding: 4rem 0;
    }
    
    .filters-card {
        margin-bottom: 2rem;
        position: static;
    }
    
    .job-details {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 576px) {
    .job-card-header {
        flex-direction: column;
        text-align: center;
    }
    
    .company-logo {
        margin: 0 auto 1rem;
    }
    
    .job-type-badge {
        margin-top: 1rem;
    }
    
    .job-details {
        grid-template-columns: 1fr;
    }
    
    .job-card-footer {
        flex-direction: column;
        gap: 1rem;
        text-align: center;
    }
}
</style>

<?php require_once 'includes/footer.php'; ?> 