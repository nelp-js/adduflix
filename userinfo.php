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

$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';
$is_editing = false;

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle Delete (now soft delete)
if (isset($_POST['delete'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        try {
            // Start transaction for atomic operations
            $conn->begin_transaction();
            
            // Deactivate all active subscriptions
            if (!$conn->query("UPDATE Subscriptions SET is_active = FALSE WHERE user_id = $user_id AND is_active = TRUE")) {
                throw new Exception("Failed to deactivate subscriptions: " . $conn->error);
            }
            
            // Soft delete the user by setting deleted_at timestamp
            $stmt = $conn->prepare("UPDATE Users SET deleted_at = NOW() WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to soft delete user: " . $conn->error);
            }
            
            $conn->commit();
            
            // Clear session and redirect
            session_unset();
            session_destroy();
            header("Location: start.php");
            exit();
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Account deactivation failed. Please try again later.";
            error_log("User soft deletion error: " . $e->getMessage());
        }
    }
}

// Handle Update
if (isset($_POST['update'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        $new_name = trim($_POST['name']);
        $new_email = trim($_POST['email']);
        $new_password = $_POST['password'];

        if (empty($new_name) || empty($new_email)) {
            $error = "Name and Email cannot be empty.";
            $is_editing = true;
        } elseif (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
            $is_editing = true;
        } else {
            // Check if email is already taken by another active user
            $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ? AND id != ? AND deleted_at IS NULL");
            $stmt->bind_param("si", $new_email, $user_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email already in use by another account.";
                $is_editing = true;
            } else {
                $update_sql = "UPDATE Users SET name = ?, email = ?" . 
                              (!empty($new_password) ? ", password_hash = ?" : "") . 
                              " WHERE id = ? AND deleted_at IS NULL";
                $stmt = $conn->prepare($update_sql);

                if (!empty($new_password)) {
                    if (strlen($new_password) < 8) {
                        $error = "Password must be at least 8 characters.";
                        $is_editing = true;
                    } else {
                        $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt->bind_param("sssi", $new_name, $new_email, $password_hash, $user_id);
                    }
                } else {
                    $stmt->bind_param("ssi", $new_name, $new_email, $user_id);
                }

                if (empty($error) && $stmt->execute()) {
                    $success = "Profile updated successfully!";
                    $_SESSION['user_name'] = $new_name; // Update session name
                } else if (empty($error)) {
                    $error = "Error updating profile: " . $stmt->error;
                }
            }
        }
    }
}

// Handle edit mode toggle
if (isset($_POST['edit_mode'])) {
    $is_editing = true;
} elseif (isset($_POST['cancel'])) {
    $is_editing = false;
}

// Fetch current user info (only if not soft-deleted)
$stmt = $conn->prepare("SELECT name, email FROM Users WHERE id = ? AND deleted_at IS NULL");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    // User not found or has been soft-deleted
    session_unset();
    session_destroy();
    header("Location: start.php");
    exit();
}

$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - AdduFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e50914;
            --dark-color: #141414;
            --light-color: #f8f9fa;
            --gray-color: #6c757d;
        }
        
        body {
            background-color: var(--dark-color);
            color: var(--light-color);
            min-height: 100vh;
        }
        
        .profile-container {
            max-width: 800px;
            background-color: rgba(0, 0, 0, 0.75);
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            overflow: hidden;
        }
        
        .profile-header {
            background-color: var(--primary-color);
            padding: 2rem;
            color: white;
        }
        
        .profile-body {
            padding: 2rem;
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
            box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-primary:hover {
            background-color: #f6121d;
            border-color: #f6121d;
        }
        
        .btn-outline-danger {
            color: var(--primary-color);
            border-color: var(--primary-color);
        }
        
        .btn-outline-danger:hover {
            background-color: var(--primary-color);
            color: white;
        }
        
        .info-item {
            padding: 1rem;
            background-color: rgba(255, 255, 255, 0.05);
            border-radius: 5px;
            margin-bottom: 1rem;
        }
        
        .info-label {
            color: var(--gray-color);
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
        }
        
        .info-value {
            font-size: 1.1rem;
        }
        
        .password-strength {
            height: 4px;
            background-color: #333;
            border-radius: 2px;
            margin-top: 5px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            color: var(--gray-color);
            margin-top: 0.5rem;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 0.3rem;
        }
        
        .requirement i {
            margin-right: 5px;
            font-size: 0.7rem;
        }
        
        .requirement.valid {
            color: #28a745;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <div class="profile-container">
        <div class="profile-header">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="bi bi-person-circle"></i> My Profile</h2>
                <a href="mainpage.php" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left"></i> Back to Home
                </a>
            </div>
        </div>
        
        <div class="profile-body">
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?php echo htmlspecialchars($success); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (!$is_editing): ?>
                <div class="mb-4">
                    <div class="info-item">
                        <div class="info-label">Name</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['name']); ?></div>
                    </div>
                    
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?php echo htmlspecialchars($user['email']); ?></div>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" name="edit_mode" class="btn btn-primary">
                            <i class="bi bi-pencil"></i> Edit Profile
                        </button>
                    </form>
                    
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                        <button type="submit" name="delete" class="btn btn-outline-danger"
                            onclick="return confirm('This will deactivate your account. You can contact support to recover it within 30 days. Continue?')">
                            <i class="bi bi-trash"></i> Deactivate Account
                        </button>
                    </form>
                </div>

            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="mb-4">
                        <h4 class="mb-4"><i class="bi bi-pencil-square"></i> Edit Profile</h4>
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" 
                                   value="<?php echo htmlspecialchars($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email" 
                                   value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">New Password</label>
                            <input type="password" class="form-control" id="password" name="password" 
                                   placeholder="Leave blank to keep current password">
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                            <div class="password-requirements">
                                <div class="requirement" id="lengthReq">
                                    <i class="bi bi-circle"></i>
                                    <span>At least 8 characters</span>
                                </div>
                                <div class="requirement" id="complexityReq">
                                    <i class="bi bi-circle"></i>
                                    <span>Contains letters and numbers</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div class="d-flex gap-2">
                            <button type="submit" name="update" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i> Save Changes
                            </button>
                            <button type="submit" name="cancel" class="btn btn-secondary">
                                <i class="bi bi-x-circle"></i> Cancel
                            </button>
                        </div>
                        
                        <button type="submit" name="delete" class="btn btn-outline-danger"
                            onclick="return confirm('This will deactivate your account. You can contact support to recover it within 30 days. Continue?')">
                            <i class="bi bi-trash"></i> Deactivate Account
                        </button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Password strength indicator
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        
        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                let strength = 0;
                
                // Length check
                if (password.length >= 8) {
                    strength += 50;
                    document.getElementById('lengthReq').classList.add('valid');
                    document.getElementById('lengthReq').querySelector('i').className = 'bi bi-check-circle';
                } else {
                    document.getElementById('lengthReq').classList.remove('valid');
                    document.getElementById('lengthReq').querySelector('i').className = 'bi bi-circle';
                }
                
                // Complexity check
                if (/[a-zA-Z]/.test(password) && /\d/.test(password)) {
                    strength += 50;
                    document.getElementById('complexityReq').classList.add('valid');
                    document.getElementById('complexityReq').querySelector('i').className = 'bi bi-check-circle';
                } else {
                    document.getElementById('complexityReq').classList.remove('valid');
                    document.getElementById('complexityReq').querySelector('i').className = 'bi bi-circle';
                }
                
                // Update strength bar
                strengthBar.style.width = strength + '%';
                strengthBar.style.backgroundColor = strength < 50 ? '#dc3545' : 
                                                  strength < 80 ? '#ffc107' : '#28a745';
            });
        }
    });
</script>
</body>
</html>