-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS dilli_style;

-- Use the database
USE dilli_style;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'USER',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    description TEXT,
    image_url VARCHAR(255),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10, 2) NOT NULL,
    category_id INT NOT NULL,
    image_url VARCHAR(255),
    stock_quantity INT NOT NULL DEFAULT 0,
    featured TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
);

-- Carts table
CREATE TABLE IF NOT EXISTS carts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Cart Items table
CREATE TABLE IF NOT EXISTS cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cart_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    FOREIGN KEY (cart_id) REFERENCES carts(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY (cart_id, product_id)
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'PENDING',
    shipping_address TEXT NOT NULL,
    tracking_number VARCHAR(100),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Order Items table
CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);

-- Demo data: Add an admin user (Password: admin123)
INSERT INTO users (username, email, password, role) VALUES 
('admin', 'admin@dillistyle.com', '$2y$10$HdaKOWkHRywz57mQmx1GrOIrEKIeiy5Y.yGtBXDVEu2d2iGfmNEN.', 'ADMIN');

-- Demo data: Add a regular user (Password: user123)
INSERT INTO users (username, email, password, role) VALUES 
('user', 'user@dillistyle.com', '$2y$10$zOl/9vV7.fgU9h61iNLrSOVABQJJJn0OyiM9HtTQKs/QHGQ.n.OFa', 'USER');

-- Demo data: Add categories
INSERT INTO categories (name, description, image_url) VALUES 
('Men', 'Men\'s Clothing', 'https://example.com/images/men.jpg'),
('Women', 'Women\'s Clothing', 'https://example.com/images/women.jpg'),
('Kids', 'Kids\' Clothing', 'https://example.com/images/kids.jpg'),
('Accessories', 'Fashion Accessories', 'https://example.com/images/accessories.jpg');

-- Demo data: Add products
INSERT INTO products (name, description, price, category_id, image_url, stock_quantity, featured) VALUES 
('Men\'s T-Shirt', 'Comfortable cotton t-shirt for men', 24.99, 1, 'https://example.com/images/tshirt.jpg', 100, 1),
('Women\'s Dress', 'Elegant dress for women', 49.99, 2, 'https://example.com/images/dress.jpg', 50, 1),
('Kids\' Jeans', 'Durable jeans for kids', 29.99, 3, 'https://example.com/images/kids-jeans.jpg', 75, 0),
('Leather Belt', 'Premium leather belt', 19.99, 4, 'https://example.com/images/belt.jpg', 120, 0),
('Men\'s Formal Shirt', 'Formal shirt for men', 34.99, 1, 'https://example.com/images/formal-shirt.jpg', 85, 1),
('Women\'s Jeans', 'Stylish jeans for women', 39.99, 2, 'https://example.com/images/women-jeans.jpg', 60, 0); 