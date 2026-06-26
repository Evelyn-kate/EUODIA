<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../vendor/autoload.php';
require_once __DIR__ . '/../../vendor/lbuchs/webauthn/src/WebAuthn.php';


session_start();

$input = json_decode(file_get_contents('php://input'), true);
$email = $input['email'] ?? '';

if (!$email) {
    http_response_code(400);
    echo json_encode(['error' => 'Email required']);
    exit;
}

// Find user by email
$stmt = $pdo->prepare("SELECT id, email, has_passkey FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !$user['has_passkey']) {
    http_response_code(404);
    echo json_encode(['error' => 'No passkey found for this email. Please login with password first to set up passkey.']);
    exit;
}

// Get user's passkeys
$stmt = $pdo->prepare("SELECT credential_id, public_key FROM passkeys WHERE user_id = ?");
$stmt->execute([$user['id']]);
$passkeys = $stmt->fetchAll();

if (empty($passkeys)) {
    http_response_code(404);
    echo json_encode(['error' => 'No passkeys found']);
    exit;
}

// Prepare allowed credentials
$allowed_credentials = [];
foreach ($passkeys as $passkey) {
    $allowed_credentials[] = [
        'id' => $passkey['credential_id'],
        'type' => 'public-key'
    ];
}

// Initialize WebAuthn
$webauthn = new \lbuchs\WebAuthn\WebAuthn('EUODIA', 'localhost');

// Generate login options
// If you have retrieved an array of the user's allowed credential IDs from your database:
$allowedCredentials = []; // Replace with your DB array of credential IDs if available

$loginArgs = $webauthn->getGetArgs(
    $allowedCredentials, 
    true, // Require user verification (biometrics/PIN)
    true  // Require resident key / discoverable credential
);

// Store challenge in session
$_SESSION['passkey_login_challenge'] = $webauthn->getChallenge();
$_SESSION['passkey_login_user_id'] = $user['id'];

echo json_encode([
    'success' => true,
    'options' => $loginArgs
]);
?>