<?php
header('Content-Type: application/json');
require_once '../../includes/db.php';
require_once '../../vendor/autoload.php';

use lbuchs\WebAuthn\WebAuthn;

session_start();

$input = json_decode(file_get_contents('php://input'), true);
$authentication_data = $input['authentication_data'] ?? '';

if (!$authentication_data) {
    http_response_code(400);
    echo json_encode(['error' => 'No authentication data provided']);
    exit;
}

$challenge = $_SESSION['passkey_login_challenge'] ?? '';
$user_id = $_SESSION['passkey_login_user_id'] ?? null;

if (!$challenge || !$user_id) {
    http_response_code(400);
    echo json_encode(['error' => 'No active login session']);
    exit;
}

// Get the user's passkeys
$stmt = $pdo->prepare("SELECT credential_id, public_key FROM passkeys WHERE user_id = ?");
$stmt->execute([$user_id]);
$passkeys = $stmt->fetchAll();

$credentials = [];
foreach ($passkeys as $passkey) {
    $credentials[$passkey['credential_id']] = $passkey['public_key'];
}

$webAuthn = new WebAuthn('EUODIA', 'localhost');

try {
    // Parse the authentication data
    $data = json_decode($authentication_data, true);
    
    // Get the credential ID from the authentication data
    $credentialId = $data['id'] ?? '';
    
    if (!isset($credentials[$credentialId])) {
        throw new Exception('Unknown credential');
    }
    
    // Use the correct method: processGet
    $result = $webAuthn->processGet(
        $data['id'],
        $challenge,
        $data['clientDataJSON'],
        $data['authenticatorData'],
        $data['signature'],
        $data['userHandle']
    );
    
    if ($result) {
        // Update last used
        $stmt = $pdo->prepare("UPDATE passkeys SET last_used_at = NOW() WHERE credential_id = ?");
        $stmt->execute([$credentialId]);
        
        // Log the user in
        $stmt = $pdo->prepare("SELECT id, email, username FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['authenticated'] = true;
        
        unset($_SESSION['passkey_login_challenge']);
        unset($_SESSION['passkey_login_user_id']);
        
        echo json_encode([
            'success' => true,
            'redirect' => '/account/dashboard.php'
        ]);
    } else {
        throw new Exception('Authentication verification failed');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => 'Authentication failed: ' . $e->getMessage()]);
}
?>