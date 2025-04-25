<?php
// Include database connection
require_once 'includes/db_connect.php';

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: auth/login.php");
    exit;
}

// Initialize variables
$username = $email = $full_name = $phone_number = $address = "";
$username_err = $email_err = $full_name_err = $phone_err = $address_err = $password_err = "";
$success_message = $error_message = "";

// Get user info
$user_id = $_SESSION["user_id"];
$sql = "SELECT * FROM users WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $user_info = $result->fetch_assoc();
            $username = $user_info['username'];
            $email = $user_info['email'];
            $full_name = $user_info['full_name'];
            $phone_number = $user_info['phone_number'];
            $address = $user_info['address'];
        } else {
            $error_message = "Error retrieving user information.";
        }
    } else {
        $error_message = "Something went wrong. Please try again later.";
    }
    $stmt->close();
}

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Check which form was submitted
    if (isset($_POST["update_profile"])) {
        // Validate and process profile update
        
        // Validate full name
        if (empty(trim($_POST["full_name"]))) {
            $full_name_err = "Please enter your full name.";
        } else {
            $full_name = trim($_POST["full_name"]);
        }
        
        // Validate email - cannot be empty and must be valid format
        if (empty(trim($_POST["email"]))) {
            $email_err = "Please enter your email address.";
        } elseif (!filter_var(trim($_POST["email"]), FILTER_VALIDATE_EMAIL)) {
            $email_err = "Please enter a valid email address.";
        } else {
            // Check if email exists (but is not the user's current email)
            $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $param_email, $user_id);
                $param_email = trim($_POST["email"]);
                if ($stmt->execute()) {
                    $stmt->store_result();
                    if ($stmt->num_rows > 0) {
                        $email_err = "This email is already taken.";
                    } else {
                        $email = trim($_POST["email"]);
                    }
                } else {
                    $error_message = "Oops! Something went wrong. Please try again later.";
                }
                $stmt->close();
            }
        }
        
        // Phone number and address are optional but should be validated if provided
        $phone_number = trim($_POST["phone_number"]);
        $address = trim($_POST["address"]);
        
        // If there are no errors, proceed with update
        if (empty($full_name_err) && empty($email_err)) {
            $sql = "UPDATE users SET full_name = ?, email = ?, phone_number = ?, address = ? WHERE user_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("ssssi", $param_full_name, $param_email, $param_phone, $param_address, $param_user_id);
                $param_full_name = $full_name;
                $param_email = $email;
                $param_phone = $phone_number;
                $param_address = $address;
                $param_user_id = $user_id;
                
                if ($stmt->execute()) {
                    $success_message = "Profile updated successfully!";
                } else {
                    $error_message = "Something went wrong. Please try again later.";
                }
                $stmt->close();
            }
        }
    } elseif (isset($_POST["change_password"])) {
        // Validate and process password change
        
        // Validate current password
        if (empty(trim($_POST["current_password"]))) {
            $password_err = "Please enter your current password.";
        } else {
            // Verify current password
            $sql = "SELECT password FROM users WHERE user_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("i", $user_id);
                if ($stmt->execute()) {
                    $result = $stmt->get_result();
                    if ($row = $result->fetch_assoc()) {
                        $hashed_password = $row["password"];
                        
                        // For demonstration, using a direct comparison
                        // In production, use password_verify()
                        if (trim($_POST["current_password"]) != $hashed_password) {
                            $password_err = "The current password you entered is not correct.";
                        }
                    }
                } else {
                    $error_message = "Oops! Something went wrong. Please try again later.";
                }
                $stmt->close();
            }
        }
        
        // Validate new password
        if (empty(trim($_POST["new_password"]))) {
            $password_err = "Please enter a new password.";
        } elseif (strlen(trim($_POST["new_password"])) < 6) {
            $password_err = "Password must have at least 6 characters.";
        }
        
        // Validate confirm password
        if (empty(trim($_POST["confirm_password"]))) {
            $password_err = "Please confirm the password.";
        } else {
            if (trim($_POST["new_password"]) != trim($_POST["confirm_password"])) {
                $password_err = "Password confirmation does not match.";
            }
        }
        
        // If there are no errors, proceed with update
        if (empty($password_err)) {
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("si", $param_password, $param_user_id);
                
                // Set parameters
                // In production, use password_hash()
                $param_password = trim($_POST["new_password"]);
                $param_user_id = $user_id;
                
                if ($stmt->execute()) {
                    $success_message = "Password changed successfully!";
                } else {
                    $error_message = "Something went wrong. Please try again later.";
                }
                $stmt->close();
            }
        }
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

// Get user's order history summary
$order_count = $total_spent = 0;
$sql = "SELECT COUNT(*) as order_count, SUM(total_amount) as total_spent FROM orders WHERE user_id = ?";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $order_count = $row['order_count'] ?? 0;
            $total_spent = $row['total_spent'] ?? 0;
        }
    }
    $stmt->close();
}

// Get recent orders
$recent_orders = array();
$sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 3";
if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $recent_orders[] = $row;
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
    <title>My Profile - Sri Lanka Ice Cream Shop</title>
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
                        <a class="nav-link dropdown-toggle active" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($_SESSION["username"]); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item active" href="profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php">Order History</a></li>
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
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-lg-3 mb-4">
                <div class="card">
                    <div class="card-body text-center">
                        <div class="profile-image mb-3">
                            <i class="fas fa-user-circle fa-5x text-primary"></i>
                        </div>
                        <h5 class="mb-1"><?php echo htmlspecialchars($full_name); ?></h5>
                        <p class="text-muted small mb-3">@<?php echo htmlspecialchars($username); ?></p>
                        <div class="profile-info">
                            <p class="mb-2"><i class="fas fa-envelope me-2 text-muted"></i><?php echo htmlspecialchars($email); ?></p>
                            <?php if (!empty($phone_number)): ?>
                            <p class="mb-2"><i class="fas fa-phone me-2 text-muted"></i><?php echo htmlspecialchars($phone_number); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#account-details" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                            <i class="fas fa-id-card me-2"></i> Account Details
                        </a>
                        <a href="#change-password" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <i class="fas fa-key me-2"></i> Change Password
                        </a>
                        <a href="#order-summary" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <i class="fas fa-history me-2"></i> Order Summary
                        </a>
                        <?php if ($_SESSION["role"] == "admin" || $_SESSION["role"] == "staff"): ?>
                        <a href="admin/dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i> Admin Panel
                        </a>
                        <?php endif; ?>
                        <a href="auth/logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
                
                <!-- Member Status Card -->
                <div class="card mt-4">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-gem me-2 text-primary"></i>Member Status</h5>
                        <p class="mb-2">Orders Placed: <strong><?php echo $order_count; ?></strong></p>
                        <p>Total Spent: <strong>Rs. <?php echo number_format($total_spent, 2); ?></strong></p>
                        <?php
                        // Determine member status based on total spent
                        $status = "Bronze";
                        $progress = 0;
                        
                        if ($total_spent >= 10000) {
                            $status = "Platinum";
                            $progress = 100;
                        } elseif ($total_spent >= 5000) {
                            $status = "Gold";
                            $progress = 75;
                            $remaining = 10000 - $total_spent;
                        } elseif ($total_spent >= 2000) {
                            $status = "Silver";
                            $progress = 50;
                            $remaining = 5000 - $total_spent;
                        } elseif ($total_spent > 0) {
                            $progress = 25;
                            $remaining = 2000 - $total_spent;
                        }
                        ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><?php echo $status; ?> Member</span>
                            <?php if ($status !== "Platinum"): ?>
                            <span class="small text-muted">Rs. <?php echo number_format($remaining, 2); ?> to next level</span>
                            <?php endif; ?>
                        </div>
                        <div class="progress">
                            <div class="progress-bar bg-primary" role="progressbar" style="width: <?php echo $progress; ?>%" aria-valuenow="<?php echo $progress; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Profile Content -->
            <div class="col-lg-9">
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
                
                <div class="tab-content">
                    <!-- Account Details Tab -->
                    <div class="tab-pane fade show active" id="account-details">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-user-edit me-2"></i>Account Details</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="username" class="form-label">Username</label>
                                            <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" disabled>
                                            <div class="form-text text-muted">Username cannot be changed</div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label">Email Address</label>
                                            <input type="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>">
                                            <div class="invalid-feedback"><?php echo $email_err; ?></div>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name</label>
                                        <input type="text" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" id="full_name" name="full_name" value="<?php echo htmlspecialchars($full_name); ?>">
                                        <div class="invalid-feedback"><?php echo $full_name_err; ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone_number" class="form-label">Phone Number</label>
                                        <input type="text" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>">
                                        <div class="invalid-feedback"><?php echo $phone_err; ?></div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control <?php echo (!empty($address_err)) ? 'is-invalid' : ''; ?>" id="address" name="address" rows="3"><?php echo htmlspecialchars($address); ?></textarea>
                                        <div class="invalid-feedback"><?php echo $address_err; ?></div>
                                    </div>
                                    <button type="submit" name="update_profile" class="btn btn-primary">Update Profile</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password Tab -->
                    <div class="tab-pane fade" id="change-password">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-key me-2"></i>Change Password</h5>
                            </div>
                            <div class="card-body">
                                <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                                    <?php if (!empty($password_err)): ?>
                                    <div class="alert alert-danger"><?php echo $password_err; ?></div>
                                    <?php endif; ?>
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new_password" name="new_password" required>
                                        <div class="form-text">Password must be at least 6 characters long.</div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                                    </div>
                                    <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order Summary Tab -->
                    <div class="tab-pane fade" id="order-summary">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-shopping-bag me-2"></i>Recent Orders</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($recent_orders)): ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-shopping-cart fa-3x mb-3 text-muted"></i>
                                    <p>You haven't placed any orders yet.</p>
                                    <a href="index.php#products" class="btn btn-primary">Start Shopping</a>
                                </div>
                                <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Order #</th>
                                                <th>Date</th>
                                                <th>Amount</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recent_orders as $order): ?>
                                            <tr>
                                                <td>#<?php echo $order['order_id']; ?></td>
                                                <td><?php echo date("M d, Y", strtotime($order['order_date'])); ?></td>
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
                                                <td>
                                                    <a href="order_details.php?id=<?php echo $order['order_id']; ?>" class="btn btn-sm btn-outline-primary">Details</a>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <div class="text-center mt-3">
                                    <a href="orders.php" class="btn btn-outline-primary">View All Orders</a>
                                </div>
                                <?php endif; ?>
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
            
            // Initialize the tab functionality that was cut off
            // Check if there's a hash in the URL
            const hash = window.location.hash;
            if (hash) {
                // Find the tab link that corresponds to the hash and activate it
                const tabLink = document.querySelector(`a[href="${hash}"]`);
                if (tabLink) {
                    const tab = new bootstrap.Tab(tabLink);
                    tab.show();
                }
            }
            
            // Add click event listeners to tab links to update the URL hash
            document.querySelectorAll('.list-group-item').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.getAttribute('href').startsWith('#')) {
                        window.location.hash = this.getAttribute('href');
                    }
                });
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
            
            // Add to cart function for product pages
            window.addToCart = function(productId, name, price, image, quantity = 1) {
                // Check if product already exists in cart
                const existingItemIndex = cart.findIndex(item => item.id === productId);
                
                if (existingItemIndex !== -1) {
                    // Update quantity if product already in cart
                    cart[existingItemIndex].quantity += quantity;
                } else {
                    // Add new item to cart
                    cart.push({
                        id: productId,
                        name: name,
                        price: price,
                        image: image,
                        quantity: quantity
                    });
                }
                
                // Save cart to localStorage
                localStorage.setItem('cart', JSON.stringify(cart));
                updateCartCount();
                
                // Show toast notification
                const toast = new bootstrap.Toast(document.getElementById('addToCartToast'));
                document.getElementById('toast-product-name').textContent = name;
                toast.show();
            };
        });

        // Password validation
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            if (newPasswordInput && confirmPasswordInput) {
                confirmPasswordInput.addEventListener('input', function() {
                    if (newPasswordInput.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.setCustomValidity("Passwords don't match");
                    } else {
                        confirmPasswordInput.setCustomValidity('');
                    }
                });
                
                newPasswordInput.addEventListener('input', function() {
                    if (newPasswordInput.value.length < 6) {
                        newPasswordInput.setCustomValidity("Password must be at least 6 characters long");
                    } else {
                        newPasswordInput.setCustomValidity('');
                        // Also check confirm password match when new password changes
                        if (confirmPasswordInput.value) {
                            if (newPasswordInput.value !== confirmPasswordInput.value) {
                                confirmPasswordInput.setCustomValidity("Passwords don't match");
                            } else {
                                confirmPasswordInput.setCustomValidity('');
                            }
                        }
                    }
                });
            }
        });
        </script>
    </body>
</html>