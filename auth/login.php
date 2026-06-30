<?php
/**
 * EUODIA - Zero Trust Login System
 * Implementation: Argon2id Hashing, JWT Session Management, and MFA Gatekeeping
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include "../includes/db.php";
include "../includes/jwt.php";

require_once '../includes/db.php';
require_once '../includes/jwt_utils.php';


$error = '';

function get_user_ga_secret(int $userId, PDO $conn) {
    // Looks up the 2FA secret key using standard PDO methods
    $stmt = $conn->prepare("SELECT google_authenticator_secret FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        return $row['google_authenticator_secret'];
    }
    
    return null; 
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize input to prevent SQL Injection
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password_input = $_POST['password']; // Plain text from the user form

    // 1. Fetch user by email only (Identity Lookup)
    $q = $conn->query("SELECT * FROM users WHERE email='$email' LIMIT 1");
    
    if ($q && $q->num_rows == 1) {
        // $user is now defined correctly from the database result
        $user = $q->fetch_assoc(); 

        // 2. Verify the hash (Zero Trust: Always Verify Credentials)
        $passwordVerified = false;
        $needsRehash = false;

        if (password_verify($password_input, $user['password'])) {
            $passwordVerified = true;
            if (password_needs_rehash($user['password'], PASSWORD_ARGON2ID)) {
                $needsRehash = true;
            }
        } elseif (password_verify(md5($password_input), $user['password'])) {
            $passwordVerified = true;
            $needsRehash = true;
        } elseif (preg_match('/^[a-f0-9]{32}$/i', $user['password']) && hash_equals($user['password'], md5($password_input))) {
            $passwordVerified = true;
            $needsRehash = true;
        }

        if ($passwordVerified) {
            if ($needsRehash) {
                $newHash = password_hash($password_input, PASSWORD_ARGON2ID);
                $conn->query("UPDATE users SET password='$newHash' WHERE id=" . intval($user['id']));
            }

            // 3. Establish "Limited Trust" Session
            // We store the user data but don't grant full dashboard access yet
            $_SESSION['user'] = $user;
            $_SESSION['temp_user_id'] = $user['id'];
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['is_admin'] = $user['is_admin'];

            // 4. MFA Gatekeeping (Policy Enforcement Point)
            // If MFA is enabled, redirect to the verification gate
            if (isset($user['mfa_enabled']) && $user['mfa_enabled'] == 1) {
                header("Location: verify_mfa.php");
                exit();
            }

            // 5. New Admin Setup Logic
            // If an admin has no secret yet, send them to the setup page
            if ($user['role'] === 'admin' && empty($user['mfa_secret'])) {
                header("Location: ../admin/mfa_setup.php");
                exit();
            }

            // 6. Full Authentication Success (For users without MFA or non-admins)
            // Generate access + refresh JWT tokens
            $tokens = JWTHandler::createTokenPair($user['id'], $user['email'], $user['name']);
            $_SESSION['jwt_token'] = $tokens['access_token'];
            JWTHandler::setTokenCookies($tokens['access_token'], $tokens['refresh_token']);
            JWTHandler::storeRefreshToken($tokens['refresh_token'], $user['id'], $conn);

            // 7. Context-Based Redirection
            if ($user['is_admin'] == 1) {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../uploads/index.php");
            }
            exit();
        } else {
            // Generic error prevents attackers from knowing if the email was correct
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
    }
}


// Initialize JWT Manager
$jwt_manager = new JWTManager($pdo, $jwt_secret);

// Determine request type
$is_api_request = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false 
                  || strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false;

// Get credentials from either JSON or form POST
if ($is_api_request) {
    // API request (JSON)
    $input = json_decode(file_get_contents('php://input'), true);
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $remember_me = $input['remember_me'] ?? false;
} else {
    // Traditional form POST
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $remember_me = isset($_POST['remember_me']);
}

// Validate credentials
if (empty($email) || empty($password)) {
    if ($is_api_request) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password required']);
    } else {
        $_SESSION['login_error'] = 'Email and password required';
        header('Location: login.php');
    }
    exit;
}

// Query user
$stmt = $pdo->prepare("SELECT id, email, username, password_hash, is_admin, mfa_enabled FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

// Verify password (using your existing password_verify)
if (!$user || !password_verify($password, $user['password_hash'])) {
    // Log failed attempt (optional but recommended)
    $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 0)");
    $stmt->execute([$email, $_SERVER['REMOTE_ADDR']]);
    
    if ($is_api_request) {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    } else {
        $_SESSION['login_error'] = 'Invalid email or password';
        header('Location: login.php');
    }
    exit;
}

// Log successful attempt
$stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, success) VALUES (?, ?, 1)");
$stmt->execute([$email, $_SERVER['REMOTE_ADDR']]);

// ============================================
// NEW ZERO TRUST CODE - ADD THIS SECTION
// ============================================

// Generate device fingerprint for Zero Trust
$device_fingerprint = JWTManager::generateDeviceFingerprint();

// Check if MFA is required (you already have Google Authenticator)
$mfa_required = $user['mfa_enabled'] == 1;

if ($mfa_required && !isset($_POST['mfa_code']) && !isset($input['mfa_code'])) {
    // MFA required but not provided yet
    if ($is_api_request) {
        echo json_encode([
            'requires_mfa' => true,
            'user_id' => $user['id'],
            'temp_token' => $jwt_manager->createTempMFAToken($user['id'], $device_fingerprint)
        ]);
    } else {
        // Store temp session for MFA verification
        $_SESSION['mfa_pending_user_id'] = $user['id'];
        $_SESSION['mfa_device_fingerprint'] = $device_fingerprint;
        header('Location: mfa_verify.php');
    }
    exit;
}

// Verify MFA code if provided
if ($mfa_required && (isset($_POST['mfa_code']) || isset($input['mfa_code']))) {
    $mfa_code = $_POST['mfa_code'] ?? $input['mfa_code'] ?? '';
    
    // Verify Google Authenticator code
    require_once 'includes/gauth.php';
    $ga = new PHPGangsta_GoogleAuthenticator();
    $secret = get_user_ga_secret($user['id'], $pdo);
    
    if (!$ga->verifyCode($secret, $mfa_code, 2)) {
        if ($is_api_request) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid MFA code']);
        } else {
            $_SESSION['login_error'] = 'Invalid MFA code';
            header('Location: login.php');
        }
        exit;
    }
}



// In your login.php, when creating the access token:
$device_fingerprint = JWTManager::generateDeviceFingerprint();

// Create token with device binding
$access_token = $jwt_manager->createAccessToken($user['id'], $device_fingerprint);

// Store device info in session for audit
$_SESSION['device_hash'] = $device_fingerprint;
$_SESSION['device_registered_at'] = time();


// ============================================
// ZERO TRUST TOKEN GENERATION (THE NEW CODE)
// ============================================

// Create tokens
$access_token = $jwt_manager->createAccessToken($user['id'], $device_fingerprint);
$refresh_token = $jwt_manager->createRefreshToken($user['id']);

// Store refresh token in HTTP-only cookie (more secure)
$refresh_token_expiry = time() + (86400 * 7); // 7 days
setcookie('refresh_token', $refresh_token, [
    'expires' => $refresh_token_expiry,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,  // Only send over HTTPS
    'httponly' => true, // Not accessible via JavaScript
    'samesite' => 'Strict'
]);

// ============================================
// PRESERVE YOUR EXISTING SESSION LOGIC
// ============================================

// Keep your existing session for backward compatibility
$_SESSION['user_id'] = $user['id'];
$_SESSION['email'] = $user['email'];
$_SESSION['username'] = $user['username'];
$_SESSION['is_admin'] = $user['is_admin'];
$_SESSION['authenticated'] = true;
$_SESSION['device_fingerprint'] = $device_fingerprint;

// Also store JWT in session for server-side validation
$_SESSION['access_token'] = $access_token;

// ============================================
// RESPONSE BASED ON REQUEST TYPE
// ============================================

if ($is_api_request) {
    // Return JSON with tokens for SPA/mobile apps
    echo json_encode([
        'success' => true,
        'access_token' => $access_token,
        'refresh_token' => $refresh_token,
        'expires_in' => 900, // 15 minutes
        'token_type' => 'Bearer',
        'user' => [
            'id' => $user['id'],
            'email' => $user['email'],
            'username' => $user['username'],
            'is_admin' => $user['is_admin']
        ]
    ]);
} else {
    // Traditional redirect for form submission
    $redirect_to = $_POST['redirect_to'] ?? '../admin/dashboard.php';
    header('Location: ' . $redirect_to);
}
exit;

?>    

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Euodia Peace Scents</title>
  <link rel="stylesheet" href="../uploads/style.css">
  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #0d0d0d, #1a1a1a);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }
    .login-container {
      background: #111;
      border: 1px solid #333;
      border-radius: 12px;
      padding: 40px;
      width: 100%;
      max-width: 400px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.6);
    }
    .logo {
      text-align: center;
      margin-bottom: 30px;
    }
    .logo h1 {
      color: #d4af37;
      font-size: 1.8em;
      letter-spacing: 2px;
    }
    .logo p {
      color: #888;
      font-size: 0.9em;
    }
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      color: #ccc;
      margin-bottom: 8px;
      font-size: 0.9em;
    }
    .form-group input {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #333;
      border-radius: 6px;
      background: #1a1a1a;
      color: #eee;
      font-size: 1em;
      transition: border 0.3s;
    }
    .form-group input:focus {
      outline: none;
      border-color: #d4af37;
    }
    .error {
      background: #3a1a1a;
      border: 1px solid #662222;
      color: #ff6b6b;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
      font-size: 0.9em;
    }
    .btn {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #d4af37, #b8962e);
      border: none;
      border-radius: 6px;
      color: #000;
      font-size: 1em;
      font-weight: bold;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
    }
    .links {
      text-align: center;
      margin-top: 25px;
    }
    .links a {
      color: #d4af37;
      text-decoration: none;
      font-size: 0.9em;
    }
    .links a:hover {
      text-decoration: underline;
    }
    .divider {
      color: #555;
      margin: 0 10px;
    }
  </style>
</head>
<body>
<div class="login-container">
  <div class="logo">
    <h1>EUODIA</h1>
    <p>Peace Scents</p>
  </div>
  <?php if ($error): ?>
    <div class="error"><?php echo $error; ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="Enter your email" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password" required>
    </div>
    <button type="submit" class="btn">Sign In</button>
    <button id="passkeyLoginBtn" class="btn btn-secondary" style="margin-top: 1rem;">
    <i class="fas fa-fingerprint"></i> Sign in with Passkey
</button>

<div id="passkeyLoginStatus"></div>
  </form>
  <div class="links">
    <a href="register.php">Create Account</a>
    <span class="divider">|</span>
    <a href="../uploads/index.php">Back to Shop</a>
  </div>
</div>
</body>


<script>
document.getElementById('passkeyLoginBtn')?.addEventListener('click', async function() {
    const emailInput = document.getElementById('email'); // Adjust to your email field ID
    const email = emailInput?.value;
    
    if (!email) {
        alert('Please enter your email address first');
        return;
    }
    
    if (!window.PublicKeyCredential) {
        alert('Your browser does not support passkeys. Please use Chrome, Edge, or Safari.');
        return;
    }
    
    const statusDiv = document.getElementById('passkeyLoginStatus');
    statusDiv.innerHTML = '<div style="color: #3b82f6;">🔐 Waiting for biometric verification...</div>';
    
    try {
        // Step 1: Get challenge from server
        const startRes = await fetch('../api/passkey/login_start.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email })
        });
        
        const startData = await startRes.json();
        if (!startData.success) throw new Error(startData.error);
        
        // Step 2: Get credential from device (fingerprint/face ID)
        const credential = await navigator.credentials.get({
            publicKey: startData.options
        });
        
        // Step 3: Verify with server
        const completeRes = await fetch('../api/passkey/login_complete.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                authentication_data: JSON.stringify(credential)
            })
        });
        
        const completeData = await completeRes.json();
        
        if (completeData.success) {
            statusDiv.innerHTML = '<div style="color: #10b981;">✅ Login successful! Redirecting...</div>';
            window.location.href = completeData.redirect || '/accounts/sessions.php';
        } else {
            throw new Error(completeData.error);
        }
        
    } catch (error) {
        statusDiv.innerHTML = '<div style="color: #dc2626;">❌ ' + error.message + '</div>';
        console.error('Passkey login error:', error);
    }
});
</script>
</html>