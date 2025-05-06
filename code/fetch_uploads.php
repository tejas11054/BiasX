<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'User not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

$servername = "localhost";
$username = "root";
$password = "";
$database = "bdamt_db";

$conn = new mysqli($servername, $username, $password, $database);

if ($conn->connect_error) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

$sql = "SELECT DATE_FORMAT(uploaded_at, '%Y-%m') AS month, COUNT(*) AS count 
        FROM received_files 
        WHERE user_id = ? 
        GROUP BY month 
        ORDER BY month";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$months = [];
$counts = [];

while ($row = $result->fetch_assoc()) {
    $months[] = $row['month'];
    $counts[] = $row['count'];
}

$stmt->close();
$conn->close();

echo json_encode(['months' => $months, 'counts' => $counts]);
?>
