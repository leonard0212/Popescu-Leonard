-- Migration: add points_awarded to interventions and is_published to invoices
ALTER TABLE interventions
  ADD COLUMN points_awarded TINYINT(1) DEFAULT 0;

ALTER TABLE invoices
  ADD COLUMN is_published TINYINT(1) DEFAULT 0;

-- Run with:
-- Get-Content .\migrations\migration_points_and_invoice_flags.sql -Raw | docker exec -i lamp_mysql mysql -u root -proot service_flow_db
