<?php
$conn = new mysqli("localhost", "root", "", "euodia_scents");
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


// Check if the connection is HTTPS (Cloudflare sends this header)
$protocol = (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') ? 'https://' : 'http://';

// Define the Base URL for the project
$base_url = $protocol . $_SERVER['HTTP_HOST'] . '/'; 
define('SITE_URL', $base_url);
?>
