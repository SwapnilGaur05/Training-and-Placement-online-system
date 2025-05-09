<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is a company
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'company') {
    header('Location: ../login.php');
    exit;
}

// Get company information
$userId = $_SESSION['user_id'];
$companyQuery = "SELECT c.* FROM companies c WHERE c.user_id = ?";
$stmt = $conn->prepare($companyQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$company = $stmt->get_result()->fetch_assoc();

if (!$company) {
    header('Location: ../logout.php');
    exit;
}

// Check if event_registrations table exists
$tableCheck = $conn->query("SHOW TABLES LIKE 'event_registrations'");
if ($tableCheck->num_rows == 0) {
    // Table doesn't exist, create it
    $createTable = "CREATE TABLE IF NOT EXISTS event_registrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        student_id INT NOT NULL,
        company_id INT,
        registration_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        status ENUM('registered', 'attended', 'cancelled') NOT NULL DEFAULT 'registered',
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE,
        FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE CASCADE
    )";
    $conn->query($createTable);
}

// Handle search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$eventType = isset($_GET['event_type']) ? $_GET['event_type'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'upcoming';

// Base query
$query = "SELECT e.*, 
          COALESCE((SELECT COUNT(*) FROM event_registrations er WHERE er.event_id = e.id), 0) as total_registrations 
          FROM events e 
          WHERE 1=1";
$params = [];
$types = "";

// Add search condition
if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.venue LIKE ?)";
    $searchParam = "%$search%";
    $params = array_merge($params, [$searchParam, $searchParam, $searchParam]);
    $types .= "sss";
}

// Add event type filter
if (!empty($eventType)) {
    $query .= " AND e.event_type = ?";
    $params[] = $eventType;
    $types .= "s";
}

// Add status filter
if ($status === 'upcoming') {
    $query .= " AND e.event_date >= CURRENT_DATE()";
} elseif ($status === 'past') {
    $query .= " AND e.event_date < CURRENT_DATE()";
}

// Add sorting
switch ($sort) {
    case 'past':
        $query .= " ORDER BY e.event_date DESC";
        break;
    case 'registrations':
        $query .= " ORDER BY total_registrations DESC";
        break;
    default: // upcoming
        $query .= " ORDER BY e.event_date ASC";
}

$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$events = $stmt->get_result();

// Get statistics
$statsQuery = "SELECT 
    COUNT(*) as total_events,
    SUM(CASE WHEN event_date >= CURRENT_DATE() THEN 1 ELSE 0 END) as upcoming_events,
    SUM(CASE WHEN event_date < CURRENT_DATE() THEN 1 ELSE 0 END) as past_events,
    COALESCE((SELECT COUNT(*) FROM event_registrations), 0) as total_registrations
    FROM events";
$stats = $conn->query($statsQuery)->fetch_assoc();

// Set page title
$pageTitle = 'Events';
include 'includes/header.php';
?>

<!-- Page Header -->
<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2">Events</h1>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php 
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Events</h5>
                <h2 class="card-text"><?php echo $stats['total_events']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-success">
            <div class="card-body">
                <h5 class="card-title">Upcoming Events</h5>
                <h2 class="card-text"><?php echo $stats['upcoming_events']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-warning">
            <div class="card-body">
                <h5 class="card-title">Past Events</h5>
                <h2 class="card-text"><?php echo $stats['past_events']; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-bg-info">
            <div class="card-body">
                <h5 class="card-title">Total Registrations</h5>
                <h2 class="card-text"><?php echo $stats['total_registrations']; ?></h2>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <div class="form-floating">
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Search events" value="<?php echo htmlspecialchars($search); ?>">
                    <label for="search">Search events</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="event_type" name="event_type">
                        <option value="">All Types</option>
                        <option value="Workshop" <?php echo $eventType === 'Workshop' ? 'selected' : ''; ?>>Workshop</option>
                        <option value="Seminar" <?php echo $eventType === 'Seminar' ? 'selected' : ''; ?>>Seminar</option>
                        <option value="Job Fair" <?php echo $eventType === 'Job Fair' ? 'selected' : ''; ?>>Job Fair</option>
                        <option value="Other" <?php echo $eventType === 'Other' ? 'selected' : ''; ?>>Other</option>
                    </select>
                    <label for="event_type">Event Type</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="status" name="status">
                        <option value="">All Events</option>
                        <option value="upcoming" <?php echo $status === 'upcoming' ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="past" <?php echo $status === 'past' ? 'selected' : ''; ?>>Past</option>
                    </select>
                    <label for="status">Status</label>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-floating">
                    <select class="form-select" id="sort" name="sort">
                        <option value="upcoming" <?php echo $sort === 'upcoming' ? 'selected' : ''; ?>>Upcoming First</option>
                        <option value="past" <?php echo $sort === 'past' ? 'selected' : ''; ?>>Past First</option>
                        <option value="registrations" <?php echo $sort === 'registrations' ? 'selected' : ''; ?>>Most Registrations</option>
                    </select>
                    <label for="sort">Sort By</label>
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 h-100">Apply Filters</button>
            </div>
        </form>
    </div>
</div>

<!-- Events List -->
<?php if ($events->num_rows > 0): ?>
    <div class="row">
        <?php while ($event = $events->fetch_assoc()): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <span class="badge bg-<?php 
                                    echo isset($event['event_type']) && $event['event_type'] === 'Workshop' ? 'primary' : 
                                        (isset($event['event_type']) && $event['event_type'] === 'Seminar' ? 'success' : 
                                        (isset($event['event_type']) && $event['event_type'] === 'Job Fair' ? 'warning' : 'info')); 
                                ?> mb-2"><?php echo isset($event['event_type']) ? $event['event_type'] : 'Event'; ?></span>
                                <h5 class="card-title mb-1"><?php echo htmlspecialchars($event['title']); ?></h5>
                                <p class="text-muted mb-0">
                                    <i class="fas fa-map-marker-alt me-2"></i><?php echo htmlspecialchars(isset($event['venue']) ? $event['venue'] : (isset($event['location']) ? $event['location'] : 'TBA')); ?>
                                </p>
                            </div>
                            <span class="badge bg-<?php echo strtotime($event['event_date']) < time() ? 'danger' : 'success'; ?>">
                                <?php echo strtotime($event['event_date']) < time() ? 'Past' : 'Upcoming'; ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted">
                                <i class="fas fa-calendar me-2"></i>Date: <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                            </small>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-clock me-2"></i>Time: <?php 
                                    $timeStr = isset($event['event_time']) ? $event['event_time'] : null;
                                    if ($timeStr !== null) {
                                        echo date('h:i A', strtotime($timeStr));
                                    } else {
                                        // Try to extract time from event_date if event_time is not available
                                        $dt = new DateTime($event['event_date']);
                                        echo $dt->format('h:i A');
                                    }
                                ?>
                            </small>
                            <br>
                            <small class="text-muted">
                                <i class="fas fa-users me-2"></i>Registrations: <?php echo $event['total_registrations']; ?>
                            </small>
                        </div>
                        
                        <p class="card-text mb-3"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                        
                        <div class="d-flex justify-content-end">
                            <?php if (strtotime($event['event_date']) >= time()): ?>
                                <a href="view-event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-2"></i>View Details
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
<?php else: ?>
    <div class="card">
        <div class="card-body text-center py-5">
            <h4 class="text-muted mb-3">No Events Found</h4>
            <p class="mb-3">There are currently no events matching your criteria.</p>
        </div>
    </div>
<?php endif; ?>

<script>
    // Auto-submit form when filters change
    document.querySelectorAll('select').forEach(select => {
        select.addEventListener('change', () => {
            document.querySelector('form').submit();
        });
    });
</script>

<?php include 'includes/footer.php'; ?> 
 