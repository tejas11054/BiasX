<?php
// Database connection details
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

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $msg = trim($_POST["msg"]);
    
    // Validate input fields
    if (empty($name) || empty($email) || empty($msg)) {
        die("All fields are required.");
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("Invalid email format.");
    }
    
    // Additional email validation: Check domain
    $email_domain = substr(strrchr($email, "@"), 1);
    if (!checkdnsrr($email_domain, "MX")) {
        die("Invalid email domain.");
    }
    
    // Prepare and bind SQL statement
    $stmt = $conn->prepare("INSERT INTO contact (name, email, msg) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $msg);
    
    // Execute statement
    if ($stmt->execute()) {
        echo "<script>alert('Feedback submitted successfully!'); window.location.href = 'index.html#contact';</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "'); window.history.back();</script>";
    }
    
    // Close statement and connection
    $stmt->close();
    $conn->close();
}
?>