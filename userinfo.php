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

// Handle Delete
if (isset($_POST['delete'])) {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        $conn->begin_transaction();
        try {
            $tables = ['Payments', 'Subscriptions', 'ViewingHistory', 'Reviews'];
            foreach ($tables as $table) {
                if (!$conn->query("DELETE FROM $table WHERE user_id = $user_id")) {
                    throw new Exception("Failed to delete from $table: " . $conn->error);
                }
            }
            if (!$conn->query("DELETE FROM Users WHERE id = $user_id")) {
                throw new Exception("Failed to delete user: " . $conn->error);
            }
            $conn->commit();
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
}

// Handle Update
if (isset($_POST['update'])) {
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
            $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ? AND id != ?");
            $stmt->bind_param("si", $new_email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error = "Email already in use by another account.";
                $is_editing = true;
            } else {
                $update_sql = "UPDATE Users SET name = ?, email = ?" . 
                              (!empty($new_password) ? ", password_hash = ?" : "") . 
                              " WHERE id = ?";
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
                    $_SESSION['user_name'] = $new_name;
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

// Fetch current user info
$stmt = $conn->prepare("SELECT name, email FROM Users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>My Profile - AdduFlix</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet" />
<style>
    :root {
        --primary-color: #004aad; 
        --dark-color:rgb(55, 52, 52);
        --light-color: #f8f9fa;
        --gray-color: #6c757d;
    }
    body, html {
        height: 100%;
        margin: 0;
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background: url('https://raw.githubusercontent.com/Unonkgw/my-images/refs/heads/main/AAAAQavW2NsPoRMpRHtA9QrkRartIDbya5GDWj9uAjmtlkC7PSIMKoQ5QJ3k8SnlnKScjniyV7H0Owxjd7-CVxX3BCawy4K-8b0z_h8sEqbi4Rh1nMGhqVWa1RLbUXlW3SzGLnruqO1sjjiw5oeLqri7MtL3HDU.jpg') no-repeat center center fixed;
        background-size: cover;
        display: flex;
        justify-content: center;
        align-items: center;
        color: var(--light-color);
        min-height: 100vh;
    }
    .profile-container {
    background-color: rgba(27, 25, 25, 0.8);
    padding: 30px 40px; 
    border-radius: 12px;
    width: 620px; 
    max-width: 95vw;
    box-shadow: 0 0 20px #004aad;
    position: relative; 
    }

    /* Container header to hold the back button aligned right */
    .profile-header {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 15px;
    }

    .info-item {
        background-color:rgb(0, 0, 0);
        padding: 1rem;
        border-radius: 6px;
        margin-bottom: 1rem;
        text-align: left;
    }
    .info-label {
        font-weight: 600;
        color: var(--gray-color);
        margin-bottom: 0.3rem;
        font-size: 0.85rem;
    }
    .info-value {
        font-size: 1.1rem;
        word-break: break-word;
    }
    .form-control, .form-select {
        background-color: #000000;
        border: none;
        border-radius: 5px;
        color: white;
        padding: 0.5rem 0.75rem;
    }
    .form-control:focus, .form-select:focus {
        background-color: #000000;
        color: white;
        outline: none;
        box-shadow: 0 0 5px var(--primary-color);
        border: none;
    }

    /* Style the back button */
    .back-button {
        background-color: #004aad;
        border: none;
        color: white;
        padding: 6px 14px;
        font-weight: 600;
        border-radius: 6px;
        cursor: pointer;
        text-decoration: none;
        font-size: 0.9rem;
        transition: background-color 0.3s ease;
    }

    .back-button:hover,
    .back-button:focus {
        background-color: #004aad;
        outline: none;
    }
    form[aria-label="Profile action buttons"] {
    display: flex;
    justify-content: center;
    gap: 15px;
    margin-top: 1rem;
    }
    .btn-primary {
        background-color: var(--primary-color);
        border: none;
        font-weight: 600;
    }
    .btn-primary:hover {
        background-color: #004aad;
    }
    .btn-outline-danger {
        border-color: var(--primary-color);
        color: var(--primary-color);
    }
    .btn-outline-danger:hover {
        background-color: var(--primary-color);
        color: white;
    }
    .d-flex.gap-2 {
        justify-content: center;
    }
    .password-strength {
        height: 6px;
        background-color: #444;
        border-radius: 3px;
        margin-top: 6px;
        overflow: hidden;
    }
    .password-strength-bar {
        height: 100%;
        width: 0;
        transition: width 0.3s ease;
        background: var(--primary-color);
    }
    .password-requirements {
        font-size: 0.8rem;
        color: #ffffff;
        margin-top: 0.5rem;
        text-align: left;
    }
    .requirement {
        display: flex;
        align-items: center;
        margin-bottom: 0.3rem;
    }
    .requirement i {
        margin-right: 6px;
        font-size: 0.85rem;
    }
    .requirement.valid {
        color: #28a745;
    }
</style>
</head>
<body>
<div class="profile-container" role="main" aria-label="User Profile Container">
    <a href="mainpage.php" class="back-button" aria-label="Back to Home">Back to Home</a>
    <div class="profile-header" tabindex="0">
    <h2><i class="bi bi-person-circle"></i> My Profile</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger" role="alert" tabindex="0"><?=htmlspecialchars($error)?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success" role="alert" tabindex="0"><?=htmlspecialchars($success)?></div>
<?php endif; ?>

<?php if ($is_editing): ?>
    <form method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>" />
        <div class="mb-3 text-start">
            <label for="name" class="form-label">Name</label>
            <input 
                type="text" id="name" name="name" class="form-control" 
                value="<?=htmlspecialchars($_POST['name'] ?? $user['name'])?>" required 
                aria-required="true"
            />
        </div>
        <div class="mb-3 text-start">
            <label for="email" class="form-label">Email</label>
            <input 
                type="email" id="email" name="email" class="form-control" 
                value="<?=htmlspecialchars($_POST['email'] ?? $user['email'])?>" required 
                aria-required="true"
            />
        </div>
        <div class="mb-1 text-start">
            <label for="password" class="form-label">New Password <small>(leave blank to keep current)</small></label>
            <input 
                type="password" id="password" name="password" class="form-control" 
                aria-describedby="passwordHelp"
                autocomplete="new-password"
            />
            <div id="passwordHelp" class="password-requirements" aria-live="polite" aria-atomic="true">
                <div class="requirement" id="lengthReq"><i class="bi bi-x-circle"></i> Minimum 8 characters</div>
                <div class="requirement" id="upperReq"><i class="bi bi-x-circle"></i> At least one uppercase letter</div>
                <div class="requirement" id="lowerReq"><i class="bi bi-x-circle"></i> At least one lowercase letter</div>
                <div class="requirement" id="numberReq"><i class="bi bi-x-circle"></i> At least one number</div>
            </div>
        </div>

        <div class="d-flex gap-2 justify-content-center mt-4">
            <button type="submit" name="update" class="btn btn-primary">Save</button>
            <button type="submit" name="cancel" class="btn btn-primary">Cancel</button>
        </div>
    </form>
<?php else: ?>
    <div class="info-item" tabindex="0">
        <div class="info-label">Name</div>
        <div class="info-value"><?=htmlspecialchars($user['name'])?></div>
    </div>
    <div class="info-item" tabindex="0">
        <div class="info-label">Email</div>
        <div class="info-value"><?=htmlspecialchars($user['email'])?></div>
    </div>

        <form method="POST" class="mt-3" aria-label="Profile action buttons">
    <input type="hidden" name="csrf_token" value="<?=htmlspecialchars($_SESSION['csrf_token'])?>" />
    <button type="submit" name="edit_mode" class="btn btn-primary me-2" aria-label="Edit profile">
        <i class="bi bi-pencil-square"></i> Edit Profile
    </button>
    <button 
        type="submit" name="delete" class="btn btn-primary me-2"
        onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.');"
        aria-label="Delete account"
    >
        <i class="bi bi-trash"></i> Delete Account
    </button>
</form>

<?php endif; ?>
