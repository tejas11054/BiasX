<?php
session_start(); // Start session to access user_id

// Debugging: Log the session value
error_log("Session user_id in dashboard.php: " . ($_SESSION['user_id'] ?? 'not_logged_in'));

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
} else {
    $user_id = "not_logged_in"; // Default if not logged in
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode(["user_id" => $user_id]);


?>
