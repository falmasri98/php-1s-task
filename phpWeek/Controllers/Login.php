<?php
// For debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../Models/dbConnection.php');

// Create a database connection
$dbConnection = createDBConnection();

// Start a session
session_start();

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Get the raw JSON data from the request body
    $data = json_decode(file_get_contents("php://input"));

    // Check if the JSON data contains the expected fields
    if (isset($data->email, $data->password)) {
        $email = $data->email;
        $password = $data->password;

        // Authenticate the user and obtain the user_id
        $user_id = authenticateUser($email, $password, $dbConnection);

        if ($user_id !== false) {
            // Authentication successful, set the user ID in the session
            $_SESSION['user_id'] = $user_id;
            echo "Login successfully with user ID: " . $user_id;
        } else {
            echo "Login failed. Invalid email or password.";
        }
    } else {
        echo "Invalid JSON data. Ensure you provide 'email' and 'password' in the JSON request body.";
    }
}

// Close the database connection
$dbConnection->close();

//  authentication logic
function authenticateUser($email, $password, $dbConnection) {

    $stmt = $dbConnection->prepare("SELECT id FROM users WHERE email = ? AND password = ?");
    $stmt->bind_param("ss", $email, $password);

    if ($stmt->execute()) {
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $user_id = "";
            $stmt->bind_result($user_id);
            $stmt->fetch();
            return $user_id;
        }
    }

    return false; // Authentication failed
}
?>
