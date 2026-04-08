<?php
include "../includes/db.php";

$error = '';
$success = '';

if($_POST){
  $n=$_POST['name']; $e=$_POST['email']; $p=md5($_POST['password']);
  $conn->query("INSERT INTO users(name,email,password) VALUES('$n','$e','$p')");
  header("Location: login.php");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register — Euodia Peace Scents</title>
  <link rel="stylesheet" href="../uploads/style.css">

  <style>
    * { box-sizing: border-box; margin: 0; padding: 0; }
    body {
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(135deg, #0d0d0d, #1a1a1a);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      padding: 20px;
    }
    .register-container {
      background: #111;
      border: 1px solid #333;
      border-radius: 12px;
      padding: 40px;
      width: 100%;
      max-width: 420px;
      box-shadow: 0 10px 40px rgba(0,0,0,0.6);
    }
    .logo {
      text-align: center;
      margin-bottom: 30px;
    }
    .logo h1 {
      color: #d4af37;
      font-size: 1.8em;
      letter-spacing: 2px;
    }
    .logo p {
      color: #888;
      font-size: 0.9em;
    }
    .form-group {
      margin-bottom: 18px;
    }
    .form-group label {
      display: block;
      color: #ccc;
      margin-bottom: 8px;
      font-size: 0.9em;
    }
    .form-group input {
      width: 100%;
      padding: 12px 15px;
      border: 1px solid #333;
      border-radius: 6px;
      background: #1a1a1a;
      color: #eee;
      font-size: 1em;
      transition: border 0.3s;
    }
    .form-group input:focus {
      outline: none;
      border-color: #d4af37;
    }
    .error {
      background: #3a1a1a;
      border: 1px solid #662222;
      color: #ff6b6b;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
      font-size: 0.9em;
    }
    .success {
      background: #1a3a1a;
      border: 1px solid #226622;
      color: #6bff6b;
      padding: 10px;
      border-radius: 6px;
      margin-bottom: 20px;
      text-align: center;
      font-size: 0.9em;
    }
    .btn {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, #d4af37, #b8962e);
      border: none;
      border-radius: 6px;
      color: #000;
      font-size: 1em;
      font-weight: bold;
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 20px rgba(212, 175, 55, 0.4);
    }
    .links {
      text-align: center;
      margin-top: 25px;
    }
    .links a {
      color: #d4af37;
      text-decoration: none;
      font-size: 0.9em;
    }
    .links a:hover {
      text-decoration: underline;
    }
    .divider {
      color: #555;
      margin: 0 10px;
    }
    .password-hint {
      color: #666;
      font-size: 0.8em;
      margin-top: 5px;
    }
  </style>
</head>
<body>
<div class="register-container">
  <div class="logo">
    <h1>EUODIA</h1>
    <p>Peace Scents</p>
  </div>
  <?php if ($error): ?>
    <div class="error"><?php echo $error; ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="success"><?php echo $success; ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Full Name</label>
      <input type="text" name="name" placeholder="Enter your full name" required>
    </div>
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="Enter your email" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Create a password" required>
      <p class="password-hint">At least 6 characters</p>
    </div>
    <div class="form-group">
      <label>Confirm Password</label>
      <input type="password" name="confirm_password" placeholder="Confirm your password" required>
    </div>
    <button type="submit" class="btn">Create Account</button>
  </form>
  <div class="links">
    <a href="login.php">Already have an account? Sign In</a>
    <span class="divider">|</span>
    <a href="../uploads/index.php">Back to Shop</a>
  </div>
</div>
</body>
</html>
