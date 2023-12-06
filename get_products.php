<?php
require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

try {
    // Use prepared statement to fetch product details from the tbl_items table
    $query = "SELECT id, product_code, product_name, group_name, price, total_price, TO_BASE64(image) as image, stocks, unit_of_measure
    FROM tbl_items 
    WHERE (id, product_name, group_name, unit_of_measure) IN (
        SELECT MAX(id) as id, product_name, group_name, unit_of_measure
        FROM tbl_items
        GROUP BY product_name, group_name, unit_of_measure
    )
    ORDER BY group_name, id;
    ";

    $stmt = $conn->prepare($query);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result) {
        // Check if any rows were returned
        if ($result->num_rows > 0) {
            // Fetch all rows into an associative array
            $products = $result->fetch_all(MYSQLI_ASSOC);

            // Return the products as JSON with success status
            echo json_encode(['status' => 'success', 'data' => $products]);
        } else {
            // No rows found for the specified query
            echo json_encode(['status' => 'error', 'message' => 'Products not found']);
        }
    } else {
        // Handle the database query error
        throw new Exception("Failed to fetch products");
    }
} catch (Exception $e) {
    // Handle other exceptions
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
