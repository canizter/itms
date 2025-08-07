-- ITMS Asset Management Migration Script
-- Version: 1.5
-- Created: 2025-08-07

-- Categories Table
CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- Vendors Table
CREATE TABLE IF NOT EXISTS vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- Locations Table
CREATE TABLE IF NOT EXISTS locations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL
);

-- Employees Table
CREATE TABLE IF NOT EXISTS employees (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    department VARCHAR(255),
    email VARCHAR(255),
    position VARCHAR(255)
);

-- Assets Table
CREATE TABLE IF NOT EXISTS assets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_tag VARCHAR(100) NOT NULL UNIQUE,
    category_id INT NOT NULL,
    vendor_id INT NOT NULL,
    location_id INT NOT NULL,
    status VARCHAR(50) NOT NULL DEFAULT 'Available',
    serial_number VARCHAR(100) NOT NULL UNIQUE,
    lan_mac VARCHAR(17),
    wlan_mac VARCHAR(17),
    assigned_to_employee_id INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    FOREIGN KEY (location_id) REFERENCES locations(id),
    FOREIGN KEY (assigned_to_employee_id) REFERENCES employees(id)
);

-- Asset History Table
CREATE TABLE IF NOT EXISTS asset_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    field_changed VARCHAR(100) NOT NULL,
    old_value TEXT,
    new_value TEXT,
    action VARCHAR(50) NOT NULL,
    changed_by VARCHAR(100),
    changed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id)
);

-- Asset Assignments Table
CREATE TABLE IF NOT EXISTS asset_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    asset_id INT NOT NULL,
    employee_id INT NOT NULL,
    assigned_by VARCHAR(100),
    assigned_date DATE,
    return_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (asset_id) REFERENCES assets(id),
    FOREIGN KEY (employee_id) REFERENCES employees(id)
);

-- Consumable Types Table
CREATE TABLE IF NOT EXISTS consumable_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type VARCHAR(100) NOT NULL
);

-- Consumables Table
CREATE TABLE IF NOT EXISTS consumables (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL DEFAULT 0,
    consumable_type INT NOT NULL,
    min_quantity INT NOT NULL DEFAULT 0,
    max_quantity INT,
    FOREIGN KEY (consumable_type) REFERENCES consumable_types(id)
);

-- Consumable Transactions Table
CREATE TABLE IF NOT EXISTS consumable_transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    consumable_id INT NOT NULL,
    action ENUM('receive','issue') NOT NULL,
    quantity INT NOT NULL,
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (consumable_id) REFERENCES consumables(id) ON DELETE CASCADE
);
