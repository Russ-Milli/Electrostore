<?php
// api/database_conn.php - Local + Vercel friendly

// Always fallback properly
$servername = !empty($_ENV['DB_HOST']) ? $_ENV['DB_HOST'] : 'localhost';
$username   = !empty($_ENV['DB_USER']) ? $_ENV['DB_USER'] : 'root';
$password   = !empty($_ENV['DB_PASS']) ? $_ENV['DB_PASS'] : '';
$dbname     = !empty($_ENV['DB_NAME']) ? $_ENV['DB_NAME'] : 'electrostore';

// Database connection with error handling
try {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    $conn->set_charset("utf8mb4");

} catch (Exception $e) {
    if (isset($_ENV['VERCEL']) || isset($_ENV['VERCEL_URL'])) {
        die("Service temporarily unavailable. Please try again later.");
    } else {
        die("Database connection error: " . $e->getMessage());
    }
}

// Optional: PDO connection (recommended)

function getPDOConnection() {
    $host = 'localhost';
    $dbname = 'electrostore';
    $username = 'root';
    $password = '';
    
    try {
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
                       $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        return $pdo;
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        die("Database connection failed. Please try again later.");
    }
}


function isProduction() {
    return isset($_ENV['VERCEL']) || isset($_ENV['VERCEL_URL']);
}

// Helper function to get base URL
function getBaseUrl() {
    if (isset($_ENV['VERCEL_URL'])) {
        return 'https://' . $_ENV['VERCEL_URL'];
    } elseif (isset($_ENV['APP_URL'])) {
        return $_ENV['APP_URL'];
    } else {
        // Local development fallback
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'];
    }
}

// Helper function to fix image paths
function getImagePath($imagePath) {
    // If path already starts with /, return as is
    if (strpos($imagePath, '/') === 0) {
        return $imagePath;
    }
    
    // If path starts with 'img/', add leading slash
    if (strpos($imagePath, 'img/') === 0) {
        return '/' . $imagePath;
    }
    
    // If it's just a filename, prepend /img/
    return '/img/' . $imagePath;
}




?>

