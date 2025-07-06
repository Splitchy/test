-- Logistics Management System Database Schema

-- Users table with role-based access
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    role ENUM('client', 'livreur', 'admin') NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'suspended') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Client-specific fields
    cin VARCHAR(50),
    store_name VARCHAR(255),
    bank_info TEXT,
    stock_management_enabled BOOLEAN DEFAULT FALSE,
    
    -- Livreur-specific fields
    delivery_fee DECIMAL(10,2),
    refusal_fee DECIMAL(10,2),
    cin_front_path VARCHAR(255),
    cin_back_path VARCHAR(255),
    rib_path VARCHAR(255),
    
    INDEX idx_email (email),
    INDEX idx_role (role),
    INDEX idx_status (status)
);

-- Cities table
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    zone_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_zone (zone_id)
);

-- Zones table
CREATE TABLE zones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Tariffs table per city/zone
CREATE TABLE tariffs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    city_id INT,
    zone_id INT,
    delivery_price DECIMAL(10,2) NOT NULL,
    refusal_price DECIMAL(10,2) NOT NULL,
    return_price DECIMAL(10,2) NOT NULL,
    standard_delivery_time INT NOT NULL, -- in hours
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES zones(id) ON DELETE CASCADE,
    INDEX idx_city (city_id),
    INDEX idx_zone (zone_id)
);

-- Stock items table
CREATE TABLE stock_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    reference VARCHAR(100) NOT NULL,
    quantity INT NOT NULL,
    description TEXT,
    photo_path VARCHAR(255),
    status ENUM('EN_ATTENTE', 'APPROUVE', 'REFUSE') DEFAULT 'EN_ATTENTE',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status)
);

-- Orders table (unified for both ramassage and stock)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    livreur_id INT,
    type ENUM('ramassage', 'stock') NOT NULL,
    reference VARCHAR(100) NOT NULL,
    
    -- Product information
    product_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    
    -- Delivery information
    phone VARCHAR(20) NOT NULL,
    city_id INT NOT NULL,
    address TEXT NOT NULL,
    note TEXT,
    
    -- Status tracking
    status ENUM('EN_ATTENTE_RAMASSAGE', 'EN_ATTENTE_PREPARATION', 'EN_PREPARATION', 'RAMASSE', 'PRET_POUR_DISTRIBUTION', 'MISE_EN_DISTRIBUTION', 'LIVRE', 'REFUSE', 'ANNULE') NOT NULL,
    
    -- Tracking information
    tracking_code VARCHAR(100),
    delivery_date DATETIME,
    delivery_note TEXT,
    delivery_location JSON, -- {lat, lng}
    
    -- Linked to stock item if type is 'stock'
    stock_item_id INT,
    
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (livreur_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (city_id) REFERENCES cities(id),
    FOREIGN KEY (stock_item_id) REFERENCES stock_items(id) ON DELETE SET NULL,
    
    INDEX idx_client (client_id),
    INDEX idx_livreur (livreur_id),
    INDEX idx_status (status),
    INDEX idx_type (type),
    INDEX idx_tracking (tracking_code)
);

-- Bon de Livraison table
CREATE TABLE bon_livraison (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    client_id INT NOT NULL,
    status ENUM('EN_PREPARATION', 'RECU') DEFAULT 'EN_PREPARATION',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_status (status)
);

-- Bon de Livraison Orders junction table
CREATE TABLE bon_livraison_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bon_livraison_id INT NOT NULL,
    order_id INT NOT NULL,
    scanned_at TIMESTAMP NULL,
    
    FOREIGN KEY (bon_livraison_id) REFERENCES bon_livraison(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bon_order (bon_livraison_id, order_id)
);

-- Bon d'Envoi table
CREATE TABLE bon_envoi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    livreur_id INT NOT NULL,
    zone_id INT NOT NULL,
    status ENUM('PRET_POUR_DISTRIBUTION', 'RECU') DEFAULT 'PRET_POUR_DISTRIBUTION',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (livreur_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (zone_id) REFERENCES zones(id),
    INDEX idx_livreur (livreur_id),
    INDEX idx_zone (zone_id),
    INDEX idx_status (status)
);

-- Bon d'Envoi Orders junction table
CREATE TABLE bon_envoi_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    bon_envoi_id INT NOT NULL,
    order_id INT NOT NULL,
    
    FOREIGN KEY (bon_envoi_id) REFERENCES bon_envoi(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    UNIQUE KEY unique_bon_order (bon_envoi_id, order_id)
);

-- Livreur Invoices table
CREATE TABLE livreur_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    livreur_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    status ENUM('ouvert', 'ferme', 'paye') DEFAULT 'ouvert',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_proof_path VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (livreur_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_livreur (livreur_id),
    INDEX idx_status (status)
);

-- Client Invoices table
CREATE TABLE client_invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    order_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    product_amount DECIMAL(10,2) NOT NULL,
    delivery_fee DECIMAL(10,2) NOT NULL,
    extra_services JSON, -- [{service: 'name', amount: 00.00}]
    total_amount DECIMAL(10,2) NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (client_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_client (client_id),
    INDEX idx_order (order_id)
);

-- Tracking Log table for audit trail
CREATE TABLE tracking_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50),
    notes TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_order (order_id),
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
);

-- Sessions table for authentication
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    expires_at TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- Settings table for global configurations
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(100) NOT NULL UNIQUE,
    value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default admin user (password: admin123)
INSERT INTO users (email, password_hash, first_name, last_name, role, status) 
VALUES ('admin@logistics.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', 'admin', 'approved');

-- Insert default zones
INSERT INTO zones (name) VALUES ('Zone Nord'), ('Zone Sud'), ('Zone Est'), ('Zone Ouest');

-- Insert default cities
INSERT INTO cities (name, zone_id) VALUES 
('Casablanca', 1), ('Rabat', 1), ('Fès', 2), ('Marrakech', 2), 
('Agadir', 3), ('Tanger', 3), ('Oujda', 4), ('Tétouan', 4);

-- Insert default tariffs
INSERT INTO tariffs (city_id, zone_id, delivery_price, refusal_price, return_price, standard_delivery_time) VALUES
(1, 1, 25.00, 10.00, 15.00, 24), (2, 1, 30.00, 10.00, 15.00, 24),
(3, 2, 35.00, 15.00, 20.00, 48), (4, 2, 30.00, 12.00, 18.00, 48),
(5, 3, 40.00, 15.00, 25.00, 72), (6, 3, 35.00, 12.00, 20.00, 72),
(7, 4, 45.00, 18.00, 30.00, 96), (8, 4, 40.00, 15.00, 25.00, 96);

-- Insert default settings
INSERT INTO settings (key_name, value, description) VALUES
('company_name', 'Logistics Management System', 'Company name displayed in system'),
('default_delivery_time', '24', 'Default delivery time in hours'),
('max_file_size', '5242880', 'Maximum file upload size in bytes'),
('enable_sms_notifications', '1', 'Enable SMS notifications'),
('enable_email_notifications', '1', 'Enable email notifications');