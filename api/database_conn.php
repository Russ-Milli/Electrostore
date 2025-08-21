<?php
// api/database_conn.php - Vercel & Local compatible

// Environment-aware database configuration
function getDatabaseConfig() {
    return [
        'host'     => getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? 'localhost'),
        'username' => getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? 'root'),
        'password' => getenv('DB_PASS') ?: ($_ENV['DB_PASS'] ?? ''),
        'database' => getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? 'electrostore'),
        'port'     => getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? '3306'),
    ];
}

// Get database configuration
$dbConfig = getDatabaseConfig();
$servername = $dbConfig['host'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$dbname = $dbConfig['database'];

// Primary MySQLi connection (for backward compatibility)
try {
    $conn = new mysqli($servername, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
} catch (Exception $e) {
    error_log("Database connection error: " . $e->getMessage());
    
    if (isProduction()) {
        die("Service temporarily unavailable. Please try again later.");
    } else {
        die("Database connection error: " . $e->getMessage());
    }
}

// PDO connection function (recommended for new code)
function getPDOConnection() {
    $config = getDatabaseConfig();
    
    try {
        $dsn = "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4";
        
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => false, // For cloud databases
        ]);
        
        return $pdo;
        
    } catch (PDOException $e) {
        error_log("PDO connection failed: " . $e->getMessage());
        
        if (isProduction()) {
            die("Service temporarily unavailable. Please try again later.");
        } else {
            die("Database connection failed: " . $e->getMessage());
        }
    }
}

// Environment detection
function isProduction() {
    return !empty(getenv('VERCEL')) || 
           !empty(getenv('VERCEL_URL')) || 
           !empty($_ENV['VERCEL']) || 
           !empty($_ENV['VERCEL_URL']);
}

// Get base URL for the application
function getBaseUrl() {
    if (!empty(getenv('VERCEL_URL'))) {
        return 'https://' . getenv('VERCEL_URL');
    } elseif (!empty(getenv('APP_URL'))) {
        return getenv('APP_URL');
    } else {
        // Local development fallback
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        return $protocol . '://' . $_SERVER['HTTP_HOST'];
    }
}

// Helper function for image paths
function getImagePath($imagePath) {
    if (empty($imagePath)) return '/img/placeholder.jpg';
    
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

// Helper function for API responses
function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Helper function for error responses
function sendErrorResponse($message, $statusCode = 500) {
    sendJsonResponse([
        'success' => false,
        'error' => $message
    ], $statusCode);
}

// Test database connection (optional - remove in production)
if (isProduction() === false && isset($_GET['test_db'])) {
    try {
        $testPdo = getPDOConnection();
        echo "Database connection successful!<br>";
        echo "Host: " . $dbConfig['host'] . "<br>";
        echo "Database: " . $dbConfig['database'] . "<br>";
        echo "Environment: " . (isProduction() ? 'Production' : 'Development');
    } catch (Exception $e) {
        echo "Database test failed: " . $e->getMessage();
    }
    exit;
}
?>