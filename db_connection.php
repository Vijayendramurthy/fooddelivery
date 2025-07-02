<?php
// Database connection file
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'fooddelivery';

$conn = new mysqli($host, $username, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
