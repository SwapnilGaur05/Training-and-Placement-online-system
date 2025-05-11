<?php
// Database configuration
$host = "localhost";
$username = "";
$password = ""; // Add your MySQL root password here if it has one
$database = "";

// Create database connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character set
$conn->set_charset("utf8");
?> 
