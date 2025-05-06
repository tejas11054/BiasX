<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../PHPMailer-master/src/Exception.php';
require '../PHPMailer-master/src/PHPMailer.php';
require '../PHPMailer-master/src/SMTP.php'; // Ensure PHPMailer is installed

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $answer = $_POST['answer'];

    if (empty($email) || empty($answer)) {
        echo "Error: Email or answer is missing.";
        exit;
    }

    $mail = new PHPMailer(true);

    try {
        // SMTP Configuration
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';  // SMTP server (Use 'smtp.office365.com' for Outlook)
        $mail->SMTPAuth = true;
        $mail->Username = 'teambiasx@gmail.com'; // Replace with your Gmail
        $mail->Password = 'ntxq zgjg hlcp bsaw';   // Generate an App Password from Google
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        // Sender & Recipient
        $mail->setFrom('teambiasx@gmail.com', 'Team BiasX');
        $mail->addAddress($email);

        // Email Content
        $mail->isHTML(false);
        $mail->Subject = "Response to Your Query";
        $mail->Body = "Hello,\n\nWe have received your query. Here is our response:\n\n$answer\n\nBest regards,\nAdmin Team";

        // Send Email
        if ($mail->send()) {
            echo "Response sent successfully.";
        } else {
            echo "Failed to send response.";
        }
    } catch (Exception $e) {
        echo "Error: " . $mail->ErrorInfo;
    }
}
?>
