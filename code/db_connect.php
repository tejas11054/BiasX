<?php
$host = "localhost"; // Change if using a different database server
$user = "root"; // Change to your database username
$pass = ""; // Change to your database password
$dbname = "bdamt_db"; // Change to your actual database name

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
