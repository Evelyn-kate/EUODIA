<?php
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/jwt_utils.php';

// Require authentication
requireAuth();

$current_user = getCurrentUser($pdo);
$page_title = "Manage Devices";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #1e293b;
        }
        .container { max-width: 1200px; margin: 0 auto; padding: 2rem; }
        .header { margin-bottom: 2rem; }
        .header h1 { font-size: 1.875rem; font-weight: 600; margin-bottom: 0.5rem; }
        .header p { color: #64748b; }
        
        .card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
        }
        
        .btn {
            padding: 0.625rem 1.25rem;
            border-radius: 0.5rem;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
            font-size: 0.875rem;
        }
        .btn-primary { background: #3b82f6; color: white; }
        .btn-primary:hover { background: #2563eb; }
        .btn-success { background: #10b981; color: white; }
        .btn-success:hover { background: #059669; }
        .btn-danger { background: #ef4444; color: white; }
        .btn-danger:hover { background: #dc2626; }
        
        .passkey-section {
            background: #f0fdf4;
            border: 2px solid #10b981;
            border-radius: 1rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.75rem;
            margin-top: 1rem;
        }
        .alert-success { background: #f0fdf4; color: #10b981; border: 1px solid #10b981; }
        .alert-error { background: #fef2f2; color: #dc2626; border: 1px solid #dc2626; }
        
        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1rem;
        }
        .session-item {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        .session-item.current { border-color: #10b981; background: #f0fdf4; }
        .session-icon { font-size: 1.5rem; }
        .session-info { flex: 1; }
        .session-device { font-weight: 500; }
        .session-meta { font-size: 0.75rem; color: #64748b; }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        .badge-current { background: #10b981; color: white; }
        .badge-inactive { background: #e2e8f0; color: #64748b; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-laptop-code"></i> Manage Devices</h1>
            <p>View and manage devices where you're signed in</p>
        </div>

        <!-- Passkey Enrollment Section -->
        <div class="passkey-section" id="passkeySection">
            <h3><i class="fas fa-fingerprint"></i> Passwordless Login</h3>
            <p style="color: #64748b; margin: 0.5rem 0;">
                Set up a passkey to login without a password using your device's biometrics.
            </p>
            
            <?php if ($current_user && $current_user['has_passkey']): ?>
                <div class="alert alert-success">
                    ✅ Passkey is enabled on this account.
                </div>
                <button class="btn btn-danger" id="removePasskeyBtn">
                    <i class="fas fa-trash"></i> Remove Passkey
                </button>
            <?php else: ?>
                <div id="passkeyStatus"></div>
                <button class="btn btn-primary" id="registerPasskeyBtn">
                    <i class="fas fa-fingerprint"></i> Set up Passkey
                </button>
            <?php endif; ?>
        </div>

        <!-- Sessions List -->
        <div class="card">
            <h3><i class="fas fa-list"></i> Active Sessions</h3>
            <div id="sessionsContainer">
                <div style="text-align:center; padding: 2rem; color: #64748b;">
                    <i class="fas fa-spinner fa-spin"></i> Loading...
                </div>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // PASSKEY REGISTRATION
        // ============================================
        document.getElementById('registerPasskeyBtn')?.addEventListener('click', async function() {
            if (!window.PublicKeyCredential) {
                alert('Your browser does not support passkeys. Please use Chrome, Edge, or Safari.');
                return;
            }
            
            const statusDiv = document.getElementById('passkeyStatus');
            
            try {
                statusDiv.innerHTML = '<div class="alert alert-info">⏳ Starting passkey registration...</div>';
                
                const response = await fetch('/api/passkey/register_start.php');
                const data = await response.json();
                
                if (!data.success) throw new Error(data.error);
                
                statusDiv.innerHTML = '<div class="alert alert-info">🔐 Please complete biometric verification on your device...</div>';
                
                const credential = await navigator.credentials.create({
                    publicKey: data.options
                });
                
                const complete = await fetch('/api/passkey/register_complete.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        registration_data: JSON.stringify(credential)
                    })
                });
                
                const result = await complete.json();
                
                if (result.success) {
                    statusDiv.innerHTML = '<div class="alert alert-success">✅ Passkey registered successfully! You can now login without a password.</div>';
                    location.reload();
                } else {
                    throw new Error(result.error);
                }
                
            } catch (error) {
                statusDiv.innerHTML = '<div class="alert alert-error">❌ Error: ' + error.message + '</div>';
                console.error('Passkey registration error:', error);
            }
        });

        // ============================================
        // LOAD SESSIONS
        // ============================================
        async function loadSessions() {
            try {
                const response = await fetch('/api/get_sessions.php', {
                    headers: {
                        'Authorization': 'Bearer <?php echo $_SESSION['access_token'] ?? ''; ?>'
                    }
                });
                
                if (!response.ok) throw new Error('Failed to load sessions');
                
                const data = await response.json();
                const container = document.getElementById('sessionsContainer');
                
                if (!data.sessions || data.sessions.length === 0) {
                    container.innerHTML = '<p style="color: #64748b;">No active sessions found.</p>';
                    return;
                }
                
                container.innerHTML = '<div class="sessions-grid">' + 
                    data.sessions.map(session => `
                        <div class="session-item ${session.is_current ? 'current' : ''}">
                            <div class="session-icon">
                                <i class="fas ${session.device_type === 'mobile' ? 'fa-mobile-alt' : 'fa-desktop'}"></i>
                            </div>
                            <div class="session-info">
                                <div class="session-device">${session.device_name}</div>
                                <div class="session-meta">
                                    ${session.location || 'Unknown'} • Last active: ${session.last_activity_relative || 'Just now'}
                                    ${session.is_current ? ' <span class="badge badge-current">Current</span>' : ''}
                                </div>
                            </div>
                            ${!session.is_current ? `
                                <button class="btn btn-danger" onclick="revokeSession('${session.session_id}')" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">
                                    <i class="fas fa-times"></i>
                                </button>
                            ` : ''}
                        </div>
                    `).join('') + '</div>';
                    
            } catch (error) {
                console.error('Error loading sessions:', error);
                document.getElementById('sessionsContainer').innerHTML = '<p style="color: #dc2626;">Failed to load sessions.</p>';
            }
        }

        async function revokeSession(sessionId) {
            if (!confirm('Sign out this device?')) return;
            
            try {
                const response = await fetch('/api/revoke_session.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Authorization': 'Bearer <?php echo $_SESSION['access_token'] ?? ''; ?>'
                    },
                    body: JSON.stringify({ session_id: sessionId })
                });
                
                if (response.ok) {
                    alert('Device signed out successfully');
                    loadSessions();
                } else {
                    alert('Failed to revoke session');
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        // Load sessions on page load
        document.addEventListener('DOMContentLoaded', loadSessions);
    </script>
</body>
</html>