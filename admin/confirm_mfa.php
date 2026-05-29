<?php
session_start();
include "../includes/db.php";
require_once "../includes/GoogleAuthenticator.php";

$ga = new PHPGangsta_GoogleAuthenticator();
$error = '';

// Ensure the user is authenticated and has an MFA setup secret
if (!isset($_SESSION['user'])) {
    header("Location: ../auth/login.php");
    exit();
}

if (!isset($_SESSION['mfa_secret_temp'])) {
    header("Location: ../admin/mfa_setup.php");
    exit();
}

$user = $_SESSION['user'];
$secret = $_SESSION['mfa_secret_temp'];
$userId = $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code'])) {
    $verify_code = $_POST['verify_code'];
    $checkResult = $ga->verifyCode($secret, $verify_code, 2);

    if ($checkResult) {
        // Code is valid! Now officially enable MFA for this user
        $conn->query("UPDATE users SET mfa_secret='$secret', mfa_enabled=1 WHERE id='$userId'");
        unset($_SESSION['mfa_secret_temp']);
        header("Location: ../admin/dashboard.php");
        exit();
    } else {
        $error = "Invalid code. Please try again.";
    }
}
?>

<form method="POST" action="confirm_mfa.php">
    <h3>Two-Factor Authentication</h3>
    <p>Enter the 6-digit code from your app:</p>
    <?php if($error) echo "<p style='color:red'>" . htmlspecialchars($error) . "</p>"; ?>
    <input type="text" name="verify_code" maxlength="6" required>
    <button type="submit">Verify & Login</button>
</form>