<?php
require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Ensure that the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve user_id and image_data from the POST request
    $user_id = $_POST['user_id'];
    $image_data = $_FILES['image_data']['tmp_name']; // Use the 'tmp_name' property

    // Ensure that the user_id is not empty
    if (empty($user_id)) {
        $response = ['status' => 'error', 'message' => 'User ID is required'];
        echo json_encode($response);
        exit;
    }

    // Ensure that the image_data is not empty
    if (empty($image_data)) {
        $response = ['status' => 'error', 'message' => 'Image data is required'];
        echo json_encode($response);
        exit;
    }

    // Process and save the image to the database
    $image_content = file_get_contents($image_data);
    $encoded_image = base64_encode($image_content);

    // Use appropriate database connection and query to update the image in the companion_users table
    $sql = "UPDATE companion_users SET picture = ? WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('ss', $encoded_image, $user_id);
    
    if ($stmt->execute()) {
        $response = ['status' => 'success', 'message' => 'Image updated successfully'];
        echo json_encode($response);
    } else {
        $response = ['status' => 'error', 'message' => 'Failed to update image'];
        echo json_encode($response);
    }

    $stmt->close();
    $conn->close();
} else {
    $response = ['status' => 'error', 'message' => 'Invalid request method'];
    echo json_encode($response);
}
?>
