<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'adduflix');

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in both fields.";
    } else {
        $email_esc = $conn->real_escape_string($email);

        $result = $conn->query("SELECT * FROM Users WHERE email = '$email_esc'");

        if ($result && $result->num_rows === 1) {
            $user = $result->fetch_assoc();

            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];

                // Check for active subscription
                $user_id = $user['id'];
                $sub_res = $conn->query("SELECT * FROM Subscriptions WHERE user_id = $user_id AND is_active = 1 LIMIT 1");
                
                if ($sub_res && $sub_res->num_rows > 0) {
                    // User has active subscription - go to mainpage
                    header("Location: mainpage.php");
                    exit();
                } else {
                    // No active subscription - redirect to subscription page
                    header("Location: sub.php");
                    exit();
                }
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "User not found.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Start/Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 400px;">
    <h2 class="mb-4">Login</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="post" action="start.php">
        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <input id="email" name="email" type="email" class="form-control" required value="<?php echo isset($email) ? htmlspecialchars($email) : '' ?>">
        </div>

        <div class="mb-3">
            <label for="password" class="form-label">Password</label>
            <input id="password" name="password" type="password" class="form-control" required>
        </div>

        <button type="submit" class="btn btn-primary w-100">Start</button>
    </form>
</div>
</body>
</html>
