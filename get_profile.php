<?php
require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Get the user_id from the POST request
$user_id = isset($_POST['user_id']) ? $_POST['user_id'] : null;

try {
    if ($user_id !== null) {
        // Use prepared statement to fetch user details from the companion_users table
        $query = "SELECT * FROM companion_users WHERE user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result) {
            // Check if any rows were returned
            if ($result->num_rows > 0) {
                // Convert the result into an associative array
                $userProfile = $result->fetch_assoc();

                // Return the user profile as JSON with success status
                echo json_encode(['status' => 'success', 'data' => $userProfile]);
            } else {
                // No rows found for the specified user_id
                echo json_encode(['status' => 'error', 'message' => 'User not found']);
            }
        } else {
            // Handle the database query error
            throw new Exception("Failed to fetch user profile");
        }
    } else {
        // Handle the case where user_id is not set
        throw new Exception("user_id not provided");
    }
} catch (Exception $e) {
    // Handle other exceptions
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
