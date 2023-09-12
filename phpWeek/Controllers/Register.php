<?php
// For debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the database connection script
require_once('../Models/dbConnection.php');

// Initialize variables
$username = "";
$email = "";
$password = "";

// Create a database connection
$dbConnection = createDBConnection();

// Start a session
session_start();

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the raw JSON data from the request body
    $data = json_decode(file_get_contents("php://input"));

    // Check if the JSON data contains the expected fields
    if (isset($data->username, $data->email, $data->password)) {
        $username = $data->username;
        $email = $data->email;
        $password = $data->password;

        // Hash the password using password_hash()
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $dbConnection->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");

        if ($stmt) {
            $stmt->bind_param("sss", $username, $email, $hashedPassword);

            if ($stmt->execute()) {
                // Registration successful, set a session variable to indicate the user is logged in
                $_SESSION['user_logged_in'] = true;
                echo "Registration successful.";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Error in preparing the SQL statement: " . $dbConnection->error;
        }
    } else {
        echo "Invalid JSON data. Ensure you provide 'username', 'email', and 'password' in the JSON request body.";
    }
}

// Close the database connection
$dbConnection->close();
?>
