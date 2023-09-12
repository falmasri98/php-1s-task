<?php
// comments.php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once('../Models/dbConnection.php');

// Start or resume the session
session_start();

// Create a database connection
$dbConnection = createDBConnection();

if ($_SERVER["REQUEST_METHOD"] === "GET") {
    // GET request to retrieve comments by post ID
    if (isset($_GET['post_id'])) {
        $post_id = $_GET['post_id'];

        // ... (rest of your code for retrieving comments)
        $sql = "SELECT 
                    comments.id AS comment_id, 
                    comments.comment, 
                    users.username AS commenter,
                    posts.title AS post_title,
                    post_author.username AS post_author,  -- Updated alias for post author username
                    posts.content AS post_content
                FROM comments
                INNER JOIN users ON comments.user_id = users.id
                INNER JOIN posts ON comments.post_id = posts.id
                INNER JOIN users AS post_author ON posts.user_id = post_author.id  -- Alias for post author
                WHERE comments.post_id = ?";

        $stmt = $dbConnection->prepare($sql);
        $stmt->bind_param("i", $post_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $comments = array();

            while ($row = $result->fetch_assoc()) {
                $comments[] = $row;
            }

            echo json_encode($comments);
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();


    }
    else {
        echo "Missing 'post_id' parameter in the URL.";
    }
} elseif ($_SERVER["REQUEST_METHOD"] === "POST") {
    // POST request to add a new comment
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->user_id, $data->post_id, $data->comment)) {
        // Check if the user is logged in
        if (isset($_SESSION['user_id'])) {
            $user_id = $_SESSION['user_id'];
            $post_id = $data->post_id;
            $comment = $data->comment;

            // Prepare and execute the SQL statement to insert a new comment
            $sql = "INSERT INTO comments (user_id, post_id, comment) VALUES (?, ?, ?)";

            $stmt = $dbConnection->prepare($sql);
            $stmt->bind_param("iis", $user_id, $post_id, $comment);

            if ($stmt->execute()) {
                echo "Comment added successfully.";
            } else {
                echo "Error: " . $stmt->error;
            }

            $stmt->close();

        } else {
            echo "You must be logged in to add a comment.";
        }
    } else {
        echo "Missing required parameters in the request body.";
    }
}


elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    // PUT request to update a comment by comment ID
    $data = json_decode(file_get_contents("php://input"));

    if (isset($_GET['comment_id'], $data->comment)) {
        // Check if the user is logged in
        if (isset($_SESSION['user_id'])) {
            $comment_id = $_GET['comment_id'];
            $comment = $data->comment;
            $user_id = $_SESSION['user_id']; // Get the user's ID from the session

            // Check if the logged-in user owns the comment
            $sql_check_comment_ownership = "SELECT user_id FROM comments WHERE id = ?";
            $stmt_check_comment_ownership = $dbConnection->prepare($sql_check_comment_ownership);
            $stmt_check_comment_ownership->bind_param("i", $comment_id);

            if ($stmt_check_comment_ownership->execute()) {
                $stmt_check_comment_ownership->store_result();

                if ($stmt_check_comment_ownership->num_rows > 0) {
                    $stmt_check_comment_ownership->bind_result($comment_user_id);
                    $stmt_check_comment_ownership->fetch();

                    if ($comment_user_id == $user_id) {
                        // Update the comment
                        $sql_update_comment = "UPDATE comments SET comment = ? WHERE id = ?";
                        $stmt_update_comment = $dbConnection->prepare($sql_update_comment);
                        $stmt_update_comment->bind_param("si", $comment, $comment_id);

                        if ($stmt_update_comment->execute()) {
                            echo "Comment updated successfully.";
                        } else {
                            echo "Error updating comment: " . $stmt_update_comment->error;
                        }

                        $stmt_update_comment->close();
                    } else {
                        echo "You are not authorized to update this comment.";
                    }
                } else {
                    echo "Comment not found.";
                }
            } else {
                echo "Error checking comment ownership: " . $stmt_check_comment_ownership->error;
            }

            $stmt_check_comment_ownership->close();

        } else {
            echo "You must be logged in to update a comment.";
        }
    } else {
        echo "Missing required parameters in the request body or 'comment_id' in the URL.";
    }
}

elseif ($_SERVER["REQUEST_METHOD"] === "DELETE") {
    // DELETE request to delete a comment by comment ID
    if (isset($_GET['comment_id'])) {
        // Check if the user is logged in
        if (isset($_SESSION['user_id'])) {
            $comment_id = $_GET['comment_id'];
            $user_id = $_SESSION['user_id']; // Get the user's ID from the session

            // Check if the logged-in user owns the comment
            $sql_check_comment_ownership = "SELECT user_id FROM comments WHERE id = ?";
            $stmt_check_comment_ownership = $dbConnection->prepare($sql_check_comment_ownership);
            $stmt_check_comment_ownership->bind_param("i", $comment_id);

            if ($stmt_check_comment_ownership->execute()) {
                $stmt_check_comment_ownership->store_result();

                if ($stmt_check_comment_ownership->num_rows > 0) {
                    $stmt_check_comment_ownership->bind_result($comment_user_id);
                    $stmt_check_comment_ownership->fetch();

                    if ($comment_user_id == $user_id) {
                        // Delete the comment
                        $sql_delete_comment = "DELETE FROM comments WHERE id = ?";
                        $stmt_delete_comment = $dbConnection->prepare($sql_delete_comment);
                        $stmt_delete_comment->bind_param("i", $comment_id);

                        if ($stmt_delete_comment->execute()) {
                            echo "Comment deleted successfully.";
                        } else {
                            echo "Error deleting comment: " . $stmt_delete_comment->error;
                        }

                        $stmt_delete_comment->close();
                    } else {
                        echo "You are not authorized to delete this comment.";
                    }
                } else {
                    echo "Comment not found.";
                }
            } else {
                echo "Error checking comment ownership: " . $stmt_check_comment_ownership->error;
            }

            $stmt_check_comment_ownership->close();

        } else {
            echo "You must be logged in to delete a comment.";
        }
    } else {
        echo "Missing 'comment_id' parameter in the URL.";
    }
}

// Close the database connection
$dbConnection->close();
?>
