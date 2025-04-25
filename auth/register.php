<?php
// Include database connection
require_once '../includes/db_connect.php';

// Define variables and initialize with empty values
$username = $email = $password = $confirm_password = $full_name = $phone_number = "";
$username_err = $email_err = $password_err = $confirm_password_err = $full_name_err = $phone_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        // Prepare a select statement
        $sql = "SELECT user_id FROM users WHERE username = ?";
        
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("s", $param_username);
            
            // Set parameters
            $param_username = trim($_POST["username"]);
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Store result
                $stmt->store_result();
                
                if ($stmt->num_rows == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = sanitize_input($_POST["username"]);
                }
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Validate email
    if (empty(trim($_POST["email"]))) {
        $email_err = "Please enter an email.";
    } else {
        // Check if email is valid
        if (!filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) {
            $email_err = "Invalid email format.";
        } else {
            // Check if email is already registered
            $sql = "SELECT user_id FROM users WHERE email = ?";
            
            if ($stmt = $conn->prepare($sql)) {
                $stmt->bind_param("s", $param_email);
                $param_email = trim($_POST["email"]);
                
                if ($stmt->execute()) {
                    $stmt->store_result();
                    
                    if ($stmt->num_rows == 1) {
                        $email_err = "This email is already registered.";
                    } else {
                        $email = sanitize_input($_POST["email"]);
                    }
                } else {
                    echo "Oops! Something went wrong. Please try again later.";
                }
                
                $stmt->close();
            }
        }
    }
    
    // Validate full name
    if (empty(trim($_POST["full_name"]))) {
        $full_name_err = "Please enter your full name.";
    } else {
        $full_name = sanitize_input($_POST["full_name"]);
    }
    
    // Validate phone number (optional)
    if (!empty(trim($_POST["phone_number"]))) {
        // Simple validation - can be enhanced for Sri Lankan phone formats
        if (!preg_match('/^[0-9+\-\s]{7,15}$/', trim($_POST["phone_number"]))) {
            $phone_err = "Please enter a valid phone number.";
        } else {
            $phone_number = sanitize_input($_POST["phone_number"]);
        }
    }
    
    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";     
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }
    
    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";     
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }
    
    // Check input errors before inserting in database
    if (empty($username_err) && empty($email_err) && empty($password_err) && 
        empty($confirm_password_err) && empty($full_name_err) && empty($phone_err)) {
        
        // Prepare an insert statement
        $sql = "INSERT INTO users (username, email, password, full_name, phone_number, role) VALUES (?, ?, ?, ?, ?, ?)";
         
        if ($stmt = $conn->prepare($sql)) {
            // Bind variables to the prepared statement as parameters
            $stmt->bind_param("ssssss", $param_username, $param_email, $param_password, $param_full_name, $param_phone, $param_role);
            
            // Set parameters
            $param_username = $username;
            $param_email = $email;
            $param_password = password_hash($password, PASSWORD_DEFAULT); // Creates a password hash
            $param_full_name = $full_name;
            $param_phone = $phone_number;
            $param_role = "customer"; // Default role for new registrations
            
            // Attempt to execute the prepared statement
            if ($stmt->execute()) {
                // Redirect to login page
                header("location: login.php");
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            // Close statement
            $stmt->close();
        }
    }
    
    // Close connection
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Sri Lanka Ice Cream Shop</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../css/styles.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-4 mb-4">
            <div class="col-lg-6 col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h2>Sri Lanka Ice Cream Shop</h2>
                        <p>Galle Branch</p>
                    </div>
                    <div class="card-body p-4">
                        <h3 class="text-center mb-4">Create an Account</h3>
                        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" name="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>">
                                <span class="invalid-feedback"><?php echo $username_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control <?php echo (!empty($email_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $email; ?>">
                                <span class="invalid-feedback"><?php echo $email_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Full Name</label>
                                <input type="text" name="full_name" class="form-control <?php echo (!empty($full_name_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $full_name; ?>">
                                <span class="invalid-feedback"><?php echo $full_name_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Phone Number (Optional)</label>
                                <input type="text" name="phone_number" class="form-control <?php echo (!empty($phone_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $phone_number; ?>" placeholder="+94 77 123 4567">
                                <span class="invalid-feedback"><?php echo $phone_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Password</label>
                                <input type="password" name="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $password_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Confirm Password</label>
                                <input type="password" name="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>">
                                <span class="invalid-feedback"><?php echo $confirm_password_err; ?></span>
                            </div>
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary w-100">Sign Up</button>
                            </div>
                            <p class="text-center">Already have an account? <a href="login.php">Login here</a>.</p>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>