-- ================================================================
-- SERVICEFLOW DATABASE - FULL SCHEMA (v2.0)
-- Integrat cu: Admin Panel, Client Portal, Sign-up & Pricing
-- ================================================================

CREATE DATABASE IF NOT EXISTS service_flow_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE service_flow_db;

-- 1. TABELA ADMINS / SERVICE PROVIDERS
-- Actualizat conform: signup.html
-- Un admin reprezintă, de fapt, un cont de Service Auto
CREATE TABLE IF NOT EXISTS admins (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,  -- name="email" din signup.html
    password_hash VARCHAR(255) NOT NULL, -- Parola criptată

    -- Date despre Service (Profil)
    service_name VARCHAR(100) NOT NULL,  -- name="service-name"
    cui_cif VARCHAR(50),                 -- name="cui"

    -- Date despre Abonament (pricing.html)
    subscription_plan ENUM('basic', 'pro', 'enterprise') DEFAULT 'basic',
    account_status ENUM('active', 'suspended', 'pending') DEFAULT 'active',

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 2. TABELA CLIENTS
-- Actualizat conform: features.html (Sistem de Loialitate)
CREATE TABLE IF NOT EXISTS clients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    -- Legătură cu Service-ul (în caz că aplicația devine multi-tenant real,
    -- altfel toți clienții aparțin adminului unic al instalării)
    admin_id INT DEFAULT 1,

    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150), -- Poate fi NULL dacă clientul nu vrea cont online
    phone VARCHAR(20) NOT NULL,
    address TEXT,
    cui_cif VARCHAR(50),

    -- Acces Portal Client (client_profile.html)
    password_hash VARCHAR(255),

    -- Feature: Sistem de Loialitate
    loyalty_points INT DEFAULT 0,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (admin_id) REFERENCES admins(id)
);

-- 3. TABELA EQUIPMENT
-- Rămâne neschimbată, esențială pentru flow-ul de service
CREATE TABLE IF NOT EXISTS equipment (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    model VARCHAR(100) NOT NULL,
    serial_number VARCHAR(100) NOT NULL, -- VIN / Serie
    warranty_expiry_date DATE,
    itp_expiry_date DATE,

    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    INDEX (serial_number)
);

-- 4. TABELA INTERVENTIONS
-- Core-ul aplicației
CREATE TABLE IF NOT EXISTS interventions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    equipment_id INT NOT NULL,

    problem_description TEXT NOT NULL,
    diagnostic_notes TEXT,
    parts_used TEXT,
    labor_cost DECIMAL(10, 2) DEFAULT 0.00,

    status ENUM('programata', 'in_desfasurare', 'finalizata', 'anulata') DEFAULT 'programata',

    scheduled_date DATETIME, -- Pentru Calendar
    completed_date DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
);

-- 5. TABELA MARKETING_CAMPAIGNS (NOU)
-- Conform: features.html ("Marketing Automatizat") și admin_marketing.html
CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message_content TEXT NOT NULL,
    target_audience ENUM('all', 'active_clients', 'inactive_clients') DEFAULT 'all',
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 6. TABELA NOTIFICATIONS (NOU)
-- Pentru "Notificări Automate" (ITP, Revizii)
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    type ENUM('itp_reminder', 'warranty_reminder', 'booking_confirmation', 'marketing') NOT NULL,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

-- ================================================================
-- SEED DATA (Date de test actualizate)
-- ================================================================

-- 1. Un Service nou creat (Sign-up)
INSERT INTO admins (email, password_hash, service_name, cui_cif, subscription_plan)
VALUES ('admin@serviceflow.ro', 'hash_secret', 'Service Auto Total', 'RO123456', 'pro');

-- 2. Clienți (Acum cu puncte de loialitate!)
INSERT INTO clients (admin_id, full_name, email, phone, loyalty_points) VALUES
(1, 'Popescu Ion', 'ion.popescu@email.com', '0722 123 456', 150), -- Client fidel
(1, 'Vasilescu Ana', 'ana.v@email.com', '0733 987 654', 0);

-- 3. Echipamente
INSERT INTO equipment (client_id, model, serial_number, itp_expiry_date) VALUES
(1, 'Dacia Logan', 'B 123 ABC', '2025-10-25'),
(2, 'Ford Focus', 'B 456 XYZ', '2025-12-01');

-- 4. O campanie de marketing draft
INSERT INTO marketing_campaigns (name, subject, message_content, target_audience)
VALUES ('Promo Iarna', 'Pregătește-te de iarnă!', 'Vino acum pentru schimbul de anvelope și primești 10% reducere.', 'all');