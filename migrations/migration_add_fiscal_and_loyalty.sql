-- Migration: add fiscal fields and loyalty points
ALTER TABLE admins
  ADD COLUMN address VARCHAR(255) DEFAULT NULL,
  ADD COLUMN fiscal_address VARCHAR(255) DEFAULT NULL,
  ADD COLUMN vat_rate_default DECIMAL(5,2) DEFAULT 19.00;

ALTER TABLE clients
  ADD COLUMN loyalty_points INT DEFAULT 0;

-- Extend invoices to keep breakdown
ALTER TABLE invoices
  ADD COLUMN parts_amount DECIMAL(10,2) DEFAULT 0.00,
  ADD COLUMN labor_amount DECIMAL(10,2) DEFAULT 0.00,
  ADD COLUMN vat_rate DECIMAL(5,2) DEFAULT 19.00;

-- Note: run this migration once against the DB container:
-- docker exec -i lamp_mysql mysql -u root -proot service_flow_db < migrations/migration_add_fiscal_and_loyalty.sql
