<?php
ob_start();
include "../includes/db.php";
include "../includes/payunit.php";

if(!isset($_SESSION['user'])) die("<p>Please login first.</p>");
$cart=json_decode($_COOKIE['cart']??'[]',true);
if(!$cart){ echo "<p>Cart empty.</p>"; include "../includes/footer.php"; exit; }

$total=0; foreach($cart as $i) $total+=$i['price'];
$txid="TX".time();

$redirect_url = "";
$debug_msg = "";

if(isset($_POST['process_payment'])){
  $debug_msg = "POST received | ";
  $pay = payunit_charge($total,$_SESSION['user']['email'],$_SESSION['user']['name'],$txid);
  $debug_msg .= "Pay response: " . json_encode($pay) . " | ";
  
  if($pay && isset($pay['payment_url']) && $pay['payment_url']){
    // Clear cart before redirect
    setcookie("cart", "", time()-3600);
    $redirect_url = $pay['payment_url'];
    $debug_msg .= "Redirect URL set: " . $redirect_url;
  } else {
    $debug_msg .= "No payment_url found";
  }
}

include "../includes/header.php";?>

<link rel="stylesheet" href="style.css">

<style>
  .checkout-container {
    max-width: 900px;
    margin: 40px auto;
    padding: 20px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
  }

  .checkout-title {
    grid-column: 1 / -1;
    color: #d4af37;
    text-align: center;
    font-size: 2em;
    margin-bottom: 20px;
    text-shadow: 1px 1px 3px #000;
  }

  .order-summary, .payment-section {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 25px;
  }

  .section-title {
    color: #d4af37;
    font-size: 1.4em;
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #333;
  }

  .order-item {
    display: flex;
    justify-content: space-between;
    padding: 12px 0;
    border-bottom: 1px solid #333;
    color: #eee;
  }

  .order-item:last-child {
    border-bottom: none;
  }

  .item-info {
    color: #d4af37;
    font-weight: bold;
  }

  .item-cost {
    color: #d4af37;
    font-weight: bold;
  }

  .order-total {
    display: flex;
    justify-content: space-between;
    padding: 20px 0;
    margin-top: 15px;
    border-top: 2px solid #d4af37;
    font-size: 1.3em;
    font-weight: bold;
  }

  .total-label {
    color: #d4af37;
  }

  .total-amount {
    color: #d4af37;
  }

  .user-info {
    display: flex;
    flex-direction: column;
    gap: 12px;
    margin-bottom: 25px;
  }

  .info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px;
    background: #111;
    border-radius: 4px;
    border: 1px solid #333;
  }

  .info-label {
    color: #999;
    font-size: 0.9em;
  }

  .info-value {
    color: #d4af37;
    font-weight: bold;
  }

  .payment-method {
    background: #111;
    border: 1px solid #333;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 20px;
  }

  .payment-method h4 {
    color: #d4af37;
    margin: 0 0 10px 0;
    font-size: 1.1em;
  }

  .payment-method p {
    color: #999;
    margin: 5px 0;
    font-size: 0.9em;
  }

  .payment-btn {
    width: 100%;
    padding: 15px;
    background: #d4af37;
    color: #000;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    font-size: 1.1em;
    cursor: pointer;
    transition: all 0.3s ease;
  }

  .payment-btn:hover {
    opacity: 0.8;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
  }

  .back-link {
    grid-column: 1 / -1;
    text-align: center;
    margin-top: 20px;
  }

  .back-link a {
    color: #d4af37;
    text-decoration: none;
    font-weight: bold;
  }

  .back-link a:hover {
    text-decoration: underline;
  }

  @media (max-width: 768px) {
    .checkout-container {
      grid-template-columns: 1fr;
      gap: 20px;
    }

    .info-row {
      flex-direction: column;
      align-items: flex-start;
    }
  }
</style>

<div class="checkout-container">
  <h1 class="checkout-title">Checkout</h1>

  <!-- Order Summary -->
  <div class="order-summary">
    <h2 class="section-title">Order Summary</h2>
    
    <?php foreach($cart as $index => $item): ?>
      <div class="order-item">
        <span class="item-info"><?php echo $item['name']; ?></span>
        <span class="item-cost"><?php echo $item['price']; ?> XAF</span>
      </div>
    <?php endforeach; ?>

    <div class="order-total">
      <span class="total-label">Total:</span>
      <span class="total-amount"><?php echo $total; ?> XAF</span>
    </div>
  </div>

  <!-- Payment Section -->
  <div class="payment-section">
    <h2 class="section-title">Payment</h2>

    <!-- User Information -->
    <div class="user-info">
      <div class="info-row">
        <span class="info-label">Name</span>
        <span class="info-value"><?php echo htmlspecialchars($_SESSION['user']['name']); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Email</span>
        <span class="info-value"><?php echo htmlspecialchars($_SESSION['user']['email']); ?></span>
      </div>
      <div class="info-row">
        <span class="info-label">Transaction ID</span>
        <span class="info-value"><?php echo $txid; ?></span>
      </div>
    </div>

    <!-- Payment Method -->
    <div class="payment-method">
      <h4>💳 Pay with Mobile Money</h4>
      <p>Safe & secure payment via MTN MoMo or Orange Money</p>
      <p style="font-size: 0.85em; color: #666;">Powered by PayUnit</p>
    </div>

    <!-- Payment Form -->
    <form method="POST" action="checkout.php">
      <input type="hidden" name="process_payment" value="1">
      <button type="submit" class="payment-btn">Proceed to Payment</button>
    </form>
  </div>

  <div class="back-link">
    <a href="cart.php">← Back to Cart</a>
  </div>
</div>

<?php 
if($redirect_url) {
  echo "<script>
    console.log('Page loaded, redirecting to: " . addslashes($redirect_url) . "');
    localStorage.removeItem('cart');
    document.cookie = 'cart=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;';
    setTimeout(function() {
      window.location.href = '" . addslashes($redirect_url) . "';
    }, 1000);
  </script>";
}
?>

<?php include "../includes/footer.php"; ?>
