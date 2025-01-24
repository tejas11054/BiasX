<?php
// Database connection
$host = 'localhost';
$username = 'root';
$password = '';
$dbname = 'bdamt_db';

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to fetch user data
$query = "SELECT id, name, email, profession, gender, mobile FROM users";
$result = $conn->query($query);

// Check if there are results
if ($result->num_rows > 0) {
    // Start table
    echo "<table border='1'>
            <tr>
                <th>Sr. No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Profession</th>
                <th>Gender</th>
                <th>Mobile</th>
            </tr>";
    
    // Loop through the results and display each user
    $sr_no = 1;
    while($row = $result->fetch_assoc()) {
        echo "<tr>
                <td>" . $sr_no++ . "</td>
                <td>" . $row['name'] . "</td>
                <td>" . $row['email'] . "</td>
                <td>" . $row['profession'] . "</td>
                <td>" . $row['gender'] . "</td>
                <td>" . $row['mobile'] . "</td>
              </tr>";
    }

    echo "</table>";
} else {
    echo "No users found";
}

$conn->close();
?>
