-- Create database if not exists and use it
CREATE DATABASE IF NOT EXISTS electrostore;
USE electrostore;

-- Drop tables if they exist (in order to avoid foreign key constraint issues)
DROP TABLE IF EXISTS purchases;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS users;

-- Users table 
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Purchases table
CREATE TABLE purchases (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample products
INSERT INTO products (name, description, price, image_path) VALUES
('HP Pavilion 13.3', 'A sleek and lightweight laptop designed for portability and performance. The HP Pavilion 13.3" delivers crisp visuals, fast processing, and long battery life—perfect for students, professionals, and on-the-go users.', 699, 'img/laptop1.jpg'),
('HP 15 Notebook', 'Combining everyday reliability with solid performance, the HP 15 Notebook is ideal for work, school, or home use. Enjoy a spacious display, responsive computing, and dependable battery life in one affordable package.', 799, 'img/laptop2.jpg'),
('Hp Desktop Elite core i5', 'Built for business and multitasking, the HP Elite Desktop with Intel Core i5 processor delivers robust performance in a compact design. A perfect choice for office productivity, data tasks, and smooth daily computing.', 1299, 'img/pc1.jpg'),
('HP Pavilion Gaming TG01', 'Unleash your gaming potential with the HP Pavilion TG01 Gaming Desktop. Powered by advanced graphics and high-speed processors, it’s built to handle modern games and creative workloads without compromise.', 1499, 'img/pc2.jpg'),
('HP Pro Tablet 10 EE G1', 'Engineered for education and field use, the HP Pro Tablet 10 EE G1 combines durability with enterprise-grade features. With stylus support and Windows integration, it''s a smart tool for learning and productivity.', 499, 'img/tab1.jpeg'),
('HP Elite x2 1012 G2', 'Versatile and powerful, the HP Elite x2 1012 G2 is a 2-in-1 device that adapts to your workflow. With detachable keyboard, touchscreen, and business-class security, it''s the ideal companion for mobile professionals.', 699, 'img/tab2.png'),
('HP LaserJet Pro MFP', 'Print, scan, copy, and fax with ease using the HP LaserJet Pro MFP. This all-in-one laser printer delivers fast, high-quality output and is designed to keep your office running smoothly and efficiently.', 449, 'img/printer1.jpg'),
('HP Officejet 3830', 'A compact all-in-one inkjet printer perfect for home or small office use. The HP OfficeJet 3830 offers wireless printing, quiet operation, and mobile compatibility at a great value.', 299, 'img/printer2.png');