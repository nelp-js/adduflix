<?php
$conn = new mysqli('localhost', 'root', '', 'adduflix');
session_start();
$content_id = $_GET['content_id'];
$user_id = $_SESSION['user_id'];
$conn->query("INSERT INTO ViewingHistory (user_id, content_id) VALUES ('$user_id', '$content_id')");
header("Location: review.php?content_id=$content_id");
?>
