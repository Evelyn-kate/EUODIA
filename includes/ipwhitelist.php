<?php
/**
 * IP Whitelisting Handler
 * Manages admin access restrictions based on IP addresses
 */

class IPWhitelist {
    private $conn;
    private $table = 'ip_whitelist';
    
    public function __construct($database) {
        $this->conn = $database;
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ip = '';
        
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP']; // Cloudflare
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            $ip = $_SERVER['HTTP_FORWARDED'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '';
    }
    
    /**
     * Check if IP is whitelisted
     */
    public function isIPWhitelisted($ip = null) {
        if (!$ip) {
            $ip = self::getClientIP();
        }
        
        // Check if whitelist is enabled
        $settings = $this->conn->query("SELECT value FROM settings WHERE key_name = 'ip_whitelist_enabled' LIMIT 1");
        if ($settings && $result = $settings->fetch_assoc()) {
            if ($result['value'] !== '1') {
                return true; // Whitelist disabled
            }
        }
        
        // Check if IP exists in whitelist
        $ip_escaped = $this->conn->real_escape_string($ip);
        $query = "SELECT id FROM " . $this->table . " WHERE ip_address = '$ip_escaped' AND status = 'active' LIMIT 1";
        $result = $this->conn->query($query);
        
        return $result && $result->num_rows > 0;
    }
    
    /**
     * Get all whitelisted IPs
     */
    public function getWhitelistedIPs() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY created_at DESC";
        return $this->conn->query($query);
    }
    
    /**
     * Add IP to whitelist
     */
    public function addIP($ip, $description = '') {
        // Validate IP
        if (!filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'message' => 'Invalid IP address'];
        }
        
        // Check if already exists
        $ip_escaped = $this->conn->real_escape_string($ip);
        $check = $this->conn->query("SELECT id FROM " . $this->table . " WHERE ip_address = '$ip_escaped' LIMIT 1");
        
        if ($check && $check->num_rows > 0) {
            return ['success' => false, 'message' => 'IP already whitelisted'];
        }
        
        // Insert new IP
        $description = $this->conn->real_escape_string($description);
        $query = "INSERT INTO " . $this->table . " (ip_address, description, status, created_at) 
                 VALUES ('$ip_escaped', '$description', 'active', NOW())";
        
        if ($this->conn->query($query)) {
            return ['success' => true, 'message' => 'IP added successfully'];
        } else {
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        }
    }
    
    /**
     * Remove IP from whitelist
     */
    public function removeIP($id) {
        $id = intval($id);
        $query = "DELETE FROM " . $this->table . " WHERE id = $id";
        
        if ($this->conn->query($query)) {
            return ['success' => true, 'message' => 'IP removed successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to remove IP'];
        }
    }
    
    /**
     * Toggle IP status
     */
    public function toggleIPStatus($id) {
        $id = intval($id);
        $query = "UPDATE " . $this->table . " SET status = IF(status = 'active', 'inactive', 'active') WHERE id = $id";
        
        if ($this->conn->query($query)) {
            return ['success' => true, 'message' => 'Status updated'];
        } else {
            return ['success' => false, 'message' => 'Failed to update status'];
        }
    }
    
    /**
     * Enable/Disable whitelist globally
     */
    public function setWhitelistEnabled($enabled) {
        $value = $enabled ? '1' : '0';
        
        $check = $this->conn->query("SELECT id FROM settings WHERE key_name = 'ip_whitelist_enabled'");
        
        if ($check && $check->num_rows > 0) {
            $query = "UPDATE settings SET value = '$value' WHERE key_name = 'ip_whitelist_enabled'";
        } else {
            $query = "INSERT INTO settings (key_name, value) VALUES ('ip_whitelist_enabled', '$value')";
        }
        
        return $this->conn->query($query);
    }
    
    /**
     * Get whitelist status
     */
    public function isWhitelistEnabled() {
        $query = "SELECT value FROM settings WHERE key_name = 'ip_whitelist_enabled' LIMIT 1";
        $result = $this->conn->query($query);
        
        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            return $row['value'] === '1';
        }
        return false;
    }

    /**
     * Check IP access and log unauthorized attempts
     * Returns: true if allowed, false if blocked
     */
    public function checkAndLogAccess($ip = null, $page = '') {
        if (!$ip) {
            $ip = self::getClientIP();
        }

        // If whitelist is disabled, allow all
        if (!$this->isWhitelistEnabled()) {
            return true;
        }

        // Check if IP is whitelisted
        $is_whitelisted = $this->isIPWhitelisted($ip);

        // Log access attempt if IP is not whitelisted
        if (!$is_whitelisted) {
            $this->logUnauthorizedAccess($ip, $page);
        }

        return $is_whitelisted;
    }

    /**
     * Log unauthorized access attempts
     */
    private function logUnauthorizedAccess($ip, $page = '') {
        $ip_escaped = $this->conn->real_escape_string($ip);
        $page_escaped = $this->conn->real_escape_string($page);
        $user_agent = $this->conn->real_escape_string($_SERVER['HTTP_USER_AGENT'] ?? '');
        
        $query = "INSERT INTO ip_access_logs (ip_address, page, user_agent, attempt_time) 
                 VALUES ('$ip_escaped', '$page_escaped', '$user_agent', NOW())";
        
        $this->conn->query($query);
    }

    /**
     * Get access statistics
     */
    public function getAccessStats($limit = 100) {
        $query = "SELECT * FROM ip_access_logs ORDER BY attempt_time DESC LIMIT $limit";
        return $this->conn->query($query);
    }

    /**
     * Update IP description
     */
    public function updateIPDescription($id, $description) {
        $id = intval($id);
        $description = $this->conn->real_escape_string($description);
        $query = "UPDATE " . $this->table . " SET description = '$description' WHERE id = $id";
        
        return $this->conn->query($query);
    }
}
?>
