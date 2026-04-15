<?php
header('Content-Type: application/json');
include "../includes/db.php";
include "../includes/jwt.php";

// Verify JWT token
$token = JWTHandler::getToken();
if (!$token) {
  http_response_code(401);
  echo json_encode(['error' => 'Missing authentication token']);
  exit;
}

$user = JWTHandler::verifyToken($token);
if (!$user) {
  http_response_code(401);
  echo json_encode(['error' => 'Invalid or expired token']);
  exit;
}

// Token is valid, get product
$id = $_GET['id'] ?? null;
if (!$id) {
  http_response_code(400);
  echo json_encode(['error' => 'Product ID required']);
  exit;
}

$p = $conn->query("SELECT id,name,price FROM products WHERE id=$id")->fetch_assoc();
if ($p) {
  echo json_encode($p);
} else {
  http_response_code(404);
  echo json_encode(['error' => 'Product not found']);
}
?>
