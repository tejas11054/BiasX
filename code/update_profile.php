<?php
session_start();

$servername = "localhost";
$username = "root";
$password = ""; 
$dbname = "bdamt_db";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access.");
}

$user_id = $_SESSION['user_id'];

// Get the form data
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$gender = isset($_POST['gender']) ? $_POST['gender'] : NULL;
$profession = trim($_POST['profession']);
$mobile = trim($_POST['mobile']);

// Validate Name (only letters and spaces)
if (!preg_match("/^[a-zA-Z\s]+$/", $name)) {
    die("<script>alert('Name should only contain letters and spaces.'); window.history.back();</script>");
}

// Validate Email Format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    die("<script>alert('Invalid email format.'); window.history.back();</script>");
}

// Validate Mobile Number
if (!empty($mobile)) {
    if (!preg_match("/^[0-9]{10}$/", $mobile)) {
        die("<script>alert('Invalid mobile number. It should be exactly 10 digits.'); window.history.back();</script>");
    }
} else {
    $mobile = NULL; // Allow NULL if the user leaves it empty
}

// Update the database
$sql = "UPDATE users SET name = ?, email = ?, gender = ?, profession = ?, mobile = ? WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssi", $name, $email, $gender, $profession, $mobile, $user_id);

if ($stmt->execute()) {
    echo "<script>alert('Profile updated successfully!'); window.location.href='profile.php';</script>";
} else {
    echo "<script>alert('Error updating profile: " . $stmt->error . "'); window.history.back();</script>";
}

$stmt->close();
$conn->close();
?>
