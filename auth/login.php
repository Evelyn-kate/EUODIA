<?php
include "../includes/db.php";
include "../includes/jwt.php";

$error = '';

if ($_POST) {
  $email = $_POST['email'];
  $password = md5($_POST['password']);

  $q = $conn->query("SELECT * FROM users WHERE email='$email' AND password='$password'");
  
  if ($q->num_rows == 1) {
    $user = $q->fetch_assoc();
    $_SESSION['user'] = $user;
    
    // Generate JWT token
    $token = JWTHandler::createToken($user['id'], $user['email'], $user['name']);
    
    // Store token in session and cookie
    $_SESSION['jwt_token'] = $token;
    JWTHandler::setTokenCookie($token);
    
    header("Location: ../uploads/index.php");
  } else {
    $error = "Invalid login";
  }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — Euodia Peace Scents</title>
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
    }
    .login-container {
      background: #111;
      border: 1px solid #333;
      border-radius: 12px;
      padding: 40px;
      width: 100%;
      max-width: 400px;
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
      margin-bottom: 20px;
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
  </style>
</head>
<body>
<div class="login-container">
  <div class="logo">
    <h1>EUODIA</h1>
    <p>Peace Scents</p>
  </div>
  <?php if ($error): ?>
    <div class="error"><?php echo $error; ?></div>
  <?php endif; ?>
  <form method="POST">
    <div class="form-group">
      <label>Email Address</label>
      <input type="email" name="email" placeholder="Enter your email" required>
    </div>
    <div class="form-group">
      <label>Password</label>
      <input type="password" name="password" placeholder="Enter your password" required>
    </div>
    <button type="submit" class="btn">Sign In</button>
  </form>
  <div class="links">
    <a href="register.php">Create Account</a>
    <span class="divider">|</span>
    <a href="../uploads/index.php">Back to Shop</a>
  </div>
</div>
</body>
</html>