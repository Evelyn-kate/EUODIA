<?php
// Start session and require authentication
session_start();
require_once '../includes/db.php';
require_once '../includes/auth_check.php';
require_once '../includes/jwt_utils.php';

// Require authentication - this will redirect to login if not logged in
requireAuth();

// Optional: Validate JWT for extra security
if (isset($jwt_secret)) {
    if (!validateSessionJWT($pdo, $jwt_secret)) {
        logout();
        header('Location: ../auth/login.php?error=session_expired');
        exit;
    }
}

// Get current user for display
$current_user = getCurrentUser($pdo);
$page_title = "Manage Devices";

// Generate access token for API calls (if not already in session)
if (!isset($_SESSION['access_token']) && isset($jwt_secret)) {
    $jwt_manager = new JWTManager($pdo, $jwt_secret);
    $device_fingerprint = JWTManager::generateDeviceFingerprint();
    $_SESSION['access_token'] = $jwt_manager->createAccessToken($_SESSION['user_id'], $device_fingerprint);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?> - My Account</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Your existing CSS here (same as before) */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            color: #1e293b;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        /* Header */
        .header {
            margin-bottom: 2rem;
        }
        
        .header h1 {
            font-size: 1.875rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 0.5rem;
        }
        
        .header p {
            color: #64748b;
        }
        
        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border-radius: 1rem;
            padding: 1.25rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .stat-card .label {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 0.5rem;
        }
        
        .stat-card .value {
            font-size: 2rem;
            font-weight: 700;
            color: #0f172a;
        }
        
        /* Actions Bar */
        .actions-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
            gap: 1rem;
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
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        
        .btn-danger:hover {
            background: #dc2626;
        }
        
        .btn-outline {
            background: white;
            border: 1px solid #e2e8f0;
            color: #64748b;
        }
        
        .btn-outline:hover {
            background: #f8fafc;
        }
        
        /* Sessions Grid */
        .sessions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .session-card {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
        }
        
        .session-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .session-card.current {
            border: 2px solid #10b981;
            background: #f0fdf4;
        }
        
        .device-icon {
            width: 48px;
            height: 48px;
            background: #eef2ff;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
        }
        
        .device-icon i {
            font-size: 1.5rem;
            color: #3b82f6;
        }
        
        .device-info {
            flex: 1;
        }
        
        .device-name {
            font-weight: 600;
            font-size: 1.125rem;
            margin-bottom: 0.25rem;
        }
        
        .device-details {
            font-size: 0.875rem;
            color: #64748b;
            margin-bottom: 1rem;
        }
        
        .device-details i {
            width: 20px;
            margin-right: 0.5rem;
        }
        
        .session-meta {
            font-size: 0.75rem;
            color: #94a3b8;
            margin-top: 1rem;
            padding-top: 1rem;
            border-top: 1px solid #e2e8f0;
        }
        
        .badge {
            display: inline-block;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-current {
            background: #10b981;
            color: white;
        }
        
        .badge-active {
            background: #3b82f6;
            color: white;
        }
        
        .badge-inactive {
            background: #e2e8f0;
            color: #64748b;
        }
        
        .revoke-btn {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: none;
            border: none;
            color: #94a3b8;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 0.5rem;
            transition: all 0.2s;
        }
        
        .revoke-btn:hover {
            color: #ef4444;
            background: #fef2f2;
        }
        
        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
        }
        
        .modal-content {
            background: white;
            border-radius: 1rem;
            padding: 1.5rem;
            max-width: 400px;
            width: 90%;
        }
        
        .modal-content h3 {
            margin-bottom: 1rem;
        }
        
        .modal-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 1.5rem;
        }
        
        /* Loading State */
        .loading {
            text-align: center;
            padding: 3rem;
            color: #64748b;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem;
            color: #64748b;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .spinner {
            width: 40px;
            height: 40px;
            border: 3px solid #e2e8f0;
            border-top-color: #3b82f6;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        /* User info bar */
        .user-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .logout-btn {
            color: #64748b;
            text-decoration: none;
            font-size: 0.875rem;
        }
        
        .logout-btn:hover {
            color: #ef4444;
        }
        
        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }
            .sessions-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- User Info Bar -->
        <div class="user-bar">
            <div class="user-info">
                <i class="fas fa-user-circle" style="font-size: 2rem; color: #3b82f6;"></i>
                <div>
                    <strong><?php echo htmlspecialchars($current_user['username'] ?? $current_user['email'] ?? 'User'); ?></strong>
                    <span style="font-size: 0.75rem; color: #64748b; display: block;"><?php echo htmlspecialchars($current_user['email'] ?? ''); ?></span>
                </div>
            </div>
            <a href="/logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Sign Out</a>
        </div>
        
        <div class="header">
            <h1><i class="fas fa-laptop-code"></i> Manage Devices</h1>
            <p>View and manage devices where you're signed in</p>
        </div>
        
        <div class="stats-grid" id="statsGrid">
            <div class="stat-card">
                <div class="label">Active Sessions</div>
                <div class="value" id="totalSessions">-</div>
            </div>
            <div class="stat-card">
                <div class="label">Current Device</div>
                <div class="value" id="currentDeviceIndicator">-</div>
            </div>
        </div>
        
        <div class="actions-bar">
            <div></div>
            <button class="btn btn-outline" id="revokeAllBtn">
                <i class="fas fa-sign-out-alt"></i> Sign Out All Other Devices
            </button>
        </div>
        
        <div id="sessionsContainer">
            <div class="loading">
                <div class="spinner"></div>
                <p>Loading your devices...</p>
            </div>
        </div>
    </div>
    
    <!-- Confirmation Modal -->
    <div class="modal" id="confirmModal">
        <div class="modal-content">
            <h3 id="modalTitle">Confirm Action</h3>
            <p id="modalMessage">Are you sure you want to perform this action?</p>
            <div class="modal-buttons">
                <button class="btn btn-outline" id="modalCancel">Cancel</button>
                <button class="btn btn-danger" id="modalConfirm">Confirm</button>
            </div>
        </div>
    </div>
    
    <!-- Toast Notification -->
    <div id="toast" style="position: fixed; bottom: 20px; right: 20px; background: #333; color: white; padding: 12px 20px; border-radius: 8px; display: none; z-index: 1000;"></div>
    
    <script>
        let currentAction = null;
        let currentSessionId = null;
        
        // Get access token from PHP session
        const accessToken = '<?php echo $_SESSION['access_token'] ?? ''; ?>';
        
        function getAccessToken() {
            return accessToken;
        }
        
        // Load sessions on page load
        document.addEventListener('DOMContentLoaded', loadSessions);
        
        // Event listeners
        const revokeAllBtn = document.getElementById('revokeAllBtn');
        if (revokeAllBtn) {
            revokeAllBtn.addEventListener('click', () => showConfirmModal('revokeAll', null, 'Sign out all other devices?', 'This will sign you out on all other devices. You will remain signed in on this device.'));
        }
        
        document.getElementById('modalCancel').addEventListener('click', closeModal);
        document.getElementById('modalConfirm').addEventListener('click', executeAction);
        
        async function loadSessions() {
            try {
                const token = getAccessToken();
                if (!token) {
                    throw new Error('Not authenticated');
                }
                
                const response = await fetch('../api/get_sessions.php', {
                    headers: {
                        'Authorization': 'Bearer ' + token,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (response.status === 401) {
                    // Redirect to login
                    window.location.href = '../auth/login.php?error=session_expired';
                    return;
                }
                
                if (!response.ok) throw new Error('Failed to load sessions');
                
                const data = await response.json();
                renderSessions(data.sessions);
                updateStats(data.sessions);
                
            } catch (error) {
                console.error('Error:', error);
                document.getElementById('sessionsContainer').innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-exclamation-circle" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>Failed to load devices. Please refresh the page.</p>
                    </div>
                `;
            }
        }
        
        function renderSessions(sessions) {
            const container = document.getElementById('sessionsContainer');
            
            if (!sessions || sessions.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-laptop" style="font-size: 3rem; margin-bottom: 1rem;"></i>
                        <p>No active sessions found</p>
                    </div>
                `;
                return;
            }
            
            container.innerHTML = `
                <div class="sessions-grid">
                    ${sessions.map(session => `
                        <div class="session-card ${session.is_current ? 'current' : ''}">
                            ${!session.is_current ? `
                                <button class="revoke-btn" onclick="showConfirmModal('revoke', '${escapeHtml(session.session_id)}', 'Revoke ${escapeHtml(session.device_name)}?', 'This device will be signed out immediately.')">
                                    <i class="fas fa-times-circle"></i>
                                </button>
                            ` : ''}
                            <div class="device-icon">
                                <i class="fas ${getDeviceIcon(session.device_type)}"></i>
                            </div>
                            <div class="device-info">
                                <div class="device-name">
                                    ${escapeHtml(session.device_name)}
                                    ${session.is_current ? ' <span class="badge badge-current">Current</span>' : ''}
                                </div>
                                <div class="device-details">
                                    <div><i class="fas fa-globe"></i> ${escapeHtml(session.location)}</div>
                                    <div><i class="fas fa-clock"></i> Last active: ${escapeHtml(session.last_activity_relative)}</div>
                                    <div><i class="fas fa-calendar"></i> Signed in: ${new Date(session.created_at).toLocaleDateString()}</div>
                                </div>
                                <div class="session-meta">
                                    <span class="badge badge-${session.status === 'active_now' ? 'active' : 'inactive'}">
                                        ${session.status === 'active_now' ? '● Active now' : 'Inactive'}
                                    </span>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            `;
        }
        
        function updateStats(sessions) {
            const total = sessions.length;
            const current = sessions.find(s => s.is_current);
            
            document.getElementById('totalSessions').textContent = total;
            document.getElementById('currentDeviceIndicator').innerHTML = current ? 
                `<i class="fas fa-check-circle" style="color: #10b981;"></i> ${escapeHtml(current.device_name)}` : '-';
        }
        
        function showConfirmModal(action, sessionId, title, message) {
            currentAction = action;
            currentSessionId = sessionId;
            document.getElementById('modalTitle').textContent = title;
            document.getElementById('modalMessage').textContent = message;
            document.getElementById('confirmModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('confirmModal').style.display = 'none';
            currentAction = null;
            currentSessionId = null;
        }
        
        async function executeAction() {
            closeModal();
            
            try {
                let response;
                let message;
                const token = getAccessToken();
                
                if (currentAction === 'revoke') {
                    response = await fetch('../api/revoke_session.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer ' + token
                        },
                        body: JSON.stringify({ session_id: currentSessionId })
                    });
                    message = 'Device signed out successfully';
                } else if (currentAction === 'revokeAll') {
                    response = await fetch('../api/revoke_all_sessions.php', {
                        method: 'POST',
                        headers: {
                            'Authorization': 'Bearer ' + token
                        }
                    });
                    message = 'All other devices signed out successfully';
                }
                
                if (response && response.ok) {
                    showToast(message, 'success');
                    loadSessions(); // Reload the list
                } else {
                    const errorData = await response.json();
                    throw new Error(errorData.error || 'Action failed');
                }
                
            } catch (error) {
                console.error('Error:', error);
                showToast(error.message || 'Failed to complete action. Please try again.', 'error');
            }
        }
        
        function showToast(message, type) {
            const toast = document.getElementById('toast');
            toast.style.backgroundColor = type === 'success' ? '#10b981' : '#ef4444';
            toast.textContent = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 3000);
        }
        
        function getDeviceIcon(deviceType) {
            switch(deviceType) {
                case 'mobile': return 'fa-mobile-alt';
                case 'tablet': return 'fa-tablet-alt';
                default: return 'fa-desktop';
            }
        }
        
        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
    </script>
</body>
</html>