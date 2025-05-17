<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'adduflix');

if (!isset($_SESSION['user_id'])) {
    header("Location: start.php");
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: start.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch content
$content_result = $conn->query("SELECT * FROM Content WHERE status = 'active'");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Main Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .content-box {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .content-box.expanded {
            transform: scale(1.05);
            background-color: #f8f9fa;
        }

        .watch-btn {
            display: none;
        }

        .content-box.expanded .watch-btn {
            display: block;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="#">Main Page</a>
    <div class="d-flex align-items-center">
        <a href="userinfo.php" class="btn btn-outline-secondary me-2">
            <i class="bi bi-person"></i>
        </a>
        <form method="post" class="d-inline">
            <button type="submit" name="logout" class="btn btn-outline-danger">Logout</button>
        </form>
    </div>
  </div>
</nav>

<div class="container">
    <div class="row g-4">
        <?php while ($row = $content_result->fetch_assoc()): ?>
            <div class="col-md-4">
                <div class="card content-box p-3" onclick="expandBox(this)">
                    <h5 class="card-title"><?php echo htmlspecialchars($row['title']); ?></h5>
                    <p class="card-text"><?php echo htmlspecialchars($row['genre']); ?></p>
                    <form action="review.php" method="post" class="watch-btn">
                        <input type="hidden" name="content_id" value="<?php echo $row['id']; ?>">
                        <button type="submit" name="watch" class="btn btn-primary">Watch Now</button>
                    </form>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script>
    function expandBox(card) {
        // Collapse all others
        document.querySelectorAll('.content-box').forEach(box => {
            box.classList.remove('expanded');
        });

        // Expand clicked box
        card.classList.add('expanded');
    }
</script>

</body>
</html>
