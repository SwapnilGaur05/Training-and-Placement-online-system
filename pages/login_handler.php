<?php
session_start();
require_once '../config/db_connect.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $errors = [];

    // Validate input
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        // Check user credentials
        $query = "SELECT id, email, password, user_type FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['email'] = $user['email'];

                // Redirect based on user type
                switch ($user['user_type']) {
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
                        $_SESSION['error'] = "Invalid user type";
                        header('Location: login.php');
                }
                exit;
            } else {
                $errors[] = "Invalid email or password";
            }
        } else {
            $errors[] = "Invalid email or password";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?> 
session_start();
require_once '../config/db_connect.php';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $errors = [];

    // Validate input
    if (empty($email)) {
        $errors[] = "Email is required";
    }
    if (empty($password)) {
        $errors[] = "Password is required";
    }

    if (empty($errors)) {
        // Check user credentials
        $query = "SELECT id, email, password, user_type FROM users WHERE email = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['email'] = $user['email'];

                // Redirect based on user type
                switch ($user['user_type']) {
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
                        $_SESSION['error'] = "Invalid user type";
                        header('Location: login.php');
                }
                exit;
            } else {
                $errors[] = "Invalid email or password";
            }
        } else {
            $errors[] = "Invalid email or password";
        }
    }

    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
        header('Location: login.php');
        exit;
    }
} else {
    header('Location: login.php');
    exit;
}
?> 