-- Models table (each model belongs to a single vendor)
CREATE TABLE IF NOT EXISTS models (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    vendor_id INT NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);
-- IT Management System Database
-- Created: August 3, 2025

CREATE DATABASE IF NOT EXISTS itms_db;
USE itms_db;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'manager', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Categories table for asset types
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Vendors table
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    contact_person VARCHAR(100),
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Locations table
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    building VARCHAR(100),
    floor VARCHAR(50),
    room VARCHAR(50),
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Assets table (main inventory table)
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    vendor_id INT,
    location_id INT,
    model VARCHAR(100),
    serial_number VARCHAR(100),
    purchase_date DATE,
    purchase_price DECIMAL(10,2),
    warranty_expiry DATE,
    status ENUM('active', 'inactive', 'maintenance', 'disposed') DEFAULT 'active',
    assigned_to_employee_id INT,
    -- assigned_to VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (assigned_to_employee_id) REFERENCES employees(id)
);

-- Employees table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    department VARCHAR(100),
    email VARCHAR(100) NOT NULL,
    position VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Asset history table for tracking changes
CREATE TABLE IF NOT EXISTS asset_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    field_changed VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    action VARCHAR(100) NOT NULL,
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id)
);

-- Software licenses table
CREATE TABLE IF NOT EXISTS software_licenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    software_name VARCHAR(200) NOT NULL,
    license_key VARCHAR(500),
    license_type ENUM('perpetual', 'subscription', 'oem') DEFAULT 'perpetual',
    vendor_id INT,
    purchase_date DATE,
    expiry_date DATE,
    seats_total INT DEFAULT 1,
    seats_used INT DEFAULT 0,
    cost DECIMAL(10,2),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
);

-- Asset assignments table (for tracking who has what)
CREATE TABLE IF NOT EXISTS asset_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT,
    employee_id INT NOT NULL,
    assigned_by VARCHAR(100),
    assigned_date DATE NOT NULL,
    return_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Insert default data
INSERT IGNORE INTO categories (name, description) VALUES
('Desktop Computers', 'Desktop PCs and workstations'),
('Laptops', 'Laptop computers and notebooks'),
('Servers', 'Server hardware'),
('Network Equipment', 'Routers, switches, access points'),
('Printers', 'Printers and multifunction devices'),
('Storage Devices', 'External drives, NAS, SAN'),
('Mobile Devices', 'Tablets, smartphones'),
('Software', 'Software applications and licenses'),
('Accessories', 'Keyboards, mice, monitors'),
('Other', 'Miscellaneous IT equipment');

INSERT IGNORE INTO vendors (name, contact_person, email, phone) VALUES
('Dell Technologies', 'Sales Team', 'sales@dell.com', '1-800-WWW-DELL'),
('HP Inc.', 'Business Sales', 'business@hp.com', '1-800-HP-HELP'),
('Lenovo', 'Enterprise Sales', 'enterprise@lenovo.com', '1-855-253-6686'),
('Cisco Systems', 'Partner Support', 'partner@cisco.com', '1-800-553-NETS'),
('Microsoft Corporation', 'Volume Licensing', 'licensing@microsoft.com', '1-800-426-9400'),
('Apple Inc.', 'Business Team', 'business@apple.com', '1-800-APL-CARE');

INSERT IGNORE INTO locations (name, building, floor, room, description) VALUES
('IT Department', 'Main Building', '3rd Floor', 'Room 301', 'IT staff workspace'),
('Server Room', 'Main Building', 'Basement', 'B-101', 'Primary server room'),
('Reception', 'Main Building', 'Ground Floor', 'Lobby', 'Reception area'),
('Conference Room A', 'Main Building', '2nd Floor', 'Room 201', 'Large conference room'),
('Warehouse', 'Storage Building', 'Ground Floor', 'W-001', 'Equipment storage');
