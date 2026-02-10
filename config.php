<?php
// config.php - Simple database connection

$host = "localhost";
$username = "root";
$password = "";
$database = "intelligence";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// echo "Connected successfully";

?>
