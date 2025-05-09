<?php
session_start();
require_once '../../config/db_connect.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = $_POST['title'] ?? '';
    $description = $_POST['description'] ?? '';
    $event_date = $_POST['event_date'] ?? '';
    $event_time = $_POST['event_time'] ?? '';
    $location = $_POST['location'] ?? '';

    // Validate required fields
    if (empty($title) || empty($description) || empty($event_date) || empty($event_time)) {
        $error_message = "Please fill in all required fields.";
    } else {
        try {
            // Combine date and time
            $event_datetime = date('Y-m-d H:i:s', strtotime("$event_date $event_time"));
            
            // Insert event
            $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, location, created_by) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $title, $description, $event_datetime, $location, $_SESSION['user_id']);
            
            if ($stmt->execute()) {
                $success_message = "Event added successfully!";
                // Clear form data
                $_POST = array();
            } else {
                $error_message = "Error adding event: " . $stmt->error;
            }
        } catch (Exception $e) {
            $error_message = "Error adding event: " . $e->getMessage();
        }
    }
}

// Set page title
$pageTitle = 'Add New Event';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Event - TPOS</title>
    <!-- Include Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../../assets/css/admin.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>

    <div class="container-fluid">
        <div class="row">
            

            <!-- Main content -->
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Add New Event</h1>
                </div>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="title" class="form-label">Event Title *</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo $_POST['title'] ?? ''; ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Event Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="4" required><?php echo $_POST['description'] ?? ''; ?></textarea>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="event_date" class="form-label">Event Date *</label>
                                    <input type="date" class="form-control" id="event_date" name="event_date" 
                                           value="<?php echo $_POST['event_date'] ?? ''; ?>" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="event_time" class="form-label">Event Time *</label>
                                    <input type="time" class="form-control" id="event_time" name="event_time" 
                                           value="<?php echo $_POST['event_time'] ?? ''; ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Event Location</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       value="<?php echo $_POST['location'] ?? ''; ?>" 
                                       placeholder="Enter event location">
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-secondary me-md-2" onclick="window.location.href='events.php'">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Event</button>
                            </div>
                        </form>
                    </div>
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
        // Form validation
        (function () {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html> 