-- Database schema for Delivery Management System
CREATE DATABASE IF NOT EXISTS delivery_system;
USE delivery_system;

-- Users table for multi-role management
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'vendor', 'delivery_agent') NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    phone VARCHAR(20),
    address TEXT,
    city_id INT,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (city_id) REFERENCES cities(id)
);

-- Cities table for delivery zones with fees
CREATE TABLE cities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(10) UNIQUE NOT NULL,
    delivery_fee DECIMAL(10,2) DEFAULT 0.00,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Pickup requests table
CREATE TABLE pickup_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    pickup_address TEXT NOT NULL,
    delivery_address TEXT NOT NULL,
    pickup_city_id INT NOT NULL,
    delivery_city_id INT NOT NULL,
    package_type ENUM('parcel', 'document', 'fragile') NOT NULL,
    package_weight DECIMAL(8,2),
    package_dimensions VARCHAR(100),
    package_description TEXT,
    recipient_name VARCHAR(100) NOT NULL,
    recipient_phone VARCHAR(20) NOT NULL,
    cod_amount DECIMAL(10,2) DEFAULT 0.00,
    delivery_fee DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    special_instructions TEXT,
    qr_code VARCHAR(255),
    status VARCHAR(50) DEFAULT 'en_attente',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES users(id),
    FOREIGN KEY (pickup_city_id) REFERENCES cities(id),
    FOREIGN KEY (delivery_city_id) REFERENCES cities(id)
);

-- Delivery packages table for active deliveries
CREATE TABLE delivery_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pickup_request_id INT NOT NULL,
    delivery_agent_id INT,
    tracking_number VARCHAR(50) UNIQUE NOT NULL,
    current_status VARCHAR(50) DEFAULT 'en_attente',
    estimated_delivery DATE,
    actual_delivery DATETIME,
    delivery_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pickup_request_id) REFERENCES pickup_requests(id),
    FOREIGN KEY (delivery_agent_id) REFERENCES users(id)
);

-- Delivery slips for bulk assignment to agents
CREATE TABLE delivery_slips (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slip_number VARCHAR(50) UNIQUE NOT NULL,
    delivery_agent_id INT NOT NULL,
    created_by INT NOT NULL,
    slip_date DATE NOT NULL,
    total_packages INT DEFAULT 0,
    status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_agent_id) REFERENCES users(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Junction table for delivery slip packages
CREATE TABLE delivery_slip_packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    delivery_slip_id INT NOT NULL,
    delivery_package_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (delivery_slip_id) REFERENCES delivery_slips(id),
    FOREIGN KEY (delivery_package_id) REFERENCES delivery_packages(id),
    UNIQUE KEY unique_package_slip (delivery_slip_id, delivery_package_id)
);

-- Complete tracking history for packages
CREATE TABLE delivery_status_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT,
    change_location VARCHAR(255),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (package_id) REFERENCES delivery_packages(id),
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- Real-time package tracking
CREATE TABLE package_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    package_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    location VARCHAR(255),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    scan_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    scanned_by INT,
    device_info VARCHAR(255),
    FOREIGN KEY (package_id) REFERENCES delivery_packages(id),
    FOREIGN KEY (scanned_by) REFERENCES users(id)
);

-- System notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- System settings
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default cities
INSERT INTO cities (name, code, delivery_fee) VALUES
('Casablanca', 'CAS', 25.00),
('Rabat', 'RAB', 30.00),
('Marrakech', 'MAR', 35.00),
('Fès', 'FES', 30.00),
('Tanger', 'TAN', 40.00),
('Agadir', 'AGA', 45.00),
('Meknès', 'MEK', 35.00),
('Oujda', 'OUJ', 40.00),
('Kénitra', 'KEN', 25.00),
('Tétouan', 'TET', 35.00);

-- Insert default admin user (password: admin123)
INSERT INTO users (username, email, password_hash, role, first_name, last_name, phone, city_id) VALUES
('admin', 'admin@delivery.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'System', 'Administrator', '+212600000000', 1);

-- Insert default system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('company_name', 'Delivery Management System', 'Company name for branding'),
('company_logo', '', 'Path to company logo'),
('default_delivery_fee', '25.00', 'Default delivery fee'),
('cod_fee_percentage', '2.5', 'COD fee percentage'),
('sms_notifications', '1', 'Enable SMS notifications'),
('email_notifications', '1', 'Enable email notifications');