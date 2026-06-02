<?php
header('Content-Type: application/json');
include "../includes/db.php";
include "../includes/jwt.php";

$refreshToken = JWTHandler::getRefreshToken();
if (!$refreshToken) {
    http_response_code(401);
    echo json_encode(['error' => 'Missing refresh token']);
    exit;
}

$newTokens = JWTHandler::refreshToken($refreshToken, $conn);
if (!$newTokens) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid or expired refresh token']);
    exit;
}

echo json_encode([
    'access_token' => $newTokens['access_token'],
    'refresh_token' => $newTokens['refresh_token'],
    'expires_in' => $newTokens['expires_in']
]);
?>