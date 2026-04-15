<?php
session_start();
include "../includes/db.php";
require_once "../includes/GoogleAuthenticator.php";

$ga = new PHPGangsta_GoogleAuthenticator();
$verify_code = $_POST['verify_code'];
$secret = $_SESSION['mfa_secret_temp'];
$userId = $_SESSION['user']['id'];

// Check if the code matches the secret
$checkResult = $ga->verifyCode($secret, $verify_code, 2); 

if ($checkResult) {
    // Code is valid! Now officially enable MFA for this user
    $conn->query("UPDATE users SET mfa_secret='$secret', mfa_enabled=1 WHERE id='$userId'");
    
    unset($_SESSION['mfa_secret_temp']);
    echo "MFA Enabled Successfully!";
    header("Location: dashboard.php");
} else {
    echo "Invalid code. Please try scanning again.";
}
?>