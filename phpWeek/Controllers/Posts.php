<?php
// For debugging
// For debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../Models/dbConnection.php');

// Create a database connection
$dbConnection = createDBConnection();

// Start a session
session_start();

// Check if the user is logged in before allowing actions

// Start a session

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    echo "User is logged in with user ID: " . $_SESSION['user_id'];
} else {
    echo "User is not logged in.";
}



// GET METHOD
// based on user id
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // GET request to retrieve posts by user ID
    if (isset($_GET['user_id'])) {
        $user_id = $_GET['user_id'];

        // Ensure that $_SESSION['user_id'] matches the provided user_id
        if ($_SESSION['user_id'] == $user_id) {
            // Prepare and execute the SQL statement to retrieve posts for the user
            $sql = "SELECT posts.id AS post_id, posts.title, posts.content, users.username 
                    FROM posts 
                    INNER JOIN users ON posts.user_id = users.id
                    WHERE users.id = ?";

            $stmt = $dbConnection->prepare($sql);
            $stmt->bind_param("i", $user_id);

            if ($stmt->execute()) {
                $result = $stmt->get_result();
                $posts = array();

                while ($row = $result->fetch_assoc()) {
                    $posts[] = $row;
                }

                // Output the posts as JSON
                header('Content-Type: application/json');
                echo json_encode($posts);
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "You are not authorized to access this user's data.";
        }
    } else {
        echo "Missing 'user_id' parameter in the URL.";
    }
}

    // POST METHOD
// based on user id
elseif($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        // Get the user ID from the session
        $user_id = $_SESSION['user_id'];

        // Get the raw JSON data from the request body
        $data = json_decode(file_get_contents("php://input"));

        // Check if the JSON data contains the expected fields
        if (isset($data->title, $data->content)) {
            $title = $data->title;
            $content = $data->content;

            // Prepare and execute the SQL statement to insert a new post
            $sql = "INSERT INTO posts (user_id, title, content) VALUES (?, ?, ?)";

            $stmt = $dbConnection->prepare($sql);
            $stmt->bind_param("iss", $user_id, $title, $content);

            if ($stmt->execute()) {
                echo "Post added successfully.";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();
        } else {
            echo "Missing required parameters in the request body.";
        }
    } else {
        echo "You must be logged in to create a post.";
    }
}

// PUT METHOD
// based on post id
elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        // Get the user ID from the session
        $user_id = $_SESSION['user_id'];

        // Get the raw JSON data from the request body
        $data = json_decode(file_get_contents("php://input"));

        // Check if the JSON data contains the expected fields
        if (isset($_GET['post_id'], $data->title, $data->content)) {
            $post_id = $_GET['post_id'];
            $title = $data->title;
            $content = $data->content;

            // Ensure that the post belongs to the logged-in user
            $sql_check = "SELECT user_id FROM posts WHERE id = ?";
            $stmt_check = $dbConnection->prepare($sql_check);
            $stmt_check->bind_param("i", $post_id);

            if ($stmt_check->execute()) {
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    $stmt_check->bind_result($post_user_id);
                    $stmt_check->fetch();

                    if ($post_user_id == $user_id) {
                        // Update the post
                        $sql_update = "UPDATE posts SET title = ?, content = ? WHERE id = ?";
                        $stmt_update = $dbConnection->prepare($sql_update);
                        $stmt_update->bind_param("ssi", $title, $content, $post_id);

                        if ($stmt_update->execute()) {
                            echo "Post updated successfully.";
                        } else {
                            echo "Error: " . $stmt_update->error;
                        }

                        $stmt_update->close();
                    } else {
                        echo "You are not authorized to update this post.";
                    }
                } else {
                    echo "Post not found.";
                }
            } else {
                echo "Error: " . $stmt_check->error;
            }

            $stmt_check->close();
        } else {
            echo "Missing required parameters in the request body or 'post_id' in the URL.";
        }
    } else {
        echo "You must be logged in to update a post.";
    }
}


elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        // Get the user ID from the session
        $user_id = $_SESSION['user_id'];

        // Check if 'post_id' is set in the URL
        if (isset($_GET['post_id'])) {
            $post_id = $_GET['post_id'];

            // Ensure that the post belongs to the logged-in user
            $sql_check = "SELECT user_id FROM posts WHERE id = ?";
            $stmt_check = $dbConnection->prepare($sql_check);
            $stmt_check->bind_param("i", $post_id);

            if ($stmt_check->execute()) {
                $stmt_check->store_result();

                if ($stmt_check->num_rows > 0) {
                    $stmt_check->bind_result($post_user_id);
                    $stmt_check->fetch();

                    if ($post_user_id == $user_id) {
                        // Delete the post
                        $sql_delete = "DELETE FROM posts WHERE id = ?";
                        $stmt_delete = $dbConnection->prepare($sql_delete);
                        $stmt_delete->bind_param("i", $post_id);

                        if ($stmt_delete->execute()) {
                            echo "Post deleted successfully.";
                        } else {
                            echo "Error: " . $stmt_delete->error;
                        }

                        $stmt_delete->close();
                    } else {
                        echo "You are not authorized to delete this post.";
                    }
                } else {
                    echo "Post not found.";
                }
            } else {
                echo "Error: " . $stmt_check->error;
            }

            $stmt_check->close();
        } else {
            echo "Missing 'post_id' parameter in the URL.";
        }
    } else {
        echo "You must be logged in to delete a post.";
    }
}
// Close the database connection
$dbConnection->close();
?>
