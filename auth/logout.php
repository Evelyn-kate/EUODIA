<?php 
include "../includes/db.php";
include "../includes/jwt.php";


header('Content-Type: application/json');
require_once '../includes/db.php';
require_once '../includes/jwt_utils.php';
require_once '../auth/auth_middleware.php';

session_start();
JWTHandler::revokeRefreshTokenFromCookie($conn);
JWTHandler::clearAllTokenCookies();
session_destroy();
header("Location: login.php");



$jwt_manager = new JWTManager($pdo, $jwt_secret);
$auth = new AuthMiddleware($jwt_manager);

$auth->logout();


?>
