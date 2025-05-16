<?php
$conn = new mysqli('localhost', 'root', '', 'adduflix');
if (isset($_POST['submit'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $sql = "INSERT INTO Users (name, email, password_hash) VALUES ('$name', '$email', '$password_hash')";
    $conn->query($sql);
    header("Location: start.php");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Create Account</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow p-4">
            <h2>Create Account</h2>
            <form method="post">
                <input type="text" name="name" class="form-control mb-3" placeholder="Name" required>
                <input type="email" name="email" class="form-control mb-3" placeholder="Email" required>
                <input type="password" name="password" class="form-control mb-3" placeholder="Password" required>
                <button type="submit" name="submit" class="btn btn-primary w-100">Create Account</button>
            </form>
        </div>
    </div>
</body>
</html>
