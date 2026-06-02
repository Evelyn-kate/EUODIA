<?php
require_once __DIR__ . '/../vendor/autoload.php';
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTHandler {
    private static $secret = "euodia_scents_secret_key_change_in_production";
    private static $algorithm = "HS256";
    private static $accessExpiry = 15 * 60; // 15 minutes
    private static $refreshExpiry = 7 * 24 * 60 * 60; // 7 days
    private static $issuer = 'euodia_scents';

    private static function getAllHeaders() {
        if (function_exists('getallheaders')) {
            return getallheaders();
        }

        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (strpos($name, 'HTTP_') === 0) {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
        return $headers;
    }

    public static function createAccessToken($user_id, $email, $name) {
        return self::createToken($user_id, $email, $name, 'access');
    }

    public static function createRefreshToken($user_id, $email, $name) {
        return self::createToken($user_id, $email, $name, 'refresh');
    }

    private static function createToken($user_id, $email, $name, $type) {
        $issuedAt = time();
        $expire = $issuedAt + ($type === 'refresh' ? self::$refreshExpiry : self::$accessExpiry);

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $user_id,
            'email' => $email,
            'name' => $name,
            'iss' => self::$issuer,
            'type' => $type,
            'jti' => bin2hex(random_bytes(16))
        ];

        return JWT::encode($payload, self::$secret, self::$algorithm);
    }

    public static function decodeToken($token) {
        try {
            return JWT::decode($token, new Key(self::$secret, self::$algorithm));
        } catch (\Exception $e) {
            error_log('JWT decode error: ' . $e->getMessage());
            return null;
        }
    }

    public static function verifyToken($token, $type = 'access') {
        if (!$token) {
            return null;
        }

        $payload = self::decodeToken($token);
        if (!$payload || !isset($payload->type) || $payload->type !== $type) {
            return null;
        }

        return $payload;
    }

    public static function getTokenFromHeader() {
        $headers = self::getAllHeaders();
        if (isset($headers['Authorization'])) {
            $parts = explode(' ', $headers['Authorization']);
            if (count($parts) === 2 && strtolower($parts[0]) === 'bearer') {
                return $parts[1];
            }
        }
        return null;
    }

    public static function getAccessToken() {
        $token = self::getTokenFromHeader();
        if ($token) {
            return $token;
        }

        if (isset($_COOKIE['jwt_access_token'])) {
            return $_COOKIE['jwt_access_token'];
        }

        if (isset($_POST['access_token'])) {
            return $_POST['access_token'];
        }

        return null;
    }

    public static function getRefreshToken() {
        if (isset($_COOKIE['jwt_refresh_token'])) {
            return $_COOKIE['jwt_refresh_token'];
        }

        if (isset($_POST['refresh_token'])) {
            return $_POST['refresh_token'];
        }

        return null;
    }

    public static function getToken($type = 'access') {
        return $type === 'refresh' ? self::getRefreshToken() : self::getAccessToken();
    }

    public static function setAccessTokenCookie($token, $expiry = null) {
        if ($expiry === null) {
            $expiry = self::$accessExpiry;
        }

        setcookie('jwt_access_token', $token, time() + $expiry, '/', '', false, false);
        $_COOKIE['jwt_access_token'] = $token;
    }

    public static function setRefreshTokenCookie($token, $expiry = null) {
        if ($expiry === null) {
            $expiry = self::$refreshExpiry;
        }

        setcookie('jwt_refresh_token', $token, time() + $expiry, '/', '', false, true);
        $_COOKIE['jwt_refresh_token'] = $token;
    }

    public static function clearAccessTokenCookie() {
        setcookie('jwt_access_token', '', time() - 3600, '/');
        unset($_COOKIE['jwt_access_token']);
    }

    public static function clearRefreshTokenCookie() {
        setcookie('jwt_refresh_token', '', time() - 3600, '/');
        unset($_COOKIE['jwt_refresh_token']);
    }

    public static function clearTokens() {
        self::clearAccessTokenCookie();
        self::clearRefreshTokenCookie();
    }

    public static function storeRefreshToken($token, $user_id, $conn, $ip = null, $user_agent = null) {
        $payload = self::verifyToken($token, 'refresh');
        if (!$payload) {
            return false;
        }

        $token_hash = hash('sha256', $token);
        $jti = $conn->real_escape_string($payload->jti ?? '');
        $ip_address = $conn->real_escape_string($ip ?? $_SERVER['REMOTE_ADDR'] ?? '');
        $user_agent = $conn->real_escape_string($user_agent ?? $_SERVER['HTTP_USER_AGENT'] ?? '');
        $expires_at = date('Y-m-d H:i:s', $payload->exp);

        $stmt = $conn->prepare("INSERT INTO refresh_tokens (user_id, token_hash, jti, ip_address, user_agent, expires_at) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('isssss', $user_id, $token_hash, $jti, $ip_address, $user_agent, $expires_at);
        return $stmt->execute();
    }

    public static function revokeRefreshToken($token, $conn) {
        $payload = self::verifyToken($token, 'refresh');
        if (!$payload) {
            return false;
        }

        $token_hash = hash('sha256', $token);
        $stmt = $conn->prepare("UPDATE refresh_tokens SET revoked_at=NOW() WHERE token_hash = ?");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $token_hash);
        $stmt->execute();
        return $stmt->affected_rows > 0;
    }

    public static function revokeRefreshTokenFromCookie($conn) {
        $refreshToken = self::getRefreshToken();
        if ($refreshToken) {
            self::revokeRefreshToken($refreshToken, $conn);
        }
        self::clearRefreshTokenCookie();
    }

    public static function isRefreshTokenStored($token, $conn) {
        $payload = self::verifyToken($token, 'refresh');
        if (!$payload) {
            return false;
        }

        $token_hash = hash('sha256', $token);
        $stmt = $conn->prepare("SELECT revoked_at, expires_at FROM refresh_tokens WHERE token_hash = ? LIMIT 1");
        if (!$stmt) {
            return false;
        }

        $stmt->bind_param('s', $token_hash);
        $stmt->execute();
        $result = $stmt->get_result();
        if (!$result || $result->num_rows === 0) {
            return false;
        }

        $row = $result->fetch_assoc();
        if (!empty($row['revoked_at'])) {
            return false;
        }

        if (strtotime($row['expires_at']) < time()) {
            return false;
        }

        return true;
    }

    public static function refreshToken($refreshToken, $conn) {
        $payload = self::verifyToken($refreshToken, 'refresh');
        if (!$payload || !self::isRefreshTokenStored($refreshToken, $conn)) {
            return null;
        }

        self::revokeRefreshToken($refreshToken, $conn);

        $newAccessToken = self::createAccessToken($payload->user_id, $payload->email, $payload->name);
        $newRefreshToken = self::createRefreshToken($payload->user_id, $payload->email, $payload->name);
        self::setAccessTokenCookie($newAccessToken);
        self::setRefreshTokenCookie($newRefreshToken);
        self::storeRefreshToken($newRefreshToken, $payload->user_id, $conn);

        return [
            'access_token' => $newAccessToken,
            'refresh_token' => $newRefreshToken,
            'expires_in' => self::$accessExpiry
        ];
    }

    public static function createTokenPair($user_id, $email, $name) {
        $accessToken = self::createAccessToken($user_id, $email, $name);
        $refreshToken = self::createRefreshToken($user_id, $email, $name);
        return [
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'access_expires_in' => self::$accessExpiry,
            'refresh_expires_in' => self::$refreshExpiry
        ];
    }

    public static function setTokenCookies($accessToken, $refreshToken) {
        self::setAccessTokenCookie($accessToken);
        self::setRefreshTokenCookie($refreshToken);
    }

    public static function clearAllTokenCookies() {
        self::clearAccessTokenCookie();
        self::clearRefreshTokenCookie();
    }

    public static function setTokenCookie($token, $expiry = null) {
        self::setAccessTokenCookie($token, $expiry);
    }

    public static function clearTokenCookie() {
        self::clearAccessTokenCookie();
    }

    public static function isTokenValid($token) {
        return self::verifyToken($token, 'access') !== null;
    }
}
?>
