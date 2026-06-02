<?php 
include "../includes/db.php";
include "../includes/jwt.php";

session_start();
JWTHandler::revokeRefreshTokenFromCookie($conn);
JWTHandler::clearAllTokenCookies();
session_destroy();
header("Location: login.php");
?>
