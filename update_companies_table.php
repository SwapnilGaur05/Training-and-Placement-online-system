<?php
// Include database connection
require_once 'config/db_connect.php';

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Updating Companies Table Structure</h1>";

// Function to check if column exists
function columnExists($conn, $table, $column) {
    $result = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    return $result->num_rows > 0;
}

// Function to add column if it doesn't exist
function addColumnIfNotExists($conn, $table, $column, $definition) {
    if (!columnExists($conn, $table, $column)) {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql) === TRUE) {
            echo "<p style='color:green'>✓ Added column '$column' to table '$table'</p>";
        } else {
            echo "<p style='color:red'>✗ Error adding column '$column': " . $conn->error . "</p>";
        }
    } else {
        echo "<p style='color:blue'>ℹ Column '$column' already exists in table '$table'</p>";
    }
}

// Add missing columns to companies table
try {
    // Check if the companies table exists
    $tableExists = $conn->query("SHOW TABLES LIKE 'companies'")->num_rows > 0;
    
    if ($tableExists) {
        // Add all required columns
        addColumnIfNotExists($conn, 'companies', 'phone', 'VARCHAR(20) NULL');
        addColumnIfNotExists($conn, 'companies', 'website', 'VARCHAR(255) NULL');
        addColumnIfNotExists($conn, 'companies', 'address', 'TEXT NULL');
        addColumnIfNotExists($conn, 'companies', 'industry', 'VARCHAR(100) NULL');
        addColumnIfNotExists($conn, 'companies', 'about', 'TEXT NULL');
        addColumnIfNotExists($conn, 'companies', 'logo', 'VARCHAR(255) NULL');
        addColumnIfNotExists($conn, 'companies', 'created_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP');
        addColumnIfNotExists($conn, 'companies', 'updated_at', 'TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        
        echo "<p style='color:green'>Database structure updated successfully!</p>";
    } else {
        echo "<p style='color:red'>The companies table does not exist!</p>";
    }
} catch (Exception $e) {
    echo "<p style='color:red'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='pages/company/profile.php'>Go to Company Profile</a></p>";

// Close connection
$conn->close();
?> 