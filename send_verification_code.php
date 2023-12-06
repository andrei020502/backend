
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Retrieve form data
$email = $_POST["email"];

// Query to check if the email exists in the users table
$userSql = "SELECT * FROM companion_users WHERE email = '$email'";
$userResult = $conn->query($userSql);

$verificationCode = mt_rand(100000, 999999);

// Check if email exists in the users table
if ($userResult->num_rows > 0) {
    // Email exists in the users table
    $updateSql = "UPDATE companion_users SET code = $verificationCode WHERE email = '$email'";

    // Execute the update query
    if ($conn->query($updateSql) === TRUE) {
        // Create a new PHPMailer instance
        $mail = new PHPMailer(true);

        try {
            // Server settings
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = 'bernestominimart@gmail.com'; // Your Gmail email address
            $mail->Password   = 'uclnxhyvqiywevsa'; // Your App Password without spaces
            $mail->SMTPSecure = "ssl";
            $mail->Port       = 465; // Change to 465

            // Recipient
            $mail->setFrom('bernestominimart@gmail.com', 'bernestominimart');
            $mail->addAddress($email);

            // Content
            $mail->isHTML(true);
            $mail->Subject = 'Forgot Password Verification Code';
            $mail->Body    = 'Your verification code is: ' . $verificationCode;

            // Send email
            $mail->send();

            // Set success message
            $response = ['status' => 'success', 'message' => 'Verification code sent successfully'];
        } catch (Exception $e) {
            // Handle errors
            $response = ['status' => 'error', 'message' => 'Message could not be sent. Please try again later'];
        }
    } else {
        // Update query failed
        $response = ['status' => 'error', 'message' => 'Sending verification code failed'];
    }
} else {
    // Email does not exist in the users table
    $response = ['status' => 'error', 'message' => 'No email found'];
}

// Close the database connection
$conn->close();

// Send the JSON response
header('Content-Type: application/json');
echo json_encode($response);

?>
