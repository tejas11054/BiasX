<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode([]);  // Return empty array if user is not logged in
    exit;
}

$user_id = $_SESSION['user_id'];  // Get the logged-in user's ID

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "bdamt_db";
$conn = new mysqli($host, $user, $password, $database);

header('Content-Type: application/json');

// Check connection
if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Prepare SQL query to fetch analysis data for the user
$sql = "SELECT id, uploaded_at, received_file2_data FROM received_files WHERE user_id = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$report = [];

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $timestamp = $row['uploaded_at'];

    // Decode the JSON from BLOB
    $blobData = $row['received_file2_data'];
    $jsonString = is_resource($blobData) ? stream_get_contents($blobData) : $blobData; // Convert BLOB to JSON
    $jsonData = json_decode($jsonString, true);

    // Ensure JSON is properly decoded
    if (!is_array($jsonData)) {
        continue; // Skip if JSON is invalid
    }

    $datasetName = isset($jsonData[0]['dataset']) ? htmlspecialchars($jsonData[0]['dataset']) : 'N/A';

    $report[] = [
        'id' => $id,
        'uploaded_at' => $timestamp,
        'dataset' => $datasetName
    ];
}

// Close connection
$stmt->close();
$conn->close();

// Output JSON response
echo json_encode($report);
?>
