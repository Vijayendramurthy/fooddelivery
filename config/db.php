<?php
// Database configuration
$host = 'localhost';           // XAMPP localhost
$username = 'root';            // Default username for XAMPP
$password = '';                // Default password is empty
$database = 'fooddelivery';   // Database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to UTF-8
$conn->set_charset("utf8");

// Uncomment for debugging (remove in production)
// echo "Database connected successfully";
?>
