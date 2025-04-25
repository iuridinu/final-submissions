-- Ice Cream Shop Database for Sri Lanka Ice Cream Shop at Galle
-- Database Name: ice_cream_shop
CREATE Database ice_cream_shop;
USE ice_cream_shop;

-- Users Table for Authentication
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'staff', 'customer') DEFAULT 'customer',
    phone_number VARCHAR(15),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories Table for Ice Cream Types
CREATE TABLE categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL,
    description TEXT
);

-- Products Table for Ice Cream Products
CREATE TABLE products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    category_id INT,
    price DECIMAL(10, 2) NOT NULL,
    image_path VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- Orders Table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'completed', 'cancelled') DEFAULT 'pending',
    payment_method ENUM('cash', 'card', 'online') DEFAULT 'cash',
    delivery_address TEXT,
    contact_number VARCHAR(15),
    notes TEXT,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Order Items Table for Individual Items in an Order
CREATE TABLE order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price_per_unit DECIMAL(10, 2) NOT NULL,
    subtotal DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Inventory Table for Stock Management
CREATE TABLE inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    last_restock_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Special Offers and Promotions
CREATE TABLE promotions (
    promotion_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    discount_percentage DECIMAL(5, 2),
    discount_amount DECIMAL(10, 2),
    start_date DATETIME NOT NULL,
    end_date DATETIME NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);

-- Product Reviews
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

-- Insert sample data for testing
-- Categories
INSERT INTO categories (name, description) VALUES 
('Traditional', 'Classic Sri Lankan ice cream flavors'),
('Fruit Based', 'Ice creams made with fresh local fruits'),
('Premium', 'Special premium ice creams with high-quality ingredients'),
('Specials', 'House specialty ice creams unique to our shop');

-- Sample Users (password would be hashed in real application)
INSERT INTO users (username, email, password, full_name, role, phone_number) VALUES 
('admin', 'admin@icecream.lk', '$2y$10$somehashedpassword', 'Admin User', 'admin', '+94771234567'),
('staff1', 'staff1@icecream.lk', '$2y$10$somehashedpassword', 'Staff Member', 'staff', '+94772345678'),
('customer1', 'customer1@gmail.com', '$2y$10$somehashedpassword', 'Sample Customer', 'customer', '+94773456789');

-- Sample Products
INSERT INTO products (name, description, category_id, price, is_available,image_path) VALUES 
('Vanilla', 'Classic vanilla ice cream', 1, 250.00, TRUE,'images/Homemade-Vanilla-Ice-Cream-Featured1-500x375.jpg'),
('Chocolate', 'Rich chocolate ice cream', 1, 250.00, TRUE,'images/8d7d00d68780e367d23fc01081541e10.avif'),
('Mango', 'Fresh mango ice cream', 2, 300.00, TRUE,'images/Mango-Ice-Cream-Thumbnail.jpg'),
('King Coconut', 'Sri Lankan king coconut ice cream', 2, 350.00, TRUE,'images/Coconut-Ice-cream-756x471_5_11zon.avif'),
('Jaggery Special', 'Premium ice cream made with traditional jaggery', 3, 400.00, TRUE,'images/f2d0be0abbbbc9df630807828f8bc103_Coconut_Ice_Cream_with_Grated_Jaggery.jpg'),
('Cinnamon Surprise', 'Special ice cream with Ceylon cinnamon', 4, 450.00, TRUE,'images/home-made-special-cinnamon.jpg');