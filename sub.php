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
$plan_prices = ['Monthly' => 100.00, 'Yearly' => 1000.00];
$payment_methods = ['Credit Card', 'PayPal', 'GCash'];
$message = '';

// Handle submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_type'], $_POST['payment_method'])) {
    $plan_type = $_POST['plan_type'];
    $payment_method = $_POST['payment_method'];
    $start_date = date('Y-m-d');
    $amount = $plan_prices[$plan_type];

    // Insert subscription
    $stmt = $conn->prepare("INSERT INTO Subscriptions (user_id, plan_type, start_date) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $plan_type, $start_date);
    $stmt->execute();
    $subscription_id = $stmt->insert_id;

    // Insert payment
    $stmt2 = $conn->prepare("INSERT INTO Payments (user_id, subscription_id, amount, payment_method) VALUES (?, ?, ?, ?)");
    $stmt2->bind_param("iids", $user_id, $subscription_id, $amount, $payment_method);
    $stmt2->execute();

    header("Location: mainpage.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Subscribe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script>
        function updatePrice() {
            var plan = document.getElementById('plan_type').value;
            var prices = {
                'Monthly': 100,
                'Yearly': 1000
            };
            document.getElementById('price_display').innerText = 'Price: ₱' + prices[plan].toFixed(2);
        }
    </script>
</head>
<body class="bg-light">
<div class="container mt-5" style="max-width: 500px;">
    <h2>Choose Your Plan</h2>
    <form method="post">
        <div class="mb-3">
            <label for="plan_type">Plan Type</label>
            <select name="plan_type" id="plan_type" class="form-select" onchange="updatePrice()" required>
                <option value="Monthly">Monthly</option>
                <option value="Yearly">Yearly</option>
            </select>
        </div>

        <div class="mb-3" id="price_display">Price: ₱100.00</div>

        <div class="mb-3">
            <label for="payment_method">Payment Method</label>
            <select name="payment_method" id="payment_method" class="form-select" required>
                <?php foreach ($payment_methods as $method): ?>
                    <option value="<?php echo $method; ?>"><?php echo $method; ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <button type="submit" class="btn btn-success">Subscribe</button>
    </form>
</div>
<script>
    document.addEventListener('DOMContentLoaded', updatePrice);
</script>
</body>
</html>
