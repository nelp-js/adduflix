<?php
$conn = new mysqli('localhost', 'root', '', 'adduflix');
session_start();

$content_id = $_GET['content_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

// Handle review submission
if (isset($_POST['submit'])) {
    $rating = $_POST['rating'];
    $comment = $_POST['comment'];
    $conn->query("INSERT INTO Reviews (user_id, content_id, rating, comment) VALUES ('$user_id', '$content_id', '$rating', '$comment')");
    header("Location: mainpage.php?review=success");
    exit();
}

// Handle back action
if (isset($_POST['back'])) {
    header("Location: mainpage.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Review Content</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <h2>Review Content</h2>
            <form method="post">
                <label>Rating (1-5)</label>
                <input type="number" name="rating" min="1" max="5" class="form-control mb-3" placeholder="Leave empty if no review">
                
                <label>Comment</label>
                <textarea name="comment" class="form-control mb-3" placeholder="Optional comment..."></textarea>
                
                <div class="d-flex gap-2">
                    <button type="submit" name="submit" class="btn btn-success w-100">Submit Review</button>
                    <button type="submit" name="back" class="btn btn-secondary w-100">Back to Main Page</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
