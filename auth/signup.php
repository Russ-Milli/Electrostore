<?php
session_start();

if (isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

require_once '../database_conn.php';
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// SMTP Configuration - Add these to your config file or define them here
$smtp_host = 'smtp.gmail.com'; // Change to your SMTP host
$smtp_port = 587;
$smtp_username = 'ryanthuku64@gmail.com'; // Your email
$smtp_password = 'oxyw ugwr xabn skln'; // Your app password
$from_email = 'ryanthuku64@gmail.com';
$from_name = 'ElectroStore';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // Basic validation
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = "Please fill in all fields.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $database = new Database();
        $db = $database->connect();

        // Check if username or email already exists
        $checkQuery = "SELECT COUNT(*) FROM users WHERE username = :username OR email = :email";
        $checkStmt = $db->prepare($checkQuery);
        $checkStmt->bindParam(':username', $username);
        $checkStmt->bindParam(':email', $email);
        $checkStmt->execute();

        if ($checkStmt->fetchColumn() > 0) {
            $error = "Username or email already exists.";
        } else {
            // Generate verification token
            $verification_token = bin2hex(random_bytes(32));
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            // Insert user with email_verified = 0 and the verification token
            $insertQuery = "INSERT INTO users (username, email, password_hash, email_verified, verification_token, created_at) 
                           VALUES (:username, :email, :password_hash, 0, :verification_token, NOW())";
            $insertStmt = $db->prepare($insertQuery);
            $insertStmt->bindParam(':username', $username);
            $insertStmt->bindParam(':email', $email);
            $insertStmt->bindParam(':password_hash', $password_hash);
            $insertStmt->bindParam(':verification_token', $verification_token);

            if ($insertStmt->execute()) {
                // Try to send verification email with the generated token
                $emailSent = sendVerificationEmail($email, $verification_token, $username);
                
                if (!$emailSent) {
                    // Try fallback simple mail function
                    $emailSent = sendVerificationEmail($email, $verification_token, $username);
                }
                
                if ($emailSent) {
                    $success = "Account created successfully! Please check your email (" . htmlspecialchars($email) . ") and click the verification link to activate your account.";
                } else {
                    // Still create account but show different message
                    $error = "Account created but failed to send verification email. Please contact support or try logging in to resend the verification email.";
                    error_log("Failed to send verification email to: " . $email . " for user: " . $username);
                }
            } else {
                $error = "Error creating account. Please try again.";
            }
        }
    }
}

function sendVerificationEmail($email, $token, $username) {
    global $smtp_host, $smtp_port, $smtp_username, $smtp_password, $from_email, $from_name;

    // Use the token passed from the calling function
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $current_path = dirname($_SERVER['PHP_SELF']);
    $verificationLink = $protocol . $host . $current_path . "/verify.php?token=" . urlencode($token);
    
    $subject = "Email Verification Required - ElectroStore";
    $message = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <title>Email Verification</title>
        <style>
            body { font-family: 'Poppins', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 600px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
            .content { background: #f8f9fa; padding: 30px; border-radius: 0 0 10px 10px; }
            .verify-btn { 
                display: inline-block; 
                background: #10dce7ff; 
                color: white; 
                padding: 15px 30px; 
                text-decoration: none; 
                border-radius: 5px; 
                font-weight: bold;
                margin: 20px 0;
            }
            .verify-btn:hover { background: #0ccddeff; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Welcome to ElectroStore!</h1>
            </div>
            <div class='content'>
                <h2>Hello " . htmlspecialchars($username) . ",</h2>
                <p>Thank you for creating an account with ElectroStore. To complete your registration, please verify your email address by clicking the button below:</p>
                
                <div style='text-align: center;'>
                    <a href='" . $verificationLink . "' class='verify-btn'>Verify Email Address</a>
                </div>
                
               
                
                <p><strong>Note:</strong> This verification link will expire in 24 hours for security reasons.</p>
                
                <p>If you didn't create an account with ElectroStore, please ignore this email.</p>
                
                <hr style='margin: 30px 0; border: none; border-top: 1px solid #dee2e6;'>
                <p style='color: #6c757d; font-size: 14px;'>
                    Best regards,<br>
                    The ElectroStore Team
                </p>
            </div>
        </div>
    </body>
    </html>
    ";

    $mail = new PHPMailer(true);
    try {
        //Server settings
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_username;
        $mail->Password = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $smtp_port;

        //Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($email);

        //Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $message;

        // Send the email
        if ($mail->send()) {
            return true;
        } else {
            error_log("PHPMailer failed to send: " . $mail->ErrorInfo);
            return false;
        }
    } catch (Exception $e) {
        error_log("Mailer Error: " . $mail->ErrorInfo);
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - ElectroStore</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 1rem;
            text-align: center;
        }
        
        .error-message {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>

    <!-- Centered signup form -->
    <div class="login-container">
        <!-- Updated form heading with link -->
        <div class="form-heading">
            <a href="../index.php" style="text-decoration: none; color: #333;">Sign Up for <strong style="color: #007bff;">ElectroStore</strong></a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?= $success ?>
                <br><br>
                <small>Didn't receive the email? Check your spam folder or <a href="login.php">try logging in</a> to resend.</small>
            </div>
        <?php else: ?>
            <form method="POST" action="signup.php" onsubmit="return validateSignup()">
                <label for="username" class="visually-hidden">Username</label>
                <input type="text" id="username" name="username" placeholder="Username" required 
                       value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>">

                <label for="email" class="visually-hidden">Email</label>
                <input type="email" id="email" name="email" placeholder="Email" required 
                       value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>">

                <label for="password" class="visually-hidden">Password</label>
                <input type="password" id="password" name="password" placeholder="Password (min. 8 characters)" required>

                <label for="confirm_password" class="visually-hidden">Confirm Password</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>

                <button type="submit">Create Account</button>
            </form>
        <?php endif; ?>

        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <script src="../assets/js/validation.js"></script>
</body>
</html>