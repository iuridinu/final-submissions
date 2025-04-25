<?php
// Database connection parameters
$host = "localhost";  // Database host
$username = "root";   // Database username
$password = "";       // Database password 
$database = "ice_cream_shop"; // Database name

// Create connection
$conn = new mysqli($host, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to ensure proper handling of Sri Lankan characters
$conn->set_charset("utf8mb4");

// Optional: Set session parameters
session_start();

// Common functions for database operations
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}
?>