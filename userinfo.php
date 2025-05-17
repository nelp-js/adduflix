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

$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';
$is_editing = false;

// Handle Delete - now with proper foreign key constraint handling
if (isset($_POST['delete'])) {
    // Start transaction for atomic operations
    $conn->begin_transaction();
    
    try {
        // 1. First delete payments (has FK to both users and subscriptions)
        if (!$conn->query("DELETE FROM Payments WHERE user_id = $user_id")) {
            throw new Exception("Failed to delete payments: " . $conn->error);
        }
        
        // 2. Then delete subscriptions (has FK to users)
        if (!$conn->query("DELETE FROM Subscriptions WHERE user_id = $user_id")) {
            throw new Exception("Failed to delete subscriptions: " . $conn->error);
        }
        
        // 3. Delete viewing history
        if (!$conn->query("DELETE FROM ViewingHistory WHERE user_id = $user_id")) {
            throw new Exception("Failed to delete viewing history: " . $conn->error);
        }
        
        // 4. Delete reviews
        if (!$conn->query("DELETE FROM Reviews WHERE user_id = $user_id")) {
            throw new Exception("Failed to delete reviews: " . $conn->error);
        }
        
        // 5. Finally delete the user
        if (!$conn->query("DELETE FROM Users WHERE id = $user_id")) {
            throw new Exception("Failed to delete user: " . $conn->error);
        }
        
        // If we got here, all deletions succeeded
        $conn->commit();
        
        // Clear session and redirect
        session_unset();
        session_destroy();
        header("Location: start.php");
        exit();
        
    } catch (Exception $e) {
        $conn->rollback();
        $error = "Account deletion failed. Please try again later.";
        error_log("User deletion error: " . $e->getMessage());
    }
}

// Handle Update
if (isset($_POST['update'])) {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password'];

    if (empty($new_name) || empty($new_email)) {
        $error = "Name and Email cannot be empty.";
        $is_editing = true;
    } else {
        $update_sql = "UPDATE Users SET name = ?, email = ?" . (!empty($new_password) ? ", password_hash = ?" : "") . " WHERE id = ?";
        $stmt = $conn->prepare($update_sql);

        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt->bind_param("sssi", $new_name, $new_email, $password_hash, $user_id);
        } else {
            $stmt->bind_param("ssi", $new_name, $new_email, $user_id);
        }

        if ($stmt->execute()) {
            $success = "User updated successfully.";
        } else {
            $error = "Error updating user: " . $stmt->error;
        }
    }
}

// Handle edit mode toggle
if (isset($_POST['edit_mode'])) {
    $is_editing = true;
} elseif (isset($_POST['cancel'])) {
    $is_editing = false;
}

// Back button
if (isset($_POST['back'])) {
    header("Location: mainpage.php");
    exit();
}

// Fetch current user info
$sql = "SELECT name, email FROM Users WHERE id = $user_id";
$result = $conn->query($sql);
if (!$result) {
    die("Error fetching user info: " . $conn->error);
}
$user = $result->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .delete-btn { transition: all 0.3s; }
        .delete-btn:hover { transform: scale(1.05); }
    </style>
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 600px;">
    <div class="card shadow">
        <div class="card-header bg-primary text-white">
            <h2 class="mb-0">User Profile</h2>
        </div>
        
        <div class="card-body">
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>

            <?php if (!$is_editing): ?>
                <div class="mb-4">
                    <h4>Account Information</h4>
                    <div class="mb-3">
                        <label class="form-label text-muted">Name</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['name']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-muted">Email</label>
                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <form method="post" class="me-2">
                        <button type="submit" name="edit_mode" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </button>
                    </form>
                    
                    <form method="post">
                        <button type="submit" name="back" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </button>
                    </form>
                    
                    <form method="post" class="ms-auto">
                        <button type="submit" name="delete" class="btn btn-danger delete-btn"
                            onclick="return confirm('WARNING: This will permanently delete your account and all associated data. Continue?')">
                            <i class="bi bi-trash"></i> Delete Account
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <h4>Edit Profile</h4>
                <form method="post">
                    <div class="mb-3">
                        <label for="nameInput" class="form-label">Name</label>
                        <input id="nameInput" type="text" name="name" class="form-control" 
                            value="<?php echo htmlspecialchars($user['name']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="emailInput" class="form-label">Email</label>
                        <input id="emailInput" type="email" name="email" class="form-control" 
                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="passwordInput" class="form-label">New Password</label>
                        <input id="passwordInput" type="password" name="password" class="form-control" 
                            placeholder="Leave blank to keep current password">
                        <div class="form-text">Minimum 8 characters</div>
                    </div>

                    <div class="d-flex justify-content-between mt-4">
                        <div>
                            <button type="submit" name="update" class="btn btn-success me-2">
                                <i class="bi bi-check"></i> Save Changes
                            </button>
                            <button type="submit" name="cancel" class="btn btn-warning">
                                <i class="bi bi-x"></i> Cancel
                            </button>
                        </div>
                        <button type="submit" name="delete" class="btn btn-outline-danger"
                            onclick="return confirm('WARNING: This will permanently delete your account. Continue?')">
                            <i class="bi bi-trash"></i> Delete Account
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</body>
</html>