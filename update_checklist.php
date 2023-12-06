<?php

require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Ensure the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data from the request body
    $user_id = $_POST['user_id'];
    $cart_id = $_POST['cart_id'];
    $check_value = $_POST['monitored'];

    // Update the check column in the cart table
    $sql = "UPDATE cart SET `check` = ? WHERE user_id = ? AND cart_id = ?";

    $stmt = mysqli_prepare($conn, $sql);

    // Bind parameters
    mysqli_stmt_bind_param($stmt, 'iis', $check_value, $user_id, $cart_id);

    // Execute the query
    if (mysqli_stmt_execute($stmt)) {
        // Return success response
        $response = ['status' => 'success'];
        echo json_encode($response);
    } else {
        // Return error response
        $response = ['status' => 'error', 'message' => 'Failed to update checklist'];
        echo json_encode($response);
    }

    // Close statement
    mysqli_stmt_close($stmt);

    // Close connection
    mysqli_close($conn);
} else {
    // Return error response for unsupported HTTP method
    $response = ['status' => 'error', 'message' => 'Unsupported HTTP method'];
    echo json_encode($response);
}

?>
