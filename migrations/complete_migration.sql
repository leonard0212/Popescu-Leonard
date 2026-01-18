-- Complete migration for ServiceFlow (generated)
-- Run with: docker exec -i lamp_mysql mysql -u root -proot < migrations/complete_migration.sql

-- 0) Create Database and select it
CREATE DATABASE IF NOT EXISTS service_flow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE service_flow_db;

-- ==================================================================
-- 1) Core schema (tables and basic seed)
-- (sourced from Query.sql)

-- 1.1 admins
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    service_name VARCHAR(100) NOT NULL,
    cui_cif VARCHAR(50),
    subscription_plan ENUM('basic', 'pro', 'enterprise') DEFAULT 'basic',
    account_status ENUM('active', 'suspended', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.2 clients
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.3 equipment
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.4 interventions
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.5 invoices
CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    client_id INT NOT NULL,
    intervention_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    invoice_date DATE NOT NULL,
    details TEXT DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY (admin_id),
    KEY (client_id),
    KEY (intervention_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.6 marketing_campaigns
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL DEFAULT 1,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message_content TEXT NOT NULL,
    target_audience ENUM('all', 'active_clients', 'inactive_clients') DEFAULT 'all',
    sent_at DATETIME,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.7 notifications
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type ENUM('itp_reminder', 'warranty_reminder', 'booking_confirmation', 'marketing') NOT NULL,
    message TEXT,
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.8 notification_logs
CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    rule_id INT NOT NULL,
    client_id INT NOT NULL,
    sent_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 1.9 notification_rules
CREATE TABLE IF NOT EXISTS notification_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    rule_name VARCHAR(255) NOT NULL,
    trigger_type ENUM('itp_expiry','service_followup') NOT NULL,
    timing_type ENUM('before','after') NOT NULL,
    days_offset INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message_body TEXT NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_run_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==================================================================
-- 2) Apply additional migrations (columns, flags)
-- (sourced from migration_add_fiscal_and_loyalty.sql and migration_points_and_invoice_flags.sql)

-- 2.1 Add fiscal/admin fields and invoice breakdown
ALTER TABLE admins
  ADD COLUMN IF NOT EXISTS address VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS fiscal_address VARCHAR(255) DEFAULT NULL,
  ADD COLUMN IF NOT EXISTS vat_rate_default DECIMAL(5,2) DEFAULT 19.00;

ALTER TABLE clients
  ADD COLUMN IF NOT EXISTS loyalty_points INT DEFAULT 0;

ALTER TABLE invoices
  ADD COLUMN IF NOT EXISTS parts_amount DECIMAL(10,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS labor_amount DECIMAL(10,2) DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS vat_rate DECIMAL(5,2) DEFAULT 19.00;

-- 2.2 Add points_awarded and is_published flags
ALTER TABLE interventions
  ADD COLUMN IF NOT EXISTS points_awarded TINYINT(1) DEFAULT 0;

ALTER TABLE invoices
  ADD COLUMN IF NOT EXISTS is_published TINYINT(1) DEFAULT 0;

-- ==================================================================
-- 3) Seed / optional data
-- Insert a primary admin example (if not exists)
INSERT INTO admins (service_name, cui_cif, email, password_hash, subscription_plan, account_status)
SELECT 'Test Service SRL', 'RO12345678', 'admin_test@example.com', '$2y$10$32I6O1RRdrfRSCQ7ZoN8neaW9.qx3UBdVrbSC1IOOebCRsC79WfMC', 'basic', 'active'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE email = 'admin_test@example.com');

-- Additional seeds from Query.sql (lightweight)
INSERT INTO admins (email, password_hash, service_name, cui_cif, subscription_plan)
SELECT 'admin@serviceflow.ro', 'hash_secret', 'Service Auto Total', 'RO123456', 'pro'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM admins WHERE email = 'admin@serviceflow.ro');

INSERT INTO clients (admin_id, full_name, email, phone, loyalty_points)
SELECT 1, 'Popescu Ion', 'ion.popescu@email.com', '0722 123 456', 150
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE email = 'ion.popescu@email.com');

INSERT INTO clients (admin_id, full_name, email, phone, loyalty_points)
SELECT 1, 'Vasilescu Ana', 'ana.v@email.com', '0733 987 654', 0
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM clients WHERE email = 'ana.v@email.com');

INSERT INTO equipment (client_id, model, serial_number, itp_expiry_date)
SELECT 1, 'Dacia Logan', 'B 123 ABC', '2025-10-25'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM equipment WHERE serial_number = 'B 123 ABC');

INSERT INTO equipment (client_id, model, serial_number, itp_expiry_date)
SELECT 2, 'Ford Focus', 'B 456 XYZ', '2025-12-01'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM equipment WHERE serial_number = 'B 456 XYZ');

INSERT INTO marketing_campaigns (name, subject, message_content, target_audience)
SELECT 'Promo Iarna', 'Pregătește-te de iarnă!', 'Vino acum pentru schimbul de anvelope și primești 10% reducere.', 'all'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM marketing_campaigns WHERE name = 'Promo Iarna');

-- ==================================================================
-- 4) Final notes
-- This single-file migration mixes schema creation + idempotent ALTERs and lightweight seeds.
-- To run cleanly on an empty server: execute this file. On an existing DB it will attempt to add missing columns
-- and seed data without duplicating existing rows.

COMMIT;