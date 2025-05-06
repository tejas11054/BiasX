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

// Fetch analysis data for the currently logged-in user
$sql = "SELECT id, uploaded_at, received_file2_data FROM received_files WHERE user_id = ? ORDER BY uploaded_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);  // Bind the user_id to the query
$stmt->execute();
$result = $stmt->get_result();

$analysis = [];

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    $timestamp = $row['uploaded_at'];

    // Decode the BLOB JSON data
    $blobData = $row['received_file2_data'];
    $jsonString = is_resource($blobData) ? stream_get_contents($blobData) : $blobData; // Convert BLOB to JSON
    $jsonData = json_decode($jsonString, true);

    // Ensure JSON is properly decoded
    if (!is_array($jsonData)) {
        continue; // Skip if JSON is invalid
    }

    // Extract dataset name correctly from the JSON array
    $datasetName = isset($jsonData[0]['dataset']) ? $jsonData[0]['dataset'] : 'N/A';

    $analysis[] = [
        'id' => $id,
        'uploaded_at' => $timestamp,
        'dataset' => $datasetName,
        'download_url' => "download_dataset.php?id=$id"
    ];
}


// Close connection
$stmt->close();
$conn->close();

// Output JSON response
echo json_encode($analysis);
?>
