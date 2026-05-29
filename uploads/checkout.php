<?php
ob_start();
include "../includes/db.php";
include "../includes/payunit.php";
include "../includes/paypal.php";

if(!isset($_SESSION['user'])) die("<p>Please login first.</p>");
$cart=json_decode($_COOKIE['cart']??'[]',true);
if(!$cart){ echo "<p>Cart empty.</p>"; include "../includes/footer.php"; exit; }

$total=0; 
foreach($cart as $i) $total+=$i['price'];

// Get shipping countries
$shipping_countries = $conn->query("SELECT id, country_name, country_code, currency_symbol, currency_code, shipping_fee FROM shipping_countries WHERE is_active=1 ORDER BY country_name");
$countries = [];
while($row = $shipping_countries->fetch_assoc()) {
    $countries[$row['id']] = $row;
}

$shipping_fee = 0;
$shipping_country_id = null;
$currency_symbol = 'XAF';
$currency_code = 'XAF';

// Handle shipping country selection
if(isset($_POST['shipping_country_id'])) {
    $shipping_country_id = intval($_POST['shipping_country_id']);
    if(isset($countries[$shipping_country_id])) {
        $shipping_fee = $countries[$shipping_country_id]['shipping_fee'];
        $currency_symbol = $countries[$shipping_country_id]['currency_symbol'];
        $currency_code = $countries[$shipping_country_id]['currency_code'];
    }
}

$grand_total = $total + $shipping_fee;
$txid = "TX".time();

$redirect_url = "";
$debug_msg = "";
$payment_method = $_POST['payment_method'] ?? 'payunit';

if(isset($_POST['process_payment'])){
  if (!$shipping_country_id) {
      $debug_msg = "Please select a shipping country";
  } else {
      $debug_msg = "POST received | Payment Method: " . $payment_method . " | ";
      
      // Determine which payment method to use
      $use_paypal = ($payment_method == 'paypal');
      
      if ($use_paypal) {
        // PayPal payment
        $pay = PayPalHandler::createPayment($grand_total, $_SESSION['user']['email'], $_SESSION['user']['name'], $txid);
        $debug_msg .= "PayPal response: " . json_encode($pay) . " | ";
        
        if($pay && isset($pay['redirect_url']) && $pay['redirect_url']){
          // Create order first
          $conn->query("INSERT INTO orders(user_id, total, shipping_fee, shipping_country_id, grand_total, status) VALUES(".$_SESSION['user_id'].", $total, $shipping_fee, $shipping_country_id, $grand_total, 'pending')");
          $order_id = $conn->insert_id;
          
          // Create shipment record
          $tracking_number = "EUODIA-" . time() . "-" . $order_id;
          $country_name = $conn->real_escape_string($countries[$shipping_country_id]['country_name']);
          $estimated_delivery = date('Y-m-d H:i:s', strtotime('+7 days'));
          $conn->query("INSERT INTO shipments(order_id, tracking_number, country_id, country_name, shipping_fee, status, estimated_delivery) VALUES($order_id, '$tracking_number', $shipping_country_id, '$country_name', $shipping_fee, 'pending', '$estimated_delivery')");
          
          setcookie("cart", "", time()-3600);
          $redirect_url = $pay['redirect_url'];
          $debug_msg .= "Redirect URL set: " . $redirect_url;
        }
      } else {
        // PayUnit payment (default)
        $pay = payunit_charge($grand_total,$_SESSION['user']['email'],$_SESSION['user']['name'],$txid);
        $debug_msg .= "PayUnit response: " . json_encode($pay) . " | ";
        
        if($pay && isset($pay['payment_url']) && $pay['payment_url']){
          // Create order first
          $conn->query("INSERT INTO orders(user_id, total, shipping_fee, shipping_country_id, grand_total, status) VALUES(".$_SESSION['user_id'].", $total, $shipping_fee, $shipping_country_id, $grand_total, 'pending')");
          $order_id = $conn->insert_id;
          
          // Create shipment record
          $tracking_number = "EUODIA-" . time() . "-" . $order_id;
          $country_name = $conn->real_escape_string($countries[$shipping_country_id]['country_name']);
          $estimated_delivery = date('Y-m-d H:i:s', strtotime('+7 days'));
          $conn->query("INSERT INTO shipments(order_id, tracking_number, country_id, country_name, shipping_fee, status, estimated_delivery) VALUES($order_id, '$tracking_number', $shipping_country_id, '$country_name', $shipping_fee, 'pending', '$estimated_delivery')");
          
          setcookie("cart", "", time()-3600);
          $redirect_url = $pay['payment_url'];
          $debug_msg .= "Redirect URL set: " . $redirect_url;
        } else {
          $debug_msg .= "No payment_url found";
        }
      }
  }
}

if($redirect_url) {
    header("Location: " . $redirect_url);
    exit();
}

include "../includes/header.php";?>

<link rel="stylesheet" href="style.css">

<style>
  .checkout-container {
    max-width: 1000px;
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

  .order-summary, .payment-section, .shipping-section {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 25px;
  }

  .shipping-section {
    grid-column: 1 / -1;
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

  .cost-breakdown {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid #333;
  }

  .cost-row {
    display: flex;
    justify-content: space-between;
    color: #eee;
  }

  .cost-row.subtotal {
    color: #d4af37;
  }

  .cost-row.shipping {
    color: #888;
  }

  .cost-row.total {
    font-size: 1.2em;
    font-weight: bold;
    color: #d4af37;
    border-top: 1px solid #333;
    padding-top: 10px;
    margin-top: 10px;
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

  .form-group {
    margin-bottom: 20px;
  }

  .form-group label {
    display: block;
    color: #d4af37;
    margin-bottom: 8px;
    font-weight: bold;
  }

  .form-group select {
    width: 100%;
    padding: 12px;
    background: #111;
    border: 1px solid #333;
    border-radius: 4px;
    color: #eee;
    font-size: 1em;
  }

  .form-group select:focus {
    outline: none;
    border-color: #d4af37;
  }

  .country-option {
    color: #eee;
  }

  .payment-method {
    background: #111;
    border: 1px solid #333;
    border-radius: 4px;
    padding: 15px;
    margin-bottom: 15px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    cursor: pointer;
  }

  .payment-method input[type="radio"] {
    width: 18px;
    height: 18px;
    cursor: pointer;
    accent-color: #d4af37;
    margin-top: 3px;
    flex-shrink: 0;
  }

  .payment-method label {
    flex: 1;
    cursor: pointer;
    margin: 0;
  }

  .payment-method h4 {
    color: #d4af37;
    margin: 0 0 5px 0;
    font-size: 1em;
  }

  .payment-method p {
    color: #999;
    margin: 0;
    font-size: 0.85em;
  }

  .payment-methods-container {
    margin-bottom: 20px;
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

  .payment-btn:hover:not(:disabled) {
    opacity: 0.8;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(212, 175, 55, 0.3);
  }

  .payment-btn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
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

  .warning-msg {
    background: #3a1a1a;
    border: 1px solid #662222;
    color: #ff6b6b;
    padding: 12px;
    border-radius: 4px;
    margin-bottom: 15px;
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

  <!-- Shipping Information -->
  <div class="shipping-section">
    <h2 class="section-title">🌍 Shipping Information</h2>
    
    <form method="POST" action="checkout.php" id="shippingForm">
      <div class="form-group">
        <label for="shipping_country">Select Shipping Country:</label>
        <select name="shipping_country_id" id="shipping_country" onchange="updateShipping()" required>
          <option value="">-- Choose a country --</option>
          <?php foreach($countries as $id => $country): ?>
            <option value="<?php echo $id; ?>" <?php echo ($shipping_country_id == $id ? 'selected' : ''); ?>>
              <?php echo htmlspecialchars($country['country_name']); ?> - <?php echo htmlspecialchars($country['currency_symbol']); ?> <?php echo number_format($country['shipping_fee']); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>

    <?php if(!$shipping_country_id && isset($_POST['payment_method'])): ?>
      <div class="warning-msg">⚠️ Please select a shipping country before proceeding to payment.</div>
    <?php endif; ?>
  </div>

  <!-- Order Summary -->
  <div class="order-summary">
    <h2 class="section-title">Order Summary</h2>
    
    <?php foreach($cart as $index => $item): ?>
      <div class="order-item">
        <span class="item-info"><?php echo htmlspecialchars($item['name']); ?></span>
        <span class="item-cost"><?php echo number_format($item['price']); ?> <?php echo $currency_code; ?></span>
      </div>
    <?php endforeach; ?>

    <div class="cost-breakdown">
      <div class="cost-row subtotal">
        <span>Subtotal:</span>
        <span><?php echo number_format($total); ?> XAF</span>
      </div>
      <?php if($shipping_country_id): ?>
        <div class="cost-row shipping">
          <span>Shipping to <?php echo htmlspecialchars($countries[$shipping_country_id]['country_name']); ?>:</span>
          <span><?php echo number_format($shipping_fee); ?> <?php echo $currency_symbol; ?></span>
        </div>
        <div class="cost-row total">
          <span>Grand Total:</span>
          <span><?php echo number_format($grand_total); ?> <?php echo $currency_symbol; ?></span>
        </div>
      <?php else: ?>
        <div class="cost-row shipping" style="color: #999;">
          <span>Shipping:</span>
          <span>-- Select country --</span>
        </div>
      <?php endif; ?>
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

    <!-- Payment Methods -->
    <div class="payment-methods-container">
      <h4 style="color: #d4af37; margin: 0 0 15px 0;">Select Payment Method:</h4>
      
      <!-- PayUnit Method -->
      <div class="payment-method">
        <input type="radio" id="method-payunit" name="payment_method" value="payunit" checked>
        <label for="method-payunit" style="cursor: pointer; margin: 0;">
          <h4 style="margin: 0 0 5px 0;">💳 Mobile Money</h4>
          <p>MTN MoMo or Orange Money via PayUnit</p>
        </label>
      </div>

      <!-- PayPal Method -->
      <div class="payment-method">
        <input type="radio" id="method-paypal" name="payment_method" value="paypal">
        <label for="method-paypal" style="cursor: pointer; margin: 0;">
          <h4 style="margin: 0 0 5px 0;">🔵 PayPal</h4>
          <p>Pay securely with PayPal</p>
        </label>
      </div>
    </div>

    <!-- Payment Form -->
    <form method="POST" action="checkout.php">
      <input type="hidden" name="payment_method" id="paymentMethodInput" value="payunit">
      <input type="hidden" name="shipping_country_id" value="<?php echo $shipping_country_id; ?>">
      <input type="hidden" name="process_payment" value="1">
      <button type="submit" class="payment-btn" <?php echo ($shipping_country_id ? '' : 'disabled'); ?>>
        <?php echo ($shipping_country_id ? 'Proceed to Payment' : 'Select Country to Continue'); ?>
      </button>
    </form>
  </div>

  <div class="back-link">
    <a href="cart.php">← Back to Cart</a>
  </div>
</div>

<script>
  // Update shipping form and payment button
  function updateShipping() {
    const select = document.getElementById('shipping_country');
    const btn = document.querySelector('.payment-btn');
    const form = document.querySelector('form[action="checkout.php"]');
    
    if(select.value) {
      btn.disabled = false;
      btn.textContent = 'Proceed to Payment';
      form.submit();
    } else {
      btn.disabled = true;
      btn.textContent = 'Select Country to Continue';
    }
  }

  // Update payment method input
  document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
    radio.addEventListener('change', (e) => {
      document.getElementById('paymentMethodInput').value = e.target.value;
    });
  });
</script>

<?php 
if($debug_msg) {
  error_log("Checkout Debug: " . $debug_msg);
}
include "../includes/footer.php"; 
?>
