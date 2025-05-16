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

// Handle Delete
if (isset($_POST['delete'])) {
    $conn->query("DELETE FROM Users WHERE id = $user_id");
    $_SESSION = [];
    session_destroy();
    header("Location: start.php");
    exit();
}

// Handle Update
if (isset($_POST['update'])) {
    $new_name = trim($_POST['name']);
    $new_email = trim($_POST['email']);
    $new_password = $_POST['password'];

    if (empty($new_name) || empty($new_email)) {
        $error = "Name and Email cannot be empty.";
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
            $error = "Error updating user.";
        }
    }
}

// Fetch user info again to reflect updates
$sql = "SELECT name, email FROM Users WHERE id = $user_id";
$result = $conn->query($sql);
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>User Info</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 600px;">
    <h2>User Info</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <p><strong>Name:</strong> <?php echo htmlspecialchars($user['name']); ?></p>
    <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>

    <!-- Edit and Delete Buttons -->
    <form method="post" style="display:inline-block;">
        <button type="submit" name="edit_mode" class="btn btn-primary">Edit User</button>
    </form>
    <form method="post" style="display:inline-block;">
        <button type="submit" name="delete" class="btn btn-danger" onclick="return confirm('Are you sure?')">Delete User</button>
    </form>

    <?php if (isset($_POST['edit_mode'])): ?>
        <hr>
        <h4>Edit User Info</h4>
        <form method="post">
            <div class="mb-3">
                <label>Name</label>
                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label>Email</label>
                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
            </div>
            <div class="mb-3">
                <label>New Password (leave blank if unchanged)</label>
                <input type="password" name="password" class="form-control">
            </div>
            <button type="submit" name="update" class="btn btn-success">Update</button>
        </form>
    <?php endif; ?>

</div>
</body>
</html>
