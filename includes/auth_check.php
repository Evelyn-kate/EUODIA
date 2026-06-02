<?php
/**
 * Authentication Check Middleware
 * Verifies user is logged in before accessing protected pages
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Require database connection
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/jwt_utils.php';

/**
 * Check if user is authenticated via session
 * Returns true if authenticated, false otherwise
 */
function isAuthenticated(): bool {
    return isset($_SESSION['user_id']) && isset($_SESSION['authenticated']) && $_SESSION['authenticated'] === true;
}

/**
 * Get current authenticated user ID
 * Returns user ID or null if not authenticated
 */
function getCurrentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get current user data
 * Returns user array or null if not authenticated
 */
function getCurrentUser(PDO $pdo): ?array {
    if (!isAuthenticated()) {
        return null;
    }
    
    $stmt = $pdo->prepare("SELECT id, email, username, is_admin, mfa_enabled FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $user ?: null;
}

/**
 * Require authentication - redirects to login if not authenticated
 * Use this at the top of protected pages
 */
function requireAuth(): void {
    if (!isAuthenticated()) {
        // Store the requested URL to redirect back after login
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        
        // Redirect to login page
        header('Location: ../auth/login.php');
        exit;
    }
}

/**
 * Require admin authentication - redirects if not admin
 * Use this for admin-only pages
 */
function requireAdmin(PDO $pdo): void {
    requireAuth();
    
    $user = getCurrentUser($pdo);
    if (!$user || $user['is_admin'] != 1) {
        http_response_code(403);
        die('Access denied. Admin privileges required.');
    }
}

/**
 * Validate session integrity with JWT token
 * Optional - provides extra security by validating the stored JWT
 */
function validateSessionJWT(PDO $pdo, string $jwt_secret): bool {
    if (!isset($_SESSION['access_token'])) {
        return isAuthenticated(); // Fall back to session only
    }
    
    $jwt_manager = new JWTManager($pdo, $jwt_secret);
    $decoded = $jwt_manager->validateAccessToken($_SESSION['access_token']);
    
    if (!$decoded) {
        // Token expired or invalid - clear session
        session_destroy();
        return false;
    }
    
    // Verify device fingerprint hasn't changed
    $current_fingerprint = JWTManager::generateDeviceFingerprint();
    if (isset($_SESSION['device_hash']) && $_SESSION['device_hash'] !== $current_fingerprint) {
        // Device mismatch - possible session hijacking
        session_destroy();
        return false;
    }
    
    return true;
}

/**
 * Logout user - clear all session data
 */
function logout(): void {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

/**
 * Regenerate session ID to prevent fixation attacks
 * Call this after successful login
 */
function regenerateSession(): void {
    session_regenerate_id(true);
}
?>