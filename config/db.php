<?php
// Database configuration
$db_host = 'localhost';
$db_user = '';  // Default WAMP MySQL username
$db_pass = '';      // Default WAMP MySQL password
$db_name = '';

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4
$conn->set_charset("utf8mb4");

// Set timezone
date_default_timezone_set('Asia/Kolkata');
?> 
