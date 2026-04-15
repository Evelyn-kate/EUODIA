<?php
// Simple JWT implementation without external dependencies
class JWTHandler {
    private static $secret = "euodia_scents_secret_key_change_in_production";
    private static $algorithm = "HS256";
    private static $expiry = 7 * 24 * 60 * 60; // 7 days

    /**
     * Base64 URL encode
     */
    private static function base64UrlEncode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL decode
     */
    private static function base64UrlDecode($data) {
        return base64_decode(strtr($data, '-_', '+/') . str_repeat('=', 3 - (3 + strlen($data)) % 4));
    }

    /**
     * Create a JWT token for a user
     */
    public static function createToken($user_id, $email, $name) {
        $issuedAt = time();
        $expire = $issuedAt + self::$expiry;

        $header = [
            'alg' => self::$algorithm,
            'typ' => 'JWT'
        ];

        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'user_id' => $user_id,
            'email' => $email,
            'name' => $name,
            'iss' => 'euodia_scents'
        ];

        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac(
            'sha256',
            $headerEncoded . '.' . $payloadEncoded,
            self::$secret,
            true
        );
        $signatureEncoded = self::base64UrlEncode($signature);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signatureEncoded;
    }

    /**
     * Verify and decode a JWT token
     */
    public static function verifyToken($token) {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }

            list($headerEncoded, $payloadEncoded, $signatureEncoded) = $parts;

            $signature = hash_hmac(
                'sha256',
                $headerEncoded . '.' . $payloadEncoded,
                self::$secret,
                true
            );
            $expectedSignature = self::base64UrlEncode($signature);

            if ($signatureEncoded !== $expectedSignature) {
                return null;
            }

            $payload = json_decode(self::base64UrlDecode($payloadEncoded));

            // Check expiration
            if (isset($payload->exp) && $payload->exp < time()) {
                return null;
            }

            return $payload;
        } catch (\Exception $e) {
            error_log("JWT Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get token from Authorization header
     */
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            $parts = explode(' ', $headers['Authorization']);
            if (count($parts) == 2 && strtolower($parts[0]) == 'bearer') {
                return $parts[1];
            }
        }
        return null;
    }

    /**
     * Get token from request (header, cookie, or POST)
     */
    public static function getToken() {
        // First try Authorization header
        $token = self::getTokenFromHeader();
        if ($token) return $token;

        // Then try cookie
        if (isset($_COOKIE['jwt_token'])) {
            return $_COOKIE['jwt_token'];
        }

        // Finally try POST
        if (isset($_POST['token'])) {
            return $_POST['token'];
        }

        return null;
    }

    /**
     * Set JWT token in secure cookie
     */
    public static function setTokenCookie($token, $expiry = null) {
        if ($expiry === null) {
            $expiry = self::$expiry;
        }
        
        setcookie(
            'jwt_token',
            $token,
            time() + $expiry,
            '/',
            '',
            false, // httponly - set to false for development
            false  // secure - set to false if not using HTTPS
        );
    }

    /**
     * Clear JWT token cookie
     */
    public static function clearTokenCookie() {
        setcookie('jwt_token', '', time() - 3600, '/');
        unset($_COOKIE['jwt_token']);
    }

    /**
     * Check if token is valid
     */
    public static function isTokenValid($token) {
        return self::verifyToken($token) !== null;
    }
}
?>
