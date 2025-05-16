<?php
$conn = new mysqli('localhost', 'root', '', 'adduflix');
session_start();

$user_id = $_SESSION['user_id'] ?? null;

$result = $conn->query("SELECT * FROM Content WHERE status='active'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Main Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .content-card {
            cursor: pointer;
            transition: all 0.3s ease-in-out;
        }
        .expanded {
            transform: scale(1.05);
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            background-color: #f8f9fa;
        }
        .get-started {
            display: none;
        }
        .expanded .get-started {
            display: block;
        }
        .navbar-nav .nav-item .nav-link.user-icon {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .navbar-nav .nav-item .nav-link.user-icon svg {
            width: 24px;
            height: 24px;
        }
    </style>
</head>
<body class="bg-light">

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm">
  <div class="container">
    <a class="navbar-brand" href="mainpage.php">StreamApp</a>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item">
          <a class="nav-link user-icon" href="userinfo.php" title="User Info">
            <!-- Simple User Icon SVG -->
            <svg xmlns="http://www.w3.org/2000/svg" fill="currentColor" class="bi bi-person-circle" viewBox="0 0 16 16">
              <path d="M13.468 12.37C12.758 11.226 11.555 10.5 10 10.5c-1.555 0-2.758.726-3.468 1.87A6.987 6.987 0 0 1 8 15a6.987 6.987 0 0 1 5.468-2.63z"/>
              <path fill-rule="evenodd" d="M8 9a3 3 0 1 0 0-6 3 3 0 0 0 0 6zM8 1a7 7 0 1 0 0 14A7 7 0 0 0 8 1z"/>
            </svg>
            User
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<div class="container mt-4">
    <!-- Review Success Alert -->
    <?php if (isset($_GET['review']) && $_GET['review'] == 'success'): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            Review submitted successfully!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <h2>Main Page - Choose Content</h2>
    <div class="row">
        <?php while ($row = $result->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card content-card" onclick="expandCard(this)">
                    <img src="https://via.placeholder.com/300x200?text=<?php echo urlencode($row['title']); ?>" class="card-img-top">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo $row['title']; ?></h5>
                        <p class="card-text"><?php echo $row['genre']; ?></p>
                        <div class="get-started">
                            <p><?php echo $row['description']; ?></p>
                            <a href="view.php?content_id=<?php echo $row['id']; ?>" class="btn btn-primary">Watch Now</a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function expandCard(card) {
        document.querySelectorAll('.content-card').forEach(function(c) {
            c.classList.remove('expanded');
        });
        card.classList.add('expanded');
    }
</script>

</body>
</html>
