<?php
$conn = new mysqli("localhost", "root", "", "euodia_scents");
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if the connection is HTTPS (Cloudflare sends this header)
$protocol = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';

// Define the Base URL for the project, with CLI fallback.
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$base_url = $protocol . $host . '/';
define('SITE_URL', $base_url);

$db_name = "euodia_scents";
$username = "root";
$password = "";

try {
    $pdo = new PDO("mysql:host=localhost;dbname=$db_name;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Secret key for JWT - store this in environment variable in production
$jwt_secret = "your-very-long-secret-key-change-this-in-production";

?>
