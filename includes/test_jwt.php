<?php
require_once 'vendor/autoload.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

$secret_key = "your-secret-key-change-this";

// Create a test token
$payload = [
    'user_id' => 1,
    'exp' => time() + 3600
];

$token = JWT::encode($payload, $secret_key, 'HS256');
echo "Token created successfully: " . $token . "\n\n";

// Decode and verify
$decoded = JWT::decode($token, new Key($secret_key, 'HS256'));
echo "Token verified! User ID: " . $decoded->user_id;
