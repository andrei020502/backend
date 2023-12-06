<?php
require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Assuming the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve values from the Angular application
    $product_id = $_POST['product_id'];
    $user_id = $_POST['user_id'];
    $quantity = $_POST['quantity'];
    $price = $_POST['price'];  

    // Fetch the budget for the given user_id
    $budget_stmt = $conn->prepare("SELECT budget FROM budget WHERE user_id = ?");
    $budget_stmt->bind_param("i", $user_id);
    $budget_stmt->execute();
    $budget_result = $budget_stmt->get_result();

    if ($budget_result->num_rows > 0) {
        // User has a budget entry
        $budget_row = $budget_result->fetch_assoc();
        $budget = $budget_row['budget'];
    } else {
        // User has no budget entry, consider it as 0
        $budget = 0;
    }
    
    $budget_stmt->close();

    // If the budget is greater than 0, perform budget check
    if ($budget > 0) {
        // Calculate the total of existing prices in the cart for the same user_id
        $total_stmt = $conn->prepare("SELECT SUM(price) AS total_price FROM cart WHERE user_id = ?");
        $total_stmt->bind_param("i", $user_id);
        $total_stmt->execute();
        $total_result = $total_stmt->get_result();
        $total_row = $total_result->fetch_assoc();
        $total_price = $total_row['total_price'];
        $total_stmt->close();

        // Check if the sum of existing prices and the new price will exceed the budget
        if (($total_price + $price) > $budget) {
            // Budget exceeded, return an error
            $response = [
                'status' => 'error',
                'message' => 'Adding the item exceeds the budget limit',
            ];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit(); // Exit the script to prevent further execution
        }
    }

    // If the budget is not exceeded, proceed to add the item to the cart
    $stmt = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("iiid", $user_id, $product_id, $quantity, $price);
    $stmt->execute();

    // Check if the insertion was successful
    if ($stmt->affected_rows > 0) {
        // Prepare an array containing all the data
        $addedData = [
            'product_id' => $product_id,
            'user_id' => $user_id,
            'quantity' => $quantity,
            'price' => $price,
        ];

        // Send a response back to the Angular application, including all the data in the success message
        $response = [
            'status' => 'success',
            'message' => 'Item added to basket successfully',
            'data' => $addedData,
        ];
    } else {
        // If the insertion failed
        $response = [
            'status' => 'error',
            'message' => 'Failed to add item to basket',
        ];
    }

    // Close the statement and connection
    $stmt->close();
    $conn->close();

    // Send the JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
} else {
    // Handle invalid requests
    http_response_code(400);  // Bad Request
    echo 'Invalid request method.';
}

?>
