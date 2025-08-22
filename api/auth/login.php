<?php
session_start();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// FIXED: Correct file paths based on your project structure
require_once(__DIR__ . '/../database_conn.php');     // Same folder as login.php
require_once(__DIR__ . '/auth_check.php');        // Same folder as login.php

// PHPMailer namespaces (must be at top-level, not inside if-block)
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Only use PHPMailer if it's actually installed
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once(__DIR__ . '/../vendor/autoload.php');
    $phpmailer_available = true;
} else {
    $phpmailer_available = false;
}

// Email configuration (move to environment variables in production)
$smtp_host = 'smtp.gmail.com';
$smtp_port = 465;
$smtp_username = 'ryanthuku64@gmail.com';
$smtp_password = 'oxyw ugwr xabn skln'; // Consider using app-specific password
$from_email = 'ryanthuku64@gmail.com';
$from_name = 'ElectroStore';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $identifier = trim($_POST['identifier']);
    $password   = trim($_POST['password']);

    // Basic validation
    if (empty($identifier) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $db = getPDOConnection();

            // Detect if identifier is email or username
            $isEmail = filter_var($identifier, FILTER_VALIDATE_EMAIL);
            
            if ($isEmail) {
                $query = "SELECT id, username, email, password_hash, email_verified, verification_token 
                         FROM users WHERE email = :identifier LIMIT 1";
            } else {
                $query = "SELECT id, username, email, password_hash, email_verified, verification_token 
                         FROM users WHERE username = :identifier LIMIT 1";
            }

            $stmt = $db->prepare($query);
            $stmt->bindParam(':identifier', $identifier, PDO::PARAM_STR);
            $stmt->execute();

            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);

                // Verify password
                if (password_verify($password, $user['password_hash'])) {
                    
                    // Check if email is verified
                    if ($user['email_verified'] == 0) {
                        // Generate new verification token
                        $verification_token = bin2hex(random_bytes(32));
                        
                        // Update token in database
                        $updateStmt = $db->prepare("UPDATE users SET verification_token = :token WHERE id = :user_id");
                        $updateStmt->execute([
                            ':token'   => $verification_token,
                            ':user_id' => $user['id']
                        ]);

                        // Send verification email
                        if ($phpmailer_available && sendVerificationEmail($user['email'], $verification_token, $user['username'])) {
                            $success = "Please verify your email first. A new verification link has been sent to " . htmlspecialchars($user['email']);
                        } else {
                            $error = "Email not verified. Please check your email for verification link.";
                        }
                    } else {
                        // Successful login - set session variables
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['email'] = $user['email'];
                        
                        // Redirect to dashboard or home page
                        header("Location: public/index.php");
                        exit();
                    }
                } else {
                    $error = "Invalid username/email or password.";
                }
            } else {
                $error = "Invalid username/email or password.";
            }
        } catch (PDOException $e) {
            error_log("Database error in login: " . $e->getMessage());
            $error = "Database error. Please try again later.";
        } catch (Exception $e) {
            error_log("General error in login: " . $e->getMessage());
            $error = "An error occurred. Please try again.";
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
        
        // Construct verification link - adjusted for your project structure
        $verificationLink = $protocol . $host . "/../auth/verify.php?token=" . urlencode($token);

        $subject = "Email Verification Required - ElectroStore";
        $message = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Email Verification</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #007bff; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; background: #f8f9fa; }
                .button { 
                    display: inline-block; 
                    background: #007bff; 
                    color: white; 
                    padding: 12px 24px; 
                    text-decoration: none; 
                    border-radius: 5px;
                    margin: 10px 0;
                }
                .footer { padding: 20px; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>ElectroStore</h1>
                </div>
                <div class='content'>
                    <h2>Hello " . htmlspecialchars($username) . ",</h2>
                    <p>Please verify your email address to complete your account setup.</p>
                    <p><a href='" . $verificationLink . "' class='button'>Verify Email Address</a></p>
                   
            
                </div>
                <div class='footer'>
                    <p>If you didn't create an account with ElectroStore, please ignore this email.</p>
                    <p>This link will expire in 24 hours for security reasons.</p>
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
    <title>Login - ElectroStore</title>
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- CSS - Adjusted path for auth folder -->
    <link rel="stylesheet" href="../public/assets/css/style.css">
    
    <style>
        /* Fallback styles in case external CSS doesn't load */
        body {
            font-family: 'Poppins', Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #f2f5f0ff 100%);
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
        
        input[type="text"], input[type="password"] {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="text"]:focus, input[type="password"]:focus {
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
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        button:hover {
            background: #0056b3;
        }
        
        p {
            text-align: center;
            margin-top: 20px;
            color: #666;
        }
        
        p a {
            color: #007bff;
            text-decoration: none;
        }
        
        p a:hover {
            text-decoration: underline;
        }
        
        .loading {
            opacity: 0.6;
            pointer-events: none;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="form-heading">
            <a href="public/index.php">Login to <strong>ElectroStore</strong></a>
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

        <form id="loginForm" method="POST" action="login.php" onsubmit="return handleSubmit()">
            <input type="text" 
                   name="identifier" 
                   id="identifier"
                   placeholder="Email or Username" 
                   required 
                   autocomplete="username">
            
            <input type="password" 
                   name="password" 
                   id="password"
                   placeholder="Password" 
                   required 
                   autocomplete="current-password">
            
            <button type="submit" id="loginBtn">Login</button>
        </form>

        <p>Don't have an account? <a href="signup.php">Sign up here</a></p>
        
    </div>

    <script>
        function handleSubmit() {
            const form = document.getElementById('loginForm');
            const btn = document.getElementById('loginBtn');
            const identifier = document.getElementById('identifier').value.trim();
            const password = document.getElementById('password').value;

            // Basic validation
            if (!identifier || !password) {
                alert('Please fill in all fields.');
                return false;
            }

            // Show loading state
            btn.textContent = 'Logging in...';
            form.classList.add('loading');
            
            return true;
        }

        // Remove loading state if there's an error and page reloads
        window.addEventListener('load', function() {
            const form = document.getElementById('loginForm');
            const btn = document.getElementById('loginBtn');
            
            if (form.classList.contains('loading')) {
                form.classList.remove('loading');
                btn.textContent = 'Login';
            }
        });
    </script>
</body>
</html>