<?php

header('Content-Type: application/json');

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bdamt_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch user upload data
$sql = "SELECT users.name AS user_name, COUNT(received_files.id) AS file_count  
        FROM users  
        LEFT JOIN received_files ON users.id = received_files.user_id  
        GROUP BY users.name";

$result = $conn->query($sql);

$data = [];
$activeUsers = 0;
$inactiveUsers = 0;

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
        if ($row['file_count'] > 0) {
            $activeUsers++;
        } else {
            $inactiveUsers++;
        }
    }
}

// Include active/inactive users count in response
$response = [
    "users" => $data,
    "active_users" => $activeUsers,
    "inactive_users" => $inactiveUsers
];

$conn->close();

echo json_encode($response);

?>
