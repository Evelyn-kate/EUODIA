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
     * Create access token (short-lived)
     */
    public function createAccessToken(int $user_id, string $device_fingerprint): string {
        $issued_at = time();
        $jti = bin2hex(random_bytes(32)); // Unique JWT ID for blacklisting
        
        $payload = [
            'iat' => $issued_at,           // Issued at
            'exp' => $issued_at + $this->access_token_ttl, // Expires in 15 min
            'nbf' => $issued_at,           // Not before (immediately valid)
            'sub' => $user_id,             // Subject (user ID)
            'jti' => $jti,                 // JWT ID (for blacklist)
            'device' => $device_fingerprint // Device binding
        ];
        
        return JWT::encode($payload, $this->secret_key, $this->algorithm);
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
     * Generate device fingerprint for binding
     */
    public static function generateDeviceFingerprint(): string {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        return hash('sha256', $user_agent . $accept_language . $ip);
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

}
?>