<?php
// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

$hostname = "localhost";
$username = "root";
$password = "";
$database = "companion_db";

try {
    // Create a connection to the database
    $conn = new mysqli($hostname, $username, $password, $database);

    // Throw an exception if the connection has an error
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
} catch (Exception $e) {
    die($e->getMessage());
}

?>
