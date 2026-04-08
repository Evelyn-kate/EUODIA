<?php include "../includes/header.php"; ?>
<link rel="stylesheet" href="style.css">

<style>
  .success-container {
    max-width: 600px;
    margin: 60px auto;
    padding: 40px;
    text-align: center;
    background: #1a1a1a;
    border: 2px solid #d4af37;
    border-radius: 8px;
  }

  .success-icon {
    font-size: 4em;
    margin-bottom: 20px;
  }

  .success-title {
    color: #d4af37;
    font-size: 2.2em;
    margin-bottom: 15px;
    text-shadow: 1px 1px 3px #000;
  }

  .success-message {
    color: #eee;
    font-size: 1.1em;
    margin-bottom: 30px;
    line-height: 1.6;
  }

  .order-details {
    background: #111;
    border: 1px solid #333;
    border-radius: 4px;
    padding: 20px;
    margin-bottom: 30px;
    text-align: left;
  }

  .detail-row {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
    border-bottom: 1px solid #333;
  }

  .detail-row:last-child {
    border-bottom: none;
  }

  .detail-label {
    color: #999;
  }

  .detail-value {
    color: #d4af37;
    font-weight: bold;
  }

  .action-buttons {
    display: flex;
    gap: 15px;
    justify-content: center;
    flex-wrap: wrap;
  }

  .btn-primary, .btn-secondary {
    padding: 12px 30px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    text-decoration: none;
    cursor: pointer;
    font-size: 1em;
    transition: all 0.3s ease;
  }

  .btn-primary {
    background: #d4af37;
    color: #000;
  }

  .btn-primary:hover {
    opacity: 0.8;
  }

  .btn-secondary {
    background: #333;
    color: #d4af37;
    border: 1px solid #d4af37;
  }

  .btn-secondary:hover {
    background: #d4af37;
    color: #000;
  }
</style>

<?php
include "../includes/db.php";

$txid = $_GET['txid'] ?? '';
$amount = $_GET['amount'] ?? 0;
$email = $_GET['email'] ?? '';
$name = $_GET['name'] ?? '';

if(isset($_SESSION['user'])) {
  $user_id = $_SESSION['user']['id'];
  $conn->query("INSERT INTO orders(user_id, total) VALUES($user_id, $amount)");
}

// Clear cart
setcookie("cart", "", time()-3600);
?>

<script>
  // Clear localStorage cart on success
  localStorage.removeItem('cart');
  document.cookie = "cart=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
</script>

<div class="success-container">
  <div class="success-icon">✓</div>
  
  <h1 class="success-title">Payment Successful!</h1>
  
  <p class="success-message">
    Thank you for your order! Your payment has been processed successfully.
    <br>We'll prepare your items for shipment shortly.
  </p>

  <div class="order-details">
    <div class="detail-row">
      <span class="detail-label">Transaction ID:</span>
      <span class="detail-value"><?php echo htmlspecialchars($txid); ?></span>
    </div>
    <div class="detail-row">
      <span class="detail-label">Amount Paid:</span>
      <span class="detail-value"><?php echo $amount; ?> XAF</span>
    </div>
    <div class="detail-row">
      <span class="detail-label">Email:</span>
      <span class="detail-value"><?php echo htmlspecialchars($email); ?></span>
    </div>
    <div class="detail-row">
      <span class="detail-label">Date:</span>
      <span class="detail-value"><?php echo date('M d, Y H:i'); ?></span>
    </div>
  </div>

  <div class="action-buttons">
    <a href="index.php" class="btn-primary">Continue Shopping</a>
    <a href="cart.php" class="btn-secondary">View Cart</a>
  </div>
</div>

<?php include "../includes/footer.php"; ?>
