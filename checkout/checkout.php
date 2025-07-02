<?php
session_start();
require_once '../database_conn.php';

$database = new Database();
$conn = $database->connect(); // Get PDO connection

$cart = $_SESSION['cart'] ?? [];
$total = 0;

$isLoggedIn = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html>
<head>

<!-- Favicon -->
    <link href="img/favicon.ico" rel="icon" />

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap"
      rel="stylesheet"
    />

    <!-- Font Awesome -->
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css"
      rel="stylesheet"
    />

    <!-- Libraries Stylesheet -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet" />
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />

<!-- Font Awesome -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

<!-- Owl Carousel (optional, for product sliders) -->
<link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet" />

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
        div > form > button { padding: 10px 20px;   color: #fff; background-color: #28a745;border-color: #28a745; border: none; border-radius: 3px; cursor: pointer; }
        .alert { padding: 10px; background: #dff0d8; color: #3c763d; margin-top: 20px; border-radius: 5px; }
        .error { color: red; }
        
    </style>
 
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

<!-- jQuery and Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>


</body>
</html>
