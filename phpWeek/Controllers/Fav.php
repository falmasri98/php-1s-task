<?php
require_once('../Models/dbConnection.php');

// Start or resume the session
session_start();

$dbConnection = createDBConnection();

// Add to favorites
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id']; // Get user ID from the session

        $data = json_decode(file_get_contents("php://input"));

        if ($data && isset($data->post_id)) {
            $post_id = $data->post_id;

            // Check if the post is not already in favorites
            $existingFavoriteSql = "SELECT id FROM favorites WHERE user_id = ? AND post_id = ?";
            $existingFavoriteStmt = $dbConnection->prepare($existingFavoriteSql);
            $existingFavoriteStmt->bind_param("ii", $user_id, $post_id);
            $existingFavoriteStmt->execute();
            $existingFavoriteResult = $existingFavoriteStmt->get_result();

            if ($existingFavoriteResult->num_rows == 0) {
                $insertFavoriteSql = "INSERT INTO favorites (user_id, post_id) VALUES (?, ?)";
                $insertFavoriteStmt = $dbConnection->prepare($insertFavoriteSql);
                $insertFavoriteStmt->bind_param("ii", $user_id, $post_id);

                if ($insertFavoriteStmt->execute()) {
                    echo "Post added to favorites successfully.";
                } else {
                    echo "Error: " . $insertFavoriteStmt->error;
                }

                $insertFavoriteStmt->close();
            } else {
                echo "The post is already in your favorites.";
            }

            $existingFavoriteStmt->close();
        } else {
            echo "Invalid or missing parameters in the request body.";
        }
    } else {
        echo "You must be logged in to add to favorites.";
    }
}

// Remove from favorites
elseif ($_SERVER["REQUEST_METHOD"] === "PUT") {
    // Check if the user is logged in
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id']; // Get user ID from the session

        $data = json_decode(file_get_contents("php://input"));

        if ($data && isset($data->post_id)) {
            $post_id = $data->post_id;

            $softDeleteSql = "UPDATE favorites SET deleted = 1 WHERE user_id = ? AND post_id = ?";
            $softDeleteStmt = $dbConnection->prepare($softDeleteSql);
            $softDeleteStmt->bind_param("ii", $user_id, $post_id);

            if ($softDeleteStmt->execute()) {
                echo "Post removed from favorites successfully.";
            } else {
                echo "Error: " . $softDeleteStmt->error;
            }

            $softDeleteStmt->close();
        } else {
            echo "Invalid or missing parameters in the request body.";
        }
    } else {
        echo "You must be logged in to remove from favorites.";
    }
}

// Get all favorite posts
elseif ($_SERVER["REQUEST_METHOD"] === "GET") {
    // Get Favorite Posts for a Specific User
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id']; // Get user ID from the session

        $sql = "SELECT posts.id AS post_id, posts.title, posts.content
                FROM favorites
                INNER JOIN posts ON favorites.post_id = posts.id
                WHERE favorites.user_id = ? AND favorites.deleted = 0";

        $stmt = $dbConnection->prepare($sql);
        $stmt->bind_param("i", $user_id);

        if ($stmt->execute()) {
            $result = $stmt->get_result();
            $favoritePosts = array();

            while ($row = $result->fetch_assoc()) {
                $favoritePosts[] = $row;
            }

            echo json_encode($favoritePosts);
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
    } else {
        echo "You must be logged in to view favorite posts.";
    }
}

$dbConnection->close();
?>
