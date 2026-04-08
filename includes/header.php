<?php
// if (session_status() === PHP_SESSION_NONE) session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Euodia Scents</title>
<link rel="stylesheet" href="/EUODIA/uploads/style.css"></head>
<body>
<header>
  <div class="logo-wrapper">
    <img src="/EUODIA/uploads/images/logo.png" alt="Euodia Scents Logo" class="logo-img">
    <a href="/EUODIA/uploads/index.php" class="logo-text"></a>
  </div>

  <nav>
    <a href="/EUODIA/uploads/index.php">Home</a>
    <a href="/EUODIA/uploads/search.php">Shop</a>
    <?php if(!empty($_SESSION['user'])): ?>
      <a href="/EUODIA/uploads/cart.php">Cart</a>
      <span class="hi">Hi, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
      <a href="/EUODIA/auth/logout.php">Logout</a>
    <?php else: ?>
      <a href="/EUODIA/auth/login.php">Login</a>
      <a href="/EUODIA/auth/register.php">Register</a>
    <?php endif; ?>
  </nav>
</header>

<main>
