<?php
require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

$user_id = $_GET['user_id'];
$product_id = $_GET['product_id'];

// Perform a query to check if the product is in the cart for the user
// Modify this query based on your database structure
$query = "SELECT COUNT(*) as count FROM cart WHERE user_id = $user_id AND product_id = $product_id";
$result = mysqli_query($conn, $query);

if ($result) {
  $row = mysqli_fetch_assoc($result);
  $isInCart = ($row['count'] > 0);
  echo json_encode(['isInCart' => $isInCart]);
} else {
  echo json_encode(['isInCart' => false]);
}

// Close the database connection if necessary
mysqli_close($conn);
?>
