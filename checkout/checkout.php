<?php
session_start();
require_once '../database_conn.php';

$database = new Database();
$conn = $database->connect(); // Get PDO connection

$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f9f9f9; }
        h3, h4 { color: #333; }
        ul { list-style: none; padding: 0; }
        li { margin: 10px 0; }

        /* Container for two columns */
        .checkout-container {
            display: flex;
            gap: 40px; /* space between columns */
            max-width: 900px;
            margin: 20px auto;
        }

        /* Left column - order summary */
        .order-summary {
            flex: 1; /* take half available width */
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }

        /* Right column - payment form */
        .payment-form {
            flex: 1;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 12px rgba(0,0,0,0.1);
        }

        /* Responsive: stack columns on smaller screens */
        @media (max-width: 768px) {
            .checkout-container {
                flex-direction: column;
                gap: 20px;
                max-width: 100%;
                margin: 10px;
            }
        }

        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input { width: 100%; padding: 8px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 3px; }
        button { padding: 10px 20px;   color: #fff; background-color: #28a745;border-color: #28a745; border: none; border-radius: 3px; cursor: pointer; }
        .alert { padding: 10px; background: #dff0d8; color: #3c763d; margin-top: 20px; border-radius: 5px; }
        .error { color: red; }
        
    </style>
<link href="css/style.css" rel="stylesheet"> 
</head>
<body>

<div class="checkout-container">

    <div class="order-summary">
        <h3>Order Summary:</h3>
        <ul>
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
        <h4>Total: <strong>$<?php echo number_format($total, 2); ?></strong></h4>

        <p><a href="../cartpg.php" style="text-decoration:none; color:#007bff;">&larr; Back to Cart</a></p>
        
    </div>


    <div class="payment-form">
        <h3>Payment Information</h3>
        <form method="POST" action="">
            <label>Name on Card:</label>
            <input type="text" name="name" required>

            <label>Card Number:</label>
            <input type="text" name="card" required>

            <label>Address:</label>
            <input type="text" name="address" required>

            <button type="submit" name="checkout">Confirm & Pay</button>
        </form>

        <?php
        if (isset($_POST['checkout'])) {
            $name = trim($_POST['name']);
            $card = trim($_POST['card']); // not stored
            $address = trim($_POST['address']);

            if (empty($name) || empty($card) || empty($address)) {
                echo "<p class='error'>Please fill in all fields.</p>";
            } else {
                $userId = $_SESSION['user_id'] ?? null;

                if (!$userId) {
                    echo "<p class='error'>You must be logged in to complete the purchase.</p>";
                } else {
                    try {
                        $conn->beginTransaction();

                        $insert = $conn->prepare("INSERT INTO purchases (user_id, product_id, quantity, total_price) VALUES (?, ?, ?, ?)");

                        foreach ($product_details as $product) {
                            $line_total = $product['price'] * $product['qty'];
                            $insert->execute([$userId, $product['id'], $product['qty'], $line_total]);
                        }

                        $conn->commit();
                        unset($_SESSION['cart']);

                        echo "<div class='alert'>Payment successful! Thank you for your purchase, <strong>" . htmlspecialchars($name) . "</strong>.</div>";
                    } catch (PDOException $e) {
                        $conn->rollBack();
                        echo "<p class='error'>Transaction failed: " . htmlspecialchars($e->getMessage()) . "</p>";
                    }
                }
            }
        }
        ?>
    </div>
</div>

 <!-- Footer Start -->
    <div class="container-fluid bg-secondary text-dark mt-5 pt-5">
        <div class="row px-xl-5 pt-5">
            <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
                <a href="" class="text-decoration-none">
                    <h1 class="mb-4 display-5 font-weight-semi-bold"><span class="text-primary font-weight-bold border border-white px-3 mr-1">E</span>lectrostore</h1>
                </a>
                <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>123 ElectroStore</p>
                <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>info@ElectroStore.com</p>
                <p class="mb-0"><i class="fa fa-phone-alt text-primary mr-3"></i>+012 345 67890</p>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="row">
                    <div class="col-md-4 mb-5">
                        <h5 class="font-weight-bold text-dark mb-4">Quick Links</h5>
                        <div class="d-flex flex-column justify-content-start">
                            <a class="text-dark mb-2" href="index.php"><i class="fa fa-angle-right mr-2"></i>Home</a>
                            <a class="text-dark mb-2" href="cartpg.php"><i class="fa fa-angle-right mr-2"></i>Shopping Cart</a>
                            <a class="text-dark" href="contact.html"><i class="fa fa-angle-right mr-2"></i>Contact Us</a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-5">
                        <h5 class="font-weight-bold text-dark mb-4">Newsletter</h5>
                        <form action="">
                            <div class="form-group">
                                <input type="text" class="form-control border-0 py-4" placeholder="Your Name" required="required" />
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control border-0 py-4" placeholder="Your Email"
                                    required="required" />
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

  <footer class="bg-dark text-white text-center py-3 mt-auto">
    &copy; 2025 ElectroStore. All rights reserved.
  </footer>
</body>
</html>
