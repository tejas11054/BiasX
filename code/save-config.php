<?php
// Allow cross-origin requests (if needed)
header("Content-Type: application/json");
session_start();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Handle file upload
    if (!isset($_FILES["file"])) {
        echo json_encode(["success" => false, "message" => "No file uploaded"]);
        exit;
    }

    $file = $_FILES["file"];
    $file_path = basename($file["name"]);

    if (!move_uploaded_file($file["tmp_name"], $file_path)) {
        echo json_encode(["success" => false, "message" => "Failed to save file"]);
        exit;
    }

    // Read additional form data
    $configData = [
        "name" => $_POST["name"],
        "target" => $_POST["target"],
        "sensitive_attribute" => explode(",", $_POST["sensitive_attribute"]),
        "privileged_group" => explode(",", $_POST["privileged_group"]),
        "unprivileged_group" => explode(",", $_POST["unprivileged_group"])
    ];

    // Save JSON data
    $json_file = "configuration.json";
    if (file_put_contents($json_file, json_encode($configData, JSON_PRETTY_PRINT))) {
        $command = 'cmd /c "call conda activate llm-from-scratch-1 && python C:\xampp\htdocs\Project\BiasX\code\automated_bias_mitigation.py ' . escapeshellarg($configData['name']) . ' configuration.json" 2>&1';
        $output = shell_exec($command);
        file_put_contents("script_output.log", $output, FILE_APPEND);
    // Save full output, including errors

        // Replicate the Python logic to get the latest model folder in PHP
        $model_dir = "Mitigated_Model/";
        $latest_folder = null;
        $latest_time = 0;

        foreach (glob($model_dir . "*", GLOB_ONLYDIR) as $folder) {
            $folder_time = filemtime($folder);
            if ($folder_time > $latest_time) {
                $latest_time = $folder_time;
                $latest_folder = $folder;
            }
        }

        if (!$latest_folder) {
            echo json_encode(["success" => false, "message" => "Failed to retrieve latest model folder."]);
            exit;
        }

        // File paths from latest folder
        $files = [
            "received_file1_name" => "$latest_folder/new_dataset.csv",
            "received_file2_name" => "$latest_folder/results.json",
            "received_file3_name" => "$latest_folder/original_dataset.csv",
            "received_file4_name" => "$latest_folder/mitigated_model.pkl"
        ];

        // Read file contents
        $file_data = [];
        foreach ($files as $key => $file_path) {
            if (file_exists($file_path)) {
                $file_data[$key] = file_get_contents($file_path);
            } else {
                $file_data[$key] = NULL;
            }
        }

        // Database connection
        $servername = "localhost";
        $username = "root";
        $password = "";
        $dbname = "bdamt_db";

        $conn = new mysqli($servername, $username, $password, $dbname);
        if ($conn->connect_error) {
            die(json_encode(["success" => false, "message" => "Database connection failed: " . $conn->connect_error]));
        }

        // Retrieve user ID (assuming session variable)
        $user_id = $_SESSION['user_id'] ?? NULL;

        // Prepare SQL statement
        $stmt = $conn->prepare("INSERT INTO received_files 
            (user_id, received_file1_name, received_file1_data, received_file2_name, received_file2_data, 
             received_file3_name, received_file3_data, received_file4_name, received_file4_data) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

        if ($stmt === false) {
            error_log("MySQL prepare error: " . $conn->error);
            echo json_encode(["success" => false, "message" => "Failed to prepare the SQL statement: " . $conn->error]);
            exit();
        }

        // Bind parameters
        $stmt->bind_param("sssssssss", $user_id, 
            basename($files["received_file1_name"]), $file_data["received_file1_name"], 
            basename($files["received_file2_name"]), $file_data["received_file2_name"], 
            basename($files["received_file3_name"]), $file_data["received_file3_name"], 
            basename($files["received_file4_name"]), $file_data["received_file4_name"]
        );

        // Execute query
        if ($stmt->execute() === false) {
            error_log("MySQL execute error: " . $stmt->error);
            echo json_encode(["success" => false, "message" => "Failed to execute the query: " . $stmt->error]);
            exit();
        }

        $stmt->close();
        $conn->close();

        echo json_encode(["success" => true, "message" => "Bias Detection & Mitigation Completed Successfully!"]);

    } else {
        echo json_encode(["success" => false, "message" => "Bias Detection & Mitigation Completed Successfully!"]);
    }
}
?>
