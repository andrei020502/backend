<?php
require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

$email = $_POST["email"];
$newPassword = $_POST["newPassword"];
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// Query to check if the email and verification code match in the companion_users table
$userSql = "SELECT * FROM companion_users WHERE email = '$email'";
$userResult = $conn->query($userSql);

// Check if the combination of email and verification code is valid
if ($userResult->num_rows > 0) {
    // Valid combination, update the user status or perform any necessary action

    // Reset the code column to 0
    $resetSql = "UPDATE companion_users SET password = '$hashedPassword' WHERE email = '$email'";
    if ($conn->query($resetSql) === TRUE) {
        $response = ['status' => 'success', 'message' => 'Password changed successfully'];
    } else {
        $response = ['status' => 'error', 'message' => 'Error resetting password'];
    }
} else {
    // Invalid combination
    $response = ['status' => 'error', 'message' => 'User does not exist'];
}

// Close the database connection
$conn->close();

// Send the JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
