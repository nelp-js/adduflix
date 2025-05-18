<?php
session_start();

// Database connection with error handling
$conn = new mysqli('localhost', 'root', '', 'adduflix');
if ($conn->connect_error) {
    error_log("Database connection failed: " . $conn->connect_error);
    die("System maintenance in progress. Please try again later.");
}

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: mainpage.php");
    exit();
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid request. Please try again.";
    } else {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        if (empty($email) || empty($password)) {
            $error = "Please fill in both fields.";
        } else {
            // Use prepared statements to prevent SQL injection
            $stmt = $conn->prepare("SELECT id, email, password_hash, name FROM Users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                if (password_verify($password, $user['password_hash'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_name'] = $user['name'];

                    // Regenerate session ID to prevent session fixation
                    session_regenerate_id(true);

                    // Check for active subscription
                    $sub_stmt = $conn->prepare("SELECT id FROM Subscriptions WHERE user_id = ? AND is_active = 1 AND expiry_date >= CURDATE() LIMIT 1");
                    $sub_stmt->bind_param("i", $user['id']);
                    $sub_stmt->execute();
                    $sub_res = $sub_stmt->get_result();
                    
                    if ($sub_res->num_rows > 0) {
                        header("Location: mainpage.php");
                        exit();
                    } else {
                        header("Location: sub.php");
                        exit();
                    }
                } else {
                    // Log failed login attempts
                    error_log("Failed login attempt for email: $email");
                    $error = "Invalid email or password.";
                }
            } else {
                $error = "Invalid email or password.";
            }
        }
    }
}

// Generate CSRF token
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AdduFlix - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e50914;
            --dark-color: #141414;
            --light-color: #f8f9fa;
        }
        
        body {
            background-color: var(--dark-color);
            color: var(--light-color);
            height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            max-width: 400px;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
        }
        
        .form-control {
            background-color: #333;
            border: 1px solid #444;
            color: white;
            padding: 12px;
        }
        
        .form-control:focus {
            background-color: #444;
            color: white;
            border-color: #555;
            box-shadow: 0 0 0 0.25rem rgba(229, 9, 20, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 10px;
            font-weight: 500;
        }
        
        .btn-primary:hover {
            background-color: #f6121d;
            border-color: #f6121d;
        }
        
        .logo {
            max-width: 180px;
            margin-bottom: 2rem;
        }
        
        .form-floating label {
            color: #8c8c8c;
        }
        
        .additional-links {
            margin-top: 1.5rem;
            color: #737373;
        }
        
        .additional-links a {
            color: white;
            text-decoration: none;
        }
        
        .additional-links a:hover {
            text-decoration: underline;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #8c8c8c;
        }
        
        .password-toggle:hover {
            color: var(--light-color);
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center">
    <div class="login-container">
        <div class="text-center mb-4">
            <img src="https://via.placeholder.com/180x50?text=AdduFlix" alt="AdduFlix Logo" class="logo">
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-floating mb-3 position-relative">
                <input type="email" class="form-control" id="email" name="email" 
                       placeholder="name@example.com" required
                       value="<?php echo htmlspecialchars($email); ?>">
                <label for="email">Email address</label>
            </div>
            
            <div class="form-floating mb-4 position-relative">
                <input type="password" class="form-control" id="password" 
                       name="password" placeholder="Password" required>
                <label for="password">Password</label>
                <span class="password-toggle" id="togglePassword">
                    <i class="bi bi-eye"></i>
                </span>
            </div>
            
            <button type="submit" class="btn btn-primary w-100 py-2 mb-3">
                Sign In
            </button>
            
            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                <label class="form-check-label" for="rememberMe">
                    Remember me
                </label>
            </div>
            
            <div class="additional-links text-center">
                <p>New to AdduFlix? <a href="acc.php">Sign up now</a></p>
                <p><a href="forgot-password.php">Need help?</a></p>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordField = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');
        
        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.innerHTML = type === 'password' ? '<i class="bi bi-eye"></i>' : '<i class="bi bi-eye-slash"></i>';
        });
        
        // Remember me functionality
        const rememberMe = document.getElementById('rememberMe');
        const emailField = document.getElementById('email');
        
        // Check if there's a saved email in localStorage
        if (localStorage.getItem('rememberEmail') && localStorage.getItem('rememberEmail') === 'true') {
            rememberMe.checked = true;
            emailField.value = localStorage.getItem('savedEmail') || '';
        }
        
        // Save email when remember me is checked
        document.querySelector('form').addEventListener('submit', function() {
            if (rememberMe.checked) {
                localStorage.setItem('rememberEmail', 'true');
                localStorage.setItem('savedEmail', emailField.value);
            } else {
                localStorage.removeItem('rememberEmail');
                localStorage.removeItem('savedEmail');
            }
        });
    });
</script>
</body>
</html>