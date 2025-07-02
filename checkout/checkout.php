<?php
session_start();
include '../config/db.php';

$cart = $_SESSION['cart'] ?? [];
$total = 0;
?>
<!DOCTYPE html>
<html>
<head>
    <title>Checkout - ElectroStore</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <h2>Checkout</h2>

    <h3>Order Summary:</h3>
    <ul>
        <?php
        foreach ($cart as $id => $qty) {
            $query = mysqli_query($conn, "SELECT * FROM products WHERE id = $id");
            $product = mysqli_fetch_assoc($query);
            $subtotal = $product['price'] * $qty;
            $total += $subtotal;

            echo "<li>{$product['name']} x $qty = KES $subtotal</li>";
        }
        ?>
    </ul>
    <p><strong>Total: KES <?= $total ?></strong></p>

    <h3>Payment Details:</h3>
    <form method="POST" onsubmit="return confirmPayment()">
        <input type="text" name="fullname" placeholder="Full Name" required><br>
        <input type="text" name="cardno" placeholder="Card Number" pattern="\d{16}" required><br>
        <input type="text" name="address" placeholder="Delivery Address" required><br>
        <button type="submit">Confirm Payment</button>
    </form>

    <script>
        function confirmPayment() {
            alert("Payment successful! Your order is being processed.");
            return true;
        }
    </script>
</body>
</html>
