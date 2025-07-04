<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /Electrostore/index.php");
    exit();
}

require '../database_conn.php';

$db = new Database();
$pdo = $db->connect();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $phone_number = trim($_POST['phone_number']);
    $address = trim($_POST['address']);
    $password = $_POST['password'];
    $password_confirm = $_POST['confirm'];

    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        $error = "All required fields must be filled.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        // Check if username or email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or email already exists.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash, full_name, phone_number, address) VALUES (?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute([$username, $email, $password_hash, $full_name, $phone_number, $address]);
            if ($success) {
                header("Location: login.php?signup=success");
                exit;
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Sign Up - ElectroStore</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet" />

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/style.css" />
</head>
<body>

    <!-- Signup Form Container -->
    <div class="login-container">
        <div class="form-heading">
            Create account for <a href="../index.php" style="text-decoration: none; color: #333;"><strong style="color: #007bff;">ElectroStore</strong></a>
        </div>
        <?php if ($error): ?>
            <p style="color: red; font-weight: bold;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form onsubmit="return validateSignup()" method="POST" action="">
            <input type="text" id="full_name" name="full_name" placeholder="Full Name *" required
                value="<?php echo isset($full_name) ? htmlspecialchars($full_name) : ''; ?>" />

            <input type="text" id="username" name="username" placeholder="Username *" required
                value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" />

            <input type="email" id="email" name="email" placeholder="Email *" required
                value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" />

            <input type="text" id="phone_number" name="phone_number" placeholder="Phone Number"
                value="<?php echo isset($phone_number) ? htmlspecialchars($phone_number) : ''; ?>" />

            <input type="text" id="address" name="address" placeholder="Shipping Address"
                value="<?php echo isset($address) ? htmlspecialchars($address) : ''; ?>" />

            <input type="password" id="password" name="password" placeholder="Password *" required />

            <input type="password" id="confirm" name="confirm" placeholder="Confirm Password *" required />

            <button type="submit">Sign Up</button>
        </form>

        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <!-- JS for validation -->
    <script src="../assets/js/validation.js"></script>
</body>
</html>
