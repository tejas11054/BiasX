<?php
// Start the session to manage user login state
session_start();

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form inputs
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if the username is 'admin' and the password is '123'
    if ($username == 'admin' && $password == '123') {
        // Set session variable to indicate the user is logged in
        $_SESSION['loggedin'] = true;
        $_SESSION['username'] = $username;

        // Redirect to the admin dashboard
        header('Location: admin_dashboard.html');
        exit;
    } else {
        // If credentials are invalid, show an error message
        $error_message = "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../styles/admin_login.css">
    <link rel="icon" href="../images/2.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Login</h1>
        
        <!-- Login Form -->
        <form id="login-form" method="POST" action="">
            <label>User Name:</label>
            <input type="text" name="username" required><br>
            <label>Password:</label>
            <input type="password" name="password" required><br>
            <input type="submit" id="login" value="Login">
            <?php
            // Display error message if the login credentials are incorrect
            if (isset($error_message)) {
                echo "<p style='color: red;'>$error_message</p>";
            }
            ?>
        </form>
    </div>
</body>
</html>
