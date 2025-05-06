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
    die("Database connection failed: " . $conn->connect_error);
}

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];

// Retrieve user details from the database
$sql = "SELECT name, email, gender, profession, mobile FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $user = $result->fetch_assoc();
} else {
    die("User not found.");
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <link rel="stylesheet" href="../styles/profile.css">
    <link rel="icon" href="../images/logo.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

</head>
<body>
    <div class="container">
        <h1>Edit Profile</h1>
        <form action="update_profile.php" method="POST">
            <label>Name:</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required><br>

            <label>Email:</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required><br>

            <label>Gender:</label>
            <select name="gender">
                <option value="">Select</option>
                <option value="Male" <?php if ($user['gender'] == 'Male') echo 'selected'; ?>>Male</option>
                <option value="Female" <?php if ($user['gender'] == 'Female') echo 'selected'; ?>>Female</option>
                <option value="Other" <?php if ($user['gender'] == 'Other') echo 'selected'; ?>>Other</option>
            </select><br>

            <label>Profession:</label>
            <input type="text" name="profession" value="<?php echo htmlspecialchars($user['profession']); ?>"><br>

            <label>Mobile:</label>
            <input type="text" name="mobile" value="<?php echo htmlspecialchars($user['mobile']); ?>" pattern="[0-9]{10}" title="Enter a valid 10-digit mobile number" maxlength="10"><br>

            <input type="submit" value="Update Profile">
        </form>

        <a href="dashboard.html">Back to Dashboard</a>
    </div>
</body>
</html>
