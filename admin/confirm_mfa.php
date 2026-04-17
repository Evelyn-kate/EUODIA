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


session_start();

// 1. Check if the user is even supposed to be here (Zero Trust: Verify Identity)
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit();
}

$user = $_SESSION['user'];

// 2. Only check the code IF the form was actually submitted
if (isset($_POST['verify_code'])) {
    $code = $_POST['verify_code'];
    
    // Your MFA verification logic goes here...
    // if ($ga->verifyCode($user['mfa_secret'], $code)) { ... }
}

if ($checkResult) {
    // Code is valid! Now officially enable MFA for this user
    $conn->query("UPDATE users SET mfa_secret='$secret', mfa_enabled=1 WHERE id='$userId'");
    
    unset($_SESSION['mfa_secret_temp']);
    echo "MFA Enabled Successfully!";
    header("Location: ../admin/dashboard.php");

} else {
    echo "Invalid code. Please try scanning again.";
}
?>


<form method="POST" action="confirm_mfa.php">
    <h3>Two-Factor Authentication</h3>
    <p>Enter the 6-digit code from your app:</p>
    <?php if($error) echo "<p style='color:red'>$error</p>"; ?>
    <input type="text" name="verify_code" maxlength="6" required>
    <button type="submit">Verify & Login</button>
</form>