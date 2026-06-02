<?php
/**
 * Device Binding Middleware
 * Validates that the JWT's device fingerprint matches the current request
 */

require_once __DIR__ . '/../includes/jwt_utils.php';

class DeviceBindingMiddleware {
    private JWTManager $jwt_manager;
    private PDO $db;
    
    public function __construct(JWTManager $jwt_manager, PDO $db) {
        $this->jwt_manager = $jwt_manager;
        $this->db = $db;
    }
    
    /**
     * Validate device binding for the current request
     * Returns true if valid, false with appropriate response if not
     */
    public function validate(): bool {
        // Get token from Authorization header
        $headers = apache_request_headers();
        $auth_header = $headers['Authorization'] ?? '';
        
        if (empty($auth_header) || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $this->sendError('No valid token provided', 401);
            return false;
        }
        
        $token = $matches[1];
        $current_fingerprint = JWTManager::generateDeviceFingerprint();
        
        // Decode and validate token
        $decoded = $this->jwt_manager->validateAccessToken($token);
        
        if (!$decoded) {
            $this->sendError('Invalid or expired token', 401);
            return false;
        }
        
        // ============================================
        // CORE DEVICE BINDING VALIDATION
        // ============================================
        
        // Check if device hash in JWT matches current device
        if (!isset($decoded->device_hash)) {
            $this->sendError('Token missing device binding', 401);
            return false;
        }
        
        if ($decoded->device_hash !== $current_fingerprint) {
            // DEVICE MISMATCH - Potential token theft!
            $this->handleDeviceMismatch($decoded->sub, $decoded->device_hash, $current_fingerprint);
            return false;
        }
        
        // Optional: Check if device is still trusted (admin can revoke devices)
        if ($decoded->device_id && !$this->isDeviceTrusted($decoded->device_id)) {
            $this->sendError('Device has been revoked by administrator', 403);
            return false;
        }
        
        // Optional: Check session ID binding (extra security layer)
        if (isset($decoded->session_id) && $decoded->session_id !== session_id()) {
            $this->sendError('Session mismatch - possible session hijacking', 401);
            return false;
        }
        
        // Store device info in request context for logging
        $_REQUEST['device_hash'] = $current_fingerprint;
        $_REQUEST['user_id'] = $decoded->sub;
        
        return true;
    }
    
    /**
     * Handle device mismatch - potential security incident
     */
    private function handleDeviceMismatch(int $user_id, string $original_hash, string $current_hash): void {
        // Log the incident
        $stmt = $this->db->prepare("
            INSERT INTO security_incidents (user_id, incident_type, original_device_hash, current_device_hash, ip_address, user_agent, created_at)
            VALUES (?, 'token_theft_suspected', ?, ?, ?, ?, NOW())
        ");
        $stmt->execute([
            $user_id,
            $original_hash,
            $current_hash,
            $_SERVER['REMOTE_ADDR'] ?? '',
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
        
        // Revoke ALL tokens for this user (prevent further damage)
        $this->jwt_manager->revokeAllUserRefreshTokens($user_id);
        
        // Blacklist current token (already detected)
        // The token will expire in 15 minutes anyway
        
        // Send alert to security team (email/webhook)
        $this->sendSecurityAlert($user_id, $original_hash, $current_hash);
        
        // Return error to user
        $this->sendError('Security violation detected. Please login again.', 401);
    }
    
    /**
     * Check if device is still trusted (admin revocation)
     */
    private function isDeviceTrusted(int $device_id): bool {
        $stmt = $this->db->prepare("
            SELECT trusted FROM user_devices WHERE id = ?
        ");
        $stmt->execute([$device_id]);
        $device = $stmt->fetch();
        
        return $device ? (bool)$device['trusted'] : false;
    }
    
    /**
     * Send security alert (email, Slack, webhook)
     */
    private function sendSecurityAlert(int $user_id, string $original_hash, string $current_hash): void {
        // Example: Send to webhook (Slack, Discord, custom endpoint)
        $webhook_url = getenv('SECURITY_WEBHOOK_URL');
        if ($webhook_url) {
            $data = [
                'alert' => 'JWT Token Theft Detected',
                'user_id' => $user_id,
                'original_device' => substr($original_hash, 0, 16),
                'suspicious_device' => substr($current_hash, 0, 16),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $ch = curl_init($webhook_url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 2); // Don't block the request
            curl_exec($ch);
            unset($ch);
        }
        
        // Also log to error log
        error_log("SECURITY ALERT: Token theft suspected for user $user_id from IP {$_SERVER['REMOTE_ADDR']}");
    }
    
    private function sendError(string $message, int $status_code): void {
        http_response_code($status_code);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => $message,
            'code' => $status_code,
            'timestamp' => time()
        ]);
        exit;
    }
}