-- --------------------------------------------------------
-- Database: aleppogift
-- Full e-commerce structure

CREATE DATABASE IF NOT EXISTS aleppogift;
USE aleppogift;

-- Admin Table
CREATE TABLE admin (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL,
  password VARCHAR(255) NOT NULL,
  email VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO admin (username, password, email)
VALUES ('admin', '$2y$10$9s0Dz9yEFpZ/69YpQ/1a0OTm7UThcVZcge7nE2gZlqPdQ3LriV.lW', 'admin@aleppogift.com');
-- Default password = admin123 (hashed with bcrypt)

-- Customers Table
CREATE TABLE customers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(100),
  email VARCHAR(100),
  phone VARCHAR(20),
  address VARCHAR(255),
  city VARCHAR(100),
  country VARCHAR(100),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Brands Table
CREATE TABLE brands (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(255),
  name_en VARCHAR(255),
  status INT DEFAULT 1
);

-- Categories Table
CREATE TABLE categories (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name_ar VARCHAR(255),
  name_en VARCHAR(255),
  status INT DEFAULT 1
);

-- Products Table
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  category_id INT,
  brand_id INT,
  name_ar VARCHAR(255),
  name_en VARCHAR(255),
  description_ar TEXT,
  description_en TEXT,
  price DECIMAL(10,2),
  stock INT DEFAULT 0,
  featured INT DEFAULT 0,
  status INT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Product Images Table (for multiple images per product)
CREATE TABLE product_images (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  image_path VARCHAR(255)
);

-- Product Variations Table (color, size, etc.)
CREATE TABLE product_variations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  size VARCHAR(50),
  color VARCHAR(50),
  additional_price DECIMAL(10,2) DEFAULT 0.00,
  stock INT DEFAULT 0
);

-- Orders Table
CREATE TABLE orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_id INT,
  order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  total_amount DECIMAL(10,2),
  payment_status ENUM('pending','paid','failed') DEFAULT 'pending',
  payment_method VARCHAR(50),
  payment_reference VARCHAR(255),
  invoice_pdf VARCHAR(255)
);

-- Order Items Table
CREATE TABLE order_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT,
  product_id INT,
  variation_id INT,
  quantity INT,
  price DECIMAL(10,2)
);

-- Visitors Tracking Table (optional expansion)
CREATE TABLE visitors (
  id INT AUTO_INCREMENT PRIMARY KEY,
  ip_address VARCHAR(50),
  visit_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
