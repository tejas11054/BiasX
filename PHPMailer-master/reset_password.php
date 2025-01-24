<?php
// Reset Password
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get email and new password from POST
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password

    // Database connection (replace with your actual database credentials)
    $servername = "localhost";
    $username = "root";
    $password_db = "";
    $dbname = "bdamt_db";

    $conn = new mysqli($servername, $username, $password_db, $dbname);

    // Check for connection errors
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Update the password in the database
    $sql = "UPDATE users SET password = ? WHERE email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $password, $email);

    if ($stmt->execute()) {
        echo json_encode(["status" => "success", "message" => "Password reset successfully"]);
    } else {
        echo json_encode(["status" => "error", "message" => "Failed to reset password"]);
    }

    $stmt->close();
    $conn->close();
}
?>
