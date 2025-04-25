<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: auth/login.php");
    exit;
}

// Get user information
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

// Process order submission
$order_success = false;
$order_id = null;
$error_message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate cart data from POST
    if (isset($_POST['cart_data']) && !empty($_POST['cart_data'])) {
        $cart_data = json_decode($_POST['cart_data'], true);
        
        if (!empty($cart_data)) {
            // Start transaction
            $conn->begin_transaction();
            
            try {
                // Get form data
                $payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'cash';
                $delivery_address = isset($_POST['delivery_address']) ? $_POST['delivery_address'] : $user_info['address'];
                $contact_number = isset($_POST['contact_number']) ? $_POST['contact_number'] : $user_info['phone_number'];
                $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
                $total_amount = 0;
                
                // Calculate total amount
                foreach ($cart_data as $item) {
                    $total_amount += $item['price'] * $item['quantity'];
                }
                
                // Insert order into database
                $sql = "INSERT INTO orders (user_id, total_amount, status, payment_method, delivery_address, contact_number, notes) 
                        VALUES (?, ?, 'pending', ?, ?, ?, ?)";
                
                if ($stmt = $conn->prepare($sql)) {
                    $stmt->bind_param("idssss", $_SESSION["user_id"], $total_amount, $payment_method, $delivery_address, $contact_number, $notes);
                    
                    if ($stmt->execute()) {
                        $order_id = $conn->insert_id;
                        
                        // Insert order items
                        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price_per_unit, subtotal) VALUES (?, ?, ?, ?, ?)";
                        
                        if ($stmt_items = $conn->prepare($sql)) {
                            foreach ($cart_data as $item) {
                                $product_id = $item['id'];
                                $quantity = $item['quantity'];
                                $price = $item['price'];
                                $subtotal = $price * $quantity;
                                
                                $stmt_items->bind_param("iiids", $order_id, $product_id, $quantity, $price, $subtotal);
                                $stmt_items->execute();
                            }
                            
                            $stmt_items->close();
                            
                            // Update inventory (in a real application)
                            // This would reduce the quantity in the inventory table
                            
                            // Commit transaction
                            $conn->commit();
                            $order_success = true;
                        } else {
                            throw new Exception("Error preparing order items statement");
                        }
                    } else {
                        throw new Exception("Error inserting order");
                    }
                    
                    $stmt->close();
                } else {
                    throw new Exception("Error preparing order statement");
                }
            } catch (Exception $e) {
                // Roll back transaction on error
                $conn->rollback();
                $error_message = "An error occurred: " . $e->getMessage();
            }
        } else {
            $error_message = "Cart is empty";
        }
    } else {
        $error_message = "No cart data received";
    }
}

// Get active promotions
$promotions = array();
$sql = "SELECT * FROM promotions WHERE is_active = 1 AND start_date <= NOW() AND end_date >= NOW() LIMIT 1";
$result = $conn->query($sql);
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $promotions[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sri Lanka Ice Cream Shop</title>
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

    <div class="container my-5">
        <?php if ($order_success): ?>
        <!-- Order Success Message -->
        <div class="row">
            <div class="col-md-8 mx-auto">
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h3 class="mb-0"><i class="fas fa-check-circle me-2"></i> Order Placed Successfully</h3>
                    </div>
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                        <h4>Thank you for your order!</h4>
                        <p class="lead">Your order #<?php echo $order_id; ?> has been placed successfully.</p>
                        <p>We will process your order soon. You can track your order status in your order history.</p>
                        <div class="mt-4">
                            <a href="orders.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-history me-2"></i> View Order History
                            </a>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i> Return to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Script to clear cart after successful order -->
        <script>
            localStorage.removeItem('iceCreamCart');
        </script>
        
        <?php else: ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <h1 class="mb-4">Checkout</h1>
        
        <div class="row">
            <!-- Order Summary -->
            <div class="col-md-8 mb-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i> Order Summary</h5>
                    </div>
                    <div class="card-body">
                        <div id="checkout-items">
                            <!-- Items will be loaded here by JavaScript -->
                            <p class="text-center py-4">Loading cart items...</p>
                        </div>
                    </div>
                    <div class="card-footer">
                        <div class="d-flex justify-content-between">
                            <h5>Subtotal:</h5>
                            <h5 id="subtotal">Rs. 0.00</h5>
                        </div>
                        
                        <?php if (!empty($promotions)): ?>
                        <div class="d-flex justify-content-between text-success">
                            <h6>Discount (<?php echo $promotions[0]['name']; ?>):</h6>
                            <h6 id="discount">Rs. 0.00</h6>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-flex justify-content-between fw-bold">
                            <h4>Total:</h4>
                            <h4 id="total">Rs. 0.00</h4>
                        </div>
                    </div>
                </div>

                <!-- Checkout Form -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-file-invoice me-2"></i> Order Details</h5>
                    </div>
                    <div class="card-body">
                        <form id="checkout-form" method="post" action="checkout.php">
                            <input type="hidden" name="cart_data" id="cart_data_input">
                            
                            <div class="mb-3">
                                <label for="delivery_address" class="form-label">Delivery Address</label>
                                <textarea class="form-control" id="delivery_address" name="delivery_address" rows="3" required><?php echo htmlspecialchars($user_info['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="contact_number" class="form-label">Contact Number</label>
                                <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?php echo htmlspecialchars($user_info['phone_number'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Payment Method</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_cash" value="cash" checked>
                                    <label class="form-check-label" for="payment_cash">
                                        <i class="fas fa-money-bill-wave me-2"></i> Cash on Delivery
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_card" value="card">
                                    <label class="form-check-label" for="payment_card">
                                        <i class="fas fa-credit-card me-2"></i> Card on Delivery
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="payment_method" id="payment_online" value="online">
                                    <label class="form-check-label" for="payment_online">
                                        <i class="fas fa-globe me-2"></i> Online Payment
                                    </label>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Order Notes (optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Any special instructions for your order?"></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary" id="place-order-btn">
                                    <i class="fas fa-check-circle me-2"></i> Place Order
                                </button>
                                <a href="index.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i> Continue Shopping
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <!-- Order Information -->
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Order Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Customer:</strong> <?php echo htmlspecialchars($user_info['full_name']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($user_info['email']); ?></p>
                        
                        <hr>
                        
                        <h6><i class="fas fa-truck me-2"></i> Delivery Information</h6>
                        <p class="small">We deliver within Galle city limits within 1 hour. For other areas, delivery times may vary.</p>
                        
                        <hr>
                        
                        <h6><i class="fas fa-shield-alt me-2"></i> Secure Payment</h6>
                        <p class="small">All transactions are secure and encrypted. For online payments, we accept major credit/debit cards.</p>
                    </div>
                </div>
                
                <?php if (!empty($promotions)): ?>
                <div class="card mb-4 border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-tag me-2"></i> Applied Promotion</h5>
                    </div>
                    <div class="card-body">
                        <h5 class="text-success"><?php echo htmlspecialchars($promotions[0]['name']); ?></h5>
                        <p><?php echo htmlspecialchars($promotions[0]['description']); ?></p>
                        
                        <?php if ($promotions[0]['discount_percentage'] > 0): ?>
                        <div class="alert alert-success">
                            <strong><?php echo $promotions[0]['discount_percentage']; ?>% OFF</strong> on your order
                        </div>
                        <?php elseif ($promotions[0]['discount_amount'] > 0): ?>
                        <div class="alert alert-success">
                            <strong>Rs. <?php echo $promotions[0]['discount_amount']; ?> OFF</strong> on your order
                        </div>
                        <?php endif; ?>
                        
                        <p class="small text-muted">
                            Valid until: <?php echo date("F j, Y", strtotime($promotions[0]['end_date'])); ?>
                        </p>
                    </div>
                </div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-question-circle me-2"></i> Need Help?</h5>
                    </div>
                    <div class="card-body">
                        <p><i class="fas fa-phone me-2"></i> Call: +94 77 123 4567</p>
                        <p><i class="fas fa-envelope me-2"></i> Email: support@srilankaic.com</p>
                        <hr>
                        <p class="small">Our customer service team is available from 9:00 AM to 8:00 PM, seven days a week.</p>
                    </div>
                </div>
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
                            <a href="checkout.php" class="btn btn-primary">Checkout</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript for Checkout -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Get cart data from localStorage
            let cart = JSON.parse(localStorage.getItem('iceCreamCart')) || [];
            updateCartCount();
            
            // Display cart items in checkout
            displayCheckoutItems();
            
            // Add cart data to form before submit
            const checkoutForm = document.getElementById('checkout-form');
            if (checkoutForm) {
                const cartDataInput = document.getElementById('cart_data_input');
                cartDataInput.value = JSON.stringify(cart);
                
                checkoutForm.addEventListener('submit', function(e) {
                    if (cart.length === 0) {
                        e.preventDefault();
                        alert('Your cart is empty. Please add items to your cart before checkout.');
                    }
                });
            }
            
            // Cart button click - show modal
            document.getElementById('cart').addEventListener('click', function(e) {
                e.preventDefault();
                displayCartItems();
                const cartModal = new bootstrap.Modal(document.getElementById('cartModal'));
                cartModal.show();
            });
            
            // Display cart items in checkout
            function displayCheckoutItems() {
                const itemsContainer = document.getElementById('checkout-items');
                const subtotalElement = document.getElementById('subtotal');
                const totalElement = document.getElementById('total');
                const discountElement = document.getElementById('discount');
                
                if (cart.length === 0) {
                    itemsContainer.innerHTML = '<div class="alert alert-warning"><i class="fas fa-exclamation-circle me-2"></i> Your cart is empty. Please add items to your cart before checkout.</div>';
                    subtotalElement.textContent = 'Rs. 0.00';
                    totalElement.textContent = 'Rs. 0.00';
                    if (discountElement) discountElement.textContent = 'Rs. 0.00';
                    
                    // Disable checkout button
                    const placeOrderBtn = document.getElementById('place-order-btn');
                    if (placeOrderBtn) {
                        placeOrderBtn.disabled = true;
                    }
                    return;
                }
                
                let html = '<table class="table">';
                html += '<thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th></tr></thead>';
                html += '<tbody>';
                
                let subtotal = 0;
                
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    subtotal += itemTotal;
                    
                    html += `<tr>
                        <td>${item.name}</td>
                        <td>Rs. ${item.price.toFixed(2)}</td>
                        <td>${item.quantity}</td>
                        <td>Rs. ${itemTotal.toFixed(2)}</td>
                    </tr>`;
                });
                
                html += '</tbody></table>';
                itemsContainer.innerHTML = html;
                
                // Calculate discount if applicable
                let discount = 0;
                <?php if (!empty($promotions)): ?>
                <?php if ($promotions[0]['discount_percentage'] > 0): ?>
                discount = subtotal * (<?php echo $promotions[0]['discount_percentage']; ?> / 100);
                <?php elseif ($promotions[0]['discount_amount'] > 0): ?>
                discount = <?php echo $promotions[0]['discount_amount']; ?>;
                <?php endif; ?>
                <?php endif; ?>
                
                // Ensure discount doesn't exceed subtotal
                discount = Math.min(discount, subtotal);
                
                const total = subtotal - discount;
                
                subtotalElement.textContent = `Rs. ${subtotal.toFixed(2)}`;
                if (discountElement) discountElement.textContent = `Rs. ${discount.toFixed(2)}`;
                totalElement.textContent = `Rs. ${total.toFixed(2)}`;
            }
            
            // Display cart items in modal
            function displayCartItems() {
                const cartItemsContainer = document.getElementById('cart-items');
                const cartTotalElement = document.getElementById('cart-total');
                
                if (cart.length === 0) {
                    cartItemsContainer.innerHTML = '<p class="text-center py-4">Your cart is empty</p>';
                    cartTotalElement.textContent = 'Rs. 0.00';
                    return;
                }
                
                let html = '<table class="table">';
                html += '<thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th></th></tr></thead>';
                html += '<tbody>';
                
                let total = 0;
                
                cart.forEach((item, index) => {
                    const itemTotal = item.price * item.quantity;
                    total += itemTotal;
                    
                    html += `<tr>
                        <td>${item.name}</td>
                        <td>Rs. ${item.price.toFixed(2)}</td>
                        <td>
                            <div class="input-group input-group-sm" style="width: 100px;">
                                <button class="btn btn-outline-secondary decrease-qty" data-index="${index}">-</button>
                                <input type="text" class="form-control text-center" value="${item.quantity}" readonly>
                                <button class="btn btn-outline-secondary increase-qty" data-index="${index}">+</button>
                            </div>
                        </td>
                        <td>Rs. ${itemTotal.toFixed(2)}</td>
                        <td><button class="btn btn-sm btn-danger remove-item" data-index="${index}"><i class="fas fa-trash"></i></button></td>
                    </tr>`;
                });
                
                html += '</tbody></table>';
                cartItemsContainer.innerHTML = html;
                cartTotalElement.textContent = `Rs. ${total.toFixed(2)}`;
                
                // Add event listeners for cart manipulation
                const decreaseButtons = document.querySelectorAll('.decrease-qty');
                const increaseButtons = document.querySelectorAll('.increase-qty');
                const removeButtons = document.querySelectorAll('.remove-item');
                
                decreaseButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        if (cart[index].quantity > 1) {
                            cart[index].quantity -= 1;
                            localStorage.setItem('iceCreamCart', JSON.stringify(cart));
                            displayCartItems();
                            displayCheckoutItems();
                            updateCartCount();
                        }
                    });
                });
                
                increaseButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        cart[index].quantity += 1;
                        localStorage.setItem('iceCreamCart', JSON.stringify(cart));
                        displayCartItems();
                        displayCheckoutItems();
                        updateCartCount();
                    });
                });
                
                removeButtons.forEach(button => {
                    button.addEventListener('click', function() {
                        const index = parseInt(this.getAttribute('data-index'));
                        cart.splice(index, 1);
                        localStorage.setItem('iceCreamCart', JSON.stringify(cart));
                        displayCartItems();
                        displayCheckoutItems();
                        updateCartCount();
                    });
                });
            }
            
            // Update cart count in navbar
            function updateCartCount() {
                const cartCountElement = document.getElementById('cart-count');
                const totalItems = cart.reduce((total, item) => total + item.quantity, 0);
                cartCountElement.textContent = totalItems;
            }
        });
    </script>
</body>
</html>