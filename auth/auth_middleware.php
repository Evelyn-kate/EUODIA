<?php
require_once __DIR__ . '../includes/db.php';
require_once __DIR__ . '../includes/jwt_utils.php';

class AuthMiddleware {
    private JWTManager $jwt_manager;
    
    public function __construct(JWTManager $jwt_manager) {
        $this->jwt_manager = $jwt_manager;
    }
    
    /**
     * Authenticate request and return user ID if valid
     */
    public function authenticate(): ?int {
        // Get authorization header
        $headers = apache_request_headers();
        $auth_header = $headers['Authorization'] ?? '';
        
        if (empty($auth_header) || !preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $this->sendUnauthorized('No token provided');
            return null;
        }
        
        $token = $matches[1];
        $device_fingerprint = JWTManager::generateDeviceFingerprint();
        
        // Validate token
        $decoded = $this->jwt_manager->validateAccessToken($token);
        
        if (!$decoded) {
            $this->sendUnauthorized('Invalid or expired token');
            return null;
        }
        
        // Verify device binding
        if ($decoded->device !== $device_fingerprint) {
            $this->sendUnauthorized('Device mismatch - possible token theft');
            return null;
        }
        
        return $decoded->sub; // Return user ID
    }
    
    /**
     * Refresh endpoint - generates new tokens from refresh token
     */
    public function refreshTokens(): void {
        $data = json_decode(file_get_contents('php://input'), true);
        $refresh_token = $data['refresh_token'] ?? '';
        
        if (!$refresh_token) {
            $this->sendUnauthorized('Refresh token required');
            return;
        }
        
        $device_fingerprint = JWTManager::generateDeviceFingerprint();
        $result = $this->jwt_manager->refreshAccessToken($refresh_token, $device_fingerprint);
        
        if (!$result) {
            $this->sendUnauthorized('Invalid or expired refresh token');
            return;
        }
        
        echo json_encode([
            'access_token' => $result['access_token'],
            'refresh_token' => $result['refresh_token']
        ]);
    }
    
    /**
     * Logout - blacklists current token
     */
    public function logout(): void {
        $headers = apache_request_headers();
        $auth_header = $headers['Authorization'] ?? '';
        
        if (preg_match('/Bearer\s(\S+)/', $auth_header, $matches)) {
            $this->jwt_manager->blacklistAccessToken($matches[1]);
        }
        
        echo json_encode(['message' => 'Logged out successfully']);
    }
    
    private function sendUnauthorized(string $message): void {
        http_response_code(401);
        echo json_encode(['error' => $message]);
        exit;
    }
}