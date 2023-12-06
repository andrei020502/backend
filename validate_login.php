<?php

require 'connection.php';

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}
// Assuming you are using POST method to receive data
$email = isset($_POST['email']) ? $_POST['email'] : null;
$password = isset($_POST['password']) ? $_POST['password'] : null;

try {
    // Check for empty input values
    if (empty($email) || empty($password)) {
        throw new Exception('Please provide both email and password');
    }

    // Use prepared statement to perform database check for user authentication
    $query = "SELECT * FROM companion_users WHERE email = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        // User found, check the password
        $user = $result->fetch_assoc();
        $storedHashedPassword = $user['password'];

        // Use password_verify to check if the provided password matches the hashed one
        if (password_verify($password, $storedHashedPassword)) {
            // Authentication successful
            $user_id = $user['user_id'];

            $response = array(
                'status' => 'success',
                'message' => 'Login successful',
                'user_id' => $user_id,
            );
        } else {
            // Authentication failed
            $response = array(
                'status' => 'error',
                'message' => 'Invalid credentials',
            );
        }
    } else {
        // User not found
        $response = array(
            'status' => 'error',
            'message' => 'Invalid credentials',
        );
    }

    echo json_encode($response);
} catch (Exception $e) {
    // Handle other exceptions
    $response = array(
        'status' => 'error',
        'message' => $e->getMessage(),
    );

    echo json_encode($response);
}
?>
