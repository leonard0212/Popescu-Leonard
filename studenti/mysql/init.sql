-- SERVICEFLOW DATABASE - FULL SCHEMA (v2.0)
CREATE DATABASE IF NOT EXISTS service_flow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE service_flow_db;

-- 1. ADMINS
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    cui_cif VARCHAR(50),
    subscription_plan ENUM('basic', 'pro', 'enterprise') DEFAULT 'basic',
    account_status ENUM('active', 'suspended', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. CLIENTS
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT DEFAULT 1,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150),
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    cui_cif VARCHAR(50),
    password_hash VARCHAR(255),
    loyalty_points INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- 3. EQUIPMENT
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    model VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100) NOT NULL,
    warranty_expiry_date DATE,
    itp_expiry_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX (serial_number)
);

-- 4. INTERVENTIONS
CREATE TABLE IF NOT EXISTS interventions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    equipment_id INT NOT NULL,
    problem_description TEXT NOT NULL,
    diagnostic_notes TEXT,
    parts_used TEXT,
    labor_cost DECIMAL(10, 2) DEFAULT 0.00,
    status ENUM('programata', 'in_desfasurare', 'finalizata', 'anulata') DEFAULT 'programata',
    scheduled_date DATETIME,
    completed_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

-- 5. MARKETING_CAMPAIGNS
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message_content TEXT NOT NULL,
    target_audience TEXT DEFAULT 'all',
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. NOTIFICATIONS
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type ENUM('itp_reminder', 'warranty_reminder', 'booking_confirmation', 'marketing') NOT NULL,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- SEED DATA
INSERT INTO admins (email, password_hash, service_name, cui_cif, subscription_plan)
VALUES ('admin@serviceflow.ro', 'hash_secret', 'Service Auto Total', 'RO123456', 'pro');

INSERT INTO clients (admin_id, full_name, email, phone, loyalty_points) VALUES
(1, 'Popescu Ion', 'ion.popescu@email.com', '0722 123 456', 150),
(1, 'Vasilescu Ana', 'ana.v@email.com', '0733 987 654', 0);

INSERT INTO equipment (client_id, model, serial_number, itp_expiry_date) VALUES
(1, 'Dacia Logan', 'B 123 ABC', '2025-10-25'),
(2, 'Ford Focus', 'B 456 XYZ', '2025-12-01');

INSERT INTO marketing_campaigns (name, subject, message_content, target_audience)
VALUES ('Promo Iarna', 'Pregateste-te de iarna!', 'Vino acum pentru schimbul de anvelope si primesti 10% reducere.', 'all');
