<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - ElectroStore</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navigation Buttons -->
    <a href="login.php" class="top-left-back">← Back</a>
    <a href="../index.php" class="top-right-link">🏠 Home</a>

    <!-- Signup Form Container -->
    <div class="login-container">
        <h2>Create Your Account</h2>
        <form onsubmit="return validateSignup()" method="POST" action="signup_process.php">
            <label for="username" class="visually-hidden">Username</label>
            <input type="text" id="username" name="username" placeholder="Username" required>

            <label for="email" class="visually-hidden">Email</label>
            <input type="email" id="email" name="email" placeholder="Email" required>

            <label for="password" class="visually-hidden">Password</label>
            <input type="password" id="password" name="password" placeholder="Password" required>

            <label for="confirm" class="visually-hidden">Confirm Password</label>
            <input type="password" id="confirm" name="confirm" placeholder="Confirm Password" required>

            <button type="submit">Sign Up</button>
        </form>
        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <!-- JS for validation -->
    <script src="../assets/js/validation.js"></script>
</body>
</html>
