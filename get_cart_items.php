<?php

require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Validate and sanitize user input
$user_id = filter_input(INPUT_GET, 'user_id', FILTER_SANITIZE_NUMBER_INT);

if (!$user_id) {
    echo json_encode(array("status" => "error", "message" => "Invalid user ID."));
    exit();
}

// Fetch cart items for the specified user, including additional details from tbl_items
$sql = "SELECT cart.*, tbl_items.product_name, TO_BASE64(tbl_items.image) as image, cart.check as monitored 
        FROM cart
        INNER JOIN tbl_items ON cart.product_id = tbl_items.id
        WHERE cart.user_id = $user_id";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // Output data as JSON
    $cart_items = array();
    while ($row = $result->fetch_assoc()) {
        // Convert the image to base64
        $cart_items[] = $row;
    }

    echo json_encode(array("status" => "success", "data" => $cart_items));
} else {
    echo json_encode(array("status" => "success", "data" => array()));
}

$conn->close();
?>
