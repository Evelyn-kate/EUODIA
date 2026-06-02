<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/jwt_utils.php';
require_once '../includes/session_manager.php';

// Authenticate user
$jwt_manager = new JWTManager($pdo, $jwt_secret);
$auth = new AuthMiddleware($jwt_manager);
$user_id = $auth->authenticate();

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get current session ID from JWT
$headers = apache_request_headers();
$auth_header = $headers['Authorization'] ?? '';
preg_match('/Bearer\s(\S+)/', $auth_header, $matches);
$token = $matches[1];
$decoded = $jwt_manager->validateAccessToken($token);

$session_manager = new SessionManager($pdo);
$count = $session_manager->revokeAllOtherSessions($user_id, $decoded->jti ?? '');

echo json_encode([
    'success' => true,
    'message' => "Revoked $count other session(s)",
    'revoked_count' => $count
]);
?>