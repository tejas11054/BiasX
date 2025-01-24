<?php
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php';

$host = 'localhost';       // Your database host (usually localhost)
$username = 'root';        // Your database username
$password = '';            // Your database password
$dbname = 'bdamt_db';      // Your database name

// Create a connection to the database
$conn = new mysqli($host, $username, $password, $dbname);

// Check the database connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['email'])) {
        // Send OTP to email logic
        $email = $_POST['email'];
        $otp = rand(100000, 999999);
        $_SESSION['otp'] = $otp;
        $_SESSION['email'] = $email;

        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'teambiasx@gmail.com';
            $mail->Password = '';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('teambiasx@gmail.com', 'Team BiasX');
            $mail->addAddress($email);
            $mail->Subject = "Your OTP Code";
            $mail->Body = "Your OTP for password reset is: " . $otp;

            $mail->send();
            echo json_encode(["status" => "success", "message" => "OTP sent successfully"]);
        } catch (Exception $e) {
            echo json_encode(["status" => "error", "message" => "Mailer Error: " . $mail->ErrorInfo]);
        }
    } elseif (isset($_POST['otp'])) {
        $enteredOtp = $_POST['otp'];
        
        // Debug: Check if OTP matches
        error_log("Entered OTP: " . $enteredOtp);
        error_log("Stored OTP: " . $_SESSION['otp']);
        
        if ($_SESSION['otp'] == $enteredOtp) {
            echo json_encode(["status" => "success", "message" => "OTP verified successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Invalid OTP"]);
        }
    }  elseif (isset($_POST['newPassword'])) {
        // Password reset logic
        $newPassword = $_POST['newPassword'];
        $email = $_SESSION['email'];
    
        // Hash the new password before storing it
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
        // Prepare the SQL query to update the password
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
        $stmt->bind_param("ss", $hashedPassword, $email);
    
        if ($stmt->execute()) {
            echo json_encode(["status" => "success", "message" => "Password reset successfully"]);
        } else {
            echo json_encode(["status" => "error", "message" => "Error updating password"]);
        }
    
        $stmt->close();
    }
    
    exit();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forget Password</title>
    <link rel="stylesheet" href="../styles/forfett.css">
    <link rel="icon" href="../images/2.png" type="image/png">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>
    <div class="container">
        <h1>Forget Password</h1>
        
        <!-- Send OTP Form -->
        <form id="forget-form">
            <label>Email:</label>
            <input type="email" id="email" required><br>
            <input type="submit" id="sendOTPBtn" value="Send OTP">
            <p id="responseMessage"></p>
        </form>

        <!-- OTP Verification Form (Hidden initially) -->
        <div id="otp-verification-form" style="display:none;">
            <label>Enter OTP:</label>
            <input type="text" id="otp" required><br>
            <input type="submit" id="verifyOTPBtn" value="Verify OTP">
            <p id="otpResponseMessage"></p>
        </div>

        <!-- Password Reset Form (Hidden initially) -->
        <div id="password-reset-form" style="display:none;">
            <label>New Password:</label>
            <input type="password" id="newPassword" required><br>
            <input type="submit" id="resetPasswordBtn" value="Reset Password">
            <p id="resetResponseMessage"></p>
        </div>
    </div>

    <script>
        // Send OTP
        document.getElementById("forget-form").addEventListener("submit", function(event) {
            event.preventDefault(); // Prevent form from refreshing the page

            let email = document.getElementById("email").value;

            fetch("forget.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "email=" + encodeURIComponent(email)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById("responseMessage").innerText = data.message;
                if (data.status == "success") {
                    document.getElementById("forget-form").style.display = "none";
                    document.getElementById("otp-verification-form").style.display = "block";
                }
            })
            .catch(error => {
                document.getElementById("responseMessage").innerText = "Error sending OTP";
            });
        });

        // Verify OTP

        document.getElementById("verifyOTPBtn").addEventListener("click", function(event) {
            event.preventDefault(); // Prevent form from refreshing the page

            let otp = document.getElementById("otp").value;

            fetch("forget.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "otp=" + encodeURIComponent(otp)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById("otpResponseMessage").innerText = data.message;
                if (data.status == "success") {
                    document.getElementById("otp-verification-form").style.display = "none";
                    document.getElementById("password-reset-form").style.display = "block";
                }
            })
            .catch(error => {
                document.getElementById("otpResponseMessage").innerText = "Error verifying OTP";
            });
        });


        // Reset Password
        document.getElementById("resetPasswordBtn").addEventListener("click", function(event) {
            event.preventDefault(); // Prevent form from refreshing the page

            let newPassword = document.getElementById("newPassword").value;

            fetch("forget.php", {
                method: "POST",
                headers: {
                    "Content-Type": "application/x-www-form-urlencoded"
                },
                body: "newPassword=" + encodeURIComponent(newPassword)
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById("resetResponseMessage").innerText = data.message;
                if (data.status == "success") {
                    // Alert the user that the password has been successfully reset
                    alert("Password has been reset successfully!");

                    // Redirect to login page
                    window.location.href = "login.html";
                }
            })
            .catch(error => {
                document.getElementById("resetResponseMessage").innerText = "Error resetting password";
            });
        });


    </script>
</body>
</html>
