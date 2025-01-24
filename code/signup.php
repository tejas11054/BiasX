<?php
session_start(); // Start the session

$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "bdamt_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("<script>alert('Database connection failed: " . $conn->connect_error . "');</script>");
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Capture form data
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate name (only letters and spaces)
    if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
        die("<script>alert('Name should not contain numbers or special characters.'); window.history.back();</script>");
    }

    // Validate email (proper email format)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        die("<script>alert('Please enter a valid email address.'); window.history.back();</script>");
    }

    // Validate password length (minimum 6 characters)
    if (strlen($password) < 6) {
        die("<script>alert('Password must be at least 6 characters long.'); window.history.back();</script>");
    }

    // Hash the password for security
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert into the database using prepared statements
    $sql = "INSERT INTO users (name, email, password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $name, $email, $hashedPassword);

    if ($stmt->execute()) {
        // Get the last inserted ID
        $user_id = $stmt->insert_id;

        // Store the user ID in session
        $_SESSION['user_id'] = $user_id;

        // Debugging: Log the session value
        error_log("User ID set in session: " . $_SESSION['user_id']);

        // Redirect to dashboard.html
        echo "<script>window.location.href='dashboard.html';</script>";
    } else {
        echo "<script>alert('Error: " . $stmt->error . "'); window.history.back();</script>";
    }

    $stmt->close();
    $conn->close();
} else {
    echo "<script>alert('Invalid request method'); window.history.back();</script>";
}
?>
