<?php
// Connection sa database

$host = "localhost";
$username = "root";
$password = "";
$database = "hustisyang_pambarangay";

// Establish connection
$conn = new mysqli($host, $username, $password, $database);

// Check kung may error
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set character encoding
$conn->set_charset("utf8mb4");
?>