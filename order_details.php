<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: auth/login.php");
    exit;
}

// Initialize variables
$order_id = 0;
$order_info = array();
$order_items = array();
$error_message = "";
$success_message = "";

// Get order ID from URL parameter
if (isset($_GET["id"]) && is_numeric($_GET["id"])) {
    $order_id = $_GET["id"];
    
    // Check if the order belongs to the current user or if user is admin/staff
    $user_id = $_SESSION["user_id"];
    $is_authorized = false;
    
    if ($_SESSION["role"] == "admin" || $_SESSION["role"] == "staff") {
        $is_authorized = true;
    } else {
        // Check if the order belongs to the current user
        $sql = "SELECT * FROM orders WHERE order_id = ? AND user_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("ii", $order_id, $user_id);
            if ($stmt->execute()) {
                $result = $stmt->get_result();
                if ($result->num_rows == 1) {
                    $is_authorized = true;
                }
            }
            $stmt->close();
        }
    }
    
    if (!$is_authorized) {
        // Not authorized to view this order
        header("location: orders.php");
        exit;
    }
    
    // Get order details
    $sql = "SELECT o.*, u.username, u.full_name 
            FROM orders o 
            LEFT JOIN users u ON o.user_id = u.user_id 
            WHERE o.order_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            if ($result->num_rows == 1) {
                $order_info = $result->fetch_assoc();
            } else {
                $error_message = "Order not found.";
            }
        } else {
            $error_message = "Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
    
    // Get order items
    $sql = "SELECT oi.*, p.name, p.image_path  
            FROM order_items oi 
            JOIN products p ON oi.product_id = p.product_id 
            WHERE oi.order_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $order_id);
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $order_items[] = $row;
            }
        } else {
            $error_message = "Something went wrong. Please try again later.";
        }
        $stmt->close();
    }
} else {
    // No valid order ID provided, redirect to orders list
    header("location: orders.php");
    exit;
}

// Handle cancellation request (only for pending orders)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["cancel_order"])) {
    // Check if the order is in a cancellable state (pending)
    if ($order_info["status"] == "pending") {
        $sql = "UPDATE orders SET status = 'cancelled' WHERE order_id = ?";
        if ($stmt = $conn->prepare($sql)) {
            $stmt->bind_param("i", $order_id);
            if ($stmt->execute()) {
                $success_message = "Order successfully cancelled.";
                // Update order_info with the new status
                $order_info["status"] = "cancelled";
            } else {
                $error_message = "Error cancelling order. Please try again later.";
            }
            $stmt->close();
        }
    } else {
        $error_message = "This order cannot be cancelled because it's already being processed.";
    }
}

// Function to get categories for navbar
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

// Get categories for navbar
$categories = getCategories($conn);

// Format the order status for display
function getStatusClass($status) {
    switch($status) {
        case 'pending':
            return 'bg-warning';
        case 'processing':
            return 'bg-info';
        case 'completed':
            return 'bg-success';
        case 'cancelled':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order #<?php echo $order_id; ?> - Sri Lanka Ice Cream Shop</title>
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
                            <li><a class="dropdown-item active" href="orders.php">Order History</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container mt-5">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="orders.php">Orders</a></li>
                <li class="breadcrumb-item active" aria-current="page">Order #<?php echo $order_id; ?></li>
            </ol>
        </nav>

        <!-- Alert Messages -->
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Order Details -->
            <div class="col-lg-8">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-shopping-bag me-2"></i>Order #<?php echo $order_id; ?>
                        </h5>
                        <span class="badge <?php echo getStatusClass($order_info['status']); ?> fs-6">
                            <?php echo ucfirst($order_info['status']); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">Order Information</h6>
                                <p><strong>Order Date:</strong> <?php echo date("F d, Y h:i A", strtotime($order_info['order_date'])); ?></p>
                                <p><strong>Payment Method:</strong> <?php echo ucfirst($order_info['payment_method']); ?></p>
                                <p><strong>Total Amount:</strong> Rs. <?php echo number_format($order_info['total_amount'], 2); ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Delivery Information</h6>
                                <p><strong>Contact Number:</strong> <?php echo htmlspecialchars($order_info['contact_number']); ?></p>
                                <p><strong>Delivery Address:</strong><br>
                                <?php echo nl2br(htmlspecialchars($order_info['delivery_address'])); ?></p>
                            </div>
                        </div>

                        <?php if (!empty($order_info['notes'])): ?>
                        <div class="mb-4">
                            <h6 class="text-muted">Order Notes</h6>
                            <p><?php echo nl2br(htmlspecialchars($order_info['notes'])); ?></p>
                        </div>
                        <?php endif; ?>

                        <div class="table-responsive">
                            <table class="table">
                                <thead class="table-light">
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-center">Price</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['image_path'])): ?>
                                                <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="me-3" width="50" height="50" alt="<?php echo htmlspecialchars($item['name']); ?>">
                                                <?php else: ?>
                                                <div class="me-3 bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                    <i class="fas fa-ice-cream text-muted"></i>
                                                </div>
                                                <?php endif; ?>
                                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-center">Rs. <?php echo number_format($item['price_per_unit'], 2); ?></td>
                                        <td class="text-center"><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">Rs. <?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end">Rs. <?php echo number_format($order_info['total_amount'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="mt-4 d-flex justify-content-between">
                            <a href="orders.php" class="btn btn-outline-primary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Orders
                            </a>
                            
                            <?php if ($order_info['status'] == 'pending'): ?>
                            <form method="post" onsubmit="return confirm('Are you sure you want to cancel this order?');">
                                <button type="submit" name="cancel_order" class="btn btn-danger">
                                    <i class="fas fa-times me-2"></i>Cancel Order
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Status Sidebar -->
            <div class="col-lg-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-shipping-fast me-2"></i>Order Status</h5>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush">
                            <?php 
                            $statuses = ['pending', 'processing', 'completed', 'cancelled'];
                            $current_status = $order_info['status'];
                            $current_status_index = array_search($current_status, $statuses);
                            
                            // Determine which steps to show as active/completed
                            foreach ($statuses as $index => $status):
                                if ($status == 'cancelled' && $current_status != 'cancelled') {
                                    continue; // Skip cancelled if the order is not cancelled
                                }
                                
                                $is_active = ($current_status == $status);
                                $is_completed = ($current_status != 'cancelled' && $index < $current_status_index);
                                
                                $status_icon = '';
                                $status_class = '';
                                
                                if ($is_active) {
                                    $status_class = 'active';
                                    $status_icon = '<i class="fas fa-sync-alt fa-spin me-2 text-primary"></i>';
                                } elseif ($is_completed) {
                                    $status_icon = '<i class="fas fa-check me-2 text-success"></i>';
                                } else {
                                    $status_icon = '<i class="far fa-circle me-2 text-muted"></i>';
                                }
                                
                                if ($status == 'cancelled') {
                                    $status_icon = '<i class="fas fa-times me-2 text-danger"></i>';
                                }
                            ?>
                            <li class="list-group-item <?php echo $status_class; ?>">
                                <?php echo $status_icon; ?>
                                <strong><?php echo ucfirst($status); ?></strong>
                                <?php if ($is_active): ?>
                                <small class="d-block text-muted mt-1">
                                    <?php 
                                    switch($status) {
                                        case 'pending':
                                            echo 'Your order has been received and is awaiting processing.';
                                            break;
                                        case 'processing':
                                            echo 'Your order is being prepared by our ice cream specialists.';
                                            break;
                                        case 'completed':
                                            echo 'Your order has been successfully delivered.';
                                            break;
                                        case 'cancelled':
                                            echo 'This order has been cancelled.';
                                            break;
                                    }
                                    ?>
                                </small>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>

                <?php if ($_SESSION["role"] == "admin" || $_SESSION["role"] == "staff"): ?>
                <!-- Admin Actions Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-tools me-2"></i>Admin Actions</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">Update order status:</p>
                        <form action="admin/update_order_status.php" method="post">
                            <input type="hidden" name="order_id" value="<?php echo $order_id; ?>">
                            <select name="status" class="form-select mb-3">
                                <option value="pending" <?php echo ($order_info['status'] == 'pending') ? 'selected' : ''; ?>>Pending</option>
                                <option value="processing" <?php echo ($order_info['status'] == 'processing') ? 'selected' : ''; ?>>Processing</option>
                                <option value="completed" <?php echo ($order_info['status'] == 'completed') ? 'selected' : ''; ?>>Completed</option>
                                <option value="cancelled" <?php echo ($order_info['status'] == 'cancelled') ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                            <button type="submit" class="btn btn-primary w-100">Update Status</button>
                        </form>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Customer Support Card -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-headset me-2"></i>Need Help?</h5>
                    </div>
                    <div class="card-body">
                        <p>If you have any questions about your order, feel free to contact our customer support team.</p>
                        <div class="mb-2">
                            <i class="fas fa-phone me-2 text-muted"></i> +94 77 123 4567
                        </div>
                        <div class="mb-2">
                            <i class="fas fa-envelope me-2 text-muted"></i> support@srilankaic.com
                        </div>
                        <div>
                            <i class="fas fa-clock me-2 text-muted"></i> Mon-Fri, 9:00 AM - 5:00 PM
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

    <script>
        // Cart functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize cart from localStorage
            let cart = JSON.parse(localStorage.getItem('cart')) || [];
            updateCartCount();
            
            // Open cart modal when clicking on cart icon
            document.getElementById('cart').addEventListener('click', function(e) {
                e.preventDefault();
                displayCart();
                new bootstrap.Modal(document.getElementById('cartModal')).show();
            });
            
            // Checkout button functionality
            document.getElementById('checkout-btn').addEventListener('click', function() {
                if (cart.length === 0) {
                    alert('Your cart is empty!');
                    return;
                }
                window.location.href = 'checkout.php';
            });
            
            // Functions for cart management
            function updateCartCount() {
                const count = cart.reduce((total, item) => total + item.quantity, 0);
                document.getElementById('cart-count').textContent = count;
            }
            
            function displayCart() {
                const cartItemsElement = document.getElementById('cart-items');
                const cartTotalElement = document.getElementById('cart-total');
                
                if (cart.length === 0) {
                    cartItemsElement.innerHTML = '<p class="text-center py-4">Your cart is empty</p>';
                    cartTotalElement.textContent = 'Rs. 0.00';
                    return;
                }
                
                let total = 0;
                let cartHTML = '<div class="table-responsive"><table class="table">';
                cartHTML += '<thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Subtotal</th><th></th></tr></thead><tbody>';
                
                cart.forEach((item, index) => {
                    const subtotal = item.price * item.quantity;
                    total += subtotal;
                    
                    cartHTML += `
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <img src="${item.image}" alt="${item.name}" class="cart-item-image me-2" width="50">
                                    <span>${item.name}</span>
                                </div>
                            </td>
                            <td>Rs. ${item.price.toFixed(2)}</td>
                            <td>
                                <div class="input-group input-group-sm quantity-control">
                                    <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(${index}, -1)">-</button>
                                    <input type="text" class="form-control text-center" value="${item.quantity}" readonly>
                                    <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(${index}, 1)">+</button>
                                </div>
                            </td>
                            <td>Rs. ${subtotal.toFixed(2)}</td>
                            <td>
                                <button class="btn btn-sm btn-danger" onclick="removeFromCart(${index})">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                
                cartHTML += '</tbody></table></div>';
                cartItemsElement.innerHTML = cartHTML;
                cartTotalElement.textContent = `Rs. ${total.toFixed(2)}`;
            }
            
            // Expose functions to global scope
            window.updateQuantity = function(index, change) {
                cart[index].quantity += change;
                
                if (cart[index].quantity <= 0) {
                    cart.splice(index, 1);
                }
                
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartCount();
                displayCart();
            };
            
            window.removeFromCart = function(index) {
                cart.splice(index, 1);
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartCount();
                displayCart();
            };
        });
    </script>
</body>
</html>