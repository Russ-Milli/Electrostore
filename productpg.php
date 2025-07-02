<?php
session_start();
require_once 'database_conn.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Product not found.");
}

$id = (int) $_GET['id'];
$db = new Database();
$conn = $db->connect();

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$product) {
    die("Product not found.");
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        // Not logged in - redirect to login page with message
        header('Location: auth/login.php?message=login_required');
        exit();
    }

    // User is logged in - Add item to cart session
    $item = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'quantity' => 1
    ];

    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if item already in cart
    $found = false;
    foreach ($_SESSION['cart'] as &$cart_item) {
        if ($cart_item['id'] === $item['id']) {
            $cart_item['quantity'] += 1;
            $found = true;
            break;
        }
    }
    if (!$found) {
        $_SESSION['cart'][] = $item;
    }

    $success_message = "Product added to cart!";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title><?= htmlspecialchars($product['name']) ?> - ElectroStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body>

<!-- 🔵 ElectroStore Banner -->
<nav class="navbar navbar-expand-lg" style="background-color: #0096d6">
  <div class="container">
    <a class="navbar-brand text-white fw-bold" href="index.php" style="font-size: 1.5rem">ElectroStore</a>
    <small class="text-white-50 ms-2 me-auto">Your Trusted HP Partner</small>
  </div>
</nav>

<?php if (!empty($success_message)): ?>
    <div class="container mt-3">
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    </div>
<?php endif; ?>

<!-- 🖥️ Product Section with Light Background -->
<div class="bg-light py-5">
  <div class="container">
    <div class="row align-items-center">
      <!-- Product Image -->
      <div class="col-md-6 text-center mb-4 mb-md-0">
        <img src="<?= htmlspecialchars($product['image_path']) ?>" class="img-fluid rounded shadow" alt="<?= htmlspecialchars($product['name']) ?>" />
      </div>

      <!-- Product Info -->
      <div class="col-md-6">
        <h2 class="mb-3"><?= htmlspecialchars($product['name']) ?></h2>
        <p class="fs-5"><strong>Price:</strong> $<?= number_format($product['price'], 2) ?></p>

        <p><strong>Description:</strong></p>
        <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>

        <!-- Extra Info (optional static badges) -->
        <p>
          <span class="badge bg-success me-2">1-Year Warranty</span>
          <span class="text-muted">Free shipping + 24/7 Support</span>
        </p>

        <!-- Add to Cart Button -->
        <?php if (isset($_SESSION['user_id'])): ?>
    <form method="post" class="d-inline">
        <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
    </form>
<?php else: ?>
    <a href="auth/login.php?message=login_required" class="btn btn-primary">Login to Add to Cart</a>
<?php endif; ?>

      </div>
    </div>
  </div>
</div>

<!-- ⚫ Footer -->
<footer class="bg-dark text-white text-center py-3 mt-5">
  &copy; 2025 ElectroStore. All rights reserved.
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
