<?php
require_once 'database_conn.php';
include_once 'auth/auth_check.php';  // Ensures user is logged in

$database = new Database();
$conn = $database->connect();

$cart = $_SESSION['cart'] ?? [];
$total = 0;
$userId = $_SESSION['user_id'] ?? null;
$isLoggedIn = isset($userId);

// Fetch user name
$userName = '';
if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT username FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userName = $stmt->fetchColumn();
}
$shippingAddress = '';
$phoneNumber = '';
if ($isLoggedIn) {
    $stmt = $conn->prepare("SELECT address, phone_number FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $userInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    $shippingAddress = $userInfo['address'];
    $phoneNumber = $userInfo['phone_number'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Checkout - ElectroStore</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />

    <!-- Favicon -->
    <link href="img/favicon.ico" rel="icon" />

    <!-- Google Fonts & Font Awesome -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet" />

    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet" />

    <!-- Libraries & Styles -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet" />
    <link href="css/style.css" rel="stylesheet" />
</head>
<body>

<!-- Topbar Start -->
<div class="container-fluid">
    <div class="row align-items-center py-3 px-xl-5">
        <div class="col-lg-3 d-none d-lg-block">
            <a href="index.php" class="text-decoration-none">
                <h1 class="m-0 display-6 font-weight-semi-bold">
                    <span class="text-primary font-weight-bold border px-3 mr-1">E</span>lectrostore
                </h1>
            </a>
        </div>
        <div class="col-lg-1">
            <a href="cartpg.php" class="btn border">
                <i class="fas fa-shopping-cart text-primary"></i>
            </a>
        </div>
    </div>
</div>
<!-- Topbar End -->

<!-- Navbar Start -->
<div class="container-fluid mb-2">
    <div class="row align-items-center justify-content-center">
        <div class="col-lg-11">
            <nav class="navbar navbar-expand-lg bg-light navbar-light py-3 py-lg-0 px-0">
                <a href="index.php" class="text-decoration-none d-block d-lg-none">
                    <h1 class="m-0 display-5 font-weight-semi-bold"><span class="text-primary font-weight-bold border px-3 mr-1">E</span>lectrostore</h1>
                </a>
                <button class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                    <div class="navbar-nav mr-auto py-0">
                        <a href="index.php" class="nav-item nav-link">Home</a>
                        <a href="cartpg.php" class="nav-item nav-link">Shopping Cart</a>
                        <a href="contact.php" class="nav-item nav-link">Contact</a>
                    </div>
                    <div class="navbar-nav ml-auto py-0">
                        <?php if ($isLoggedIn): ?>
                            <a href="profile.php" class="nav-item nav-link">Profile</a>
                            <a href="auth/logout.php" class="nav-item nav-link">Logout</a>
                        <?php else: ?>
                            <a href="auth/login.php" class="nav-item nav-link">Login</a>
                            <a href="auth/signup.php" class="nav-item nav-link">Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</div>
<!-- Navbar End -->

<!-- Checkout Content Start -->
<div class="container py-1 px-5">
    <div class="bg-white p-4 px-5 rounded shadow-sm">
        <h3 class="mb-4">Order Summary</h3>
        <ul class="list-unstyled">
            <?php
            $product_details = [];
            foreach ($cart as $item) {
                $subtotal = $item['price'] * $item['quantity'];
                echo "<li>" . htmlspecialchars($item['name']) . " (x{$item['quantity']}) - $" . number_format($subtotal, 2) . "</li>";
                $total += $subtotal;
                $product_details[] = [
                    'id' => $item['id'],
                    'qty' => $item['quantity'],
                    'price' => $item['price']
                ];
            }
            ?>
        </ul>
        <h5 class="mt-3">Total: <strong>$<?= number_format($total, 2); ?></strong></h5>

        <?php if (!$isLoggedIn): ?>
            <p class="text-danger mt-3">You must be <a href="login.php">logged in</a> to complete your purchase.</p>
        <?php elseif (empty($cart)): ?>
            <p class="text-warning mt-3">Your cart is empty. <a href="index.php">Continue shopping</a>.</p>
        <?php else: ?>
            <form method="POST" class="mt-4" id="paymentForm">
                <div class="form-group">
                    <label for="shipping_address">Shipping Address:</label>
                    <input type="text" name="shipping_address" id="shipping_address" class="form-control" value="<?= htmlspecialchars($shippingAddress) ?>" required>
                </div>

                <div class="form-group">
                    <label for="payment_method">Choose Payment Method:</label>
                    <select name="payment_method" id="payment_method" class="form-control" required>
                        <option value="">-- Select --</option>
                        <option value="card">Credit/Debit Card</option>
                        <option value="mpesa">M-PESA</option>
                        <option value="paypal">PayPal</option>
                    </select>
                </div>

                <div id="card_fields" class="form-group d-none">
                    <label>Card Number</label>
                    <input type="text" name="card_number" class="form-control">
                </div>

                <div id="mpesa_fields" class="form-group d-none">
                    <label>M-PESA Phone Number</label>
                    <input type="text" name="mpesa_number" class="form-control">
                </div>

                <div id="paypal_fields" class="form-group d-none">
                    <label>PayPal Email</label>
                    <input type="email" name="paypal_email" class="form-control">
                </div>

                <button type="submit" name="checkout" class="btn btn-success mt-3">Confirm & Pay</button>
            </form>
        <?php endif; ?>

        <?php
        if (isset($_POST['checkout']) && $isLoggedIn && !empty($cart)) {
            $method = $_POST['payment_method'] ?? '';
            $valid = true;
            $error = '';

            switch ($method) {
                case 'card':
                    $card = trim($_POST['card_number'] ?? '');
                    if (empty($card) || strlen($card) < 8) {
                        $valid = false;
                        $error = "Please enter a valid card number.";
                    }
                    break;
                case 'mpesa':
                    $mpesa = trim($_POST['mpesa_number'] ?? '');
                    if (!preg_match('/^07\d{8}$/', $mpesa)) {
                        $valid = false;
                        $error = "Enter a valid M-PESA number (e.g., 07XXXXXXXX).";
                    }
                    break;
                case 'paypal':
                    $paypal = trim($_POST['paypal_email'] ?? '');
                    if (!filter_var($paypal, FILTER_VALIDATE_EMAIL)) {
                        $valid = false;
                        $error = "Please enter a valid PayPal email.";
                    }
                    break;
                default:
                    $valid = false;
                    $error = "Please select a payment method.";
            }

            if (!$valid) {
                echo "<div class='alert alert-danger mt-3'>$error</div>";
            } else {
                try {
                    $conn->beginTransaction();
                    $insert = $conn->prepare("INSERT INTO purchases (user_id, product_id, quantity, total_price, payment_method) VALUES (?, ?, ?, ?, ?)");
                    foreach ($product_details as $product) {
                        $line_total = $product['price'] * $product['qty'];
                        $insert->execute([$userId, $product['id'], $product['qty'], $line_total, $method]);
                    }
                    $conn->commit();
                    unset($_SESSION['cart']);
                    echo "<div class='alert alert-success mt-3'>Payment via <strong>" . ucfirst($method) . "</strong> successful! Thank you, <strong>" . htmlspecialchars($userName) . "</strong>.</div>";
                } catch (PDOException $e) {
                    $conn->rollBack();
                    echo "<div class='alert alert-danger'>Transaction failed: " . htmlspecialchars($e->getMessage()) . "</div>";
                }
            }
        }
        ?>
    </div>
</div>
<!-- Checkout Content End -->

<!-- Footer Start -->
<div class="container-fluid bg-secondary text-dark mt-5 pt-5">
  <div class="row px-xl-5 pt-5">
    <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
      <a href="" class="text-decoration-none">
        <h1 class="mb-4 display-5 font-weight-semi-bold">
          <span class="text-primary font-weight-bold border border-white px-3 mr-1">E</span>lectrostore
        </h1>
      </a>
      <p class="mb-2">
        <i class="fa fa-map-marker-alt text-primary mr-3"></i>123 ElectroStore
      </p>
      <p class="mb-2">
        <i class="fa fa-envelope text-primary mr-3"></i>info@ElectroStore.com
      </p>
      <p class="mb-0">
        <i class="fa fa-phone-alt text-primary mr-3"></i>+012 345 67890
      </p>
    </div>
    <div class="col-lg-8 col-md-12">
      <div class="row">
        <div class="col-md-4 mb-5">
          <h5 class="font-weight-bold text-dark mb-4">Quick Links</h5>
          <div class="d-flex flex-column justify-content-start">
            <a class="text-dark mb-2" href="index.php"><i class="fa fa-angle-right mr-2"></i>Home</a>
            <a class="text-dark mb-2" href="cartpg.php"><i class="fa fa-angle-right mr-2"></i>Shopping Cart</a>
            <a class="text-dark" href="contact.php"><i class="fa fa-angle-right mr-2"></i>Contact Us</a>
          </div>
        </div>
        <div class="col-md-4 mb-5">
          <h5 class="font-weight-bold text-dark mb-4">Newsletter</h5>
          <form>
            <div class="form-group">
              <input type="text" class="form-control border-0 py-4" placeholder="Your Name" required="required" />
            </div>
            <div class="form-group">
              <input type="email" class="form-control border-0 py-4" placeholder="Your Email" required="required" />
            </div>
            <div>
              <button class="btn btn-primary btn-block border-0 py-3" type="submit">Subscribe Now</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<footer class="bg-dark text-white text-center py-3">
  &copy; 2025 ElectroStore. All rights reserved.
</footer>
<!-- Footer End -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="js/main.js"></script>

<!-- Payment Field Toggle -->
<script>
    const paymentSelect = document.getElementById('payment_method');
    const cardFields = document.getElementById('card_fields');
    const mpesaFields = document.getElementById('mpesa_fields');
    const paypalFields = document.getElementById('paypal_fields');

    paymentSelect.addEventListener('change', function () {
        cardFields.classList.add('d-none');
        mpesaFields.classList.add('d-none');
        paypalFields.classList.add('d-none');

        if (this.value === 'card') cardFields.classList.remove('d-none');
        else if (this.value === 'mpesa') mpesaFields.classList.remove('d-none');
        else if (this.value === 'paypal') paypalFields.classList.remove('d-none');
    });
</script>
</body>
</html>
