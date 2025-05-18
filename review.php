<?php
session_start();

$conn = new mysqli('localhost', 'root', '', 'adduflix');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: start.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$content_id = null;
$content_title = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['content_id']) && !isset($_POST['submit_review'])) {
        $_SESSION['content_id'] = $_POST['content_id'];
        $content_id = $_POST['content_id'];
        $stmt = $conn->prepare("INSERT INTO ViewingHistory (user_id, content_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $user_id, $content_id);
        if (!$stmt->execute()) {
            $error = "Failed to record viewing history.";
        }

        $title_stmt = $conn->prepare("SELECT title FROM Content WHERE id = ?");
        $title_stmt->bind_param("i", $content_id);
        $title_stmt->execute();
        $title_result = $title_stmt->get_result();
        if ($title_result->num_rows > 0) {
            $content_title = $title_result->fetch_assoc()['title'];
        }
    }

    if (isset($_POST['submit_review'])) {
        $rating = $_POST['rating'] ?? null;
        $comment = trim($_POST['comment'] ?? '');
        $content_id = $_SESSION['content_id'] ?? null;

        if (empty($rating)) {
            $error = "Please select a rating.";
        } elseif (!is_numeric($rating) || $rating < 1 || $rating > 5) {
            $error = "Invalid rating value.";
        } elseif (strlen($comment) > 500) {
            $error = "Comment must be less than 500 characters.";
        } else {
            $check_stmt = $conn->prepare("SELECT id FROM Reviews WHERE user_id = ? AND content_id = ?");
            $check_stmt->bind_param("ii", $user_id, $content_id);
            $check_stmt->execute();
            if ($check_stmt->get_result()->num_rows > 0) {
                $error = "You've already reviewed this content.";
            } else {
                $stmt = $conn->prepare("INSERT INTO Reviews (user_id, content_id, rating, comment) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiis", $user_id, $content_id, $rating, $comment);
                if ($stmt->execute()) {
                    $success = "Thank you for your review!";
                    unset($_SESSION['content_id']);
                    header("Refresh: 2; URL=mainpage.php");
                } else {
                    $error = "Failed to submit review. Please try again.";
                }
            }
        }
    }

    if (isset($_POST['back'])) {
        unset($_SESSION['content_id']);
        header("Location: mainpage.php");
        exit();
    }
}

if (empty($content_title) && isset($_SESSION['content_id'])) {
    $content_id = $_SESSION['content_id'];
    $title_stmt = $conn->prepare("SELECT title FROM Content WHERE id = ?");
    $title_stmt->bind_param("i", $content_id);
    $title_stmt->execute();
    $title_result = $title_stmt->get_result();
    if ($title_result->num_rows > 0) {
        $content_title = $title_result->fetch_assoc()['title'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Review - AdduFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #004aad;
            --dark-color: #141414;
            --light-color: #f8f9fa;
        }

        body {
        background: url('https://raw.githubusercontent.com/Unonkgw/my-images/refs/heads/main/AAAAQavW2NsPoRMpRHtA9QrkRartIDbya5GDWj9uAjmtlkC7PSIMKoQ5QJ3k8SnlnKScjniyV7H0Owxjd7-CVxX3BCawy4K-8b0z_h8sEqbi4Rh1nMGhqVWa1RLbUXlW3SzGLnruqO1sjjiw5oeLqri7MtL3HDU.jpg') no-repeat center center fixed;
        background-size: cover;
        color: var(--light-color);
        height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        }

        .review-container {
            max-width: 600px;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }

        .form-control, .form-select {
            background-color: #333;
            border: 1px solid #444;
            color: white;
        }

        .form-control:focus, .form-select:focus {
            background-color: #444;
            color: white;
            border-color: #555;
            box-shadow: 0 0 0 0.25rem rgba(9, 115, 229, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .btn-primary:hover {
            background-color: #003d8b;
            border-color: #003d8b;
        }

        .rating-option {
            font-size: 1.5rem;
            cursor: pointer;
            transition: transform 0.2s;
        }

        .rating-option:hover {
            transform: scale(1.2);
        }

        .rating-option.selected {
            color: gold;
            transform: scale(1.3);
        }

        .character-count {
            font-size: 0.8rem;
            text-align: right;
            color: #8c8c8c;
        }
    </style>
</head>
<body>
<div class="review-container">
    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo htmlspecialchars($success); ?>
            <div class="spinner-border spinner-border-sm ms-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
    <?php else: ?>
        <h2 class="mb-4">
            <?php echo $content_title ? 'Review: ' . htmlspecialchars($content_title) : 'Leave a Review'; ?>
        </h2>
        <form method="post" id="reviewForm">
            <input type="hidden" name="content_id" value="<?php echo htmlspecialchars($content_id ?? ''); ?>">

            <div class="mb-4">
                <label class="form-label mb-2">Rating</label>
                <div class="d-flex justify-content-between">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <div class="rating-option" data-value="<?php echo $i; ?>">
                            <i class="bi bi-star<?php echo ($i <= ($_POST['rating'] ?? 0)) ? '-fill' : ''; ?>"></i>
                        </div>
                    <?php endfor; ?>
                </div>
                <input type="hidden" name="rating" id="ratingInput" value="<?php echo htmlspecialchars($_POST['rating'] ?? ''); ?>">
            </div>

            <div class="mb-4">
                <label for="comment" class="form-label">Comment (optional)</label>
                <textarea name="comment" id="comment" class="form-control" rows="4" maxlength="500"><?php echo htmlspecialchars($_POST['comment'] ?? ''); ?></textarea>
                <div class="character-count">
                    <span id="charCount">0</span>/500 characters
                </div>
            </div>

            <div class="d-flex flex-column flex-md-row justify-content-between mt-4 gap-3">
                <button type="submit" name="back" class="btn btn-secondary px-4">
                    <i class="bi bi-arrow-left"></i> Back
                </button>
                <button type="submit" name="submit_review" class="btn btn-primary px-4">
                    <i class="bi bi-check-circle"></i> Submit Review
                </button>
            </div>
        </form>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const ratingOptions = document.querySelectorAll('.rating-option');
        const ratingInput = document.getElementById('ratingInput');
        ratingOptions.forEach(option => {
            option.addEventListener('click', function () {
                const value = this.getAttribute('data-value');
                ratingInput.value = value;
                ratingOptions.forEach((opt, idx) => {
                    const icon = opt.querySelector('i');
                    if (idx < value) {
                        icon.className = 'bi bi-star-fill';
                        opt.classList.add('selected');
                    } else {
                        icon.className = 'bi bi-star';
                        opt.classList.remove('selected');
                    }
                });
            });
        });

        const commentField = document.getElementById('comment');
        const charCount = document.getElementById('charCount');
        commentField.addEventListener('input', function () {
            charCount.textContent = this.value.length;
        });
        charCount.textContent = commentField.value.length;

        document.getElementById('reviewForm').addEventListener('submit', function (e) {
            if (!ratingInput.value) {
                e.preventDefault();
                alert('Please select a rating before submitting.');
            }
        });
    });
</script>
</body>
</html>
