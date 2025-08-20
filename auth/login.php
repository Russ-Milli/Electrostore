<?php
session_start();

require_once '../database_conn.php';
require __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// SMTP Configuration - Add these to your config file or define them here
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'ryanthuku64@gmail.com';
$smtp_password = 'oxyw ugwr xabn skln';
$from_email = 'ryanthuku64@gmail.com';
$from_name = 'ElectroStore';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password = textdomain($_POST['password']);

    if (empty($identifier) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $database = new Database();
        $db = $database->connect();

        $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
        $query = $isEmail
            ? "SELECT id, username, email, password_hash, email_verified, verification_token FROM users WHERE email = :identifier"
            : "SELECT id, username, email, password_hash, email_verified, verification_token FROM users WHERE username = :identifier";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':identifier', $identifier);
        $stmt->execute();

        if ($stmt->rowCount() === 1) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($password, $user['password_hash'])) {
                // Check if email is verified
                if ($user['email_verified'] == 0) {
                    // Generate a new verification token if none exists or update existing one
                    $verification_token = bin2hex(random_bytes(32));
                    
                    // Update the token in database
                    $updateTokenQuery = "UPDATE users SET verification_token = :token WHERE id = :user_id";
                    $updateStmt = $db->prepare($updateTokenQuery);
                    $updateStmt->bindParam(':token', $verification_token);
                    $updateStmt->bindParam(':user_id', $user['id']);
                    $updateStmt->execute();
                    
                    // Send verification email with the new token
                    if (sendVerificationEmail($user['email'], $verification_token, $user['username'])) {
                        $success = "Please check your email and verify your account before logging in. A verification email has been sent to " . htmlspecialchars($user['email']);
                    } else {
                        $error = "Failed to send verification email. Please try again later.";
                    }
                } else {
                    // User is verified, proceed with login
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    header("Location: ../index.php");
                    exit();
                }
            } else {
                $error = "Invalid username/email or password.";
            }
        } else {
            $error = "Invalid username/email or password.";
        }
    }
}

function sendVerificationEmail($email, $token, $username) {
    global $smtp_host, $smtp_port, $smtp_username, $smtp_password, $from_email, $from_name;

    // Use the token passed from the calling function (don't generate a new one here)
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    $host = $_SERVER['HTTP_HOST'];
    $current_path = dirname($_SERVER['PHP_SELF']);
    $verificationLink = $protocol . $host . $current_path . "/verify.php?token=" . urlencode($token);
    
    $subject = "Email Verification Required";
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
    <title>Login - ElectroStore</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 1rem;
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

    <!-- Centered login form -->
    <div class="login-container">
        <!-- Updated form heading with link -->
        <div class="form-heading">
            <a href="../index.php" style="text-decoration: none; color: #333;">Login to <strong style="color: #007bff;">ElectroStore</strong></a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message">
                <?= $success ?>
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