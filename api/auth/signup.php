<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FIXED: Correct file paths based on your actual project structure (api/auth folder)
require_once(__DIR__ . '/../database_conn.php');     // Same folder as signup.php
require_once(__DIR__ . '/auth_check.php');        // Same folder as signup.php

// PHPMailer namespaces (must be outside any function or conditional block)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only use PHPMailer if it's actually installed
if (file_exists(__DIR__ . '/../../vendor/autoload.php')) {
    require_once(__DIR__ . '/../../vendor/autoload.php');
    $phpmailer_available = true;
} else {
    $phpmailer_available = false;
}

// Email configuration (move to environment variables in production)
$smtp_host = 'smtp.gmail.com';
$smtp_port = 587;
$smtp_username = 'ryanthuku64@gmail.com';
$smtp_password = 'oxyw ugwr xabn skln'; // Consider using app-specific password
$from_email = 'ryanthuku64@gmail.com';
$from_name = 'ElectroStore';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);

    // Enhanced validation
    if (empty($username) || empty($email) || empty($password)) {
        $error = "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long.";
    } elseif (strlen($username) < 3 || strlen($username) > 20) {
        $error = "Username must be between 3 and 20 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = "Username can only contain letters, numbers, and underscores.";
    } else {
        try {
            $db = getPDOConnection();

            // Check if email or username already exists
            $stmt = $db->prepare("SELECT id FROM users WHERE email = :email OR username = :username LIMIT 1");
            $stmt->execute([':email' => $email, ':username' => $username]);

            if ($stmt->rowCount() > 0) {
                $error = "Email or Username already exists.";
            } else {
                // Hash password and generate verification token
                $password_hash = password_hash($password, PASSWORD_BCRYPT);
                $verification_token = bin2hex(random_bytes(32));

                $insertStmt = $db->prepare("INSERT INTO users (username, email, password_hash, verification_token, email_verified) 
                                            VALUES (:username, :email, :password_hash, :token, 0)");
                $insertStmt->execute([
                    ':username'      => $username,
                    ':email'         => $email,
                    ':password_hash' => $password_hash,
                    ':token'         => $verification_token
                ]);

                // Send verification email
                if ($phpmailer_available && sendVerificationEmail($email, $verification_token, $username)) {
                    $success = "Registration successful! A verification link was sent to " . htmlspecialchars($email);
                } else {
                    $success = "Registration successful! Please check your email for verification instructions.";
                }
            }
        } catch (PDOException $e) {
            error_log("Database error in signup: " . $e->getMessage());
            $error = "Registration failed. Please try again later.";
        } catch (Exception $e) {
            error_log("General error in signup: " . $e->getMessage());
            $error = "An error occurred during registration. Please try again.";
        }
    }
}

function sendVerificationEmail($email, $token, $username) {
    global $smtp_host, $smtp_port, $smtp_username, $smtp_password, $from_email, $from_name, $phpmailer_available;
    
    if (!$phpmailer_available) {
        return false;
    }

    try {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'];
        
        // FIXED: Correct verification link path for api/auth structure
        $verificationLink = $protocol . $host . "/../api/auth/verify.php?token=" . urlencode($token);

        $subject = "Email Verification Required - ElectroStore";
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verification</title>
            <style>
                body { font-family: 'Poppins', Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0; }
                .content { padding: 30px 20px; background: #f8f9fa; }
                .button { 
                    display: inline-block; 
                    background: #007bff; 
                    color: white !important; 
                    padding: 12px 24px; 
                    text-decoration: none; 
                    border-radius: 5px;
                    margin: 15px 0;
                    font-weight: bold;
                }
                .footer { padding: 20px; font-size: 12px; color: #666; background: #e9ecef; border-radius: 0 0 8px 8px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1 style='margin: 0;'>Welcome to ElectroStore!</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($username) . ",</h2>
                    <p>Thank you for registering with ElectroStore! We're excited to have you join our community.</p>
                    <p>To complete your registration and start shopping, please verify your email address by clicking the button below:</p>
                    <p style='text-align: center;'><a href='" . $verificationLink . "' class='button'>Verify Email Address</a></p>
                    
                    <p><strong>Why verify your email?</strong></p>
                    <ul>
                        <li>Secure your account</li>
                        <li>Receive order updates</li>
                        <li>Get exclusive offers and promotions</li>
                    </ul>
                </div>
                <div class='footer'>
                    <p><strong>Need help?</strong> Contact our support team if you have any questions.</p>
                    <p>If you didn't create an account with ElectroStore, please ignore this email.</p>
                    <p>This verification link will expire in 24 hours for security reasons.</p>
                </div>
            </div>
        </body>
        </html>";

        $mail = new PHPMailer(true);
        
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $smtp_host;
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp_username;
        $mail->Password   = $smtp_password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = $smtp_port;

        // Recipients
        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($email, $username);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        return $mail->send();
        
    } catch (Exception $e) {
        error_log("Mailer Error: " . $e->getMessage());
        return false;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup - ElectroStore</title>

    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">

    <!-- FIXED: CSS path for api/auth folder structure -->
    <link rel="stylesheet" href="../../public/assets/css/style.css">

    <style>
        /* Fallback styles in case external CSS doesn't load */
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #d0e5ebff 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            width: 100%;
        }
        
        .form-heading {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .form-heading a {
            text-decoration: none;
            color: #333;
            font-size: 24px;
        }
        
        .form-heading strong {
            color: #007bff;
        }
        
        .success-message {
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .error-message {
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        form {
            display: flex;
            flex-direction: column;
        }
        
        .visually-hidden {
            position: absolute !important;
            width: 1px !important;
            height: 1px !important;
            padding: 0 !important;
            margin: -1px !important;
            overflow: hidden !important;
            clip: rect(0, 0, 0, 0) !important;
            white-space: nowrap !important;
            border: 0 !important;
        }
        
        input[type="text"], input[type="email"], input[type="password"] {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus {
            outline: none;
            border-color: #007bff;
            box-shadow: 0 0 0 2px rgba(0, 123, 255, 0.25);
        }
        
        button {
            background: #007bff;
            color: white;
            padding: 15px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
        }
        
        button:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        p {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        p a {
            color: #007bff;
            text-decoration: none;
            font-weight: 500;
        }
        
        p a:hover {
            text-decoration: underline;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
        
        .password-strength {
            font-size: 12px;
            margin-top: -15px;
            margin-bottom: 15px;
            padding: 5px;
        }
        
        .weak { color: #dc3545; }
        .medium { color: #ffc107; }
        .strong { color: #28a745; }
    </style>
</head>
<body>

    <div class="login-container">
        <div class="form-heading">
            <!-- FIXED: Link path for api/auth folder structure -->
            <a href="../../index.php" style="text-decoration: none; color: #333;">Signup to <strong style="color: #007bff;">ElectroStore</strong></a>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="success-message"><?= $success ?></div>
        <?php endif; ?>

        <!-- Enhanced form with better validation -->
        <form id="signupForm" onsubmit="return handleSubmit()" method="POST" action="signup.php">
            <label for="username" class="visually-hidden">Username</label>
            <input type="text" 
                   id="username" 
                   name="username" 
                   placeholder="Username (3-20 characters)" 
                   required
                   minlength="3"
                   maxlength="20"
                   autocomplete="username">

            <label for="email" class="visually-hidden">Email</label>
            <input type="email" 
                   id="email" 
                   name="email" 
                   placeholder="Email Address" 
                   required
                   autocomplete="email">

            <label for="password" class="visually-hidden">Password</label>
            <input type="password" 
                   id="password" 
                   name="password" 
                   placeholder="Password (min. 6 characters)" 
                   required
                   minlength="6"
                   autocomplete="new-password">
            
            <div id="passwordStrength" class="password-strength" style="display: none;"></div>

            <button type="submit" id="signupBtn">Create Account</button>
        </form>

        <p>Already have an account? <a href="login.php">Login here</a></p>
    </div>

    <!-- FIXED: JavaScript path for api/auth folder structure -->
    <script src="../../public/assets/js/validation.js"></script>
    <script>
        // Enhanced validation and user experience
        function handleSubmit() {
            const form = document.getElementById('signupForm');
            const btn = document.getElementById('signupBtn');
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            // Client-side validation
            if (username.length < 3 || username.length > 20) {
                alert('Username must be between 3 and 20 characters.');
                return false;
            }

            if (!/^[a-zA-Z0-9_]+$/.test(username)) {
                alert('Username can only contain letters, numbers, and underscores.');
                return false;
            }

            if (password.length < 6) {
                alert('Password must be at least 6 characters long.');
                return false;
            }

            // Show loading state
            btn.textContent = 'Creating Account...';
            form.classList.add('loading');
            
            return true;
        }

        // Password strength indicator
        document.getElementById('password').addEventListener('input', function() {
            const password = this.value;
            const strengthDiv = document.getElementById('passwordStrength');
            
            if (password.length === 0) {
                strengthDiv.style.display = 'none';
                return;
            }
            
            strengthDiv.style.display = 'block';
            
            let strength = 0;
            let feedback = '';
            
            if (password.length >= 6) strength++;
            if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            
            if (strength < 2) {
                strengthDiv.className = 'password-strength weak';
                feedback = 'Weak password';
            } else if (strength < 3) {
                strengthDiv.className = 'password-strength medium';
                feedback = 'Medium strength';
            } else {
                strengthDiv.className = 'password-strength strong';
                feedback = 'Strong password';
            }
            
            strengthDiv.textContent = feedback;
        });

        // Remove loading state if there's an error and page reloads
        window.addEventListener('load', function() {
            const form = document.getElementById('signupForm');
            const btn = document.getElementById('signupBtn');
            
            if (form.classList.contains('loading')) {
                form.classList.remove('loading');
                btn.textContent = 'Create Account';
            }
        });

        // Username validation on input
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            if (username && !/^[a-zA-Z0-9_]*$/.test(username)) {
                this.setCustomValidity('Username can only contain letters, numbers, and underscores.');
            } else {
                this.setCustomValidity('');
            }
        });
    </script>
</body>
</html>