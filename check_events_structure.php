<?php
// Include database connection
require_once 'config/db_connect.php';

// Get table structure
$result = $conn->query("DESCRIBE events");

echo "<h2>Events Table Structure</h2>";
echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

if ($result) {
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "<td>" . $row['Extra'] . "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>Error: " . $conn->error . "</td></tr>";
}

echo "</table>";

// Show sample data
echo "<h2>Sample Event Data</h2>";
$sample = $conn->query("SELECT * FROM events LIMIT 1");

if ($sample && $sample->num_rows > 0) {
    $event = $sample->fetch_assoc();
    echo "<pre>";
    print_r($event);
    echo "</pre>";
} else {
    echo "No events found or error: " . $conn->error;
}

// Close the connection
$conn->close();
?> 