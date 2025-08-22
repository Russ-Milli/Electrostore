<?php
session_start();
include __DIR__ . '/database_conn.php';   
include __DIR__ . '/auth/auth_check.php';

 

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$isLoggedIn = isset($_SESSION['user_id']);

// Handle POST actions: update quantity or remove item
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['change'], $_POST['index'])) {
        $index = (int)$_POST['index'];
        $change = (int)$_POST['change'];
        if (isset($_SESSION['cart'][$index])) {
            $_SESSION['cart'][$index]['quantity'] += $change;
            if ($_SESSION['cart'][$index]['quantity'] < 1) {
                array_splice($_SESSION['cart'], $index, 1);
            }
        }
    } elseif (isset($_POST['quantity'], $_POST['index'])) {
        // NEW: Handle direct quantity updates from input
        $index = (int)$_POST['index'];
        $newQty = (int)$_POST['quantity'];
        if (isset($_SESSION['cart'][$index])) {
            if ($newQty > 0) {
                $_SESSION['cart'][$index]['quantity'] = $newQty;
            } else {
                // Remove item if quantity < 1
                array_splice($_SESSION['cart'], $index, 1);
            }
        }
    } elseif (isset($_POST['remove'], $_POST['index'])) {
        $index = (int)$_POST['index'];
        if (isset($_SESSION['cart'][$index])) {
            array_splice($_SESSION['cart'], $index, 1);
        }
    }
    header('Location: cartpg.php');
    exit();
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Your Cart - ElectroStore</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link
    href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"
    rel="stylesheet"
  />
  <link rel="preconnect" href="https://fonts.gstatic.com" />
  <link
    href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap"
    rel="stylesheet"
  />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css"
    rel="stylesheet"
  />
  <link href="public/assets/lib/owlcarousel/owlcarousel/owl.carousel.css" rel="stylesheet" />
  <link rel="stylesheet" href="/../public/css/style.css">

</head>
<body class="d-flex flex-column min-vh-100">
  <!-- Topbar Start -->
  <div class="container-fluid">
    <div class="row align-items-center py-3 px-xl-5">
      <div class="col-lg-3 d-none d-lg-block">
        <a href="/index.php" class="text-decoration-none">
          <h1 class="m-0 display-6 font-weight-semi-bold">
            <span class="text-primary font-weight-bold border px-3 mr-1">E</span>lectrostore
          </h1>
        </a>
      </div>

      <div class="col-lg-1">
        <a href="cartpg.php" class="btn border">
          <i class="fas fa-shopping-cart text-primary"></i>
          <span class="badge"></span>
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
              <a href="" class="text-decoration-none d-block d-lg-none">
                  <h1 class="m-0 display-5 font-weight-semi-bold"><span class="text-primary font-weight-bold border px-3 mr-1">E</span>lectrostore</h1>
              </a>
              <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                  <span class="navbar-toggler-icon"></span>
              </button>
              <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                  <div class="navbar-nav mr-auto py-0">
                      <a href="index.php" class="nav-item nav-link">Home</a>
                      <a href="cartpg.php" class="nav-item nav-link active">Shopping Cart</a>
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

  <!-- ElectroStore Banner -->
  <nav class="navbar navbar-expand-lg" style="background-color: #0096d6">
    <div class="container">
      <small class="text-white-50 ms-2 me-auto">Your Trusted HP Partner</small>
    </div>
  </nav>

  <!-- Main Content -->
  <main class="flex-grow-1">
    <div class="container my-5">
      <h2>Your Shopping Cart</h2>
      <table class="table table-bordered mt-4">
        <thead class="table-light">
          <tr>
            <th>Product</th>
            <th>Price</th>
            <th>Qty</th>
            <th>Total</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($_SESSION['cart'])): ?>
            <tr><td colspan="5" class="text-center">Your cart is empty.</td></tr>
          <?php else:
            $cartTotal = 0;
            foreach ($_SESSION['cart'] as $index => $item):
              $itemTotal = $item['price'] * $item['quantity'];
              $cartTotal += $itemTotal;
          ?>
          <tr>
            <td><?= htmlspecialchars($item['name']) ?></td>
            <td>$<?= number_format($item['price'], 2) ?></td>
            <td>
              <form method="post" action="cartpg.php" class="d-inline" id="qty-form-<?= $index ?>">
                <input type="hidden" name="index" value="<?= $index ?>" />
                <input type="hidden" name="quantity" id="qty-input-<?= $index ?>" value="<?= (int)$item['quantity'] ?>" />
              </form>

              <form method="post" action="cartpg.php" class="d-inline">
                <input type="hidden" name="index" value="<?= $index ?>" />
                <button name="change" value="-1" class="btn btn-sm btn-outline-info">-</button>
              </form>

              <span 
                class="qty-text" 
                data-index="<?= $index ?>" 
                style="cursor:pointer; user-select:none; margin: 0 8px;"
                title="Click to edit quantity"
              >
                <?= (int)$item['quantity'] ?>
              </span>

              <form method="post" action="cartpg.php" class="d-inline">
                <input type="hidden" name="index" value="<?= $index ?>" />
                <button name="change" value="1" class="btn btn-sm btn-outline-info">+</button>
              </form>
            </td>

            <td>$<?= number_format($itemTotal, 2) ?></td>
            <td>
              <form method="post" action="cartpg.php">
                <input type="hidden" name="index" value="<?= $index ?>" />
                <button name="remove" value="1" class="btn btn-sm btn-danger">Remove</button>
              </form>
            </td>
          </tr>
          <?php endforeach; ?>
          <tr>
            <td colspan="3" class="text-end"><strong>Total:</strong></td>
            <td colspan="2">$<?= number_format($cartTotal, 2) ?></td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
          <?php if (!empty($_SESSION['cart'])): ?>
            <div class="d-flex justify-content-end mt-3">
              <a href="checkout.php" class="btn btn-success btn-block border-0 py-3">Proceed to Checkout</a>
            </div>
          <?php endif; ?>
    </div>
  </main>

  <!-- Sticky Footer -->
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
                            <a class="text-dark" href="contact.php"><i class="fa fa-angle-right mr-2"></i>Contact Us</a>
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

  <script>
    document.querySelectorAll('.qty-text').forEach(span => {
      span.addEventListener('click', () => {
        const index = span.getAttribute('data-index');
        const currentQty = span.textContent.trim();
        
        // Create input field
        const input = document.createElement('input');
        input.type = 'number';
        input.min = '1';
        input.value = currentQty;
        input.style.width = '50px';
        input.style.textAlign = 'center';

        // Replace span with input
        span.replaceWith(input);
        input.focus();
        
        // On blur or Enter key, submit form if quantity changed
        const submitQty = () => {
          const newQty = parseInt(input.value, 10);
          if (newQty && newQty !== parseInt(currentQty, 10)) {
            const form = document.getElementById('qty-form-' + index);
            form.querySelector('input[name="quantity"]').value = newQty;
            form.submit();
          } else {
            // Restore original span if no change or invalid input
            input.replaceWith(span);
          }
        };

        input.addEventListener('blur', submitQty);
        input.addEventListener('keydown', e => {
          if (e.key === 'Enter') {
            e.preventDefault();
            input.blur();
          } else if (e.key === 'Escape') {
            // Cancel editing on Escape
            input.replaceWith(span);
          }
        });
      });
    });
    </script>

</body>
</html>
