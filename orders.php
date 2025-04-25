<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: auth/login.php");
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION["user_id"];

// Function to get all orders for a user
function getUserOrders($conn, $user_id) {
    $orders = array();
    
    $sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $user_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $orders[] = $row;
            }
        }
        
        $stmt->close();
    }
    
    return $orders;
}

// Function to get order items for a specific order
function getOrderItems($conn, $order_id) {
    $items = array();
    
    $sql = "SELECT oi.*, p.name as product_name, p.image_path 
            FROM order_items oi
            JOIN products p ON oi.product_id = p.product_id
            WHERE oi.order_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("i", $order_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $items[] = $row;
            }
        }
        
        $stmt->close();
    }
    
    return $items;
}

// Get order details if order_id is provided in GET
$order_details = null;
$order_items = null;

if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $view_order_id = intval($_GET['order_id']);
    
    // Get order details
    $sql = "SELECT * FROM orders WHERE order_id = ? AND user_id = ?";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $view_order_id, $user_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $order_details = $result->fetch_assoc();
                $order_items = getOrderItems($conn, $view_order_id);
            } else {
                // Order doesn't exist or doesn't belong to this user
                header("location: orders.php");
                exit;
            }
        }
        
        $stmt->close();
    }
}

// Get all orders for the user
$orders = getUserOrders($conn, $user_id);

// Get categories for the navigation menu
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

$categories = getCategories($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Sri Lanka Ice Cream Shop</title>
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

    <!-- Page Content -->
    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Order History</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-history me-2"></i> Your Order History</h2>
                <hr>
            </div>
        </div>

        <?php if ($order_details): ?>
        <!-- Order Details View -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Order #<?php echo $order_details['order_id']; ?></h4>
                        <a href="orders.php" class="btn btn-sm btn-secondary">
                            <i class="fas fa-arrow-left me-1"></i> Back to All Orders
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <h5>Order Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="150">Order Date:</th>
                                        <td><?php echo date('F j, Y g:i A', strtotime($order_details['order_date'])); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Status:</th>
                                        <td>
                                            <?php 
                                            $status_class = '';
                                            switch($order_details['status']) {
                                                case 'pending':
                                                    $status_class = 'bg-warning';
                                                    break;
                                                case 'processing':
                                                    $status_class = 'bg-info';
                                                    break;
                                                case 'completed':
                                                    $status_class = 'bg-success';
                                                    break;
                                                case 'cancelled':
                                                    $status_class = 'bg-danger';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo ucfirst($order_details['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th>Payment Method:</th>
                                        <td><?php echo ucfirst($order_details['payment_method']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Total Amount:</th>
                                        <td class="fw-bold">Rs. <?php echo number_format($order_details['total_amount'], 2); ?></td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-md-6">
                                <h5>Delivery Information</h5>
                                <table class="table table-borderless">
                                    <tr>
                                        <th width="150">Address:</th>
                                        <td><?php echo htmlspecialchars($order_details['delivery_address']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Contact:</th>
                                        <td><?php echo htmlspecialchars($order_details['contact_number']); ?></td>
                                    </tr>
                                    <tr>
                                        <th>Notes:</th>
                                        <td><?php echo $order_details['notes'] ? htmlspecialchars($order_details['notes']) : 'No special instructions'; ?></td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <h5 class="mt-4">Order Items</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th width="80">Image</th>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th class="text-end">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <?php if ($item['image_path']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_path']); ?>" class="img-thumbnail" width="50" alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                                            <?php else: ?>
                                            <div class="bg-light d-flex align-items-center justify-content-center" style="width: 50px; height: 50px;">
                                                <i class="fas fa-ice-cream text-secondary"></i>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                        <td>Rs. <?php echo number_format($item['price_per_unit'], 2); ?></td>
                                        <td><?php echo $item['quantity']; ?></td>
                                        <td class="text-end">Rs. <?php echo number_format($item['subtotal'], 2); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="4" class="text-end">Total:</th>
                                        <th class="text-end">Rs. <?php echo number_format($order_details['total_amount'], 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <?php if ($order_details['status'] === 'pending'): ?>
                        <div class="text-end mt-3">
                            <button class="btn btn-danger cancel-order-btn" data-order-id="<?php echo $order_details['order_id']; ?>">
                                <i class="fas fa-times me-1"></i> Cancel Order
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php else: ?>
        <!-- Order History List -->
        <div class="row">
            <div class="col-12">
                <?php if (empty($orders)): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i> You haven't placed any orders yet.
                    <a href="index.php" class="alert-link">Browse our ice cream selection</a> to place your first order!
                </div>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Order #</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['order_id']; ?></td>
                                <td><?php echo date('F j, Y', strtotime($order['order_date'])); ?></td>
                                <td>Rs. <?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <?php 
                                    $status_class = '';
                                    switch($order['status']) {
                                        case 'pending':
                                            $status_class = 'bg-warning';
                                            break;
                                        case 'processing':
                                            $status_class = 'bg-info';
                                            break;
                                        case 'completed':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'bg-danger';
                                            break;
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo ucfirst($order['payment_method']); ?></td>
                                <td class="text-end">
                                    <a href="orders.php?order_id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-eye me-1"></i> View Details
                                    </a>
                                    <?php if ($order['status'] === 'pending'): ?>
                                    <button class="btn btn-sm btn-danger cancel-order-btn" data-order-id="<?php echo $order['order_id']; ?>">
                                        <i class="fas fa-times me-1"></i> Cancel
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
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

    <!-- Cancel Order Confirmation Modal -->
    <div class="modal fade" id="cancelOrderModal" tabindex="-1" aria-labelledby="cancelOrderModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="cancelOrderModalLabel"><i class="fas fa-exclamation-triangle me-2"></i> Cancel Order</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to cancel this order? This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">No, Keep Order</button>
                    <form id="cancel-order-form" action="process/cancel_order.php" method="post">
                        <input type="hidden" name="order_id" id="cancel-order-id" value="">
                        <button type="submit" class="btn btn-danger">Yes, Cancel Order</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

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
    
    <!-- Custom JavaScript -->
    <script src="js/main.js"></script>
    <script>
        // Script for cancel order modal
        document.addEventListener('DOMContentLoaded', function() {
            const cancelBtns = document.querySelectorAll('.cancel-order-btn');
            const cancelOrderIdInput = document.getElementById('cancel-order-id');
            const cancelOrderModal = new bootstrap.Modal(document.getElementById('cancelOrderModal'));
            
            cancelBtns.forEach(btn => {
                btn.addEventListener('click', function() {
                    const orderId = this.getAttribute('data-order-id');
                    cancelOrderIdInput.value = orderId;
                    cancelOrderModal.show();
                });
            });
        });
    </script>
</body>
</html>