<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: auth/login.php");
    exit;
}

// Function to get categories for the navigation menu
function getCategories($conn) {
    $categories = array();
    $sql = "SELECT * FROM categories ORDER BY name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
    }
    
    return $categories;
}

// Get categories for navigation
$categories = getCategories($conn);

// Get featured products for "Our Specialties" section
$featured_products = array();
$sql = "SELECT * FROM products WHERE is_available = 1 ORDER BY RAND() LIMIT 3";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $featured_products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Sri Lanka Ice Cream Shop - Galle</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Google Fonts - Poppins -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-ice-cream me-2"></i>
                Sri Lanka Ice Cream
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Categories
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="categoriesDropdown">
                            <li><a class="dropdown-item" href="index.php">All Categories</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <?php foreach ($categories as $category): ?>
                                <li><a class="dropdown-item" href="index.php?category=<?php echo $category['category_id']; ?>"><?php echo htmlspecialchars($category['name']); ?></a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact Us</a>
                    </li>
                    <?php if (isset($_SESSION["role"]) && $_SESSION["role"] == "admin"): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="admin/dashboard.php">Admin Panel</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="cart">
                            <i class="fas fa-shopping-cart me-1"></i> Cart
                            <span class="badge bg-secondary" id="cart-count">0</span>
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($_SESSION["username"]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php">Order History</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- About Hero Section -->
    <div class="container-fluid py-5" style="background: linear-gradient(rgba(4, 31, 67, 0.7), rgba(3, 28, 62, 0.9)), url('images/galle-sri-lanka.jpg') no-repeat center center; background-size: cover;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-10 mx-auto text-center text-white">
                    <h1 class="display-4 fw-bold">Our Story</h1>
                    <p class="lead">Bringing authentic Sri Lankan flavors to your ice cream experience since 2010.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- About Us Main Content -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-6">
                <h2>About Sri Lanka Ice Cream Shop</h2>
                <p class="lead">Founded in 2010, our shop has been bringing authentic Sri Lankan flavors to the delightful world of ice cream.</p>
                <p>Located in the heart of the historic Galle Fort, our family-owned ice cream shop started with a simple passion: to showcase the rich and diverse flavors of Sri Lanka through premium ice cream made with locally sourced ingredients.</p>
                <p>What began as a small shop with just five flavors has now grown into a beloved destination for both locals and tourists, offering over twenty unique flavors that represent the essence of Sri Lankan cuisine and culture.</p>
                <p>Each scoop of our ice cream tells a story of tradition, innovation, and our commitment to quality. We believe in preserving authentic methods while embracing creative approaches to ice cream making.</p>
            </div>
            <div class="col-md-6">
                <div class="rounded shadow overflow-hidden">
                    <img src="images/shop-interior.jpg" alt="Sri Lanka Ice Cream Shop Interior" class="img-fluid" onerror="this.src='images/placeholder-image.jpg'">
                </div>
            </div>
        </div>
        
        <div class="row mt-5">
            <div class="col-md-12">
                <h3 class="mb-4">Our Values</h3>
            </div>
        </div>
        
        <div class="row text-center g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-leaf fa-2x text-white"></i>
                        </div>
                        <h4 class="card-title">Sustainability</h4>
                        <p class="card-text">We are committed to sustainable practices, using biodegradable packaging and supporting local farmers. Our ingredients are ethically sourced, and we strive to minimize our environmental footprint.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-heart fa-2x text-white"></i>
                        </div>
                        <h4 class="card-title">Quality</h4>
                        <p class="card-text">We never compromise on quality. From carefully selecting the freshest ingredients to perfecting our recipes, every step of our process is dedicated to creating the most delicious and authentic ice cream experience.</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px;">
                            <i class="fas fa-users fa-2x text-white"></i>
                        </div>
                        <h4 class="card-title">Community</h4>
                        <p class="card-text">We believe in giving back to our community. By supporting local producers and participating in community events, we aim to strengthen the bonds within our beautiful Galle community.</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Our Specialties Section -->
        <div class="row mt-5 pt-3">
            <div class="col-md-12">
                <h3 class="mb-4">Our Specialties</h3>
                <p>At Sri Lanka Ice Cream Shop, we take pride in our unique flavors that capture the essence of Sri Lankan cuisine. Here are some of our most beloved creations:</p>
            </div>
        </div>
        
        <div class="row g-4">
            <?php foreach ($featured_products as $product): ?>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm">
                    <?php if ($product['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>" style="height: 200px; object-fit: cover;">
                    <?php else: ?>
                    <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                        <i class="fas fa-ice-cream fa-3x text-secondary"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body text-center">
                        <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Our Location -->
        <div class="row mt-5 pt-3">
            <div class="col-md-6">
                <h3 class="mb-4">Visit Us in Galle</h3>
                <p>Our shop is located in the heart of the historic Galle Fort, a UNESCO World Heritage Site on the southern coast of Sri Lanka. The fort, built first by the Portuguese and then extensively by the Dutch from 1663, provides a charming backdrop for enjoying our ice cream.</p>
                
                <div class="mb-4">
                    <h5><i class="fas fa-map-marker-alt text-primary me-2"></i> Our Location</h5>
                    <p>42 Church Street<br>Galle Fort<br>Galle, Sri Lanka</p>
                </div>
                
                <div class="mb-4">
                    <h5><i class="fas fa-clock text-primary me-2"></i> Opening Hours</h5>
                    <p>Monday - Friday: 10:00 AM - 9:00 PM<br>
                    Saturday - Sunday: 9:00 AM - 10:00 PM<br>
                    Public Holidays: 9:00 AM - 11:00 PM</p>
                </div>
                
                <div>
                    <h5><i class="fas fa-phone text-primary me-2"></i> Contact</h5>
                    <p>Phone: +94 77 123 4567<br>
                    Email: info@srilankaic.com</p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="rounded shadow overflow-hidden">
                    <!-- Placeholder for Google Map or location image -->
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3967.8381743540696!2d80.21500021476985!3d6.025986095638991!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae173a9a0bd415f%3A0x5f820e73a47f2279!2sGalle%20Fort!5e0!3m2!1sen!2sus!4v1649405289763!5m2!1sen!2sus" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="text-center mt-3">
                    <a href="https://maps.google.com/?q=Galle+Fort+Sri+Lanka" target="_blank" class="btn btn-primary">
                        <i class="fas fa-directions me-2"></i> Get Directions
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Testimonials -->
        <div class="row mt-5 pt-3">
            <div class="col-md-12 text-center">
                <h3 class="mb-4">What Our Customers Say</h3>
            </div>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm testimonial-card">
                    <div class="card-body">
                        <div class="testimonial-stars mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">"The King Coconut ice cream is simply amazing! Every time I visit Galle, I make sure to stop by this shop. The flavors are authentic and remind me of my childhood in Sri Lanka."</p>
                        <div class="testimonial-author">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <span>SM</span>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Sanjay Mendis</h6>
                                    <small class="text-muted">Colombo</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm testimonial-card">
                    <div class="card-body">
                        <div class="testimonial-stars mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                        </div>
                        <p class="card-text">"As a tourist visiting Sri Lanka for the first time, this ice cream shop was a delightful surprise. The Cinnamon Surprise flavor was incredible and unlike anything I've tasted before. A must-visit in Galle!"</p>
                        <div class="testimonial-author">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <span>EJ</span>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Emma Johnson</h6>
                                    <small class="text-muted">London, UK</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-0 shadow-sm testimonial-card">
                    <div class="card-body">
                        <div class="testimonial-stars mb-3">
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star text-warning"></i>
                            <i class="fas fa-star-half-alt text-warning"></i>
                        </div>
                        <p class="card-text">"I love how they incorporate traditional Sri Lankan ingredients into their ice creams. The Jaggery Special is my favorite - sweet, rich, and full of authentic flavor. The service is also excellent!"</p>
                        <div class="testimonial-author">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                    <span>RP</span>
                                </div>
                                <div class="ms-3">
                                    <h6 class="mb-0">Ravi Perera</h6>
                                    <small class="text-muted">Kandy</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Sri Lanka Ice Cream Shop</h5>
                    <p>Galle Fort Branch<br>
                    42 Church Street<br>
                    Galle, Sri Lanka</p>
                    <p><i class="fas fa-phone me-2"></i> +94 77 123 4567</p>
                    <p><i class="fas fa-envelope me-2"></i> info@srilankaic.com</p>
                </div>
                <div class="col-md-4 mb-4 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php">Home</a></li>
                        <li><a href="about.php">About Us</a></li>
                        <li><a href="contact.php">Contact Us</a></li>
                        <li><a href="terms.php">Terms & Conditions</a></li>
                        <li><a href="privacy.php">Privacy Policy</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Opening Hours</h5>
                    <ul class="list-unstyled">
                        <li>Monday - Friday: 10:00 AM - 9:00 PM</li>
                        <li>Saturday - Sunday: 9:00 AM - 10:00 PM</li>
                        <li>Public Holidays: 9:00 AM - 11:00 PM</li>
                    </ul>
                    <div class="mt-3">
                        <a href="#" class="me-3"><i class="fab fa-facebook-f fa-lg"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-instagram fa-lg"></i></a>
                        <a href="#" class="me-3"><i class="fab fa-twitter fa-lg"></i></a>
                    </div>
                </div>
            </div>
            <hr class="mt-4 mb-3">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p class="mb-0">&copy; <?php echo date("Y"); ?> Sri Lanka Ice Cream Shop. All Rights Reserved.</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Shopping Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="cartModalLabel"><i class="fas fa-shopping-cart me-2"></i> Your Shopping Cart</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="cart-items">
                        <!-- Cart items will be loaded here via JavaScript -->
                        <p class="text-center py-4">Your cart is empty</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="d-flex w-100 justify-content-between align-items-center">
                        <div class="cart-total">
                            <h5>Total: <span id="cart-total">Rs. 0.00</span></h5>
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Shopping</button>
                            <button type="button" class="btn btn-primary" id="checkout-btn">Checkout</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript for Shopping Cart -->
    <script src="js/main.js"></script>
</body>
</html>