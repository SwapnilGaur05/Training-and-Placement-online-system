<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
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

// Get student information
$userId = $_SESSION['user_id'];
$studentQuery = "SELECT s.* FROM students s WHERE s.user_id = ?";
$stmt = $conn->prepare($studentQuery);
$stmt->bind_param("i", $userId);
$stmt->execute();
$studentResult = $stmt->get_result();

if ($studentResult->num_rows === 0) {
    header('Location: ../logout.php');
    exit;
}

$student = $studentResult->fetch_assoc();

// Initialize filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$timeFilter = isset($_GET['time']) ? $_GET['time'] : 'upcoming';
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'date_asc';

// Base query
$query = "SELECT e.*, u.user_type, 
          CASE 
              WHEN u.user_type = 'admin' THEN a.name
              WHEN u.user_type = 'company' THEN c.name
          END as creator_name
          FROM events e
          LEFT JOIN users u ON e.created_by = u.id
          LEFT JOIN admins a ON (u.id = a.user_id AND u.user_type = 'admin')
          LEFT JOIN companies c ON (u.id = c.user_id AND u.user_type = 'company')
          WHERE 1=1";

// Apply search filter
if (!empty($search)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
}

// Apply time filter
switch ($timeFilter) {
    case 'past':
        $query .= " AND e.event_date < NOW()";
        break;
    case 'today':
        $query .= " AND DATE(e.event_date) = CURDATE()";
        break;
    case 'upcoming':
    default:
        $query .= " AND e.event_date >= NOW()";
        break;
}

// Apply sorting
switch ($sortBy) {
    case 'date_desc':
        $query .= " ORDER BY e.event_date DESC";
        break;
    case 'title_asc':
        $query .= " ORDER BY e.title ASC";
        break;
    case 'title_desc':
        $query .= " ORDER BY e.title DESC";
        break;
    case 'date_asc':
    default:
        $query .= " ORDER BY e.event_date ASC";
        break;
}

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($search)) {
    $searchParam = "%$search%";
    $stmt->bind_param("sss", $searchParam, $searchParam, $searchParam);
}
$stmt->execute();
$result = $stmt->get_result();

// Set page title
$pageTitle = 'Events';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Events - TPOS</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/style.css">
    <link rel="stylesheet" href="../../assets/css/student.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Events</h1>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body">
                                <form method="GET" action="" class="row g-3">
                                    <div class="col-md-4">
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                                            <input type="text" class="form-control" name="search" placeholder="Search events..." value="<?php echo htmlspecialchars($search); ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" name="time">
                                            <option value="upcoming" <?php echo $timeFilter === 'upcoming' ? 'selected' : ''; ?>>Upcoming Events</option>
                                            <option value="today" <?php echo $timeFilter === 'today' ? 'selected' : ''; ?>>Today's Events</option>
                                            <option value="past" <?php echo $timeFilter === 'past' ? 'selected' : ''; ?>>Past Events</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <select class="form-select" name="sort">
                                            <option value="date_asc" <?php echo $sortBy === 'date_asc' ? 'selected' : ''; ?>>Date (Ascending)</option>
                                            <option value="date_desc" <?php echo $sortBy === 'date_desc' ? 'selected' : ''; ?>>Date (Descending)</option>
                                            <option value="title_asc" <?php echo $sortBy === 'title_asc' ? 'selected' : ''; ?>>Title (A-Z)</option>
                                            <option value="title_desc" <?php echo $sortBy === 'title_desc' ? 'selected' : ''; ?>>Title (Z-A)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-2">
                                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Events List -->
                <div class="row">
                    <?php if ($result->num_rows > 0): ?>
                        <?php while ($event = $result->fetch_assoc()): ?>
                            <div class="col-md-4 mb-4">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <?php echo htmlspecialchars($event['title']); ?>
                                            <?php if (strtotime($event['event_date']) < time()): ?>
                                                <span class="badge bg-secondary">Past</span>
                                            <?php elseif (date('Y-m-d') === date('Y-m-d', strtotime($event['event_date']))): ?>
                                                <span class="badge bg-success">Today</span>
                                            <?php endif; ?>
                                        </h5>
                                        <p class="card-text"><?php echo nl2br(htmlspecialchars(substr($event['description'], 0, 150)) . (strlen($event['description']) > 150 ? '...' : '')); ?></p>
                                        <div class="event-details">
                                            <p class="mb-2">
                                                <i class="fas fa-calendar me-2"></i>
                                                <?php echo date('M d, Y', strtotime($event['event_date'])); ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-clock me-2"></i>
                                                <?php echo date('h:i A', strtotime($event['event_date'])); ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-map-marker-alt me-2"></i>
                                                <?php echo htmlspecialchars($event['location']); ?>
                                            </p>
                                            <p class="mb-2">
                                                <i class="fas fa-user me-2"></i>
                                                Posted by: <?php echo htmlspecialchars($event['creator_name']); ?>
                                                (<?php echo ucfirst($event['user_type']); ?>)
                                            </p>
                                        </div>
                                        <div class="mt-3">
                                            <a href="view-event.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">
                                                <i class="fas fa-eye me-1"></i> View Details
                                            </a>
                                            <?php if (strtotime($event['event_date']) >= time()): ?>
                                                <button type="button" class="btn btn-outline-primary" onclick="addToCalendar('<?php echo htmlspecialchars($event['title']); ?>', '<?php echo htmlspecialchars($event['description']); ?>', '<?php echo $event['event_date']; ?>', '<?php echo htmlspecialchars($event['location']); ?>')">
                                                    <i class="fas fa-calendar-plus me-1"></i> Add to Calendar
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="col-12">
                            <div class="alert alert-info text-center">
                                No events found matching your criteria.
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Custom JavaScript -->
    <script>
        // Function to add event to calendar
        function addToCalendar(title, description, startTime, location) {
            const event = {
                title: title,
                description: description,
                start: new Date(startTime).toISOString(),
                duration: '02:00', // Default duration of 2 hours
                location: location
            };

            const googleCalendarUrl = `https://www.google.com/calendar/render?action=TEMPLATE&text=${encodeURIComponent(event.title)}&details=${encodeURIComponent(event.description)}&location=${encodeURIComponent(event.location)}&dates=${event.start.replace(/[-:]/g, '')}/${new Date(new Date(event.start).getTime() + 2 * 60 * 60 * 1000).toISOString().replace(/[-:]/g, '')}`;
            
            window.open(googleCalendarUrl, '_blank');
        }

        // Auto-submit form when filters change
        document.querySelectorAll('select[name="time"], select[name="sort"]').forEach(select => {
            select.addEventListener('change', () => {
                select.closest('form').submit();
            });
        });
    </script>
</body>
</html> 
 