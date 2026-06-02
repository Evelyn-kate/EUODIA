<?php
header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/jwt_utils.php';
require_once '../auth/auth_middlelware.php';

$jwt_manager = new JWTManager($pdo, $jwt_secret);
$auth = new AuthMiddleware($jwt_manager);

// Handle refresh request
$auth->refreshTokens();