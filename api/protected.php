<?php
header('Content-Type: application/json');
require_once '.includes/db.php';
require_once '../includes/jwt_utils.php';
require_once '../auth/auth_middleware.php';

$jwt_manager = new JWTManager($pdo, $jwt_secret);
$auth = new AuthMiddleware($jwt_manager);

// Authenticate request
$user_id = $auth->authenticate();
if (!$user_id) {
    exit; // authenticate() already sent response
}

// Get user data
$stmt = $pdo->prepare("SELECT id, email, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

echo json_encode([
    'message' => 'Access granted',
    'user' => $user,
    'timestamp' => time()
]);