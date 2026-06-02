<?php
/**
 * Session Manager - Handles device session tracking
 */

class SessionManager {
    private PDO $db;
    private string $session_id;
    
    public function __construct(PDO $db) {
        $this->db = $db;
        $this->session_id = session_id();
    }
    
    /**
     * Register a new session after successful login
     */
    public function registerSession(int $user_id, array $device_info, int $expires_in = 604800): bool {
        $session_id = bin2hex(random_bytes(32));
        $device_id = $device_info['device_id'] ?? null;
        $device_name = $this->getDeviceName($device_info);
        $device_type = $this->getDeviceType($device_info['user_agent'] ?? '');
        $browser = $this->getBrowser($device_info['user_agent'] ?? '');
        $os = $this->getOperatingSystem($device_info['user_agent'] ?? '');
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $location = $this->getIpLocation($ip);
        $expires_at = date('Y-m-d H:i:s', time() + $expires_in);
        $created_at = date('Y-m-d H:i:s');
        
        // Mark existing sessions as not current
        $stmt = $this->db->prepare("UPDATE user_sessions SET is_current = 0 WHERE user_id = ?");
        $stmt->execute([$user_id]);
        
        // Insert new session
        $stmt = $this->db->prepare("
            INSERT INTO user_sessions (
                user_id, session_id, device_id, device_name, device_type,
                browser, os, ip_address, location, last_activity, 
                created_at, expires_at, is_current
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        return $stmt->execute([
            $user_id, $session_id, $device_id, $device_name, $device_type,
            $browser, $os, $ip, $location, $created_at,
            $created_at, $expires_at
        ]);
    }
    
    /**
     * Update last activity for current session
     */
    public function updateActivity(int $user_id, string $session_id): void {
        $stmt = $this->db->prepare("
            UPDATE user_sessions 
            SET last_activity = NOW() 
            WHERE user_id = ? AND session_id = ? AND is_current = 1
        ");
        $stmt->execute([$user_id, $session_id]);
    }
    
    /**
     * Get all sessions for a user
     */
    public function getUserSessions(int $user_id): array {
        $stmt = $this->db->prepare("
            SELECT 
                id, session_id, device_name, device_type, browser, os,
                ip_address, location, last_activity, created_at, expires_at,
                is_current,
                CASE 
                    WHEN TIMESTAMPDIFF(MINUTE, last_activity, NOW()) < 5 THEN 'active_now'
                    WHEN TIMESTAMPDIFF(HOUR, last_activity, NOW()) < 1 THEN 'active_recent'
                    ELSE 'inactive'
                END as status
            FROM user_sessions
            WHERE user_id = ?
            ORDER BY is_current DESC, last_activity DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Revoke a specific session (logout remotely)
     */
    public function revokeSession(int $user_id, string $session_id): bool {
        // Get the session to be revoked
        $stmt = $this->db->prepare("
            SELECT session_id FROM user_sessions 
            WHERE user_id = ? AND session_id = ? AND is_current = 0
        ");
        $stmt->execute([$user_id, $session_id]);
        $session = $stmt->fetch();
        
        if (!$session) {
            return false;
        }
        
        // Delete the session
        $stmt = $this->db->prepare("
            DELETE FROM user_sessions 
            WHERE user_id = ? AND session_id = ?
        ");
        $stmt->execute([$user_id, $session_id]);
        
        // Add to blacklist to invalidate any active JWTs
        $this->blacklistSessionTokens($session_id);
        
        return true;
    }
    
    /**
     * Revoke all sessions except current
     */
    public function revokeAllOtherSessions(int $user_id, string $current_session_id): int {
        $stmt = $this->db->prepare("
            DELETE FROM user_sessions 
            WHERE user_id = ? AND session_id != ?
        ");
        $stmt->execute([$user_id, $current_session_id]);
        
        $count = $stmt->rowCount();
        
        // Blacklist all revoked sessions
        $this->blacklistAllUserSessions($user_id, $current_session_id);
        
        return $count;
    }
    
    /**
     * Blacklist tokens for a specific session
     */
    private function blacklistSessionTokens(string $session_id): void {
        $stmt = $this->db->prepare("
            INSERT INTO token_blacklist (jti, expires_at)
            SELECT jti, DATE_ADD(NOW(), INTERVAL 1 DAY)
            FROM refresh_tokens
            WHERE session_id = ?
        ");
        $stmt->execute([$session_id]);
    }
    
    /**
     * Blacklist all tokens for a user except current session
     */
    private function blacklistAllUserSessions(int $user_id, string $current_session_id): void {
        $stmt = $this->db->prepare("
            INSERT INTO token_blacklist (jti, expires_at)
            SELECT rt.jti, DATE_ADD(NOW(), INTERVAL 1 DAY)
            FROM refresh_tokens rt
            JOIN user_sessions us ON rt.session_id = us.session_id
            WHERE us.user_id = ? AND us.session_id != ?
        ");
        $stmt->execute([$user_id, $current_session_id]);
    }
    
    /**
     * Get device name from user agent
     */
    private function getDeviceName(array $device_info): string {
        $user_agent = $device_info['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'] ?? '';
        $browser = $this->getBrowser($user_agent);
        $os = $this->getOperatingSystem($user_agent);
        $device_type = $this->getDeviceType($user_agent);
        
        return "$browser on $os";
    }
    
    /**
     * Get device type from user agent
     */
    private function getDeviceType(string $user_agent): string {
        if (preg_match('/(tablet|ipad|playbook)|(android(?!.*mobile))/i', $user_agent)) {
            return 'tablet';
        } elseif (preg_match('/(mobile|iphone|ipod|android|blackberry|windows phone)/i', $user_agent)) {
            return 'mobile';
        }
        return 'desktop';
    }
    
    /**
     * Get browser from user agent
     */
    private function getBrowser(string $user_agent): string {
        if (preg_match('/Edg/i', $user_agent)) return 'Edge';
        if (preg_match('/Chrome/i', $user_agent)) return 'Chrome';
        if (preg_match('/Firefox/i', $user_agent)) return 'Firefox';
        if (preg_match('/Safari/i', $user_agent)) return 'Safari';
        if (preg_match('/Opera/i', $user_agent)) return 'Opera';
        if (preg_match('/MSIE|Trident/i', $user_agent)) return 'Internet Explorer';
        return 'Unknown';
    }
    
    /**
     * Get operating system from user agent
     */
    private function getOperatingSystem(string $user_agent): string {
        if (preg_match('/Windows/i', $user_agent)) return 'Windows';
        if (preg_match('/Mac/i', $user_agent)) return 'macOS';
        if (preg_match('/Linux/i', $user_agent)) return 'Linux';
        if (preg_match('/Android/i', $user_agent)) return 'Android';
        if (preg_match('/iOS|iPhone|iPad/i', $user_agent)) return 'iOS';
        return 'Unknown';
    }
    
    /**
     * Get IP location (using free API with caching)
     */
    private function getIpLocation(string $ip): string {
        // Skip private IPs
        if ($this->isPrivateIp($ip)) {
            return 'Private Network';
        }
        
        // Check cache first
        $stmt = $this->db->prepare("
            SELECT country, city FROM ip_locations 
            WHERE ip_address = ? AND last_fetched > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute([$ip]);
        $cached = $stmt->fetch();
        
        if ($cached) {
            return $cached['city'] ? $cached['city'] . ', ' . $cached['country'] : $cached['country'];
        }
        
        // Fetch from API (ip-api.com - free, no API key required)
        $location = $this->fetchIpLocation($ip);
        
        // Cache the result
        $stmt = $this->db->prepare("
            INSERT INTO ip_locations (ip_address, country, city, latitude, longitude, last_fetched)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE
            country = VALUES(country), city = VALUES(city),
            latitude = VALUES(latitude), longitude = VALUES(longitude),
            last_fetched = NOW()
        ");
        $stmt->execute([$ip, $location['country'], $location['city'], 
                       $location['lat'] ?? null, $location['lon'] ?? null]);
        
        return $location['city'] ? $location['city'] . ', ' . $location['country'] : $location['country'];
    }
    
    /**
     * Fetch location from IP API
     */
    private function fetchIpLocation(string $ip): array {
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,city,lat,lon";
        $response = @file_get_contents($url);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? 'Unknown',
                    'city' => $data['city'] ?? '',
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lon'] ?? null
                ];
            }
        }
        
        return ['country' => 'Unknown', 'city' => '', 'lat' => null, 'lon' => null];
    }
    
    /**
     * Check if IP is private
     */
    private function isPrivateIp(string $ip): bool {
        $private_ranges = [
            '10.0.0.0|10.255.255.255',
            '172.16.0.0|172.31.255.255',
            '192.168.0.0|192.168.255.255',
            '127.0.0.0|127.255.255.255'
        ];
        
        $ip_long = ip2long($ip);
        foreach ($private_ranges as $range) {
            list($start, $end) = explode('|', $range);
            if ($ip_long >= ip2long($start) && $ip_long <= ip2long($end)) {
                return true;
            }
        }
        return false;
    }
}
?>