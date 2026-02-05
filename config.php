<?php
// config.php - Simple database connection

$host = "mysql-badboychx66.alwaysdata.net";
$username = "334704";
$password = "9972454365@hr";
$database = "badboychx66_intelligence";

// Create connection
$conn = mysqli_connect($host, $username, $password, $database);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// echo "Connected successfully";
?>