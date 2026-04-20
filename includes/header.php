<?php
// if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Euodia Scents</title>
<link rel="stylesheet" href="/style.css"></head>
<body>
<header>
  <div class="logo-wrapper">
    <img src="/images/logo.png" alt="Euodia Scents Logo" class="logo-img">
    <a href="/index.php" class="logo-text"></a>
  </div>

  <nav>
    <a href="/uploads/index.php">Home</a>
    <a href="/uploads/search.php">Shop</a>
    <?php if(!empty($_SESSION['user'])): ?>
      <a href="/uploads/cart.php">Cart</a>
      <span class="hi">Hi, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
      <a href="/auth/logout.php">Logout</a>
    <?php else: ?>
      <a href="/auth/login.php">Login</a>
      <a href="/auth/register.php">Register</a>
    <?php endif; ?>
  </nav>
</header>

<main>
