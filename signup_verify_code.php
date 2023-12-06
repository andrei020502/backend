<?php
require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

$email = $_POST["email"];
$verificationCode = $_POST["verificationCode"];

// Query to check if the email and verification code match in the companion_users table
$userSql = "SELECT * FROM companion_users WHERE email = '$email' AND code = $verificationCode";
$userResult = $conn->query($userSql);

// Check if the combination of email and verification code is valid
if ($userResult->num_rows > 0) {
    // Valid combination, update the user status or perform any necessary action

    // Reset the code column to 0
    $resetSql = "UPDATE companion_users SET code = 0, verified = 1 WHERE email = '$email'";
    if ($conn->query($resetSql) === TRUE) {
        $response = ['status' => 'success', 'message' => 'Verification successful. You can now login'];
    } else {
        $response = ['status' => 'error', 'message' => 'Error resetting code column'];
    }
} else {
    // Invalid combination
    $response = ['status' => 'error', 'message' => 'Verification code does not match'];
}

// Close the database connection
$conn->close();

// Send the JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
