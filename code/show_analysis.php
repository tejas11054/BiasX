<?php
// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "bdamt_db";
$conn = new mysqli($host, $user, $password, $database);

// Fetch the ID from the URL parameter
$id = $_GET['id'];

// Prepare and execute the SQL query to get the record with the specific ID
$sql = "SELECT * FROM received_files WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Check if record exists
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $jsonData = $row['received_file2_data'];
    $decodedData = json_decode($jsonData, true);

    // Ensure JSON is properly decoded and handle array structure
    if (!is_array($decodedData) || empty($decodedData)) {
        echo "Invalid data format!";
        exit;
    }

    // Since JSON is an array, take the first element
    $data = $decodedData[0];
} else {
    echo "Record not found!";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analysis Details</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <link rel="icon" href="../images/logo.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body { font-family: 'Arial', sans-serif; background-color: #102A43; color: #fff; }
        .container { max-width: 900px; margin: 40px auto; padding: 30px; background-color: #fff; border-radius: 12px; box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1); }
        h1 { color: #0F4C75; font-weight: bold; text-align: center; margin-bottom: 40px; }
        .details { background-color: #eaf2f8; border-radius: 8px; padding: 20px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); }
        .details p { font-size: 18px; color: #102A43; }
        .details p strong { color: #0F4C75; }
        .chart-container { margin-top: 40px; background-color: #f9fbfc; border-radius: 8px; padding: 20px; }
        .btn-container { display: flex; justify-content: center; margin-top: 30px; }
        .back-btn { background-color: #0F4C75; color: white; border: none; padding: 12px 20px; border-radius: 8px; cursor: pointer; font-size: 18px; width: 20%; text-align: center; }
        .back-btn:hover { background-color: #3282B8; }
    </style>
</head>
<body>
<div class="container">
    <h1>Analysis</h1>
    <div class="details">
        <p><strong>Dataset Name:</strong> <?php echo htmlspecialchars($data['dataset']); ?></p>
        <p><strong>Number of Rows:</strong> <?php echo $data['rows']; ?></p>
        <p><strong>Number of Columns:</strong> <?php echo $data['columns']; ?></p>
        <p><strong>Attribute with Bias:</strong> <?php echo htmlspecialchars($data['Attribute_with_bias']); ?></p>
        <p><strong>Mitigation Technique Used:</strong> <?php echo htmlspecialchars($data['mitigation_technique_used']); ?></p>
        <p><strong>Model Trained:</strong> <?php echo htmlspecialchars($data['model_trained']); ?></p>
        <p><strong>Timestamp:</strong> <?php echo htmlspecialchars($data['Timestamp']); ?></p>
    </div>
    <div class="chart-container">
        <canvas id="metricsChart"></canvas>
    </div>
    <div class="chart-container">
        <canvas id="accuracyChart"></canvas>
    </div>
    <div class="btn-container">
        <button class="back-btn" onclick="window.history.back()">Go Back</button>
    </div>
    <script>
        var metricsData = <?php echo json_encode($data['metrics']); ?>;
        var accuracyData = {
            'Previous': <?php echo $data['Previous_accuracy']; ?>,
            'New': <?php echo $data['New_accuracy']; ?>
        };
        window.onload = function() {
            var ctxMetrics = document.getElementById('metricsChart').getContext('2d');
            new Chart(ctxMetrics, {
                type: 'bar',
                data: {
                    labels: Object.keys(metricsData),
                    datasets: [{
                        label: 'Bias Metrics',
                        data: Object.values(metricsData),
                        backgroundColor: 'rgba(15, 76, 117, 0.2)',
                        borderColor: 'rgba(15, 76, 117, 1)',
                        borderWidth: 1
                    }]
                },
                options: { scales: { y: { beginAtZero: true } }, responsive: true }
            });
            var ctxAccuracy = document.getElementById('accuracyChart').getContext('2d');
            new Chart(ctxAccuracy, {
                type: 'line',
                data: {
                    labels: ['Previous Accuracy', 'New Accuracy'],
                    datasets: [{
                        label: 'Accuracy',
                        data: [accuracyData['Previous'], accuracyData['New']],
                        fill: false,
                        borderColor: 'rgba(50, 130, 184, 1)',
                        tension: 0.1
                    }]
                },
                options: { responsive: true }
            });
        };
    </script>
</div>
</body>
</html>
