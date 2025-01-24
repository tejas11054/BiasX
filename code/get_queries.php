<?php
// Establish database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "bdamt_db";  // Replace with your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch queries from contact table
$sql = "SELECT name, email, msg FROM contact";  // Replace 'contact' with your table name
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Queries</title>
    <link rel="stylesheet" href="../styles/admin_dashboard.css">
</head>
<body>

<div class="container">
    <div class="left-panel">
        <h1>Dashboard</h1>
        <ul>
            <li><a href="#" class="panel-item"><i class="fas fa-home"></i> Home</a></li>
            <li><a href="profile.php" class="panel-item"><i class="fas fa-user"></i> Users</a></li>
            <li><a href="forget.php" class="panel-item"><i class="fas fa-comment"></i> Queries</a></li>
            <li><a href="index.html" class="panel-item"><i class="fas fa-sign-out-alt"></i> Logout</a></li>            
        </ul>
    </div>

    <div class="right-panel">
        <div class="top-content">
            <div class="section">
                <h3>Queries</h3>

                <!-- Table to display queries -->
                <table>
                    <thead>
                        <tr>
                            <th>Sr. No</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Message</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            $sr_no = 1;  // Starting serial number
                            // Output data of each row
                            while($row = $result->fetch_assoc()) {
                                echo "<tr>
                                        <td>" . $sr_no++ . "</td>
                                        <td>" . $row['name'] . "</td>
                                        <td>" . $row['email'] . "</td>
                                        <td>" . $row['msg'] . "</td>
                                    </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No queries found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>

<?php
// Close the connection
$conn->close();
?>
