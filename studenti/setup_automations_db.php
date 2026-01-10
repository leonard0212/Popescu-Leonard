<?php
require_once 'db_connect.php';

// Create notification_rules table
$sql_rules = "CREATE TABLE IF NOT EXISTS notification_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    rule_name VARCHAR(255) NOT NULL,
    trigger_type ENUM('itp_expiry', 'service_followup') NOT NULL,
    timing_type ENUM('before', 'after') NOT NULL,
    days_offset INT NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message_body TEXT NOT NULL,
    is_active BOOLEAN DEFAULT 1,
    last_run_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE
)";

if ($conn->query($sql_rules) === TRUE) {
    echo "Table notification_rules created successfully.<br>";
} else {
    echo "Error creating table notification_rules: " . $conn->error . "<br>";
}

// Create notification_logs table
$sql_logs = "CREATE TABLE IF NOT EXISTS notification_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    rule_id INT NOT NULL,
    client_id INT NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES admins(id) ON DELETE CASCADE,
    FOREIGN KEY (rule_id) REFERENCES notification_rules(id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
)";

if ($conn->query($sql_logs) === TRUE) {
    echo "Table notification_logs created successfully.<br>";
} else {
    echo "Error creating table notification_logs: " . $conn->error . "<br>";
}
?>