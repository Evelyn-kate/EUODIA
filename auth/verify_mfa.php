<?php
session_start();
require_once '../db_connect.php'; // Adjust path if needed
require_once '../phpqrcode/GoogleAuthenticator.php';

$ga = new PHPGangsta_GoogleAuthenticator();

// 1. Zero Trust Identity Check
// We use 'temp_user_id' because they haven't fully logged in yet.
if (!isset($_SESSION['temp_user_id'])) {
    header("Location: login.php");
    exit();
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['otp_code'];
    $userId = $_SESSION['temp_user_id'];

    // Fetch the secret from the database for this specific user
    $stmt = $conn->prepare("SELECT mfa_secret, email FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // 2. Continuous Verification
    if ($ga->verifyCode($user['mfa_secret'], $code, 2)) {
        // Validation Success: Upgrade the session to "Full Access"
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        unset($_SESSION['temp_user_id']); // Remove the temporary marker
        
        header("Location: ../admin/dashboard.php");
        exit();
    } else {
        $error = "Invalid verification code. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html>
<head><title>MFA Verification</title></head>
<body>
    <form method="POST">
        <h2>Two-Factor Authentication</h2>
        <p>Enter the 6-digit code from your Google Authenticator app.</p>
        <?php if($error) echo "<p style='color:red'>$error</p>"; ?>
        <input type="text" name="otp_code" placeholder="000000" maxlength="6" required autofocus>
        <button type="submit">Verify Identity</button>
    </form>
</body>
</html>