<?php
header('Content-Type: application/json');
$server_url = "http://192.168.101.139:5000/upload";
$response_dir = "response/";

$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "bdamt_db";

// Connect to MySQL
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_FILES["csvFiles"])) {
    $files = $_FILES["csvFiles"];
    $file_data = [];

    for ($i = 0; $i < count($files["name"]); $i++) {
        if ($files["error"][$i] !== UPLOAD_ERR_OK) {
            echo json_encode(["success" => false, "message" => "Error uploading file: " . $files["name"][$i]]);
            exit();
        }
        $file_data["file" . ($i + 1)] = new CURLFile($files["tmp_name"][$i], mime_content_type($files["tmp_name"][$i]), $files["name"][$i]);
    }

    // Send files via cURL to Flask
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $server_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $file_data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200) {
        echo json_encode(["success" => false, "message" => "Failed to send files. HTTP Code: $http_code, Response: " . $response]);
        exit();
    }

    // Check if response is valid JSON
    $response_data = json_decode($response, true);
    if ($response_data === null) {
        echo json_encode(["success" => false, "message" => "Invalid JSON received: " . $response]);
        exit();
    }

    if (!isset($response_data["file1"]) || !isset($response_data["file2"])) {
        echo json_encode(["success" => false, "message" => "Files uploaded but could not retrieve names. Response: " . json_encode($response_data)]);
        exit();
    }

    $file1_name = $response_data["file1"];
    $file2_name = $response_data["file2"];

    // Download both files one by one
    $file1_url = "http://192.168.101.139:5000/download/$file1_name";
    $file2_url = "http://192.168.101.139:5000/download/$file2_name";

    $file1_data = file_get_contents($file1_url);
    $file2_data = file_get_contents($file2_url);

    if ($file1_data === false || $file2_data === false) {
        echo json_encode(["success" => false, "message" => "Failed to download one or more files."]);
        exit();
    }

    // Ensure response directory exists
    if (!file_exists($response_dir)) {
        mkdir($response_dir, 0777, true);
    }

    // Save files locally
    file_put_contents($response_dir . $file1_name, $file1_data);
    file_put_contents($response_dir . $file2_name, $file2_data);

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO received_files (user_id, received_file1_name, received_file1_data, received_file2_name, received_file2_data) VALUES (?, ?, ?, ?, ?)");
    
    if ($stmt === false) {
        // Log the error if the statement fails to prepare
        error_log("MySQL prepare error: " . $conn->error);
        echo json_encode(["success" => false, "message" => "Failed to prepare the SQL statement: " . $conn->error]);
        exit();
    }

    // Retrieve user ID (Assuming user ID is passed as part of session or input)
    session_start();
    $user_id = $_SESSION['user_id'] ?? NULL;

    // Bind parameters
    $stmt->bind_param("sssss", $user_id, $file1_name, $file1_data, $file2_name, $file2_data);

    // Execute query
    if ($stmt->execute() === false) {
        // Log execution error
        error_log("MySQL execute error: " . $stmt->error);
        echo json_encode(["success" => false, "message" => "Failed to execute the query: " . $stmt->error]);
        exit();
    }

    $stmt->close();

    echo json_encode(["success" => true, "message" => "Files uploaded, processed, and stored successfully."]);
} else {
    echo json_encode(["success" => false, "message" => "No files received."]);
}

$conn->close();
?>
