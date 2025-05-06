<?php
session_start([
    'use_strict_mode' => true,
    'cookie_httponly' => true,
    'cookie_secure' => true, // Enable this if using HTTPS
]);

// Database connection
$host = "localhost";
$user = "root";
$password = "";
$database = "bdamt_db";

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Validate and sanitize ID parameter
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    die("Invalid ID provided.");
}

$id = intval($_GET['id']); // Ensure it's an integer

// Fetch data securely
$sql = "SELECT received_file2_data FROM received_files WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

// Check if data exists
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $jsonData = $row['received_file2_data'];
    $data = json_decode($jsonData, true);

    // Ensure JSON is properly decoded and handle array structure
    if (!is_array($data) || empty($data)) {
        echo "Invalid data format!";
        exit;
    }

    // Since JSON is an array, take the first element
    $data = $data[0];
} else {
    echo "Record not found!";
    exit;
}

$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.9.2/html2pdf.bundle.js"></script>

    <link rel="icon" href="../images/logo.png" type="image/png">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #102A43;
            color: #fff;
        }
        .container {
            max-width: 900px;
            margin: 40px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 12px;
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
            color: #102A43;
        }
        p {
            color:black;
        }
        h1, h2, h3 {
            color: #0F4C75;
            text-align: center;
        }
        .section {
            margin-top: 30px;
            padding: 20px;
            background-color: #eaf2f8;
            border-radius: 8px;
        }
        .table-container {
            margin-top: 20px;
        }
        .chart-container {
            margin-top: 40px;
            background-color: #f9fbfc;
            border-radius: 8px;
            padding: 20px;
        }
        .back-btn {
            background-color: #0F4C75;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 18px;
            width: 20%;
            text-align: center;
            display: block;
            margin: 20px auto;
        }
        .back-btn:hover {
            background-color: #3282B8;
        }
        .header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 20px 0;
        border-bottom: 2px solid #0F4C75;
        margin-bottom: 30px;
    }
    .logo-container {
        flex: 0 0 150px; /* Adjust the logo size */
    }
    .logo {
        max-width: 100%;
        height: auto;
    }
    .title-container {
        flex-grow: 1;
        text-align: center;
        margin-left: 20px;
    }
    .report-title {
        color: #0F4C75;
        font-size: 2.5em;
        margin: 0;
    }
    .report-subtitle {
        color: #3282B8;
        font-size: 1.2em;
        margin-top: 5px;
    }

    @media (max-width: 768px) {
    .container {
        padding: 15px;
        margin: 20px 10px;
    }

    .header {
        flex-direction: column;
        align-items: center;
        text-align: center;
    }

    .logo-container {
        margin-bottom: 15px;
    }

    .title-container {
        margin-left: 0;
    }

    .report-title {
        font-size: 1.8em;
    }

    .report-subtitle {
        font-size: 1em;
    }

    .back-btn {
        width: 80%;
        font-size: 16px;
    }

    canvas {
        max-width: 100% !important;
        height: auto !important;
    }

    .chart-container {
        padding: 10px;
    }

    .table {
        font-size: 14px;
    }
}

    </style>
</head>
<body>

<div class="container">
    <!-- Logo Section -->
    <div class="header">
        <div class="logo-container">
            <img src="../images/logo.png" alt="Logo" class="logo"> <!-- Replace with your logo URL -->
        </div>
        <div class="title-container">
            <h1 class="report-title">Bias Detection and Mitigation</h1>
            <p class="report-subtitle">X Out Bias, Embrace the Fairness</p>
        </div>
    </div>
    
    <div class="section">
        <h2>Introduction</h2>
        <p>This report provides an in-depth analysis of the dataset <strong><?php echo htmlspecialchars($data['dataset']); ?></strong>. The primary goal is to identify and mitigate bias in the dataset to ensure fair and equitable AI outcomes. Bias detection is an essential step in promoting fairness and transparency in AI models, and the following analysis will highlight the findings and results of this process. By applying bias mitigation strategies, we aim to improve model accuracy and ensure that the AI systems function fairly. Advanced machine learning algorithms are leveraged to quantify bias, providing a clear path for effective mitigation.</p>     
    </div>

    <div class="section">
        <h2>Dataset Overview</h2>
        <table class="table table-bordered">
            <tr ><th style="width: 50%;">Dataset Name</th><td><?php echo htmlspecialchars($data['dataset']); ?></td></tr>
            <tr><th style="width: 50%;">Number of Rows</th><td><?php echo $data['rows']; ?></td></tr>
            <tr><th style="width: 50%;">Number of Columns</th><td><?php echo $data['columns']; ?></td></tr>
        </table>
    </div>

    <div class="section">
        <h2>Bias Detection</h2>
        <table class="table table-bordered">
            <tr><th style="width: 70%;">Attribute with Bias</th><td><?php echo htmlspecialchars($data['Attribute_with_bias']); ?></td></tr>
            <tr><th style="width: 70%;">Statistical Parity</th><td><?php echo $data['metrics']['statistical_parity']; ?></td></tr>
            <tr><th style="width: 70%;">Disparate Impact</th><td><?php echo $data['metrics']['disparate impact']; ?></td></tr>
            <tr><th style="width: 70%;">Equalized Odds</th><td><?php echo $data['metrics']['equalized_odds']; ?></td></tr>
            <tr><th style="width: 70%;">Equalized Opportunity</th><td><?php echo $data['metrics']['equalized_opportunity']; ?></td></tr>
            <tr><th style="width: 70%;">Predictive Parity</th><td><?php echo $data['metrics']['predictive_parity']; ?></td></tr>
        </table>
    </div>

    <!-- Chart Section Below Bias -->
    <div class="chart-container">
        <canvas id="biasChart"></canvas>
    </div>

    <div class="section">
        <h2>Mitigation Strategy</h2>
        <p>The mitigation technique used to address bias in the dataset is <strong><?php echo htmlspecialchars($data['mitigation_technique_used']); ?></strong>.</p>

        <?php 
            $technique = $data['mitigation_technique_used'];
            switch ($technique) {
                case 'reweighting':
                    echo "<p><strong>Reweighting:</strong> This technique assigns different weights to data samples to balance out underrepresented groups. It helps the model learn equally from all subgroups, reducing bias.</p>";
                    break;
                case 'disparate_impact_remover':
                    echo "<p><strong>Disparate Impact Remover:</strong> This method edits feature values to reduce bias while preserving as much information as possible. It aims to align outcomes across groups.</p>";
                    break;
                case 'adversarial_debiasing':
                    echo "<p><strong>Adversarial Debiasing:</strong> This technique trains the model alongside an adversary that learns to detect bias. The model adapts to minimize the adversary's ability to identify protected attributes.</p>";
                    break;
                case 'prejudice_remover':
                    echo "<p><strong>Prejudice Remover:</strong> This approach adds a regularization term during training to penalize biased predictions, encouraging fairer outcomes.</p>";
                    break;
                default:
                    echo "<p>No mitigation technique description available.</p>";
            }
        ?>
        <p>The model used for training is <strong><?php echo htmlspecialchars($data['model_trained']); ?></strong>.</p>
        <p><strong>Logistic Regression:</strong>Logistic regression is a straightforward and interpretable model often used in bias detection to understand the relationship between features and outcomes. It helps identify biased patterns in datasets, serving as a baseline for evaluating fairness before and after applying mitigation techniques.</p>
    </div>




    <div class="section">
        <h2>Results and Accuracy</h2>
        <table class="table table-bordered">
            <tr><th style="width: 70%;">Previous Accuracy</th><td><?php echo $data['Previous_accuracy']; ?>%</td></tr>
            <tr><th style="width: 70%;">New Accuracy After Mitigation</th><td><?php echo $data['New_accuracy']; ?>%</td></tr>
        </table>
    </div>

    <div class="chart-container">
        <canvas id="accuracyChart"></canvas>
    </div>
    
    <div class="section">
        <h2>Conclusion</h2>
        <p>The dataset was found to have bias in the attribute <strong><?php echo htmlspecialchars($data['Attribute_with_bias']); ?></strong>. After applying the mitigation technique <strong><?php echo htmlspecialchars($data['mitigation_technique_used']); ?></strong>, the accuracy improved from <strong><?php echo $data['Previous_accuracy']; ?>%</strong> to <strong><?php echo $data['New_accuracy']; ?>%</strong>. This indicates that bias mitigation can enhance AI fairness.</p>
    </div>

    <button class="back-btn" id="downloadBtn">Download</button>
</div>

<script>
    var metricsDataBefore = <?php echo json_encode($data['metrics']); ?>;
    var accuracyData = [<?php echo $data['Previous_accuracy']; ?>, <?php echo $data['New_accuracy']; ?>];

    window.onload = function() {
        // Bias Chart
        var ctxBias = document.getElementById('biasChart').getContext('2d');
        new Chart(ctxBias, {
            type: 'bar',
            data: {
                labels: ['Statistical Parity', 'Disparate Impact', 'Equalized Odds', 'Equalized Opportunity', 'Predictive Parity'],
                datasets: [{
                    label: 'Before Mitigation',
                    data: [metricsDataBefore.statistical_parity, metricsDataBefore['disparate impact'], metricsDataBefore['equalized_odds'], metricsDataBefore['equalized_opportunity'], metricsDataBefore['predictive_parity']],
                    backgroundColor: 'rgba(15, 76, 117, 0.2)',
                    borderColor: 'rgba(15, 76, 117, 1)',
                    borderWidth: 1
                }]
            },
            options: { 
                scales: { 
                    y: { beginAtZero: true } 
                }
            }
        });

        // Accuracy Chart
        var ctxAccuracy = document.getElementById('accuracyChart').getContext('2d');
        new Chart(ctxAccuracy, {
            type: 'line',
            data: {
                labels: ['Before Mitigation', 'After Mitigation'],
                datasets: [{
                    label: 'Accuracy',
                    data: accuracyData,
                    fill: false,
                    borderColor: 'rgba(50, 130, 184, 1)',
                    tension: 0.1,
                    borderWidth: 2
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    };

    downloadBtn.addEventListener('click', function () {
    // Hide the button before generating the PDF
    downloadBtn.style.display = 'none';

    // Ensure the charts are properly sized and centered before capture
    var charts = document.querySelectorAll('canvas');
    charts.forEach(function (chart) {
        // Wrap each chart in a div for centering
        var wrapper = document.createElement('div');
        wrapper.style.display = 'flex';
        wrapper.style.justifyContent = 'center';
        wrapper.style.alignItems = 'center';
        wrapper.style.width = '100%';
        wrapper.style.pageBreakBefore = 'auto'; // Prevent unnecessary page break before chart
        wrapper.style.pageBreakInside = 'avoid';
        wrapper.style.marginBottom = '20px'; // Ensure some space between charts

        // Move the chart inside the wrapper
        chart.parentNode.insertBefore(wrapper, chart);
        wrapper.appendChild(chart);

        // Set chart dimensions to fit on the page (adjust width/height as needed)
        chart.style.width = '600px';
        chart.style.height = '400px';
    });

    // Give Chart.js some time to adjust
    setTimeout(() => {
        var options = {
            filename: 'Bias_Report.pdf',
            image: { type: 'jpeg', quality: 1 },
            html2canvas: { scale: 3, useCORS: true },
            jsPDF: {
                unit: 'mm',
                format: 'a4',
                orientation: 'portrait',
                margin: [10, 10, 10, 10], // Adds a margin to avoid overflow
            }
        };

        html2pdf()
            .from(document.querySelector('.container'))
            .set(options)
            .save()
            .then(() => {
                // Restore button after saving PDF
                downloadBtn.style.display = 'block';

                // Reset chart styles and remove wrappers
                charts.forEach(function (chart) {
                    chart.style.width = ''; // Reset
                    chart.style.height = ''; // Reset

                    let wrapper = chart.parentNode;
                    if (wrapper && wrapper.parentNode) {
                        wrapper.parentNode.insertBefore(chart, wrapper);
                        wrapper.remove();
                    }
                });
            });
    }, 500);
});


</script>

</body>
</html>
