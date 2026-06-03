<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../vendor/autoload.php';



session_start();

// Get logged-in user ID
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

$user_id = $_SESSION['user_id'];

// Get the registration data from frontend
$input = json_decode(file_get_contents('php://input'), true);
$registration_data = $input['registration_data'] ?? '';

if (!$registration_data) {
    http_response_code(400);
    echo json_encode(['error' => 'No registration data provided']);
    exit;
}

if (is_string($registration_data)) {
    $registration_data = json_decode($registration_data, true);
}

$clientDataJSON = $registration_data['clientDataJSON'] ?? null;
$attestationObject = $registration_data['attestationObject'] ?? null;
$challenge = $_SESSION['passkey_registration_challenge'] ?? null;

if (!$clientDataJSON || !$attestationObject || !$challenge) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid registration data or missing challenge']);
    exit;
}

// Arguments: (App Name, Relying Party ID / Domain)
$webauthn = new \lbuchs\WebAuthn\WebAuthn('EUODIA', 'localhost');
try {
    // Verify the registration
    $result = $webauthn->processCreate(
        $clientDataJSON,
        $attestationObject,
        $challenge
    );
    
    // Store the passkey in database
    $stmt = $pdo->prepare("
        INSERT INTO passkeys (user_id, credential_id, public_key, device_name, aaguid)
        VALUES (?, ?, ?, ?, ?)
    ");
    
    $stmt->execute([
        $user_id,
        $result->credentialId,
        $result->publicKey,
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown Device',
        $result->aaguid ?? ''
    ]);
    
    // Mark user as having passkey
    $stmt = $pdo->prepare("UPDATE users SET has_passkey = 1 WHERE id = ?");
    $stmt->execute([$user_id]);
    
    // Clear challenge from session
    unset($_SESSION['passkey_registration_challenge']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Passkey registered successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'error' => 'Registration failed: ' . $e->getMessage()
    ]);
}
?>