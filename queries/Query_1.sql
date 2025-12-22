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

ALTER TABLE marketing_campaigns CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- 4. O campanie de marketing draft



CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    audience VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    recipients_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

DROP TABLE marketing_campaigns;

CREATE TABLE marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    subject VARCHAR(255) NOT NULL,
    audience VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    recipients_count INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);


-- 1. Ștergem tabelul vechi pentru a curăța erorile
DROP TABLE IF EXISTS marketing_campaigns;

-- 2. Creăm tabelul NOU cu suport UTF8MB4 (pentru diacritice)
CREATE TABLE marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message_content TEXT NOT NULL,
    target_audience ENUM('all', 'active_clients', 'inactive_clients') DEFAULT 'all',
    sent_at DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Populăm cu datele de test (acum vor merge diacriticele)
INSERT INTO marketing_campaigns (name, subject, message_content, target_audience, sent_at, created_at) VALUES
(
    'Campanie Iarna 2024',
    'Pregătește-te de iarnă! ❄️',
    'Salut! Iarna se apropie. Vino acum pentru un schimb de anvelope și verificare antigel cu 10% reducere.',
    'all',
    '2024-11-20 10:00:00',
    '2024-11-18 09:30:00'
),
(
    'Reactivare Clienți',
    'Ne e dor de tine! 🚗',
    'Nu te-am mai văzut de mult. Treci săptămâna aceasta pentru o verificare gratuită a trenului de rulare!',
    'inactive_clients',
    '2024-12-05 14:30:00',
    '2024-12-01 11:00:00'
),
(
    'Alerta ITP',
    'Nu uita de ITP! ⚠️',
    'Verifică talonul! Dacă ITP-ul expiră curând, sună-ne pentru o programare prioritară.',
    'active_clients',
    '2025-01-10 09:15:00',
    '2025-01-05 12:20:00'
);

CREATE TABLE IF NOT EXISTS appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    equipment_id INT NOT NULL,
    appointment_date DATETIME NOT NULL,
    service_type VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('pending', 'confirmed', 'rejected', 'completed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 1. Golim tabelul de marketing pentru a putea adăuga coloana de proprietar
TRUNCATE TABLE marketing_campaigns;

-- 2. Adăugăm coloana admin_id în marketing_campaigns
ALTER TABLE marketing_campaigns
ADD COLUMN admin_id INT NOT NULL AFTER id,
ADD CONSTRAINT fk_marketing_admin FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE;

-- 3. Creăm tabelul pentru regulile de notificare (Automations)
CREATE TABLE IF NOT EXISTS notification_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    rule_type ENUM('appointment_reminder', 'itp_expiry', 'revision_due') NOT NULL,
    days_before INT NOT NULL,
    message_template TEXT NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;