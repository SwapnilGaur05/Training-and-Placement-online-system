<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit();
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

// Initialize search variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$location = isset($_GET['location']) ? trim($_GET['location']) : '';
$date_filter = isset($_GET['date_filter']) ? trim($_GET['date_filter']) : '';

// Build the query
$query = "SELECT e.*, u.email as creator_email, u.user_type,
          CASE 
            WHEN e.event_date < NOW() THEN 'Past'
            WHEN e.event_date = CURDATE() THEN 'Today'
            ELSE 'Upcoming'
          END as event_status
          FROM events e 
          JOIN users u ON e.created_by = u.id 
          WHERE 1=1";

$params = [];
$types = "";

// Add search filters
if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

if (!empty($location)) {
    $query .= " AND e.location LIKE ?";
    $params[] = "%$location%";
    $types .= "s";
}

// Date filter
if (!empty($date_filter)) {
    switch ($date_filter) {
        case 'upcoming':
            $query .= " AND e.event_date >= CURDATE()";
            break;
        case 'past':
            $query .= " AND e.event_date < CURDATE()";
            break;
        case 'today':
            $query .= " AND DATE(e.event_date) = CURDATE()";
            break;
    }
}

// Add order by
$query .= " ORDER BY e.event_date ASC";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Get distinct locations for filter dropdown
$locationsQuery = "SELECT DISTINCT location FROM events WHERE location IS NOT NULL AND location != '' ORDER BY location";
$locationsResult = $conn->query($locationsQuery);
$locations = [];
if ($locationsResult && $locationsResult->num_rows > 0) {
    while ($row = $locationsResult->fetch_assoc()) {
        $locations[] = $row['location'];
    }
}

$pageTitle = 'Manage Events';
include 'includes/header.php';
?>

<main class="admin-main">
    <div class="container">
        <div class="page-header">
            <h1>Manage Events</h1>
            <div class="header-actions">
                <a href="add-event.php" class="btn btn-primary"><i class="fas fa-plus"></i> Add New Event</a>
            </div>
        </div>

        <div class="filter-section">
            <form action="events.php" method="get" class="filter-form">
                <div class="form-group">
                    <input type="text" name="search" class="form-control" placeholder="Search by title or description" value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="form-group">
                    <select name="location" class="form-control">
                        <option value="">All Locations</option>
                        <?php foreach ($locations as $loc): ?>
                            <option value="<?php echo htmlspecialchars($loc); ?>" <?php echo ($location === $loc) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($loc); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <select name="date_filter" class="form-control">
                        <option value="">All Dates</option>
                        <option value="upcoming" <?php echo ($date_filter === 'upcoming') ? 'selected' : ''; ?>>Upcoming Events</option>
                        <option value="today" <?php echo ($date_filter === 'today') ? 'selected' : ''; ?>>Today's Events</option>
                        <option value="past" <?php echo ($date_filter === 'past') ? 'selected' : ''; ?>>Past Events</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Filter</button>
                <?php if (!empty($search) || !empty($location) || !empty($date_filter)): ?>
                    <a href="events.php" class="btn btn-secondary">Clear Filters</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Title</th>
                        <th>Date & Time</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while ($event = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                <td>
                                    <?php 
                                    $event_date = new DateTime($event['event_date']);
                                    echo $event_date->format('M d, Y h:i A'); 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($event['location']); ?></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo ($event['event_status'] === 'Upcoming') ? 'primary' : 
                                            (($event['event_status'] === 'Today') ? 'success' : 'secondary');
                                    ?>">
                                        <?php echo $event['event_status']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo ucfirst($event['user_type']); ?>
                                    </span>
                                    <?php echo htmlspecialchars($event['creator_email']); ?>
                                </td>
                                <td>
                                    <?php 
                                    $created_at = new DateTime($event['created_at']);
                                    echo $created_at->format('M d, Y'); 
                                    ?>
                                </td>
                                <td class="actions">
                                    <a href="view-event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="fas fa-eye"></i></a>
                                    <a href="edit-event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="fas fa-edit"></i></a>
                                    <button class="btn btn-sm btn-danger" onclick="deleteEvent(<?php echo $event['id']; ?>)" title="Delete"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No events found</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

<script>
function deleteEvent(eventId) {
    if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        window.location.href = `delete-event.php?id=${eventId}`;
    }
}
</script>

<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> 