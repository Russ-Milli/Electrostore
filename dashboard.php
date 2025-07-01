<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'database_conn.php';
$database = new Database();
$db = $database->connect();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dashboard</title>
</head>
<body>
    <h2>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</h2>
    
    <h3>Products</h3>
    <?php
    $query = "SELECT * FROM products";
    $stmt = $db->query($query);
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($products as $product) {
        echo "<div>";
        echo "<h4>{$product['name']}</h4>";
        echo "<p>{$product['description']}</p>";
        echo "<p>Price: \${$product['price']}</p>";
        echo "<p>Stock: {$product['stock']}</p>";
        echo "</div>";
    }
    ?>
    
    <a href="logout.php">Logout</a>
</body>
</html>