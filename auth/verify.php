<?php
session_start();
require_once '../database_conn.php';

$message = '';
$success = false;
$title = 'Email Verification';

if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = trim($_GET['token']);
    
    $database = new Database();
    $db = $database->connect();
    
    // Check if token exists and is not expired (24 hours)
    $query = "SELECT id, username, email, email_verified, verification_token FROM users 
              WHERE verification_token = :token 
              AND verification_token IS NOT NULL
              AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':token', $token);
    $stmt->execute();
    
    if ($stmt->rowCount() === 1) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user['email_verified'] == 1) {
            $message = "Your email has already been verified. You can now login to your account.";
            $success = true;
        } else {
            // Verify the user
            $updateQuery = "UPDATE users SET email_verified = 1, verification_token = NULL WHERE id = :user_id";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':user_id', $user['id']);
            
            if ($updateStmt->execute()) {
                $message = "Congratulations! Your email has been successfully verified. You can now login to your ElectroStore account.";
                $success = true;
                
                // Optionally, auto-login the user
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
            } else {
                $message = "There was an error verifying your email. Please try again or contact support.";
            }
        }
    } else {
        $message = "Invalid or expired verification link. Please request a new verification email or contact support.";
    }
} else {
    $message = "No verification token provided. Please check your email for the verification link.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title) ?> - ElectroStore</title>
    
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <!-- External CSS -->
    <link rel="stylesheet" href="../assets/css/style.css">
    
    <style>
        .verification-container {
            max-width: 500px;
            margin: 100px auto;
            padding: 40px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .verification-icon {
            font-size: 4rem;
            margin-bottom: 20px;
        }
        
        .success-icon {
            color: #28a745;
        }
        
        .error-icon {
            color: #dc3545;
        }
        
        .verification-message {
            font-size: 18px;
            line-height: 1.6;
            margin-bottom: 30px;
            color: #333;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #007bff, #0056b3);
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 10px;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,123,255,0.3);
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
            padding: 12px 30px;
            text-decoration: none;
            border-radius: 25px;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-block;
            margin: 10px;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        .auto-redirect {
            margin-top: 20px;
            color: #6c757d;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="verification-container">
        <div class="verification-icon">
            <?php if ($success): ?>
                <span class="success-icon">✓</span>
            <?php else: ?>
                <span class="error-icon">⚠</span>
            <?php endif; ?>
        </div>
        
        <h1 style="color: #333; margin-bottom: 20px;">
            <?= $success ? 'Email Verified!' : 'Verification Failed' ?>
        </h1>
        
        <div class="verification-message">
            <?= htmlspecialchars($message) ?>
        </div>
        
        <div class="action-buttons">
            <?php if ($success): ?>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="../index.php" class="btn-primary">Continue to ElectroStore</a>
                    <div class="auto-redirect">
                        <script>
                            let countdown = 5;
                            const redirectTimer = setInterval(() => {
                                countdown--;
                                document.querySelector('.auto-redirect').innerHTML = 
                                    `Redirecting to ElectroStore in ${countdown} seconds...`;
                                
                                if (countdown <= 0) {
                                    clearInterval(redirectTimer);
                                    window.location.href = '../index.php';
                                }
                            }, 1000);
                            
                            document.querySelector('.auto-redirect').innerHTML = 
                                `Redirecting to ElectroStore in ${countdown} seconds...`;
                        </script>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn-primary">Login Now</a>
                    <a href="../index.php" class="btn-secondary">Go to Home</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="btn-primary">Try Login Again</a>
                <a href="signup.php" class="btn-secondary">Create New Account</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>