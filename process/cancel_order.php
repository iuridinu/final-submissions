<?php
// Include database connection
require_once '../includes/db_connect.php';

// Check if user is logged in
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // Redirect to login page with error message
    $_SESSION["error"] = "Please log in to access this page.";
    header("location: ../auth/login.php");
    exit;
}

// Get the user ID from the session
$user_id = $_SESSION["user_id"];

// Check if form was submitted and order_id is provided
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["order_id"])) {
    $order_id = intval($_POST["order_id"]);
    
    // First, check if the order belongs to the current user and is in a cancellable state
    $sql = "SELECT order_id, status FROM orders WHERE order_id = ? AND user_id = ?";
    
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param("ii", $order_id, $user_id);
        
        if ($stmt->execute()) {
            $result = $stmt->get_result();
            
            if ($result->num_rows == 1) {
                $order = $result->fetch_assoc();
                
                // Check if the order is in a state that can be cancelled
                // Usually only pending orders can be cancelled
                if ($order["status"] == "pending") {
                    // Update the order status to cancelled
                    $update_sql = "UPDATE orders SET status = 'cancelled' WHERE order_id = ?";
                    
                    if ($update_stmt = $conn->prepare($update_sql)) {
                        $update_stmt->bind_param("i", $order_id);
                        
                        if ($update_stmt->execute()) {
                            // Success - order cancelled
                            
                            // Update inventory - return items to stock
                            // Get all items in the order
                            $items_sql = "SELECT product_id, quantity FROM order_items WHERE order_id = ?";
                            if ($items_stmt = $conn->prepare($items_sql)) {
                                $items_stmt->bind_param("i", $order_id);
                                if ($items_stmt->execute()) {
                                    $items_result = $items_stmt->get_result();
                                    
                                    // For each item, update the inventory
                                    while ($item = $items_result->fetch_assoc()) {
                                        $product_id = $item["product_id"];
                                        $quantity = $item["quantity"];
                                        
                                        // Update inventory
                                        $inventory_sql = "UPDATE inventory SET quantity = quantity + ? WHERE product_id = ?";
                                        if ($inventory_stmt = $conn->prepare($inventory_sql)) {
                                            $inventory_stmt->bind_param("ii", $quantity, $product_id);
                                            $inventory_stmt->execute();
                                            $inventory_stmt->close();
                                        }
                                    }
                                }
                                $items_stmt->close();
                            }
                            
                            // Set success message
                            $_SESSION["success"] = "Order #" . $order_id . " has been successfully cancelled.";
                        } else {
                            // Error updating order
                            $_SESSION["error"] = "Error cancelling the order. Please try again or contact support.";
                        }
                        
                        $update_stmt->close();
                    } else {
                        // Error preparing update statement
                        $_SESSION["error"] = "System error. Please try again later.";
                    }
                } else {
                    // Order is not in a cancellable state
                    $_SESSION["error"] = "This order cannot be cancelled because it's already " . $order["status"] . ".";
                }
            } else {
                // Order doesn't exist or doesn't belong to the user
                $_SESSION["error"] = "Invalid order selected.";
            }
        } else {
            // Error executing the query
            $_SESSION["error"] = "Database error. Please try again later.";
        }
        
        $stmt->close();
    } else {
        // Error preparing the statement
        $_SESSION["error"] = "System error. Please try again later.";
    }
} else {
    // Invalid request - no order_id provided
    $_SESSION["error"] = "Invalid request.";
}

// Log the cancellation for admin records
if (isset($_SESSION["success"])) {
    $log_message = "Order #" . $order_id . " cancelled by user ID " . $user_id . " (" . $_SESSION["username"] . ") on " . date("Y-m-d H:i:s");
    
    // You can implement your own logging system here
    // For example, write to a file or insert into a database table
    // file_put_contents('../logs/cancellations.log', $log_message . PHP_EOL, FILE_APPEND);
}

// Redirect back to orders page
if (isset($_GET["view"]) && $_GET["view"] == "detail") {
    // If cancelling from the detail view, go back to the orders list
    header("location: ../orders.php");
} else {
    // If already on the orders list, stay there
    header("location: ../orders.php");
}
exit;
?>