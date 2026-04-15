<?php
/**
 * Database Setup for IP Whitelisting and Settings
 * Run this once to create necessary tables
 */

include "db.php";

// Create settings table if doesn't exist
$settings_sql = "CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    key_name VARCHAR(100) UNIQUE NOT NULL,
    value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!$conn->query($settings_sql)) {
    die("Error creating settings table: " . $conn->error);
}

// Create ip_whitelist table
$whitelist_sql = "CREATE TABLE IF NOT EXISTS ip_whitelist (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";

if (!$conn->query($whitelist_sql)) {
    die("Error creating ip_whitelist table: " . $conn->error);
}

// Create ip_access_logs table for logging unauthorized attempts
$logs_sql = "CREATE TABLE IF NOT EXISTS ip_access_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    ip_address VARCHAR(50) NOT NULL,
    page VARCHAR(255),
    user_agent TEXT,
    attempt_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip (ip_address),
    INDEX idx_time (attempt_time)
)";

if (!$conn->query($logs_sql)) {
    die("Error creating ip_access_logs table: " . $conn->error);
}

// Initialize settings if doesn't exist
$check = $conn->query("SELECT id FROM settings WHERE key_name = 'ip_whitelist_enabled'");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO settings (key_name, value) VALUES ('ip_whitelist_enabled', '0')");
}

// Add localhost to whitelist by default
$check = $conn->query("SELECT id FROM ip_whitelist WHERE ip_address = '127.0.0.1'");
if ($check->num_rows == 0) {
    $conn->query("INSERT INTO ip_whitelist (ip_address, description, status) VALUES ('127.0.0.1', 'Localhost', 'active')");
}

echo "✓ Database setup complete!<br>";
echo "✓ Settings table created<br>";
echo "✓ IP Whitelist table created<br>";
echo "✓ IP Access Logs table created<br>";
echo "✓ Localhost (127.0.0.1) added to whitelist<br>";
echo "<br>Now you can access the IP Whitelist settings from the admin panel.";
?>
