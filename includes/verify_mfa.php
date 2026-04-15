<?php
session_start();
include "../includes/db.php";
require_once "../includes/GoogleAuthenticator.php";

$ga = new PHPGangsta_GoogleAuthenticator();
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['otp_code'];
    $userId = $_SESSION['temp_user_id'];

    $q = $conn->query("SELECT * FROM users WHERE id='$userId'");
    $user = $q->fetch_assoc();

    // Verify the 6-digit code against the secret key
    $checkResult = $ga->verifyCode($user['mfa_secret'], $code, 2); // 2 = 30sec clock tolerance

    if ($checkResult) {
        // SUCCESS: Now set the full session and JWT
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        unset($_SESSION['temp_user_id']); // Clear the temporary session
        
        header("Location: ../admin/dashboard.php");
        exit();
    } else {
        $error = "Invalid verification code.";
    }
}
?>
<form method="POST">
    <input type="text" name="otp_code" placeholder="000000" required maxlength="6">
    <button type="submit">Verify Code</button>
</form>