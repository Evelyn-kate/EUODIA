<?php 
include "../includes/jwt.php";

session_start();
JWTHandler::clearTokenCookie();
session_destroy();
header("Location: login.php");
?>
