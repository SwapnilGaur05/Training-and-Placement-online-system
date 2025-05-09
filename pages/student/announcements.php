<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Initialize search variable
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Initialize sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build the query
$query = "SELECT a.*, admin.name as admin_name 
          FROM announcements a 
          JOIN admins admin ON a.admin_id = admin.id 
          WHERE 1=1";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Add sorting
$query .= " ORDER BY a.created_at " . ($sort === 'oldest' ? 'ASC' : 'DESC');

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set page title
$pageTitle = 'Announcements';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Announcements</h1>
            </div>

            <!-- Search and Sort Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="announcements.php" method="get" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search announcements..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-sort"></i></span>
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <?php if (!empty($search)): ?>
                                <a href="announcements.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-times me-1"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Announcements List -->
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($announcement = $result->fetch_assoc()): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                <span class="badge bg-primary">
                                    <i class="far fa-clock me-1"></i>
                                    <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                </span>
                            </div>
                            <p class="card-text">
                                <?php 
                                $content = $announcement['content'];
                                if (strlen($content) > 300) {
                                    echo htmlspecialchars(substr($content, 0, 300)) . '...';
                                    echo '<a href="#" class="read-more" data-bs-toggle="modal" data-bs-target="#announcementModal' . $announcement['id'] . '">Read More</a>';
                                } else {
                                    echo htmlspecialchars($content);
                                }
                                ?>
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                Posted by <?php echo htmlspecialchars($announcement['admin_name']); ?>
                            </small>
                        </div>
                    </div>

                    <!-- Modal for full announcement -->
                    <?php if (strlen($content) > 300): ?>
                    <div class="modal fade" id="announcementModal<?php echo $announcement['id']; ?>" tabindex="-1" aria-labelledby="announcementModalLabel<?php echo $announcement['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="announcementModalLabel<?php echo $announcement['id']; ?>">
                                        <?php echo htmlspecialchars($announcement['title']); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                    <hr>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        Posted by <?php echo htmlspecialchars($announcement['admin_name']); ?>
                                        <br>
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('F d, Y \a\t h:i A', strtotime($announcement['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo empty($search) ? 'No announcements available.' : 'No announcements found matching your search criteria.'; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Auto-submit form when sort option changes
document.querySelector('select[name="sort"]').addEventListener('change', function() {
    this.form.submit();
});
</script> 
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php');
    exit();
}

// Initialize search variable
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Initialize sorting
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';

// Build the query
$query = "SELECT a.*, admin.name as admin_name 
          FROM announcements a 
          JOIN admins admin ON a.admin_id = admin.id 
          WHERE 1=1";

$params = [];
$types = "";

// Add search filter
if (!empty($search)) {
    $query .= " AND (a.title LIKE ? OR a.content LIKE ?)";
    $searchTerm = "%$search%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $types .= "ss";
}

// Add sorting
$query .= " ORDER BY a.created_at " . ($sort === 'oldest' ? 'ASC' : 'DESC');

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// Set page title
$pageTitle = 'Announcements';
include 'includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Main content -->
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Announcements</h1>
            </div>

            <!-- Search and Sort Section -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="announcements.php" method="get" class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Search announcements..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-sort"></i></span>
                                <select name="sort" class="form-select" onchange="this.form.submit()">
                                    <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                                    <option value="oldest" <?php echo $sort === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <?php if (!empty($search)): ?>
                                <a href="announcements.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-times me-1"></i> Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Announcements List -->
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($announcement = $result->fetch_assoc()): ?>
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                <span class="badge bg-primary">
                                    <i class="far fa-clock me-1"></i>
                                    <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                </span>
                            </div>
                            <p class="card-text">
                                <?php 
                                $content = $announcement['content'];
                                if (strlen($content) > 300) {
                                    echo htmlspecialchars(substr($content, 0, 300)) . '...';
                                    echo '<a href="#" class="read-more" data-bs-toggle="modal" data-bs-target="#announcementModal' . $announcement['id'] . '">Read More</a>';
                                } else {
                                    echo htmlspecialchars($content);
                                }
                                ?>
                            </p>
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i>
                                Posted by <?php echo htmlspecialchars($announcement['admin_name']); ?>
                            </small>
                        </div>
                    </div>

                    <!-- Modal for full announcement -->
                    <?php if (strlen($content) > 300): ?>
                    <div class="modal fade" id="announcementModal<?php echo $announcement['id']; ?>" tabindex="-1" aria-labelledby="announcementModalLabel<?php echo $announcement['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="announcementModalLabel<?php echo $announcement['id']; ?>">
                                        <?php echo htmlspecialchars($announcement['title']); ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                    <hr>
                                    <small class="text-muted">
                                        <i class="fas fa-user me-1"></i>
                                        Posted by <?php echo htmlspecialchars($announcement['admin_name']); ?>
                                        <br>
                                        <i class="far fa-clock me-1"></i>
                                        <?php echo date('F d, Y \a\t h:i A', strtotime($announcement['created_at'])); ?>
                                    </small>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle me-2"></i>
                    <?php echo empty($search) ? 'No announcements available.' : 'No announcements found matching your search criteria.'; ?>
                </div>
            <?php endif; ?>
        </main>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
// Auto-submit form when sort option changes
document.querySelector('select[name="sort"]').addEventListener('change', function() {
    this.form.submit();
});
</script> 