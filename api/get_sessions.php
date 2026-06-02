<?php
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/jwt_utils.php';
require_once '../includes/session_manager.php';

// Authenticate user
$jwt_manager = new JWTManager($pdo, $jwt_secret);
$auth = new AuthMiddleware($jwt_manager);
$user_id = $auth->authenticate();

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get sessions
$session_manager = new SessionManager($pdo);
$sessions = $session_manager->getUserSessions($user_id);

// Format sessions for display
$formatted_sessions = array_map(function($session) {
    return [
        'id' => $session['id'],
        'session_id' => $session['session_id'],
        'device_name' => $session['device_name'],
        'device_type' => $session['device_type'],
        'browser' => $session['browser'],
        'os' => $session['os'],
        'location' => $session['location'] ?: 'Unknown',
        'last_activity' => $session['last_activity'],
        'last_activity_relative' => getRelativeTime($session['last_activity']),
        'created_at' => $session['created_at'],
        'is_current' => (bool)$session['is_current'],
        'status' => $session['status']
    ];
}, $sessions);

echo json_encode([
    'success' => true,
    'sessions' => $formatted_sessions,
    'total' => count($formatted_sessions)
]);

/**
 * Get relative time string
 */
function getRelativeTime(string $datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return date('M j, g:i A', $timestamp);
}
?>