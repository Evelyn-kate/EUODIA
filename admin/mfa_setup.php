<?php
session_start();
include "../includes/db.php";
require_once "../includes/GoogleAuthenticator.php";
require_once "../phpqrcode/qrlib.php"; // Local QR Library

$ga = new PHPGangsta_GoogleAuthenticator();

// 1. Generate a brand new secret for the user
// Zero Trust: Every user gets a unique, high-entropy secret
if (!isset($_SESSION['mfa_secret_temp'])) {
    $_SESSION['mfa_secret_temp'] = $ga->createSecret();
}

$secret = $_SESSION['mfa_secret_temp'];

if (!isset($_SESSION['user'])) {
    // Zero Trust Principle: Deny by default. 
    // If we don't know who the user is, kick them back to login.
    header("Location: ../auth/login.php");
    exit();
}

$user = $_SESSION['user'];
$userEmail = $user['email'];
$title = "EuodiaPeaceScents";

// 2. Create the QR Code content (The 'otpauth' URL)
$qrCodeUrl = $ga->getQRCodeGoogleUrl($userEmail, $secret, $title);

// We will embed the external QR image directly rather than generating
// a local QR that would encode the API URL. This ensures the QR
// encodes the OTP URI and the manual secret is displayed for copy.
// Example: <img src="$qrCodeUrl" />
?>

<div class="mfa-container">
    <h2>Secure Your Account</h2>
    <p>Scan this QR code with your Authenticator App:</p>
    
    <img src="<?php echo htmlspecialchars($qrCodeUrl); ?>" alt="MFA QR Code">
    
    <div class="manual-entry">
        <p>Or enter this code manually: <strong><?php echo $secret; ?></strong></p>
    </div>

    <form action="confirm_mfa.php" method="POST">
        <label>Enter the 6-digit code from your app to confirm:</label>
        <input type="text" name="verify_code" required maxlength="6">
        <button type="submit">Enable MFA</button>
    </form>
</div>