<?php
session_start(); // Start the session to store data across pages

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

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Capture form data
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $plainPassword = isset($_POST['password']) ? $_POST['password'] : '';

    // Validate data
    if (empty($email) || empty($plainPassword)) {
        echo "<script>alert('Please fill all the fields'); window.location.href='login.html';</script>";
        exit();
    }

    // Prepare SQL query with placeholders to prevent SQL injection
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($user_id, $storedHashedPassword);
        $stmt->fetch();

        // Verify the entered password with the stored hash using password_verify()
        if (password_verify($plainPassword, $storedHashedPassword)) {
            // Password is correct, login successful

            // Store user ID in session
            $_SESSION['user_id'] = $user_id;

            // Redirect to the dashboard
            echo "<script>window.location.href='dashboard.html';</script>";
            exit();
        } else {
            // Incorrect password
            echo "<script>alert('Invalid password. Please try again.'); window.location.href='login.html';</script>";
            exit();
        }
    } else {
        // No account found with the email
        echo "<script>alert('No account found with this email. Please sign up first.'); window.location.href='login.html';</script>";
        exit();
    }

    $stmt->close();
    $conn->close();
} else {
    echo "Invalid request method";
}
?>