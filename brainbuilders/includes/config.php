<?php
// Database configuration
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'brainbuilders_cbt';

// Error reporting for database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Create connection
$conn = new mysqli($host, $user, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");

// Set timezone if needed
date_default_timezone_set('Africa/Lagos'); // Change to your timezone
?>