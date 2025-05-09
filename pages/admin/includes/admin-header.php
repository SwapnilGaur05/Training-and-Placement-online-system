<?php
session_start();

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login.php');
    exit();
}

require_once '../config/db.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Training & Placement Office</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom Admin Styles -->
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .main-content {
            margin-left: 280px;
            padding: 2rem;
            transition: all 0.3s ease;
        }

        .page-title {
            color: #2c3e50;
            margin-bottom: 2rem;
            font-weight: 600;
        }

        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e9ecef;
            padding: 1.5rem;
        }

        .card-body {
            padding: 1.5rem;
        }

        .btn {
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .btn-primary {
            background-color: #1a73e8;
            border-color: #1a73e8;
        }

        .btn-primary:hover {
            background-color: #0d47a1;
            border-color: #0d47a1;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }

        .badge {
            padding: 0.5rem 1rem;
            border-radius: 2rem;
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 991px) {
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <?php require_once 'admin-sidebar.php'; ?>
    <div class="main-content"> 