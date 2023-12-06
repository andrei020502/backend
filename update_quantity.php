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
    $quantity = $_POST['quantity'];

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

    // Fetch the product codes and prices of all items in the cart for the given user_id
    $get_cart_items_sql = "SELECT i.product_code, i.price FROM cart c JOIN tbl_items i ON c.product_id = i.id WHERE c.user_id = ?";
    $get_cart_items_stmt = $conn->prepare($get_cart_items_sql);
    $get_cart_items_stmt->bind_param("i", $user_id);
    $get_cart_items_stmt->execute();
    $get_cart_items_result = $get_cart_items_stmt->get_result();

    $total_cart_price = 0;

    while ($cart_item = $get_cart_items_result->fetch_assoc()) {
        $item_price = $cart_item['price'];
        $total_cart_price += $item_price;
    }


    $get_cart_items_stmt->close();

    // Fetch the product code from the cart
    $get_product_code_sql = "SELECT product_code FROM cart c JOIN tbl_items i ON c.product_id = i.id WHERE c.user_id = ? AND c.cart_id = ?";
    $get_product_code_stmt = $conn->prepare($get_product_code_sql);
    $get_product_code_stmt->bind_param("ii", $user_id, $cart_id);
    $get_product_code_stmt->execute();
    $get_product_code_result = $get_product_code_stmt->get_result();

    if ($get_product_code_result->num_rows > 0) {
        $get_product_code_row = $get_product_code_result->fetch_assoc();
        $product_code = $get_product_code_row['product_code'];

        // Determine the price column based on the product code
        $price_column = strpos($product_code, 'SALE') !== false ? 'price' : 'total_price';

        // Fetch the actual price value from the corresponding column
        $get_price_sql = "SELECT $price_column FROM tbl_items WHERE id = (SELECT product_id FROM cart WHERE user_id = ? AND cart_id = ?)";
        $get_price_stmt = $conn->prepare($get_price_sql);
        $get_price_stmt->bind_param("ii", $user_id, $cart_id);
        $get_price_stmt->execute();
        $get_price_result = $get_price_stmt->get_result();

        if ($get_price_result->num_rows > 0) {
            $get_price_row = $get_price_result->fetch_assoc();
            $price = $get_price_row[$price_column];

            // Calculate the new total price based on the updated quantity
            $new_total_price = $quantity * $price;


            // Check if the new total price, along with the existing cart price, exceeds the budget
            if ($budget > 0 && ($total_cart_price + $new_total_price) > $budget) {
                // Budget exceeded, return an error
                $response = [
                    'status' => 'error',
                    'message' => 'Updating the quantity exceeds the budget limit',
                ];
                header('Content-Type: application/json');
                echo json_encode($response);
                exit(); // Exit the script to prevent further execution
            }

            // Update the quantity and total price in the cart table
            $update_quantity_sql = "UPDATE cart SET quantity = ?, price = ? WHERE user_id = ? AND cart_id = ?";
            $update_quantity_stmt = $conn->prepare($update_quantity_sql);
            $update_quantity_stmt->bind_param("idis", $quantity, $new_total_price, $user_id, $cart_id);

            // Execute the query
            if ($update_quantity_stmt->execute()) {
                // Return success response
                $response = ['status' => 'success'];
                header('Content-Type: application/json');
                echo json_encode($response);
            } else {
                // Return error response
                $response = ['status' => 'error', 'message' => 'Failed to update quantity'];
                header('Content-Type: application/json');
                echo json_encode($response);
            }

            // Close statements for updating quantity and fetching price
            $update_quantity_stmt->close();
            $get_price_stmt->close();
        } else {
            // Return error response if price retrieval fails
            $response = ['status' => 'error', 'message' => 'Failed to retrieve price'];
            header('Content-Type: application/json');
            echo json_encode($response);
        }
    } else {
        // Return error response if product code retrieval fails
        $response = ['status' => 'error', 'message' => 'Failed to retrieve product code'];
        header('Content-Type: application/json');
        echo json_encode($response);
    }

    // Close statement for retrieving product code
    $get_product_code_stmt->close();
    $conn->close();
} else {
    // Return error response for unsupported HTTP method
    $response = ['status' => 'error', 'message' => 'Unsupported HTTP method'];
    header('Content-Type: application/json');
    echo json_encode($response);
}
?>
