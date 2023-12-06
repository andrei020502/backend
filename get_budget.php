<?php

require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Get user_id from POST request
$user_id = $_POST['user_id'];

// Query to get the budget for the given user_id
$query = "SELECT budget FROM budget WHERE user_id = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    // Bind the parameters
    $stmt->bind_param('i', $user_id);

    // Execute the query
    $stmt->execute();

    // Bind the result
    $stmt->bind_result($budget);

    // Fetch the result
    $stmt->fetch();

    // Close the statement
    $stmt->close();

    // Return the budget value
    echo json_encode(['budget' => $budget]);
} else {
    // Error in preparing the statement
    echo json_encode(['error' => true, 'message' => 'Error in preparing statement.']);
}

?>
