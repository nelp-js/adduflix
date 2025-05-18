<?php
session_start();

// Database connection with error handling
$conn = new mysqli('localhost', 'root', '', 'adduflix');
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Sanitize and validate inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Validation
    if (empty($name)) {
        $errors[] = "Name is required.";
    } elseif (strlen($name) > 100) {
        $errors[] = "Name must be less than 100 characters.";
    }

    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email must be less than 100 characters.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    // Check if email already exists
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $errors[] = "Email already registered.";
        }
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        
        $stmt = $conn->prepare("INSERT INTO Users (name, email, password_hash) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $name, $email, $password_hash);
        
        if ($stmt->execute()) {
            // Registration successful - redirect to start page
            header("Location: start.php");
            exit();
        } else {
            $errors[] = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account - AdduFlix</title>
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

        
        .registration-container {
            max-width: 450px;
            width: 100%;
            background-color: rgba(0, 0, 0, 0.75);
            padding: 2.5rem;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.5);
            margin: 0 auto;
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
            background-color: #004aad;
            border-color: #004aad;
        }
        
        .logo {
            width: 350px;
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background-color: #333;
            border-radius: 3px;
            overflow: hidden;
        }
        
        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: width 0.3s ease;
        }
        
        .password-requirements {
            font-size: 0.85rem;
            color: #8c8c8c;
            margin-top: 5px;
        }
        
        .requirement {
            display: flex;
            align-items: center;
            margin-bottom: 3px;
        }
        
        .requirement i {
            margin-right: 5px;
            font-size: 0.7rem;
        }
        
        .requirement.valid {
            color: #5cb85c;
        }
        
        .additional-links a {
            color: white;
            text-decoration: none;
        }
        
        .additional-links a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="registration-container">
        <div class="text-center mb-4">
            <img src="https://raw.githubusercontent.com/Unonkgw/my-images/c8e3733c786597bbdc94c9e0b434572bb27044ae/adduflixlogo.svg" alt="AdduFlix Logo" class="logo">
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="post" id="registrationForm">
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" 
                       value="<?php echo htmlspecialchars($name); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" 
                       value="<?php echo htmlspecialchars($email); ?>" required>
            </div>
            
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <div class="password-strength">
                    <div class="password-strength-bar" id="passwordStrengthBar"></div>
                </div>
                <div class="password-requirements">
                    <div class="requirement" id="lengthReq">
                        <i class="bi bi-circle"></i>
                        <span>At least 8 characters</span>
                    </div>
                    <div class="requirement" id="numberReq">
                        <i class="bi bi-circle"></i>
                        <span>Contains a number</span>
                    </div>
                    <div class="requirement" id="specialReq">
                        <i class="bi bi-circle"></i>
                        <span>Contains a special character</span>
                    </div>
                </div>
            </div>
            
            <div class="mb-4">
                <label for="confirm_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <div id="passwordMatch" class="text-danger small mt-1"></div>
            </div>
            
            <button type="submit" name="submit" class="btn btn-primary w-100 py-2 mb-3">
                Create Account
            </button>
            
            <div class="additional-links text-center">
                <p>Already have an account? <a href="start.php">Sign in</a></p>
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const passwordInput = document.getElementById('password');
        const confirmPasswordInput = document.getElementById('confirm_password');
        const passwordMatch = document.getElementById('passwordMatch');
        const strengthBar = document.getElementById('passwordStrengthBar');
        
        // Password validation
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            
            // Calculate strength (0-100)
            let strength = 0;
            
            // Length check
            if (password.length >= 8) {
                strength += 30;
                document.getElementById('lengthReq').classList.add('valid');
                document.getElementById('lengthReq').querySelector('i').className = 'bi bi-check-circle';
            } else {
                document.getElementById('lengthReq').classList.remove('valid');
                document.getElementById('lengthReq').querySelector('i').className = 'bi bi-circle';
            }
            
            // Number check
            if (/\d/.test(password)) {
                strength += 30;
                document.getElementById('numberReq').classList.add('valid');
                document.getElementById('numberReq').querySelector('i').className = 'bi bi-check-circle';
            } else {
                document.getElementById('numberReq').classList.remove('valid');
                document.getElementById('numberReq').querySelector('i').className = 'bi bi-circle';
            }
            
            // Special character check
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) {
                strength += 40;
                document.getElementById('specialReq').classList.add('valid');
                document.getElementById('specialReq').querySelector('i').className = 'bi bi-check-circle';
            } else {
                document.getElementById('specialReq').classList.remove('valid');
                document.getElementById('specialReq').querySelector('i').className = 'bi bi-circle';
            }
            
            // Update strength bar
            strengthBar.style.width = strength + '%';
            strengthBar.style.backgroundColor = strength < 50 ? '#dc3545' : 
                                              strength < 80 ? '#ffc107' : '#28a745';
        });
        
        // Password match validation
        confirmPasswordInput.addEventListener('input', function() {
            if (this.value !== passwordInput.value) {
                passwordMatch.textContent = 'Passwords do not match';
            } else {
                passwordMatch.textContent = '';
            }
        });
        
        // Form submission validation
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmPasswordInput.value) {
                e.preventDefault();
                passwordMatch.textContent = 'Passwords must match';
                confirmPasswordInput.focus();
            }
        });
    });
</script>
</body>
</html>