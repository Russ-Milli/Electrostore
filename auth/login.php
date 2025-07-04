<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: /Electrostore/index.php");
    exit();
}

require_once '../database_conn.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password = trim($_POST['password']);

    if (empty($identifier) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $database = new Database();
        $db = $database->connect();

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $query = $isEmail
            ? "SELECT id, username, email, password_hash FROM users WHERE email = :identifier"
            : "SELECT id, username, email, password_hash FROM users WHERE username = :identifier";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                header("Location: /Electrostore/index.php");
                exit();
            }
        }

        $error = "Invalid username/email or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - ElectroStore</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

    <!-- Centered login form -->
    <div class="login-container">
        <!-- Updated form heading with link -->
        <div class="form-heading">
            <a href="/Electrostore/index.php" style="text-decoration: none; color: #333;">Login to <strong style="color: #007bff;">ElectroStore</strong></a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message" style="color: red; margin-bottom: 1rem;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form onsubmit="return validateLogin()" method="POST" action="login.php">
            <label for="identifier" class="visually-hidden">Email or Username</label>
            <input type="text" id="identifier" name="identifier" placeholder="Email or Username" required>

            <label for="password" class="visually-hidden">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
    </div>

    <script src="../assets/js/validation.js"></script>
</body>
</html>
