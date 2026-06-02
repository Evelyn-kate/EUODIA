<?php
/**
 * Get Google Authenticator secret for a user
 */
function get_user_ga_secret(int $user_id, PDO $pdo): ?string {
    $stmt = $pdo->prepare("SELECT ga_secret FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $result = $stmt->fetch();
    return $result['ga_secret'] ?? null;
}

/**
 * Store Google Authenticator secret for a user
 */
function set_user_ga_secret(int $user_id, string $secret, PDO $pdo): bool {
    $stmt = $pdo->prepare("UPDATE users SET ga_secret = ?, mfa_enabled = 1 WHERE id = ?");
    return $stmt->execute([$secret, $user_id]);
}
?>