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
$plan_prices = ['Monthly' => 100.00, 'Yearly' => 1000.00];
$payment_methods = ['Credit Card', 'PayPal', 'GCash'];
$message = '';
$error = '';

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Invalid form submission. Please try again.";
    } else {
        // Validate inputs
        $plan_type = $_POST['plan_type'] ?? '';
        $payment_method = $_POST['payment_method'] ?? '';
        
        if (!array_key_exists($plan_type, $plan_prices)) {
            $error = "Invalid plan type selected.";
        } elseif (!in_array($payment_method, $payment_methods)) {
            $error = "Invalid payment method selected.";
        } else {
            $start_date = date('Y-m-d');
            $amount = $plan_prices[$plan_type];
            
            // Begin transaction
            $conn->begin_transaction();
            
            try {
                // Insert subscription
                $stmt = $conn->prepare("INSERT INTO Subscriptions (user_id, plan_type, start_date, is_active) VALUES (?, ?, ?, 1)");
                $stmt->bind_param("iss", $user_id, $plan_type, $start_date);
                $stmt->execute();
                $subscription_id = $conn->insert_id;
                
                // Insert payment
                $stmt2 = $conn->prepare("INSERT INTO Payments (user_id, subscription_id, amount, payment_method) VALUES (?, ?, ?, ?)");
                $stmt2->bind_param("iids", $user_id, $subscription_id, $amount, $payment_method);
                $stmt2->execute();
                
                // Commit transaction
                $conn->commit();
                
                // Set success message and redirect
                $_SESSION['subscription_success'] = true;
                header("Location: mainpage.php");
                exit();
            } catch (Exception $e) {
                // Rollback on error
                $conn->rollback();
                $error = "Subscription failed. Please try again.";
                error_log("Subscription error: " . $e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscribe - AdduFlix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #e50914;
            --secondary-color: #f5f5f5;
            --dark-color: #141414;
            --light-color: #f8f9fa;
        }
        
        body {
            background-color: var(--dark-color);
            color: var(--light-color);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .subscription-container {
            max-width: 600px;
            background-color: rgba(0, 0, 0, 0.75);
            padding: 2.5rem;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }
        
        .plan-card {
            background-color: #222;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
        }
        
        .plan-card.selected {
            border-color: var(--primary-color);
            background-color: rgba(229, 9, 20, 0.1);
        }
        
        .plan-price {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }
        
        .plan-savings {
            color: #5cb85c;
            font-size: 0.9rem;
        }
        
        .btn-subscribe {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            padding: 12px;
            font-weight: 600;
            font-size: 1.1rem;
            letter-spacing: 0.5px;
        }
        
        .btn-subscribe:hover {
            background-color: #f6121d;
            border-color: #f6121d;
        }
        
        .payment-method {
            display: flex;
            align-items: center;
            padding: 10px;
            border-radius: 5px;
            background-color: #333;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .payment-method:hover {
            background-color: #444;
        }
        
        .payment-method.selected {
            border: 2px solid var(--primary-color);
            background-color: rgba(229, 9, 20, 0.1);
        }
        
        .payment-icon {
            font-size: 1.5rem;
            margin-right: 15px;
            width: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
<div class="container d-flex justify-content-center">
    <div class="subscription-container">
        <h2 class="text-center mb-4">Choose Your Plan</h2>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form method="post" id="subscriptionForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="mb-4">
                <h5 class="mb-3">Select Plan</h5>
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="plan-card" onclick="selectPlan('Monthly')" id="monthlyPlan">
                            <h4>Monthly</h4>
                            <div class="plan-price">₱100</div>
                            <p class="text-muted">Billed every month</p>
                            <ul class="small">
                                <li>Full access to all content</li>
                                <li>Cancel anytime</li>
                            </ul>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="plan-card" onclick="selectPlan('Yearly')" id="yearlyPlan">
                            <h4>Yearly</h4>
                            <div class="plan-price">₱1000</div>
                            <p class="text-muted">Billed annually</p>
                            <div class="plan-savings">
                                <i class="bi bi-check-circle"></i> Save ₱200 (2 months free)
                            </div>
                            <ul class="small">
                                <li>Full access to all content</li>
                                <li>Best value</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <input type="hidden" name="plan_type" id="plan_type" value="Monthly" required>
            </div>
            
            <div class="mb-4">
                <h5 class="mb-3">Payment Method</h5>
                <div id="paymentMethods">
                    <div class="payment-method" onclick="selectPayment('Credit Card')" id="creditCard">
                        <div class="payment-icon"><i class="bi bi-credit-card"></i></div>
                        <div>Credit Card</div>
                    </div>
                    <div class="payment-method" onclick="selectPayment('PayPal')" id="paypal">
                        <div class="payment-icon"><i class="bi bi-paypal"></i></div>
                        <div>PayPal</div>
                    </div>
                    <div class="payment-method" onclick="selectPayment('GCash')" id="gcash">
                        <div class="payment-icon"><i class="bi bi-phone"></i></div>
                        <div>GCash</div>
                    </div>
                </div>
                <input type="hidden" name="payment_method" id="payment_method" value="Credit Card" required>
            </div>
            
            <div class="d-grid">
                <button type="submit" class="btn btn-subscribe">
                    <i class="bi bi-check-circle"></i> Subscribe Now
                </button>
            </div>
            
            <div class="text-center mt-3 small text-muted">
                By subscribing, you agree to our Terms of Service and Privacy Policy
            </div>
        </form>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Initialize with Monthly plan selected
    document.addEventListener('DOMContentLoaded', function() {
        selectPlan('Monthly');
        selectPayment('Credit Card');
    });
    
    function selectPlan(plan) {
        // Update visual selection
        document.querySelectorAll('.plan-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        if (plan === 'Monthly') {
            document.getElementById('monthlyPlan').classList.add('selected');
        } else {
            document.getElementById('yearlyPlan').classList.add('selected');
        }
        
        // Update hidden input
        document.getElementById('plan_type').value = plan;
    }
    
    function selectPayment(method) {
        // Update visual selection
        document.querySelectorAll('.payment-method').forEach(pm => {
            pm.classList.remove('selected');
        });
        
        document.getElementById(method.toLowerCase().replace(' ', '')).classList.add('selected');
        
        // Update hidden input
        document.getElementById('payment_method').value = method;
    }
    
    // Form validation
    document.getElementById('subscriptionForm').addEventListener('submit', function(e) {
        if (!document.getElementById('plan_type').value || !document.getElementById('payment_method').value) {
            e.preventDefault();
            alert('Please select both a plan and payment method.');
        }
    });
</script>
</body>
</html>