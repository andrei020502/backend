<?php
require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Handle actual removal of item from the cart table
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assuming you receive the cartId from the client-side
    $cartId = $_POST['cartId'];


    // Check the connection
    if ($conn->connect_error) {
        $response = ['status' => 'error', 'message' => 'Database connection failed.'];
    } else {
        // Prepare and execute the DELETE query
        $query = "DELETE FROM cart WHERE cart_id = ?";
        $statement = $conn->prepare($query);
        $statement->bind_param('i', $cartId); // 'i' represents integer

        if ($statement->execute()) {
            $response = ['status' => 'success'];
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to remove item from the cart.'];
        }

        // Close the statement and the database connection
        $statement->close();
        $conn->close();
    }

    // Return the response as JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}

// If the request method is not OPTIONS or POST, respond with an error
header("HTTP/1.1 405 Method Not Allowed");
exit();
?>
