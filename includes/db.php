<?php
$conn = new mysqli("localhost", "root", "", "euodia_scents");
if ($conn->connect_error) die("DB Error: " . $conn->connect_error);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

?>
