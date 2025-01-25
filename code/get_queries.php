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

// Query to fetch queries data from the 'contact' table
$query = "SELECT name, email, msg FROM contact ORDER BY name ASC";
$result = $conn->query($query);

// Check if query execution was successful
if (!$result) {
    die("Error executing query: " . $conn->error);
}

// Check if there are results
if ($result->num_rows > 0) {
    echo "<table class='query' border='1'>
            <tr>
                <th>Sr. No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Message</th>
                <th>Action</th>
            </tr>";
    
    $sr_no = 1;
    while ($row = $result->fetch_assoc()) {
        $email = htmlspecialchars($row['email']);
        echo "<tr>
                <td>" . $sr_no++ . "</td>
                <td>" . htmlspecialchars($row['name']) . "</td>
                <td>" . $email . "</td>
                <td>" . nl2br(htmlspecialchars($row['msg'])) . "</td>
                <td>
                    <button class='answer-btn' data-email='$email'>Answer</button>
                </td>
              </tr>";

        // Hidden row for the answer text box
        echo "<tr id='answer-box-$email' class='answer-row' style='display: none;'>
                <td colspan='8'>
                    <textarea class='answer-text'id='answer-text-$email' placeholder='Type your answer here'></textarea>
                    <button class='send-answer-btn' data-email='$email'>Send</button>
                </td>
              </tr>";
    }
    echo "</table>";
} else {
    echo "No queries found";
}

$conn->close();
?>
