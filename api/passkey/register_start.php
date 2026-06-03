<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';
// 2. Force-load the exact source file from the lbuchs package
require_once __DIR__ . '/../../vendor/lbuchs/webauthn/src/WebAuthn.php';


session_start();

// Get logged-in user ID (from your existing session)
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get user email for display
$stmt = $pdo->prepare("SELECT email, username FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Initialize WebAuthn
// Arguments: (App Name, Relying Party ID / Domain)
$webauthn = new \lbuchs\WebAuthn\WebAuthn('EUODIA', 'localhost');

// Generate registration options
$challenge = $webauthn->getCreateArgs($_POST['userId'], $_POST['userName'], $_POST['displayName']);
// Store challenge in session for verification
//$_SESSION['passkey_registration_challenge'] = $webAuthn->getChallenge();
$_SESSION['challenge'] = $webauthn->getChallenge();

// Return options to frontend
echo json_encode([
    'success' => true,
    'options' => $challenge
]);
?>