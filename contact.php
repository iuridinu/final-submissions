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

// Process contact form submission
$message = '';
$message_class = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contact_submit'])) {
    // Basic form validation
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $subject = trim($_POST['subject']);
    $message_text = trim($_POST['message']);
    
    // Check if required fields are empty
    if (empty($name) || empty($email) || empty($subject) || empty($message_text)) {
        $message = 'Please fill all required fields.';
        $message_class = 'alert-danger';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_class = 'alert-danger';
    } else {
        // In a real application, you would send an email or store the message in a database
        // For now, we'll just show a success message
        $message = 'Thank you for your message! We will get back to you shortly.';
        $message_class = 'alert-success';
        
        // Reset form fields after successful submission
        $name = $email = $subject = $message_text = '';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - Sri Lanka Ice Cream Shop - Galle</title>
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
                        <a class="nav-link" href="about.php">About Us</a>
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
                        <a class="nav-link active" href="contact.php">Contact Us</a>
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

    <!-- Contact Hero Section -->
    <div class="container-fluid py-5" style="background: linear-gradient(rgba(4, 31, 67, 0.7), rgba(3, 28, 62, 0.9)), url('images/contact-hero.jpg') no-repeat center center; background-size: cover;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-10 mx-auto text-center text-white">
                    <h1 class="display-4 fw-bold">Contact Us</h1>
                    <p class="lead">We'd love to hear from you! Reach out with questions, feedback, or to place a special order.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Information and Form -->
    <div class="container mt-5">
        <?php if (!empty($message)): ?>
        <div class="alert <?php echo $message_class; ?> alert-dismissible fade show" role="alert">
            <?php echo $message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <!-- Contact Information -->
            <div class="col-lg-5 mb-4 mb-lg-0">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <h2 class="mb-4">Get In Touch</h2>
                        
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="bg-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-map-marker-alt fa-lg text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h5>Visit Our Shop</h5>
                                <p class="mb-0">42 Church Street<br>Galle Fort<br>Galle, Sri Lanka</p>
                            </div>
                        </div>
                        
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="bg-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-phone fa-lg text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h5>Call Us</h5>
                                <p class="mb-0">Phone: +94 77 123 4567<br>WhatsApp: +94 77 123 4567</p>
                            </div>
                        </div>
                        
                        <div class="d-flex mb-4">
                            <div class="flex-shrink-0">
                                <div class="bg-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-envelope fa-lg text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h5>Email Us</h5>
                                <p class="mb-0">General Inquiries: info@srilankaic.com<br>Orders: orders@srilankaic.com</p>
                            </div>
                        </div>
                        
                        <div class="d-flex">
                            <div class="flex-shrink-0">
                                <div class="bg-primary rounded-circle p-3 d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                    <i class="fas fa-clock fa-lg text-white"></i>
                                </div>
                            </div>
                            <div class="ms-3">
                                <h5>Opening Hours</h5>
                                <p class="mb-0">Monday - Friday: 10:00 AM - 9:00 PM<br>
                                Saturday - Sunday: 9:00 AM - 10:00 PM<br>
                                Public Holidays: 9:00 AM - 11:00 PM</p>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h5>Follow Us</h5>
                            <div class="d-flex social-links">
                                <a href="#" class="social-link bg-primary d-flex align-items-center justify-content-center me-2">
                                    <i class="fab fa-facebook-f text-white"></i>
                                </a>
                                <a href="#" class="social-link bg-primary d-flex align-items-center justify-content-center me-2">
                                    <i class="fab fa-instagram text-white"></i>
                                </a>
                                <a href="#" class="social-link bg-primary d-flex align-items-center justify-content-center me-2">
                                    <i class="fab fa-twitter text-white"></i>
                                </a>
                                <a href="#" class="social-link bg-primary d-flex align-items-center justify-content-center">
                                    <i class="fab fa-tripadvisor text-white"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Contact Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <h2 class="mb-4">Send Us a Message</h2>
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Your Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address *</label>
                                    <input type="email" class="form-control" id="email" name="email" required value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                                </div>
                                <div class="col-12">
                                    <label for="subject" class="form-label">Subject *</label>
                                    <input type="text" class="form-control" id="subject" name="subject" required value="<?php echo isset($subject) ? htmlspecialchars($subject) : ''; ?>">
                                </div>
                                <div class="col-12">
                                    <label for="message" class="form-label">Your Message *</label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required><?php echo isset($message_text) ? htmlspecialchars($message_text) : ''; ?></textarea>
                                </div>
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="newsletter" name="newsletter">
                                        <label class="form-check-label" for="newsletter">
                                            Subscribe to our newsletter for updates and special offers
                                        </label>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" name="contact_submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-paper-plane me-2"></i> Send Message
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Location and Map -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="mb-4">Our Location</h2>
            </div>
            <div class="col-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-0">
                        <!-- Replace with actual Google Maps embed code in production -->
                        <div class="ratio ratio-21x9" style="min-height: 400px;">
                            
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3967.8381743540696!2d80.21500021476985!3d6.025986095638991!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3ae173a9a0bd415f%3A0x5f820e73a47f2279!2sGalle%20Fort!5e0!3m2!1sen!2sus!4v1649405289763!5m2!1sen!2sus" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                            
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <div class="row mt-5">
            <div class="col-12">
                <h2 class="mb-4">Frequently Asked Questions</h2>
            </div>
            <div class="col-lg-8">
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                Do you offer delivery services?
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, we offer delivery within Galle Fort and surrounding areas. For delivery outside these areas, please contact us directly. Our ice cream is packed in special insulated containers to ensure it stays frozen during delivery.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Can I place a bulk order for events?
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Absolutely! We cater for various events including weddings, corporate events, and parties. Please contact us at least 3 days in advance for bulk orders to ensure availability. Special discounts are available for large orders.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Do you have dairy-free or vegan options?
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, we offer a selection of fruit sorbets that are dairy-free. We also have coconut-based vegan ice cream options. Please ask our staff about available flavors when you visit.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                Can I request a custom flavor for a special occasion?
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                We love creating custom flavors! Please contact us at least a week in advance with your requests, and our ice cream artisans will work with you to create something special. Minimum order quantities may apply for custom flavors.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                Are your ingredients locally sourced?
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Yes, we pride ourselves on using locally sourced ingredients whenever possible. Our fruits come from local farmers, and we use authentic Sri Lankan spices and flavors. This not only ensures freshness but also supports the local economy.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4 mt-4 mt-lg-0">
                <div class="card border-0 shadow-sm bg-light">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-question-circle text-primary me-2"></i> Still Have Questions?</h5>
                        <p class="card-text">If you couldn't find the answer to your question in our FAQ section, please don't hesitate to contact us directly.</p>
                        <a href="tel:+94771234567" class="btn btn-primary mb-2 w-100">
                            <i class="fas fa-phone me-2"></i> Call Us
                        </a>
                        <a href="mailto:info@srilankaic.com" class="btn btn-outline-primary w-100">
                            <i class="fas fa-envelope me-2"></i> Email Us
                        </a>
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
    
    <!-- Custom style for social links -->
    <style>
        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            transition: all 0.3s ease;
        }
        
        .social-link:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</body>
</html>