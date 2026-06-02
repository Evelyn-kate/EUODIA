<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTManager {
    private string $secret_key;
    private string $algorithm = 'HS256';
    private int $access_token_ttl = 900; // 15 minutes in seconds
    private int $refresh_token_ttl = 604800; // 7 days in seconds
    private PDO $db;
    
    public function __construct(PDO $db, string $secret_key) {
        $this->db = $db;
        $this->secret_key = $secret_key;
    }
    
   
    
    /**
     * Create refresh token (long-lived, stored in DB)
     */
    public function createRefreshToken(int $user_id): string {
        // Generate a random token (not JWT for refresh tokens)
        $refresh_token = bin2hex(random_bytes(64));
        $token_hash = hash('sha256', $refresh_token);
        $expires_at = date('Y-m-d H:i:s', time() + $this->refresh_token_ttl);
        
        // Store hashed refresh token in database
        $stmt = $this->db->prepare("
            INSERT INTO refresh_tokens (user_id, token_hash, expires_at) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$user_id, $token_hash, $expires_at]);
        
        return $refresh_token; // Return raw token to client
    }

    /**
     * Validate access token and return decoded payload
     */
public function validateAccessToken(string $token): ?object {
        try {
            $decoded = JWT::decode($token, new Key($this->secret_key, $this->algorithm));
            if (isset($decoded->purpose) && $decoded->purpose === 'mfa_verification') {
                return null;
            }
            return $decoded;
        } catch (Exception $e) {
            return null;
        }
    }
    /**
     * Refresh access token using a refresh token
     * Implements token rotation: old refresh token is revoked, new one issued
     */
    public function refreshAccessToken(string $refresh_token, string $device_fingerprint): ?array {
        $token_hash = hash('sha256', $refresh_token);
        
        // Find the refresh token in database
        $stmt = $this->db->prepare("
            SELECT id, user_id, expires_at, revoked 
            FROM refresh_tokens 
            WHERE token_hash = ?
        ");
        $stmt->execute([$token_hash]);
        $token_record = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$token_record) {
            return null; // Token not found
        }
        
        // Check if revoked or expired
        if ($token_record['revoked'] || strtotime($token_record['expires_at']) < time()) {
            return null; // Token invalid
        }
        
        // Revoke the used refresh token (token rotation)
        $stmt = $this->db->prepare("UPDATE refresh_tokens SET revoked = 1 WHERE id = ?");
        $stmt->execute([$token_record['id']]);
        
        // Generate new tokens
        $user_id = $token_record['user_id'];
        $new_access_token = $this->createAccessToken($user_id, $device_fingerprint);
        $new_refresh_token = $this->createRefreshToken($user_id);
        
        return [
            'access_token' => $new_access_token,
            'refresh_token' => $new_refresh_token
        ];
    }
    
    /**
     * Blacklist an access token (for logout)
     */
    public function blacklistAccessToken(string $token): bool {
        $decoded = $this->validateAccessToken($token);
        if (!$decoded) {
            return false;
        }
        
        $expires_at = date('Y-m-d H:i:s', $decoded->exp);
        $stmt = $this->db->prepare("
            INSERT INTO token_blacklist (jti, expires_at) 
            VALUES (?, ?)
        ");
        return $stmt->execute([$decoded->jti, $expires_at]);
    }
    
    /**
     * Revoke all refresh tokens for a user (on password change, account compromise)
     */
    public function revokeAllUserRefreshTokens(int $user_id): void {
        $stmt = $this->db->prepare("
            UPDATE refresh_tokens SET revoked = 1 
            WHERE user_id = ? AND revoked = 0
        ");
        $stmt->execute([$user_id]);
    }
    
    /**
     * Check if a token is blacklisted
     */
    private function isTokenBlacklisted(string $jti): bool {
        $stmt = $this->db->prepare("
            SELECT id FROM token_blacklist WHERE jti = ?
        ");
        $stmt->execute([$jti]);
        return $stmt->fetch() !== false;
    }

/**
 * Create a temporary MFA token (expires in 5 minutes)
 */
public function createTempMFAToken(int $user_id, string $device_fingerprint): string {
    $issued_at = time();
    $ttl = 300; // 5 minutes for MFA verification
    
    $payload = [
        'iat' => $issued_at,
        'exp' => $issued_at + $ttl,
        'sub' => $user_id,
        'device' => $device_fingerprint,
        'purpose' => 'mfa_verification'
    ];
    
    return JWT::encode($payload, $this->secret_key, $this->algorithm);
}


/**
 * Create access token WITH device binding
 * This is the core of Zero Trust - token is tied to specific device
 */
public function createAccessToken(int $user_id, ?string $device_fingerprint = null): string {
    $issued_at = time();
    $jti = bin2hex(random_bytes(32));
    
    // Get device fingerprint if not provided
    if ($device_fingerprint === null) {
        $device_fingerprint = self::generateDeviceFingerprint();
    }
    
    // Also get device ID if you have registered devices
    $device_id = $this->getDeviceId($user_id, $device_fingerprint);
    
    $payload = [
        'iat' => $issued_at,                    // Issued at
        'exp' => $issued_at + $this->access_token_ttl, // 15 minutes
        'nbf' => $issued_at,                    // Not before
        'sub' => $user_id,                      // Subject (user ID)
        'jti' => $jti,                          // JWT ID (for blacklist)
        'device_hash' => $device_fingerprint,   // DEVICE BINDING - KEY FIELD
        'device_id' => $device_id,              // Registered device ID (optional)
        'session_id' => session_id()            // PHP session ID for extra binding
    ];
    
    return JWT::encode($payload, $this->secret_key, $this->algorithm);
}

/**
 * Get or create device ID for a user-device pair
 * This allows tracking devices over time
 */
private function getDeviceId(int $user_id, string $device_fingerprint): ?int {
    try {
        // Check if device already registered
        $stmt = $this->db->prepare("
            SELECT id FROM user_devices 
            WHERE user_id = ? AND device_hash = ?
        ");
        $stmt->execute([$user_id, $device_fingerprint]);
        $device = $stmt->fetch();
        
        if ($device) {
            // Update last seen timestamp
            $stmt = $this->db->prepare("
                UPDATE user_devices 
                SET last_seen = NOW(), ip_address = ? 
                WHERE id = ?
            ");
            $stmt->execute([$_SERVER['REMOTE_ADDR'] ?? '', $device['id']]);
            return $device['id'];
        }
        
        // Register new device
        $stmt = $this->db->prepare("
            INSERT INTO user_devices (user_id, device_hash, first_seen, last_seen, ip_address)
            VALUES (?, ?, NOW(), NOW(), ?)
        ");
        $stmt->execute([$user_id, $device_fingerprint, $_SERVER['REMOTE_ADDR'] ?? '']);
        return $this->db->lastInsertId();
        
    } catch (PDOException $e) {
        // Log error but don't break authentication
        error_log("Device tracking error: " . $e->getMessage());
        return null;
    }
}


/**
 * Generate a unique device fingerprint from available data
 * The more data points, the stronger the binding
 */
public static function generateDeviceFingerprint(): string {
    // Core data that's hard for attackers to spoof completely
    $fingerprint_data = [
        // HTTP headers (present in every request)
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
        'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
        'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
        
        // Network hints (available in modern browsers)
        'platform' => $_SERVER['HTTP_SEC_CH_UA_PLATFORM'] ?? '',
        'mobile' => $_SERVER['HTTP_SEC_CH_UA_MOBILE'] ?? '',
        
        // IP network (use /24 subnet for privacy but still binding)
        'ip_subnet' => self::getIpSubnet($_SERVER['REMOTE_ADDR'] ?? ''),
        
        // Session identifier (rotates but provides additional entropy)
        'session_id' => session_id() ?: ''
    ];
    
    // Create a hash that's deterministic but hard to reverse
    return hash('sha256', implode('|', $fingerprint_data));
}

/**
 * Get IP subnet (/24 for IPv4, /64 for IPv6)
 * This provides location binding without exact IP tracking
 */
private static function getIpSubnet(string $ip): string {
    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        // For IPv4, use /24 subnet (first 3 octets)
        return preg_replace('/\.\d+$/', '.0', $ip);
    } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
        // For IPv6, use /64 subnet (first 4 groups)
        $parts = explode(':', $ip);
        return implode(':', array_slice($parts, 0, 4)) . '::/64';
    }
    return 'unknown';
}

/**
 * Enhanced fingerprint with JavaScript-collected data (optional)
 * Call this from frontend and send via header
 */
public static function generateEnhancedFingerprint(array $js_data = []): string {
    $core_data = [
        self::generateDeviceFingerprint(),
        $js_data['screen_resolution'] ?? '',
        $js_data['timezone'] ?? '',
        $js_data['canvas_hash'] ?? '',  // Canvas fingerprinting
        $js_data['webgl_vendor'] ?? '',
        $js_data['audio_hash'] ?? ''
    ];
    
    return hash('sha256', implode('|', $core_data));
}

}
?>