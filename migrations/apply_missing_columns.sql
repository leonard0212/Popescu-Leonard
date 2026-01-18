-- Safe migration: add missing columns only if they do not exist
-- For MySQL 5.7 compatibility we use INFORMATION_SCHEMA + prepared statements

-- admins.address
SELECT COUNT(*) INTO @c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='service_flow_db' AND TABLE_NAME='admins' AND COLUMN_NAME='address';
SET @s = IF(@c=0, 'ALTER TABLE admins ADD COLUMN address VARCHAR(255) DEFAULT NULL', 'SELECT "admins.address exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- admins.fiscal_address
SELECT COUNT(*) INTO @c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='service_flow_db' AND TABLE_NAME='admins' AND COLUMN_NAME='fiscal_address';
SET @s = IF(@c=0, 'ALTER TABLE admins ADD COLUMN fiscal_address VARCHAR(255) DEFAULT NULL', 'SELECT "admins.fiscal_address exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- admins.vat_rate_default
SELECT COUNT(*) INTO @c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='service_flow_db' AND TABLE_NAME='admins' AND COLUMN_NAME='vat_rate_default';
SET @s = IF(@c=0, 'ALTER TABLE admins ADD COLUMN vat_rate_default DECIMAL(5,2) DEFAULT 19.00', 'SELECT "admins.vat_rate_default exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- clients.loyalty_points
SELECT COUNT(*) INTO @c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='service_flow_db' AND TABLE_NAME='clients' AND COLUMN_NAME='loyalty_points';
SET @s = IF(@c=0, 'ALTER TABLE clients ADD COLUMN loyalty_points INT DEFAULT 0', 'SELECT "clients.loyalty_points exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- invoices.parts_amount
SELECT COUNT(*) INTO @c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='service_flow_db' AND TABLE_NAME='invoices' AND COLUMN_NAME='parts_amount';
SET @s = IF(@c=0, 'ALTER TABLE invoices ADD COLUMN parts_amount DECIMAL(10,2) DEFAULT 0.00', 'SELECT "invoices.parts_amount exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- invoices.labor_amount
SELECT COUNT(*) INTO @c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='service_flow_db' AND TABLE_NAME='invoices' AND COLUMN_NAME='labor_amount';
SET @s = IF(@c=0, 'ALTER TABLE invoices ADD COLUMN labor_amount DECIMAL(10,2) DEFAULT 0.00', 'SELECT "invoices.labor_amount exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- invoices.vat_rate
SELECT COUNT(*) INTO @c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='service_flow_db' AND TABLE_NAME='invoices' AND COLUMN_NAME='vat_rate';
SET @s = IF(@c=0, 'ALTER TABLE invoices ADD COLUMN vat_rate DECIMAL(5,2) DEFAULT 19.00', 'SELECT "invoices.vat_rate exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- invoices.is_published
SELECT COUNT(*) INTO @c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='service_flow_db' AND TABLE_NAME='invoices' AND COLUMN_NAME='is_published';
SET @s = IF(@c=0, 'ALTER TABLE invoices ADD COLUMN is_published TINYINT(1) DEFAULT 0', 'SELECT "invoices.is_published exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

-- interventions.points_awarded
SELECT COUNT(*) INTO @c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='service_flow_db' AND TABLE_NAME='interventions' AND COLUMN_NAME='points_awarded';
SET @s = IF(@c=0, 'ALTER TABLE interventions ADD COLUMN points_awarded TINYINT(1) DEFAULT 0', 'SELECT "interventions.points_awarded exists"');
PREPARE st FROM @s; EXECUTE st; DEALLOCATE PREPARE st;

SELECT 'DONE' AS status;
