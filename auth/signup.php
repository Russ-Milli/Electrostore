<?php
session_start();

if (isset($_SESSION['user_id'])) {
    // User is already logged in, redirect to home
    header("Location: /Electrostore/index.php");
    exit();
}

require '../database_conn.php';

$db = new Database();
$pdo = $db->connect();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['confirm'];  // note the change here to match your new form

    // Validate inputs
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif ($password !== $password_confirm) {
        $error = "Passwords don't match.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters.";
    } else {
        // Check if user exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $error = "Username or email already exists.";
        } else {
            // Create user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            if ($stmt->execute([$username, $email, $password_hash])) {
                // You can redirect after successful signup or show a message
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
    <!-- Navigation Buttons -->
    <a href="login.php" class="top-left-back">← Back</a>
    <a href="../index.php" class="top-right-link">🏠 Home</a>

    <!-- Signup Form Container -->
    <div class="login-container">
        <h2>Create Your Account</h2>

        <?php if ($error): ?>
            <p style="color: red; font-weight: bold;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>

        <form onsubmit="return validateSignup()" method="POST" action="">
            <label for="username" class="visually-hidden">Username</label>
            <input type="text" id="username" name="username" placeholder="Username" required
                value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>" />

            <label for="email" class="visually-hidden">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required
                value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>" />

            <label for="password" class="visually-hidden">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required />

            <label for="confirm" class="visually-hidden">Confirm Password</label>
            <input type="password" id="confirm" name="confirm" placeholder="Confirm Password" required />

            <button type="submit">Sign Up</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <!-- JS for validation -->
    <script src="../assets/js/validation.js"></script>
</body>
</html>
