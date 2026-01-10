<?php
require_once 'db_connect.php';

// 1. Add logo_path to admins table
// We use simple ALTER IGNORE or just try-catch approach since we can't check easily without INFORMATION_SCHEMA access (which might be restricted or slow)
// Actually we can check with SHOW COLUMNS
$result = $conn->query("SHOW COLUMNS FROM admins LIKE 'logo_path'");
if ($result->num_rows == 0) {
    if ($conn->query("ALTER TABLE admins ADD COLUMN logo_path VARCHAR(255) DEFAULT NULL AFTER cui_cif")) {
        echo "Added logo_path to admins.<br>";
    } else {
        echo "Error adding logo_path: " . $conn->error . "<br>";
    }
}

// 2. Create marketing_campaigns table
$sql_mkt = "CREATE TABLE IF NOT EXISTS marketing_campaigns (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    target_audience TEXT,
    message_content TEXT NOT NULL,
    sent_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
)";
if ($conn->query($sql_mkt)) {
    echo "Table marketing_campaigns checked/created.<br>";
} else {
    echo "Error creating marketing_campaigns: " . $conn->error . "<br>";
}

// 3. Create invoices table
$sql_inv = "CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    client_id INT NOT NULL,
    intervention_id INT NOT NULL,
    invoice_number VARCHAR(50) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    invoice_date DATE NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    FOREIGN KEY (intervention_id) REFERENCES interventions(id) ON DELETE CASCADE
)";
if ($conn->query($sql_inv)) {
    echo "Table invoices checked/created.<br>";
} else {
    echo "Error creating invoices: " . $conn->error . "<br>";
}
?>