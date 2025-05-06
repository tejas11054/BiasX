<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    die('You must be logged in to download the file.');
}

if (!isset($_GET['id'])) {
    die('File ID is required.');
}

$fileId = $_GET['id'];

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "bdamt_db";
$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Fetch the file BLOB data from the database
$sql = "SELECT received_file1_data FROM received_files WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $fileId);
$stmt->execute();
$stmt->store_result();
$stmt->bind_result($received_file1_data);
$stmt->fetch();

if (!$received_file1_data) {
    die('File not found.');
}

// Prepare the CSV file
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="downloaded_file.csv"');
header('Pragma: no-cache');
header('Expires: 0');

// Open output buffer to create a CSV stream
$output = fopen('php://output', 'w');

// Convert BLOB data to an array (assuming it's stored as a string with `;` or `,` delimiters)
$lines = preg_split('/\r\n|\r|\n/', trim($received_file1_data));// Split by `;`

foreach ($lines as $line) {
    $columns = str_getcsv($line); // Convert to array (handles quoted strings)
    fputcsv($output, $columns);   // Write row to CSV
}

fclose($output);
$stmt->close();
$conn->close();
exit;
?>
