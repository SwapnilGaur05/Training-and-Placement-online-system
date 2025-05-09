<?php
session_start();
require_once '../config/db_connect.php';

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    // Redirect based on user type
    switch ($_SESSION['user_type']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            break;
        case 'student':
            header('Location: student/dashboard.php');
            break;
        case 'company':
            header('Location: company/dashboard.php');
            break;
        default:
            header('Location: ../index.php');
    }
    exit;
}

// Initialize variables
$error = '';
$success = '';
$formData = [
    'name' => '',
    'email' => '',
    'user_type' => isset($_GET['type']) ? $_GET['type'] : 'student',
    'roll_number' => '',
    'department' => '',
    'year_of_passing' => '',
    'company_name' => '',
    'company_website' => '',
    'company_location' => '',
    'contact_person' => '',
    'contact_email' => '',
    'contact_phone' => ''
];

// Process registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $formData['email'] = trim($_POST['email']);
    $formData['user_type'] = $_POST['user_type'];
    $password = $_POST['password'];
    $confirmPassword = $_POST['confirm_password'];
    
    // Get user type specific data
    if ($formData['user_type'] === 'student') {
        $formData['name'] = trim($_POST['name']);
        $formData['roll_number'] = trim($_POST['roll_number']);
        $formData['department'] = trim($_POST['department']);
        $formData['year_of_passing'] = trim($_POST['year_of_passing']);
        
        // Validate student-specific fields
        if (empty($formData['name']) || empty($formData['roll_number']) || 
            empty($formData['department']) || empty($formData['year_of_passing'])) {
            $error = 'Please fill in all required student fields.';
        }
    } elseif ($formData['user_type'] === 'company') {
        $formData['company_name'] = trim($_POST['company_name']);
        $formData['company_website'] = trim($_POST['company_website']);
        $formData['company_location'] = trim($_POST['company_location']);
        $formData['contact_person'] = trim($_POST['contact_person']);
        $formData['contact_email'] = trim($_POST['contact_email']);
        $formData['contact_phone'] = trim($_POST['contact_phone']);
        
        // Validate company-specific fields
        if (empty($formData['company_name']) || empty($formData['company_location']) || 
            empty($formData['contact_person']) || empty($formData['contact_email']) || 
            empty($formData['contact_phone'])) {
            $error = 'Please fill in all required company fields.';
        }
        // Validate contact email format
        elseif (!filter_var($formData['contact_email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid contact email address.';
        }
        // Validate website URL if provided
        elseif (!empty($formData['company_website']) && 
                !filter_var($formData['company_website'], FILTER_VALIDATE_URL)) {
            $error = 'Please enter a valid website URL.';
        }
        // Validate phone number format (basic check)
        elseif (!preg_match('/^[0-9+\-\(\)\s]{10,20}$/', $formData['contact_phone'])) {
            $error = 'Please enter a valid phone number.';
        }
    }
    
    // Common validation
    if (empty($error)) {
        if (empty($formData['email']) || empty($password) || empty($confirmPassword)) {
            $error = 'Please fill in all required fields.';
        } elseif (!filter_var($formData['email'], FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif ($password !== $confirmPassword) {
            $error = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } elseif (!isset($_POST['terms'])) {
            $error = 'You must agree to the Terms and Conditions.';
        } else {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $formData['email']);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = 'Email address is already registered.';
            } else {
                // Hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Insert into users table
                    $stmt = $conn->prepare("INSERT INTO users (email, password, user_type) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $formData['email'], $hashedPassword, $formData['user_type']);
                    $stmt->execute();
                    
                    $userId = $conn->insert_id;
                    
                    // Insert into specific user type table
                    if ($formData['user_type'] === 'student') {
                        $stmt = $conn->prepare("INSERT INTO students (user_id, name, roll_number, department, year_of_passing) VALUES (?, ?, ?, ?, ?)");
                        $stmt->bind_param("isssi", $userId, $formData['name'], $formData['roll_number'], $formData['department'], $formData['year_of_passing']);
                        $stmt->execute();
                    } elseif ($formData['user_type'] === 'company') {
                        $stmt = $conn->prepare("INSERT INTO companies (user_id, name, website, location, contact_person, contact_email, contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?)");
                        $stmt->bind_param("issssss", $userId, $formData['company_name'], $formData['company_website'], $formData['company_location'], $formData['contact_person'], $formData['contact_email'], $formData['contact_phone']);
                        $stmt->execute();
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    
                    $success = 'Registration successful! You can now login.';
                    
                    // Clear form data
                    $formData = [
                        'name' => '',
                        'email' => '',
                        'user_type' => 'student',
                        'roll_number' => '',
                        'department' => '',
                        'year_of_passing' => '',
                        'company_name' => '',
                        'company_website' => '',
                        'company_location' => '',
                        'contact_person' => '',
                        'contact_email' => '',
                        'contact_phone' => ''
                    ];

                    // Redirect to login page after successful registration
                    $_SESSION['success'] = 'Registration successful! Please login with your credentials.';
                    header('Location: login.php');
                    exit;
                    
                } catch (Exception $e) {
                    // Rollback transaction on error
                    $conn->rollback();
                    $error = 'Registration failed. Please try again later. Error: ' . $e->getMessage();
                }
            }
            
            $stmt->close();
        }
    }
}

// Set page title
$pageTitle = 'Register';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - TPOS</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .navbar {
            background-color: rgba(255, 255, 255, 0.95) !important;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .navbar-brand span {
            font-size: 0.875rem;
            display: block;
            color: #6c757d;
        }
        .nav-link {
            color: #2c3e50 !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: color 0.3s ease;
        }
        .nav-link:hover {
            color: #0d6efd !important;
        }
        .register-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }
        .register-card {
            width: 100%;
            max-width: 500px;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            overflow: hidden;
        }
        .register-header {
            background: #0d6efd;
            padding: 2rem;
            text-align: center;
            color: white;
        }
        .register-header i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        .register-body {
            padding: 2rem;
        }
        .form-floating > label {
            color: #6c757d;
        }
        .form-control:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
        .btn-register {
            padding: 0.75rem;
            font-weight: 500;
        }
        .user-type-selector {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .user-type-selector .form-check {
            flex: 1;
        }
        .user-type-selector .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
        }
        .password-field {
            position: relative;
        }
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="../index.php">
                TPOS
                <span>Training & Placement Online System</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="register.php">Register</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Register Section -->
    <div class="register-container">
        <div class="register-card">
            <div class="register-header">
                <i class="fas fa-user-plus"></i>
                <h4 class="mb-0">Create Account</h4>
            </div>
            <div class="register-body">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $success; ?>
                        <p class="mb-0"><a href="login.php">Click here to login</a></p>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="register.php" method="post" class="needs-validation">
                    <div class="user-type-selector">
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="student" name="user_type" value="student" <?php echo ($formData['user_type'] === 'student') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="student">
                                <i class="fas fa-user-graduate me-2"></i>Student
                            </label>
                        </div>
                        <div class="form-check">
                            <input type="radio" class="form-check-input" id="company" name="user_type" value="company" <?php echo ($formData['user_type'] === 'company') ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="company">
                                <i class="fas fa-building me-2"></i>Company
                            </label>
                        </div>
                    </div>

                    <!-- Student specific fields -->
                    <div id="student-fields" class="user-type-fields" <?php echo ($formData['user_type'] !== 'student') ? 'style="display: none;"' : ''; ?>>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="name" name="name" placeholder="Full Name" value="<?php echo htmlspecialchars($formData['name']); ?>" required>
                            <label for="name"><i class="fas fa-user me-2"></i>Full Name</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="roll_number" name="roll_number" placeholder="Roll Number" value="<?php echo htmlspecialchars($formData['roll_number']); ?>" required>
                            <label for="roll_number"><i class="fas fa-id-card me-2"></i>Roll Number</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="department" name="department" placeholder="Department" value="<?php echo htmlspecialchars($formData['department']); ?>" required>
                            <label for="department"><i class="fas fa-university me-2"></i>Department</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="number" class="form-control" id="year_of_passing" name="year_of_passing" placeholder="Year of Passing" value="<?php echo htmlspecialchars($formData['year_of_passing']); ?>" required>
                            <label for="year_of_passing"><i class="fas fa-calendar me-2"></i>Year of Passing</label>
                        </div>
                    </div>

                    <!-- Company specific fields -->
                    <div id="company-fields" class="user-type-fields" <?php echo ($formData['user_type'] !== 'company') ? 'style="display: none;"' : ''; ?>>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="company_name" name="company_name" placeholder="Company Name" value="<?php echo htmlspecialchars($formData['company_name']); ?>" required>
                            <label for="company_name"><i class="fas fa-building me-2"></i>Company Name</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="url" class="form-control" id="company_website" name="company_website" placeholder="Website" value="<?php echo htmlspecialchars($formData['company_website']); ?>">
                            <label for="company_website"><i class="fas fa-globe me-2"></i>Website (Optional)</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="company_location" name="company_location" placeholder="Location" value="<?php echo htmlspecialchars($formData['company_location']); ?>" required>
                            <label for="company_location"><i class="fas fa-map-marker-alt me-2"></i>Location</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="text" class="form-control" id="contact_person" name="contact_person" placeholder="Contact Person" value="<?php echo htmlspecialchars($formData['contact_person']); ?>" required>
                            <label for="contact_person"><i class="fas fa-user-tie me-2"></i>Contact Person</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="email" class="form-control" id="contact_email" name="contact_email" placeholder="Contact Email" value="<?php echo htmlspecialchars($formData['contact_email']); ?>" required>
                            <label for="contact_email"><i class="fas fa-envelope me-2"></i>Contact Email</label>
                        </div>
                        <div class="form-floating mb-3">
                            <input type="tel" class="form-control" id="contact_phone" name="contact_phone" placeholder="Contact Phone" value="<?php echo htmlspecialchars($formData['contact_phone']); ?>" required>
                            <label for="contact_phone"><i class="fas fa-phone me-2"></i>Contact Phone</label>
                        </div>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email address" value="<?php echo htmlspecialchars($formData['email']); ?>" required>
                        <label for="email"><i class="fas fa-envelope me-2"></i>Email address</label>
                    </div>

                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
                        <label for="password"><i class="fas fa-lock me-2"></i>Password</label>
                        <button type="button" class="password-toggle" onclick="togglePassword('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <div class="form-floating mb-4">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                        <label for="confirm_password"><i class="fas fa-lock me-2"></i>Confirm Password</label>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>

                    <div class="form-check mb-4">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" required>
                        <label class="form-check-label" for="terms">
                            I agree to the <a href="terms.php">Terms and Conditions</a>
                        </label>
                    </div>

                    <button class="btn btn-primary w-100 btn-register mb-4" type="submit">
                        <i class="fas fa-user-plus me-2"></i>Create Account
                    </button>

                    <div class="text-center">
                        <p class="text-muted mb-0">Already have an account? <a href="login.php">Sign in</a></p>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer text-center">
        <div class="container">
            <small>&copy; <?php echo date('Y'); ?> Training and Placement Online System. All rights reserved.</small>
        </div>
    </footer>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom scripts -->
    <script>
        // Toggle password visibility
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = event.currentTarget.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Toggle between student and company fields
        document.querySelectorAll('input[name="user_type"]').forEach(radio => {
            radio.addEventListener('change', function() {
                const studentFields = document.getElementById('student-fields');
                const companyFields = document.getElementById('company-fields');
                
                if (this.value === 'student') {
                    studentFields.style.display = 'block';
                    companyFields.style.display = 'none';
                    // Reset company fields
                    companyFields.querySelectorAll('input').forEach(input => {
                        input.required = false;
                    });
                    // Enable student fields
                    studentFields.querySelectorAll('input').forEach(input => {
                        input.required = true;
                    });
                } else {
                    studentFields.style.display = 'none';
                    companyFields.style.display = 'block';
                    // Reset student fields
                    studentFields.querySelectorAll('input').forEach(input => {
                        input.required = false;
                    });
                    // Enable company fields
                    companyFields.querySelectorAll('input').forEach(input => {
                        if (input.id !== 'company_website') { // Website is optional
                            input.required = true;
                        }
                    });
                }
            });
        });

        // Initialize form fields on page load
        document.addEventListener('DOMContentLoaded', function() {
            const userType = document.querySelector('input[name="user_type"]:checked').value;
            const studentFields = document.getElementById('student-fields');
            const companyFields = document.getElementById('company-fields');

            if (userType === 'student') {
                studentFields.style.display = 'block';
                companyFields.style.display = 'none';
                companyFields.querySelectorAll('input').forEach(input => {
                    input.required = false;
                });
                studentFields.querySelectorAll('input').forEach(input => {
                    input.required = true;
                });
            } else {
                studentFields.style.display = 'none';
                companyFields.style.display = 'block';
                studentFields.querySelectorAll('input').forEach(input => {
                    input.required = false;
                });
                companyFields.querySelectorAll('input').forEach(input => {
                    if (input.id !== 'company_website') {
                        input.required = true;
                    }
                });
            }
        });

        // Form validation before submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const userType = document.querySelector('input[name="user_type"]:checked').value;
            let isValid = true;

            if (userType === 'company') {
                const requiredFields = [
                    'company_name',
                    'company_location',
                    'contact_person',
                    'contact_email',
                    'contact_phone',
                    'email',
                    'password',
                    'confirm_password'
                ];

                requiredFields.forEach(fieldId => {
                    const field = document.getElementById(fieldId);
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('is-invalid');
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                // Validate email format
                const emailField = document.getElementById('email');
                const contactEmailField = document.getElementById('contact_email');
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                
                if (!emailRegex.test(emailField.value.trim())) {
                    isValid = false;
                    emailField.classList.add('is-invalid');
                }
                if (!emailRegex.test(contactEmailField.value.trim())) {
                    isValid = false;
                    contactEmailField.classList.add('is-invalid');
                }

                // Validate phone number
                const phoneField = document.getElementById('contact_phone');
                const phoneRegex = /^[0-9+\-\(\)\s]{10,20}$/;
                if (!phoneRegex.test(phoneField.value.trim())) {
                    isValid = false;
                    phoneField.classList.add('is-invalid');
                }
            }

            if (!isValid) {
                e.preventDefault();
                alert('Please fill in all required fields correctly.');
            }
        });
    </script>
</body>
</html> 