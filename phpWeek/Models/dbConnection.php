<?php
function createDBConnection() {
    $servername = "localhost";
    $username = "new_root";
    $password = "0000";
    $dbname = "blogs";

    // Create a new MySQLi connection
    $connection = new mysqli($servername, $username, $password, $dbname);

    // Check for connection errors
    if ($connection->connect_error) {
        die("Connection Failed: " . $connection->connect_error);
    }

    // Return the connection object
//    echo higirls;
//    var_dump("hello");
    return $connection ;
}
//$dbConnection = createDBConnection();
?>
