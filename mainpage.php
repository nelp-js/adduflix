<?php
session_start();

// Database connection with error handling
$conn = new mysqli('localhost', 'root', '', 'adduflix');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Redirect if not logged in
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

// Fetch active content with additional details
$content_query = "SELECT c.*, 
                 (SELECT COUNT(*) FROM ViewingHistory WHERE content_id = c.id) AS view_count,
                 (SELECT AVG(rating) FROM Reviews WHERE content_id = c.id) AS avg_rating
                 FROM Content c 
                 WHERE status = 'active' 
                 ORDER BY view_count DESC";
$content_result = $conn->query($content_query);

// Fetch user's viewing history for "Continue Watching" section
$history_query = "SELECT c.*, vh.watch_time 
                 FROM ViewingHistory vh
                 JOIN Content c ON vh.content_id = c.id
                 WHERE vh.user_id = $user_id
                 ORDER BY vh.watch_time DESC
                 LIMIT 8";
$history_result = $conn->query($history_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>AdduFlix - Home</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #004aad;
            --dark-color: #141414;
            --light-color: #f5f5f5;
        }
        
        body {
            background-color: var(--dark-color);
            color: var(--light-color);
        }
        
        .navbar {
            background-color: var(--dark-color) !important;
            border-bottom: 1px solid #333;
        }
        
        .navbar-brand {
            color: var(--primary-color) !important;
            font-weight: bold;
            font-size: 1.8rem;
        }
        

        .content-card {
            transition: all 0.3s ease;
            background-color: #222;
            border: none;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            cursor: pointer;
        }
        
        .content-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        
        .content-card img {
            width: 100%;
            height: 480px;
            object-fit: cover;
        }

        .content-card2 {
            transition: all 0.3s ease;
            background-color: #222;
            border: none;
            border-radius: 8px;
            overflow: hidden;
            height: 100%;
            cursor: pointer;
        }

        .content-card2:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.3);
        }
        
        .content-card2 img {
            width: 100%;
            height: 480px;
            object-fit: cover;
        }
        
        .card-body {
            padding: 1rem;
        }

        
        .badge-rating {
            background-color: gold;
            color: #000;
        }
        
        .section-title {
            border-left: 4px solid var(--primary-color);
            padding-left: 10px;
            margin: 2rem 0 1rem;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            object-fit: cover;
            margin-right: 10px;
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-danger {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-secondary {
            color:rgb(243, 247, 251);
            border-color: rgb(64, 139, 238);
        }
        
        .btn-outline-secondary:hover {
            background-color: rgb(64, 139, 238, 0.1);
        }
        
        .watch-btn {
            display: none;
            margin-top: 10px;
            width: 100%;
        }

        .watch-btn2 {
            display: none;
            margin-top: 10px;
            width: 100%;
        }
        
        .content-card.expanded {
            transform: scale(1.03);
            box-shadow: 0 0 15px rgba(229, 9, 20, 0.5);
        }
        
        .content-card.expanded .watch-btn {
            display: block;
            animation: fadeIn 0.3s ease;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark mb-4">
  <div class="container-fluid">
    <a class="navbar-brand" href="mainpage.php">
        <div class="text-center">
            <img src="https://raw.githubusercontent.com/Unonkgw/my-images/c8e3733c786597bbdc94c9e0b434572bb27044ae/adduflixlogo.svg" alt="AdduFlix Logo" class="logo">
        </div>
    </a>
            
    
    <div class="d-flex align-items-center">
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" 
               id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <span><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
            </a>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="userinfo.php"><i class="bi bi-person"></i> Profile</a></li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="post">
                        <button type="submit" name="logout" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right"></i> Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>
    </div>
  </div>
</nav>

<div class="container">
    
<div class="container">  
    <h3 class="section-title">Continue Watching</h3>

    <div class="row">
        <?php while ($history_row = $history_result->fetch_assoc()): ?>
            <div class="col-md-4 col-lg-3 mb-4">
                <div class="content-card" onclick="expandBox(this)">
                    <img src="<?php echo htmlspecialchars($history_row['image_url']); ?>" 
                        alt="<?php echo htmlspecialchars($history_row['title']); ?>" class="img-fluid">
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($history_row['title']); ?></h5>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($history_row['genre']); ?></span>
                            
                            <form action="remove_continue.php" method="post" onsubmit="return confirm('Remove this from Continue Watching?');" style="margin-left: 10px; margin-right: 10px;">
                                <input type="hidden" name="content_id" value="<?php echo $history_row['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Remove</button>
                            </form>

                            <form action="review.php" method="post" class="btn-outline-secondary">
                                <input type="hidden" name="content_id" value="<?php echo $history_row['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-outline-secondary">
                                    <i class="bi bi-play-fill"></i> Continue
                                </button>
                            </form>

                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div> 
</div> 



        


    <!-- Popular Content Section -->
    <div class="mb-5">
        <h3 class="section-title">Popular on AdduFlix</h3>
        <div class="row g-4">
            <?php while ($row = $content_result->fetch_assoc()): ?>
            <div class="col-md-4 col-lg-3">
                <div class="content-card" onclick="expandBox(this)">
                    <img src="<?php echo htmlspecialchars($row['image_url']); ?>" 
                         alt="<?php echo htmlspecialchars($row['title']); ?>"
                         class="poster-img">
                    <div class="card-body">
                        <h5><?php echo htmlspecialchars($row['title']); ?></h5>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span class="badge bg-secondary"><?php echo htmlspecialchars($row['genre']); ?></span>
                            <?php if ($row['avg_rating']): ?>
                            <span class="badge badge-rating">
                                <i class="bi bi-star-fill"></i> <?php echo number_format($row['avg_rating'], 1); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted"><?php echo $row['view_count']; ?> views</small>
                            <form action="review.php" method="post" class="watch-btn">
                                <input type="hidden" name="content_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-play-fill"></i> Watch
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>


<!-- Bootstrap JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function expandBox(card) {
        // Collapse all other cards
        document.querySelectorAll('.content-card').forEach(box => {
            if (box !== card) {
                box.classList.remove('expanded');
            }
        });
        
        // Toggle clicked card
        card.classList.toggle('expanded');
    }
    
    // Close expanded card when clicking anywhere else
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.content-card')) {
            document.querySelectorAll('.content-card').forEach(card => {
                card.classList.remove('expanded');
            });
        }
    });
</script>

</body>
</html>