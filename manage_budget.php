<?php
require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Assuming you have user_id and budget data sent via POST
$user_id = $_POST['user_id'];
$new_budget = $_POST['budget'];

// Check if the new budget is within the allowed range
if ($new_budget > 10000) {
    $response = ['status' => 'error', 'message' => 'Budget exceeds the allowed limit of 10,000.'];
} else {
    // Retrieve the sum of prices from the "cart" table
    $stmt_sum_prices = $conn->prepare("SELECT SUM(price) as total_price FROM cart WHERE user_id = ?");
    $stmt_sum_prices->bind_param("i", $user_id);
    $stmt_sum_prices->execute();
    $stmt_sum_prices->bind_result($total_price);
    $stmt_sum_prices->fetch();
    $stmt_sum_prices->close();

    // Compare the sum of prices with the new budget
    if ($total_price > $new_budget) {
        $response = ['status' => 'error', 'message' => 'The total price on the basket exceeds the new budget.'];
    } else {
        // Check if user has an existing entry in the budget table
        $stmt_check = $conn->prepare("SELECT COUNT(*) as count FROM budget WHERE user_id = ?");
        $stmt_check->bind_param("i", $user_id);
        $stmt_check->execute();
        $stmt_check->bind_result($count);
        $stmt_check->fetch();
        $stmt_check->close();

        if ($count > 0) {
            // Update the existing entry
            $stmt_update = $conn->prepare("UPDATE budget SET budget = ? WHERE user_id = ?");
            $stmt_update->bind_param("di", $new_budget, $user_id);
            $stmt_update->execute();
            $stmt_update->close();
            $response = ['status' => 'success', 'message' => 'Budget updated successfully.'];
        } else {
            // Insert a new entry
            $stmt_insert = $conn->prepare("INSERT INTO budget (user_id, budget) VALUES (?, ?)");
            $stmt_insert->bind_param("id", $user_id, $new_budget);
            $stmt_insert->execute();
            $stmt_insert->close();
            $response = ['status' => 'success', 'message' => 'Budget inserted successfully.'];
        }
    }
}

// Return JSON response
header('Content-Type: application/json');
echo json_encode($response);
?>
