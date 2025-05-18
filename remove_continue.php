<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: start.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content_id'])) {
    $user_id = $_SESSION['user_id'];
    $content_id = intval($_POST['content_id']);

    $conn = new mysqli('localhost', 'root', '', 'adduflix');
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $stmt = $conn->prepare("DELETE FROM ViewingHistory WHERE user_id = ? AND content_id = ?");
    $stmt->bind_param("ii", $user_id, $content_id);

    if ($stmt->execute()) {
        // Redirect back to mainpage after removal
        header("Location: mainpage.php");
        exit();
    } else {
        echo "Failed to remove from Continue Watching.";
    }
} else {
    header("Location: mainpage.php");
    exit();
}
?>
