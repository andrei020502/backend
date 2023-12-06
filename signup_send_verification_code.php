<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require 'connection.php';

// Initialize variables
$response = [];
$insertStmt = null;
$updateStmt = null;

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Retrieve form data
$email = $_POST["email"];
$username = $_POST["username"];
$password = $_POST["password"];
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// Check if email exists in the users table (using prepared statement to prevent SQL injection)
$userStmt = $conn->prepare("SELECT * FROM companion_users WHERE email = ?");
$userStmt->bind_param("s", $email);
$userStmt->execute();
$userResult = $userStmt->get_result();

if ($userResult->num_rows > 0) {
    // Email already exists, output an error message
    $response = ['status' => 'error', 'message' => 'Email already exists. Please choose another email.'];
} else {
    // Email does not exist, proceed to insert a new user and send the verification code
    $verificationCode = mt_rand(100000, 999999);

    // Insert a new user (using prepared statement to prevent SQL injection)
    $insertStmt = $conn->prepare("INSERT INTO companion_users (email, username, password) VALUES (?, ?, ?)");
    $insertStmt->bind_param("sss", $email, $username, $hashedPassword);

    if ($insertStmt !== false && $insertStmt->execute()) {

        // Update verification code for the new user
        $updateStmt = $conn->prepare("UPDATE companion_users SET code = ? WHERE email = ?");
        $updateStmt->bind_param("ss", $verificationCode, $email);

        // Check if the update statement is prepared successfully
        if ($updateStmt !== false) {
            if ($updateStmt->execute()) {
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
                    $mail->setFrom('bernestominimart@gmail.com', 'plvclearance');
                    $mail->addAddress($email);

                    // Content
                    $mail->isHTML(true);
                    $mail->Subject = 'Account Verification Code';
                    $mail->Body    = 'Your verification code is: ' . $verificationCode;

                    // Send email
                    if ($mail->send()) {
                        // Set success message
                        $response = ['status' => 'success', 'message' => 'Verification code sent successfully'];
                    } else {
                        // Set error message for mail sending failure
                        $response = ['status' => 'error', 'message' => 'Message could not be sent. Please try again later'];
                    }
                } catch (Exception $e) {
                    // Handle errors
                    $response = ['status' => 'error', 'message' => 'Message could not be sent. Please try again later'];
                }
            } else {
                // Set error message for update statement failure
                $response = ['status' => 'error', 'message' => 'Failed to update verification code'];
            }
        } else {
            // Set error message for update statement preparation failure
            $response = ['status' => 'error', 'message' => 'Update statement preparation failed'];
        }
    } else {
        // Insert query failed
        $response = ['status' => 'error', 'message' => 'User insertion and sending verification code failed'];
    }
}

// Close the prepared statements
$userStmt->close();

// Check if $insertStmt is not null before calling close()
if ($insertStmt !== null) {
    $insertStmt->close();
}

// Check if $updateStmt is not null before calling close()
if ($updateStmt !== null) {
    $updateStmt->close();
}

// Close the database connection
$conn->close();

// Send the JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>