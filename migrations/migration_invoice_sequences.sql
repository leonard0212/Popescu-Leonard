-- Migration: create invoice_sequences table for per-admin invoice numbering
CREATE TABLE IF NOT EXISTS invoice_sequences (
    admin_id INT NOT NULL PRIMARY KEY,
    last_seq INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Note: run this migration with your usual process (mysql < migration_invoice_sequences.sql)
