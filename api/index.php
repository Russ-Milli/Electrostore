<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);



// Security headers
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

$isLoggedIn = isset($_SESSION['user_id']);

// Clean up includes - remove duplicates
require_once __DIR__ . '/database_conn.php';


try {
    $conn = getPDOConnection();
    
    // Fetch 8 products with error handling
    $query = "SELECT id, name, price, image_path FROM products ORDER BY created_at DESC LIMIT 8";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $products = [];
}

// Handle goodbye message
$message = "";
$alertType = "";

if (isset($_GET['goodbye'])) {
    $message = "Your account has been successfully deleted. Goodbye! 🥲";
    $alertType = 'info';
}

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'], $_POST['product_id'])) {
    if (!$isLoggedIn) {
        header('Location: index.php?login_required=1');
        exit();
    }

    $productId = (int)$_POST['product_id'];
    
    try {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($product) {
            $item = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => 1
            ];

            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            $found = false;
            foreach ($_SESSION['cart'] as &$cart_item) {
                if ($cart_item['id'] === $item['id']) {
                    $cart_item['quantity'] += 1;
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $_SESSION['cart'][] = $item;
            }

            header('Location: index.php?added=1');
            exit();
        }
    } catch (PDOException $e) {
        error_log("Cart error: " . $e->getMessage());
        header('Location: index.php?error=1');
        exit();
    }
}

// Handle messages
if (isset($_GET['login_required'])) {
    $message = "You must be logged in to add items to the cart.";
    $alertType = 'warning';
} elseif (isset($_GET['added'])) {
    $message = "Product added to cart!";
    $alertType = 'success';
} elseif (isset($_GET['error'])) {
    $message = "An error occurred. Please try again.";
    $alertType = 'danger';
}

// Calculate cart count
$cartCount = 0;
if (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cartCount += $item['quantity'];
    }
}


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>ElectroStore - Home</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <meta content="Electronics Store - HP Devices" name="description">
    <meta content="electrostore, hp, laptops, computers" name="keywords">

    <!-- Favicon -->
    <link href="../public/assets/img/favicon.ico" rel="icon">

    <!-- Preconnect for faster loading -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com">

    <!-- Google Web Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet"> 

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Bootstrap CSS -->
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/css/bootstrap.min.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="../public/assets/lib/owlcarousel/owlcarousel/owl.carousel.css" rel="stylesheet">

    <!-- Main Stylesheet - Fixed Path -->
    <link href="../public/css/style.css" rel="stylesheet">
    
    <!-- Critical CSS for immediate loading -->
    <style>
        /* Critical styles that must load immediately */
        body {
            font-family: 'Poppins', sans-serif !important;
        }
        
        .alert {
            margin-bottom: 0;
            border-radius: 0;
            animation: slideDown 0.3s ease-out;
        }
        
        @keyframes slideDown {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            padding: 2px 6px;
            font-size: 12px;
            font-weight: bold;
            min-width: 18px;
            text-align: center;
        }
        
        .product-item {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .product-item:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.1);
        }
        
        .btn-cart {
            transition: all 0.3s ease;
        }
        
        .btn-cart:hover {
            background-color: #007bff !important;
            color: white !important;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>

<body>
    <!-- Alert Messages -->
    <?php if ($message): ?>
        <div class="alert alert-<?= $alertType ?> alert-dismissible fade show text-center" role="alert">
            <?= htmlspecialchars($message) ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <!-- Topbar Start -->
    <div class="container-fluid">
        <div class="row align-items-center py-3 px-xl-5">
            <div class="col-lg-3 d-none d-lg-block">
                <a href="index.php" class="text-decoration-none">
                    <h1 class="m-0 display-6 font-weight-semi-bold">
                        <span class="text-primary font-weight-bold border px-3 mr-1">E</span>lectrostore
                    </h1>
                </a>
            </div>
            <div class="col-lg-3 col-6 text-right">
                <a href="/api/cartpg.php" class="btn border position-relative">
                    <i class="fas fa-shopping-cart text-primary"></i>
                    <?php if ($cartCount > 0): ?>
                        <span class="cart-badge"><?= $cartCount ?></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar Start -->
    <div class="container-fluid mb-5">
        <div class="row border-top px-xl-5">
            <div class="col-lg-12">
                <nav class="navbar navbar-expand-lg bg-light navbar-light py-3 py-lg-0 px-0">
                    <a href="index.php" class="text-decoration-none d-block d-lg-none">
                        <h1 class="m-0 display-5 font-weight-semi-bold">
                            <span class="text-primary font-weight-bold border px-3 mr-1">E</span>lectrostore
                        </h1>
                    </a>
                    <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                        <span class="navbar-toggler-icon"></span>
                    </button>
                    <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                        <div class="navbar-nav mr-auto py-0">
                            <a href="index.php" class="nav-item nav-link active">Home</a>
                            <a href="/api/cartpg.php" class="nav-item nav-link">Shopping Cart</a>
                            <a href="/api/contact.php" class="nav-item nav-link">Contact</a>
                        </div>
                        <div class="navbar-nav ml-auto py-0">
                            <?php if ($isLoggedIn): ?>
                                <span class="nav-item nav-link">Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'User') ?>!</span>
                                <a href="/api/profile.php" class="nav-item nav-link">Profile</a>
                                <a href="/api/auth/logout.php" class="nav-item nav-link">Logout</a>
                            <?php else: ?>
                                <a href="/api/auth/login.php" class="nav-item nav-link">Login</a>
                                <a href="/api/auth/signup.php" class="nav-item nav-link">Register</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </nav>
            </div>
        </div>
    </div>
    <!-- Navbar End -->

    <!-- Carousel Start -->
    <div class="container-fluid mb-5">
        <div class="row border-top px-xl-5">
            <div class="col-lg-12">
                <div id="header-carousel" class="carousel slide" data-ride="carousel">
                    <div class="carousel-inner">
                        <div class="carousel-item active" style="height: 410px;">
                            <img class="img-fluid w-100 h-100" src="/../public/img/carousel-1.jpg" alt="HP Devices" style="object-fit: cover;">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                                <div class="p-3" style="max-width: 700px;">
                                    <h3 class="display-4 text-white font-weight-semi-bold mb-4">Reliable HP Devices for Work and Homes</h3>
                                    
                                </div>
                            </div>
                        </div>
                        <div class="carousel-item" style="height: 410px;">
                            <img class="img-fluid w-100 h-100" src="/../public/img/carousel-2.jpg" alt="HP Innovation" style="object-fit: cover;">
                            <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                                <div class="p-3" style="max-width: 700px;">
                                    <h3 class="display-4 text-white font-weight-semi-bold mb-4">HP Innovation at Your Fingertips</h3>
                                    
                                </div>
                            </div>
                        </div>
                    </div>
                    <a class="carousel-control-prev" href="#header-carousel" data-slide="prev">
                        <div class="btn btn-dark" style="width: 45px; height: 45px;">
                            <span class="carousel-control-prev-icon mb-n2"></span>
                        </div>
                    </a>
                    <a class="carousel-control-next" href="#header-carousel" data-slide="next">
                        <div class="btn btn-dark" style="width: 45px; height: 45px;">
                            <span class="carousel-control-next-icon mb-n2"></span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    <!-- Carousel End -->

    <!-- Featured Start -->
    <div class="container-fluid pt-5">
        <div class="row px-xl-5 pb-3">
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <h1 class="fa fa-check text-primary m-0 mr-3"></h1>
                    <h5 class="font-weight-semi-bold m-0">Quality Product</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <h1 class="fa fa-shipping-fast text-primary m-0 mr-2"></h1>
                    <h5 class="font-weight-semi-bold m-0">Free Shipping</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <h1 class="fas fa-exchange-alt text-primary m-0 mr-3"></h1>
                    <h5 class="font-weight-semi-bold m-0">14-Day Return</h5>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                <div class="d-flex align-items-center border mb-4" style="padding: 30px;">
                    <h1 class="fa fa-phone-volume text-primary m-0 mr-3"></h1>
                    <h5 class="font-weight-semi-bold m-0">24/7 Support</h5>
                </div>
            </div>
        </div>
    </div>
    <!-- Featured End -->

    <!-- Products Start -->

    

    
     <div class="container-fluid pt-5">
        <div class="text-center mb-4">
            <h2 class="section-title px-5"><span class="px-2">Products</span></h2>
        </div>
        <div class="row px-xl-5 pb-3">
            <?php foreach ($products as $product): ?>
                <div class="col-lg-3 col-md-6 col-sm-12 pb-1">
                    <div class="card product-item border-0 mb-4">
                        <div class="card-header product-img position-relative overflow-hidden bg-transparent border p-0">
                            <img class="img-fluid w-100" src="<?=  htmlspecialchars($product['image_path']) ?>" alt="<?= htmlspecialchars($product['name']) ?>">
                        </div>
                        <div class="card-body border-left border-right text-center p-0 pt-4 pb-3">
                            <h6 class="text-truncate mb-3"><?= htmlspecialchars($product['name']) ?></h6>
                            <div class="d-flex justify-content-center">
                                <h6>$<?= number_format($product['price'], 2) ?></h6>
                            </div>
                        </div>
                        <div class="card-footer d-flex justify-content-between bg-light border">
                            <a href="/api/productpg.php?id=<?= $product['id'] ?>" class="btn btn-sm text-dark p-0">
                                <i class="fas fa-eye text-primary mr-1"></i>
                                View Detail
                            </a>

                            <form method="post" style="margin: 0; padding: 0;">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button
                                    type="submit"
                                    name="add_to_cart"
                                    class="btn btn-sm text-dark p-0"
                                    style="background: none; border: none; cursor: pointer;"
                                                              >
                                    <i class="fas fa-shopping-cart text-primary mr-1"></i>
                                    Add To Cart
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <!-- Products End -->

    <!-- Footer Start -->
    <div class="container-fluid bg-secondary text-dark mt-5 pt-5">
        <div class="row px-xl-5 pt-5">
            <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
                <a href="index.php" class="text-decoration-none">
                    <h1 class="mb-4 display-5 font-weight-semi-bold">
                        <span class="text-primary font-weight-bold border border-white px-3 mr-1">E</span>lectrostore
                    </h1>
                </a>
                <p class="mb-2"><i class="fa fa-map-marker-alt text-primary mr-3"></i>123 ElectroStore Avenue, Tech City</p>
                <p class="mb-2"><i class="fa fa-envelope text-primary mr-3"></i>info@electrostore.com</p>
                <p class="mb-0"><i class="fa fa-phone-alt text-primary mr-3"></i>+012 345 67890</p>
            </div>
            <div class="col-lg-8 col-md-12">
                <div class="row">
                    <div class="col-md-4 mb-5">
                        <h5 class="font-weight-bold text-dark mb-4">Quick Links</h5>
                        <div class="d-flex flex-column justify-content-start">
                            <a class="text-dark mb-2" href="index.php"><i class="fa fa-angle-right mr-2"></i>Home</a>
                            <a class="text-dark mb-2" href="api/cartpg.php"><i class="fa fa-angle-right mr-2"></i>Shopping Cart</a>
                            <a class="text-dark" href="api/contact.php"><i class="fa fa-angle-right mr-2"></i>Contact Us</a>
                        </div>
                    </div>
                    <div class="col-md-4 mb-5">
                        <h5 class="font-weight-bold text-dark mb-4">Account</h5>
                        <div class="d-flex flex-column justify-content-start">
                            <?php if ($isLoggedIn): ?>
                                <a class="text-dark mb-2" href="profile.php"><i class="fa fa-angle-right mr-2"></i>My Account</a>
                                <a class="text-dark mb-2" href="orders.php"><i class="fa fa-angle-right mr-2"></i>Order History</a>
                                <a class="text-dark" href="/api/auth/logout.php"><i class="fa fa-angle-right mr-2"></i>Logout</a>
                            <?php else: ?>
                                <a class="text-dark mb-2" href="/api/auth/login.php"><i class="fa fa-angle-right mr-2"></i>Login</a>
                                <a class="text-dark mb-2" href="/api/auth/signup.php"><i class="fa fa-angle-right mr-2"></i>Register</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-md-4 mb-5">
                        <h5 class="font-weight-bold text-dark mb-4">Newsletter</h5>
                        <form action="newsletter.php" method="POST">
                            <div class="form-group">
                                <input type="text" class="form-control border-0 py-4" name="name" placeholder="Your Name" required />
                            </div>
                            <div class="form-group">
                                <input type="email" class="form-control border-0 py-4" name="email" placeholder="Your Email" required />
                            </div>
                            <div>
                                <button class="btn btn-primary btn-block border-0 py-3" type="submit">Subscribe Now</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-white text-center py-3">
        &copy; 2025 ElectroStore. All rights reserved.
    </footer>
    <!-- Footer End -->

    <!-- Back to Top -->
    <a href="#" class="btn btn-primary back-to-top"><i class="fa fa-angle-double-up"></i></a>

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="../public/assets/lib/easing/easing.min.js"></script>
    <script src="../public/assets/lib/owlcarousel/owlcarousel/owl.carousel.min.js"></script>
    

    <!-- Template Javascript -->
    <script src="../public/js/main.js"></script>

    <script>
        $(document).ready(function() {
            // Auto-dismiss alerts after 5 seconds
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);

            // Add to cart loading state
            $('.add-to-cart-form').on('submit', function() {
                const btn = $(this).find('.btn-cart');
                const btnText = btn.find('.btn-text');
                
                btn.prop('disabled', true);
                btnText.html('<span class="loading-spinner"></span> Adding...');
            });

            // Smooth scroll for anchor links
            $('a[href^="#"]').on('click', function(e) {
                e.preventDefault();
                const target = $(this.getAttribute('href'));
                if (target.length) {
                    $('html, body').animate({
                        scrollTop: target.offset().top - 100
                    }, 800);
                }
            });

            // Back to top button
            $(window).scroll(function() {
                if ($(this).scrollTop() > 100) {
                    $('.back-to-top').fadeIn('slow');
                } else {
                    $('.back-to-top').fadeOut('slow');
                }
            });

            $('.back-to-top').click(function() {
                $('html, body').animate({scrollTop: 0}, 800);
                return false;
            });

            // Product image error handling
            $('img').on('error', function() {
                if (this.src !== 'public/assets/img/default-product.jpg') {
                    this.src = 'public/assets/img/default-product.jpg';
                }
            });
        });
    </script>
</body>
</html>