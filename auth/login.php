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

    <!-- Top-right Home button -->
    <a href="../index.html" class="top-right-link">🏠 Home</a>

    <!-- Centered login form -->
    <div class="login-container">
        <!-- Heading aligned to right but close to form -->
        <div class="form-heading">Login to ElectroStore</div>

        <form onsubmit="return validateLogin()" method="POST" action="login_process.php">
            <label for="identifier" class="visually-hidden">Email or Username</label>
            <input type="text" id="identifier" name="identifier" placeholder="Email or Username" required>

            <label for="password" class="visually-hidden">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>

            <button type="submit">Login</button>
        </form>

        <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
    </div>

    <!-- JS for validation -->
    <script src="../assets/js/validation.js"></script>
</body>
</html>
