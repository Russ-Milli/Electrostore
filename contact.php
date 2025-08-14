<?php
session_start();

// Initialize cart if not set
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$isLoggedIn = isset($_SESSION['user_id']);

?>

<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8" />
    <title>Contact us - ElectroStore</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport" />
    <meta content="Free HTML Templates" name="keywords" />
    <meta content="Free HTML Templates" name="description" />

    <!--Favicon-->
    <link href="img/favicon.ico" rel="icon" />

    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com" />
    <link
      href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap"
      rel="stylesheet"
    />

    <!-- Font Awesome -->
    <link
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css"
      rel="stylesheet"
    />

    <!-- Libraries Stylesheet -->
    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet" />
  </head>

  <body>
    <!-- Topbar Start -->
    <div class="container-fluid">
      <div class="row align-items-center py-3 px-xl-5">
        <div class="col-lg-3 d-none d-lg-block">
          <a href="" class="text-decoration-none">
            <h1 class="m-0 display-6 font-weight-semi-bold">
              <span class="text-primary font-weight-bold border px-3 mr-1"
                >E</span
              >lectrostore
            </h1>
          </a>
        </div>

        <div class="col-lg-1">
          <a href="cartpg.php" class="btn border">
            <i class="fas fa-shopping-cart text-primary"></i>
            <span class="badge"></span>
          </a>
        </div>
      </div>
    </div>
    <!-- Topbar End -->

    <!-- Navbar Start -->
    <div class="container-fluid mb-2">
        <div class="row align-items-center justify-content-center">
          <div class="col-lg-11">
            <nav class="navbar navbar-expand-lg bg-light navbar-light py-3 py-lg-0 px-0">
              <a href="" class="text-decoration-none d-block d-lg-none">
                  <h1 class="m-0 display-5 font-weight-semi-bold"><span class="text-primary font-weight-bold border px-3 mr-1">E</span>lectrostore</h1>
              </a>
              <button type="button" class="navbar-toggler" data-toggle="collapse" data-target="#navbarCollapse">
                  <span class="navbar-toggler-icon"></span>
              </button>
              <div class="collapse navbar-collapse justify-content-between" id="navbarCollapse">
                  <div class="navbar-nav mr-auto py-0">
                      <a href="index.php" class="nav-item nav-link">Home</a>
                      <a href="cartpg.php" class="nav-item nav-link">Shopping Cart</a>
                      <a href="contact.php" class="nav-item nav-link active">Contact</a>
                  </div>
                  <div class="navbar-nav ml-auto py-0">
                      <?php if ($isLoggedIn): ?>
                          <a href="profile.php" class="nav-item nav-link">Profile</a>
                          <a href="auth/logout.php" class="nav-item nav-link">Logout</a>
                      <?php else: ?>
                          <a href="auth/login.php" class="nav-item nav-link">Login</a>
                          <a href="auth/signup.php" class="nav-item nav-link">Register</a>
                      <?php endif; ?>
                  </div>
              </div>
            </nav>
          </div>
        </div>
    </div>
    <!-- Navbar End -->

  <!-- ElectroStore Banner -->
  <nav class="navbar navbar-expand-lg" style="background-color: #0096d6">
    <div class="container">
      <small class="text-white-50 ms-2 me-auto">Your Trusted HP Partner</small>
    </div>
  </nav>

    <!-- Page Header Start -->
    <div class="container-fluid">
      <div
        class="d-flex flex-column align-items-center justify-content-center"
        style="min-height: 150px"
      >
        <h1 class="font-weight-semi-bold text-uppercase">Contact Us</h1>
      </div>
    </div>
    <!-- Page Header End -->

<!-- Contact Start -->
<div class="container-fluid pt-5">
  <div class="row px-xl-5">
    <!-- Contact Form -->
    <div class="col-lg-7 mb-5">
      <h5 class="font-weight-semi-bold mb-3">Send Us a Message</h5>
      <p>We’d love to hear from you. Reach out with any questions or comments!</p>
      <form id="contactForm" novalidate>
        <div class="mb-3">
          <input type="text" class="form-control" id="name" placeholder="Your Name" required />
        </div>
        <div class="mb-3">
          <input type="email" class="form-control" id="email" placeholder="Your Email" required />
        </div>
        <div class="mb-3">
          <input type="text" class="form-control" id="subject" placeholder="Subject" required />
        </div>
        <div class="mb-3">
          <textarea class="form-control" rows="5" id="message" placeholder="Message" required></textarea>
        </div>
        <button class="btn btn-primary py-2 px-4" type="submit">Send Message</button>
      </form>
    </div>

    <!-- Contact Info -->
    <div class="col-lg-5 mb-5">
      <h5 class="font-weight-semi-bold mb-3">Get In Touch</h5>
      <p>We're available Monday–Saturday, 8am to 6pm, and always ready to help you.</p>

      <div class="mb-4">
        <h6 class="text-dark">ElectroStore Nairobi</h6>
        <p><i class="fa fa-map-marker-alt text-primary mr-2"></i>Ground Floor, Kenyatta Avenue, Nairobi</p>
        <p><i class="fa fa-envelope text-primary mr-2"></i>support@electrostore.co.ke</p>
        <p><i class="fa fa-phone-alt text-primary mr-2"></i>+254 700 123 456</p>
      </div>

      <div>
        <h6 class="text-dark">ElectroStore Mombasa</h6>
        <p><i class="fa fa-map-marker-alt text-primary mr-2"></i>Moi Avenue, Mombasa</p>
        <p><i class="fa fa-envelope text-primary mr-2"></i>coast@electrostore.co.ke</p>
        <p><i class="fa fa-phone-alt text-primary mr-2"></i>+254 711 987 654</p>
      </div>
    </div>
  </div>
</div>
<!-- Contact End -->


    <!-- Footer Start -->
    <div class="container-fluid bg-secondary text-dark mt-5 pt-5">
      <div class="row px-xl-5 pt-5">
        <div class="col-lg-4 col-md-12 mb-5 pr-3 pr-xl-5">
          <a href="" class="text-decoration-none">
            <h1 class="mb-4 display-5 font-weight-semi-bold">
              <span
                class="text-primary font-weight-bold border border-white px-3 mr-1"
                >E</span
              >lectrostore
            </h1>
          </a>
          <p class="mb-2">
            <i class="fa fa-map-marker-alt text-primary mr-3"></i>123
            ElectroStore
          </p>
          <p class="mb-2">
            <i class="fa fa-envelope text-primary mr-3"></i
            >info@ElectroStore.com
          </p>
          <p class="mb-0">
            <i class="fa fa-phone-alt text-primary mr-3"></i>+012 345 67890
          </p>
        </div>
        <div class="col-lg-8 col-md-12">
          <div class="row">
            <div class="col-md-4 mb-5">
              <h5 class="font-weight-bold text-dark mb-4">Quick Links</h5>
              <div class="d-flex flex-column justify-content-start">
                <a class="text-dark mb-2" href="index.php"
                  ><i class="fa fa-angle-right mr-2"></i>Home</a
                >
                <a class="text-dark mb-2" href="cartpg.php"
                  ><i class="fa fa-angle-right mr-2"></i>Shopping Cart</a
                >
                <a class="text-dark" href="contact.php"
                  ><i class="fa fa-angle-right mr-2"></i>Contact Us</a
                >
              </div>
            </div>
            <div class="col-md-4 mb-5">
              <h5 class="font-weight-bold text-dark mb-4">Newsletter</h5>
              <form action="">
                <div class="form-group">
                  <input
                    type="text"
                    class="form-control border-0 py-4"
                    placeholder="Your Name"
                    required="required"
                  />
                </div>
                <div class="form-group">
                  <input
                    type="email"
                    class="form-control border-0 py-4"
                    placeholder="Your Email"
                    required="required"
                  />
                </div>
                <div>
                  <button
                    class="btn btn-primary btn-block border-0 py-3"
                    type="submit"
                  >
                    Subscribe Now
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
    </div>
                                    
<footer class="bg-dark text-white text-center py-3">&copy; 2025 ElectroStore. All rights reserved.
</footer>
    <!-- Footer End -->

    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>

    <!-- Contact Javascript File -->
    <script src="mail/jqBootstrapValidation.min.js"></script>
    <script src="mail/contact.js"></script>

    <!-- Template Javascript -->
    <script src="js/main.js"></script>
  </body>
</html>
