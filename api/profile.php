<?php

session_start(); // ✅ MUST be first before using $_SESSION

include __DIR__ . '/database_conn.php';
include __DIR__ . '/auth/auth_check.php';

$conn = getPDOConnection(); // get PDO connection

// ✅ Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php"); 
    exit();
}

$userId = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';

// ✅ Handle account deletion first
if (isset($_POST['delete_account']) && isset($_POST['delete_password'])) {
    $password = $_POST['delete_password'] ?? '';

    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    header('Content-Type: application/json');

    if ($user && password_verify($password, $user['password_hash'])) {
        // Delete user
        $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $deleteStmt->execute([$userId]);

        session_destroy();
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Incorrect password. Account not deleted.']);
        exit;
    }
}

// ✅ Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['delete_account'])) {
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone_number']);
    $address = trim($_POST['address']);

    $updateStmt = $conn->prepare("UPDATE users SET full_name = ?, phone_number = ?, address = ? WHERE id = ?");
    $updateStmt->execute([$full_name, $phone, $address, $userId]);

    header("Location: profile.php?updated=1");
    exit();
}

// ✅ Fetch user data
$userStmt = $conn->prepare("SELECT full_name, email, phone_number, address FROM users WHERE id = ?");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC);

// ✅ Fetch purchase history
$sql = "SELECT p.name AS product_name, pu.quantity, pr.price, (pu.quantity * pr.price) AS total, pu.purchase_date, pu.payment_method
        FROM purchases pu
        JOIN products pr ON pu.product_id = pr.id
        JOIN products p ON pu.product_id = p.id
        WHERE pu.user_id = ?
        ORDER BY pu.purchase_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute([$userId]);
$purchases = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile - ElectroStore</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
  <link href="public/assets/lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet" />
   <link rel="stylesheet" href="/../public/css/style.css">
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
                        <a href="#" class="nav-item nav-link active">Profile</a>
                        <a href="auth/logout.php" class="nav-item nav-link">Logout</a>
                    </div>
                </div>
            </nav>
        </div>
    </div>
</div>
<!-- Navbar End -->

<!-- Profile Content Start -->
<div class="container py-2">
    <div class="bg-white p-4 rounded shadow-sm">
        <h3 class="mb-4">Hello, <?= htmlspecialchars($userData['full_name']); ?> 👋</h3>
<h5 class="mb-3">Your Profile Details</h5>

<?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
    <div class="alert alert-success" id="updateSuccess">Profile updated successfully!</div>
<?php endif; ?>

<form method="POST" action="profile.php" class="row g-3 mb-4">
    <div class="col-md-6">
        <label for="full_name" class="form-label">Full Name</label>
        <input type="text" class="form-control" id="full_name" name="full_name"
            value="<?= htmlspecialchars($userData['full_name'] ?? '') ?>" readonly>
    </div>
    <div class="col-md-6">
        <label for="email" class="form-label">Email (readonly)</label>
        <input type="email" class="form-control" id="email" name="email"
            value="<?= htmlspecialchars($userData['email'] ?? '') ?>" readonly>
    </div>
    <div class="col-md-6">
        <label for="phone_number" class="form-label">Phone Number</label>
        <input type="text" class="form-control" id="phone_number" name="phone_number"
            value="<?= htmlspecialchars($userData['phone_number'] ?? '') ?>" readonly>
    </div>
    <div class="col-md-6">
        <label for="address" class="form-label">Shipping Address</label>
        <input type="text" class="form-control" id="address" name="address"
            value="<?= htmlspecialchars($userData['address'] ?? '') ?>" readonly>
    </div>
    <div class="col-12">
        <button type="button" class="btn btn-warning" id="editBtn">Edit</button>
        <button type="submit" class="btn btn-primary d-none" id="saveBtn">Save Changes</button>
    </div>
</form>

        <h5 class="mb-3">Your Purchase History</h5> 

        <?php if (count($purchases) > 0): ?>
            <div class="table-responsive">
                <table class="table table-bordered text-center">
                    <thead class="thead-light">
                        <tr>
                            <th>Product</th>
                            <th>Quantity</th>
                            <th>Price ($)</th>
                            <th>Total ($)</th>
                            <th>Purchase Date</th>
                            <th>Payment Method</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($purchases as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars($item['product_name']) ?></td>
                                <td><?= $item['quantity'] ?></td>
                                <td><?= number_format($item['price'], 2) ?></td>
                                <td><?= number_format($item['total'], 2) ?></td>
                                <td><?= $item['purchase_date'] ?></td>
                                <td><?= htmlspecialchars(ucfirst($item['payment_method'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-muted">You haven't purchased anything yet.</p>
        <?php endif; ?>
    </div>
</div>
<!-- Profile Content End -->

<?php if (isset($deleteError)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($deleteError) ?></div>
<?php endif; ?>

<!-- Delete Account Error Placeholder -->
<div id="deleteError" class="alert alert-danger d-none text-center" style="margin: 1rem 10rem 0;"></div>

<!-- Delete Trigger button -->
<div class="text-center my-4">
    <button class="btn btn-outline-danger" id="showDeleteForm">Delete My Account</button>
</div>

<!-- Delete Account Section (hidden by default) -->
<div id="deleteContainer" class="container my-4 d-none">
    <div class="bg-light p-4 rounded border border-danger">
        <h5 class="text-danger">Delete Account</h5>
        <p class="mb-3">This action is irreversible. Click the button below to confirm.</p>

        <form id="deleteForm">
            <div class="mb-3">
                <label for="delete_password" class="form-label">Confirm Password</label>
                <input type="password" class="form-control" id="delete_password" name="delete_password" required>
            </div>
            <button type="submit" class="btn btn-danger">Confirm Delete</button>
        </form>
    </div>
</div>
<!--  -->

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
<script src="public/assets/lib/easing/easing.min.js"></script>
<script src="public/assets/lib/owlcarousel/owl.carousel.min.js"></script>
<script src="/../public/js/main.js"></script>

<script>
document.getElementById('editBtn').addEventListener('click', function () {
    const formFields = document.querySelectorAll('#full_name, #phone_number, #address');
    formFields.forEach(field => field.removeAttribute('readonly'));

    document.getElementById('saveBtn').classList.remove('d-none');
    this.classList.add('d-none');
});
</script>

<script>
// Hide URL param "?updated=1" after success message is shown
window.addEventListener('DOMContentLoaded', () => {
    const alertBox = document.getElementById('updateSuccess');
    if (alertBox) {
        // Remove query param from URL without reloading
        const url = new URL(window.location.href);
        url.searchParams.delete('updated');
        window.history.replaceState({}, document.title, url.pathname);
    }
});
</script>

<script>
// Toggle delete section
document.getElementById('showDeleteForm').addEventListener('click', function () {
    document.getElementById('deleteContainer').classList.remove('d-none');
    this.classList.add('d-none');
});

// Handle AJAX delete
document.getElementById('deleteForm').addEventListener('submit', async function (e) {
    e.preventDefault();

    const password = document.getElementById('delete_password').value;

    const response = await fetch('profile.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            delete_account: 1,
            delete_password: password
        })
    });

    const result = await response.json();

    if (result.success) {
        // ✅ Redirect to home with goodbye message
        window.location.href = 'index.php?goodbye=1';
    } else {
        // ❌ Show error
        const errorBox = document.getElementById('deleteError');
        errorBox.textContent = result.message || 'Something went wrong.';
        errorBox.classList.remove('d-none');
    }
});
</script>



</body>
</html>
