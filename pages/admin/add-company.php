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
    $name = $_POST['name'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $description = $_POST['description'] ?? '';
    $website = $_POST['website'] ?? '';
    $location = $_POST['location'] ?? '';
    $contact_person = $_POST['contact_person'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? '';

    // Validate required fields
    if (empty($name) || empty($email) || empty($password) || empty($contact_person) || empty($contact_email)) {
        $error_message = "Please fill in all required fields.";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // First, create user account
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $user_stmt = $conn->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, 'company')");
            $user_stmt->bind_param("ss", $email, $hashed_password);
            $user_stmt->execute();
            $user_id = $conn->insert_id;

            // Then, create company profile
            $company_stmt = $conn->prepare("INSERT INTO companies (user_id, name, description, website, location, contact_person, contact_email, contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $company_stmt->bind_param("isssssss", $user_id, $name, $description, $website, $location, $contact_person, $contact_email, $contact_phone);
            $company_stmt->execute();

            // If everything is successful, commit the transaction
            $conn->commit();
            $success_message = "Company added successfully!";

            // Clear form data
            $_POST = array();

        } catch (Exception $e) {
            // If there's an error, rollback the transaction
            $conn->rollback();
            $error_message = "Error adding company: " . $e->getMessage();
        }
    }
}

// Set page title
$pageTitle = 'Add New Company';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Company - TPOS</title>
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
                    <h1 class="h2">Add New Company</h1>
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
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Company Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" value="<?php echo $_POST['name'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Company Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $_POST['email'] ?? ''; ?>" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="password" class="form-label">Password *</label>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="website" class="form-label">Website</label>
                                    <input type="url" class="form-control" id="website" name="website" value="<?php echo $_POST['website'] ?? ''; ?>">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Company Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo $_POST['description'] ?? ''; ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo $_POST['location'] ?? ''; ?>">
                            </div>

                            <div class="row mb-3">
                                <div class="col-md-4">
                                    <label for="contact_person" class="form-label">Contact Person *</label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person" value="<?php echo $_POST['contact_person'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="contact_email" class="form-label">Contact Email *</label>
                                    <input type="email" class="form-control" id="contact_email" name="contact_email" value="<?php echo $_POST['contact_email'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="contact_phone" class="form-label">Contact Phone</label>
                                    <input type="tel" class="form-control" id="contact_phone" name="contact_phone" value="<?php echo $_POST['contact_phone'] ?? ''; ?>">
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <button type="button" class="btn btn-secondary me-md-2" onclick="window.location.href='companies.php'">Cancel</button>
                                <button type="submit" class="btn btn-primary">Add Company</button>
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