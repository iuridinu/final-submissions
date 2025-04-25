<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: auth/login.php");
    exit;
}

// Function to get categories
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

// Function to get products (optionally filtered by category)
function getProducts($conn, $category_id = null) {
    $products = array();
    
    $sql = "SELECT p.*, c.name as category_name FROM products p
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.is_available = 1";
    
    if ($category_id !== null) {
        $sql .= " AND p.category_id = " . intval($category_id);
    }
    
    $sql .= " ORDER BY p.name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
    }
    
    return $products;
}

// Get categories
$categories = getCategories($conn);

// Handle category filter
$selected_category = isset($_GET['category']) ? intval($_GET['category']) : null;

// Get products based on filter
$products = getProducts($conn, $selected_category);

// Get promotions
$promotions = array();
$sql = "SELECT * FROM promotions WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW() LIMIT 3";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $promotions[] = $row;
    }
}

// Fetch user info
$user_info = array();
$sql = "SELECT * FROM users WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $_SESSION["user_id"]);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user_info = $result->fetch_assoc();
        }
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sri Lanka Ice Cream Shop - Galle</title>
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
                        <a class="nav-link active" href="index.php">Home</a>
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
                    <?php if ($_SESSION["role"] == "admin"): ?>
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

    <!-- Welcome Hero Section with Ocean Background -->
    <div class="container-fluid py-5" style="background: linear-gradient(rgba(4, 31, 67, 0.7), rgba(3, 28, 62, 0.9)), url('images/galle-fort.jpg') no-repeat center center; background-size: cover;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8 col-md-10 mx-auto text-center text-white">
                    <h1 class="display-4 fw-bold">Welcome to Sri Lanka Ice Cream Shop</h1>
                    <p class="lead">Experience the taste of traditional Sri Lankan flavors in our premium ice cream selection.</p>
                    <p>Discover our unique flavors made with fresh local ingredients from Galle!</p>
                    <a href="#products" class="btn btn-light btn-lg mt-3">
                        <i class="fas fa-ice-cream me-2"></i> Explore Ice Cream
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Categories Section -->
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="mb-0">Our Ice Cream Categories</h2>
                    <a href="index.php" class="btn btn-outline-primary <?php echo ($selected_category === null) ? 'active' : ''; ?>">All Categories</a>
                </div>
                <div class="row g-4">
                    <?php foreach ($categories as $category): ?>
                    <div class="col-md-3 col-sm-6">
                        <a href="index.php?category=<?php echo $category['category_id']; ?>" class="text-decoration-none">
                            <div class="card h-100 <?php echo ($selected_category == $category['category_id']) ? 'border-primary' : ''; ?>">
                                <div class="card-body text-center">
                                    <i class="fas fa-ice-cream fa-3x mb-3 text-primary"></i>
                                    <h5 class="card-title"><?php echo htmlspecialchars($category['name']); ?></h5>
                                    <p class="card-text small"><?php echo htmlspecialchars($category['description']); ?></p>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Promotions Section (if any) -->
    <?php if (!empty($promotions)): ?>
    <div class="container mt-5">
        <h2 class="mb-4">Special Offers</h2>
        <div class="row">
            <?php foreach ($promotions as $promo): ?>
            <div class="col-md-4 mb-4">
                <div class="card bg-light h-100 position-relative">
                    <div class="ribbon ribbon-top-right"><span>OFFER</span></div>
                    <div class="card-body text-center">
                        <h4 class="card-title text-primary"><?php echo htmlspecialchars($promo['name']); ?></h4>
                        <p class="card-text"><?php echo htmlspecialchars($promo['description']); ?></p>
                        <?php if ($promo['discount_percentage'] > 0): ?>
                        <h3 class="text-accent"><?php echo $promo['discount_percentage']; ?>% OFF</h3>
                        <?php elseif ($promo['discount_amount'] > 0): ?>
                        <h3 class="text-accent">Rs. <?php echo $promo['discount_amount']; ?> OFF</h3>
                        <?php endif; ?>
                        <p class="small text-muted">
                            Valid until: <?php echo date("F j, Y", strtotime($promo['end_date'])); ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Products Section -->
    <div class="container mt-5" id="products">
        <h2 class="mb-4">
            <?php echo $selected_category !== null ? "Products in " . htmlspecialchars($categories[array_search($selected_category, array_column($categories, 'category_id'))]['name']) : "All Ice Cream Products"; ?>
        </h2>
        
        <?php if (empty($products)): ?>
        <div class="alert alert-info">
            No products available in this category.
        </div>
        <?php else: ?>
        <div class="row">
            <?php foreach ($products as $product): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="product-card h-100">
                    <?php if ($product['image_path']): ?>
                    <img src="<?php echo htmlspecialchars($product['image_path']); ?>" class="card-img-top product-img" alt="<?php echo htmlspecialchars($product['name']); ?>">
                    <?php else: ?>
                    <div class="card-img-top product-img d-flex align-items-center justify-content-center bg-light">
                        <i class="fas fa-ice-cream fa-3x text-secondary"></i>
                    </div>
                    <?php endif; ?>
                    <div class="card-body">
                        <span class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <p class="card-text small"><?php echo htmlspecialchars($product['description']); ?></p>
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <span class="product-price">Rs. <?php echo htmlspecialchars($product['price']); ?></span>
                            <button class="btn btn-sm btn-primary add-to-cart" data-id="<?php echo $product['product_id']; ?>" data-name="<?php echo htmlspecialchars($product['name']); ?>" data-price="<?php echo $product['price']; ?>">
                                <i class="fas fa-cart-plus me-1"></i> Add
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
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