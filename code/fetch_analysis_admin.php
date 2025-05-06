<?php
session_start();

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

// Fetch all analysis data along with user name
$sql = "
    SELECT rf.id, rf.uploaded_at, rf.received_file2_data, rf.user_id, u.name AS user_name
    FROM received_files rf
    JOIN users u ON rf.user_id = u.id
    ORDER BY rf.uploaded_at DESC";

$result = $conn->query($sql);

$analysis = [];

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $timestamp = $row['uploaded_at'];
    $userName = $row['user_name'];  // Fetch user's name

    // Decode the JSON stored in received_file2_data
    $blobData = $row['received_file2_data'];
    $jsonString = is_resource($blobData) ? stream_get_contents($blobData) : $blobData;
    $jsonData = json_decode($jsonString, true);

    // Extract the dataset name
    if (isset($jsonData[0]) && is_array($jsonData[0])) {
        $datasetName = $jsonData[0]['dataset'] ?? 'Tejas';
    } else {
        $datasetName = $jsonData['dataset'] ?? 'Tejas'; // fallback
    }

    $analysis[] = [
        'id' => $id,
        'uploaded_at' => $timestamp,
        'user_name' => $userName,  // Include user name
        'dataset' => $datasetName
    ];
}

// Close connection
$conn->close();

// Output JSON response
echo json_encode($analysis);
?>
