<?php include "../includes/header.php"; ?>
<link rel="stylesheet" href="style.css">

<style>
  .cart-container {
    max-width: 800px;
    margin: 40px auto;
    padding: 20px;
  }

  .cart-title {
    color: #d4af37;
    text-align: center;
    font-size: 2em;
    margin-bottom: 30px;
    text-shadow: 1px 1px 3px #000;
  }

  .cart-items {
    background: #1a1a1a;
    border: 1px solid #333;
    border-radius: 8px;
    padding: 20px;
    margin-bottom: 20px;
  }

  .cart-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #333;
    color: #eee;
  }

  .cart-item:last-child {
    border-bottom: none;
  }

  .item-name {
    font-weight: bold;
    color: #d4af37;
    flex-grow: 1;
  }

  .item-price {
    color: #d4af37;
    font-weight: bold;
    font-size: 1.1em;
    min-width: 100px;
    text-align: right;
  }

  .item-details {
    display: flex;
    align-items: center;
    gap: 15px;
    color: #999;
    font-size: 0.9em;
  }

  .remove-btn {
    background: #d4af37;
    color: #000;
    border: none;
    padding: 5px 10px;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
    margin-left: 15px;
  }

  .remove-btn:hover {
    opacity: 0.8;
  }

  .cart-empty {
    text-align: center;
    padding: 40px;
    color: #999;
    font-size: 1.1em;
  }

  .cart-total {
    background: #000;
    padding: 20px;
    border-radius: 8px;
    text-align: right;
    margin-bottom: 20px;
  }

  .total-amount {
    color: #d4af37;
    font-size: 1.8em;
    font-weight: bold;
  }

  .cart-actions {
    display: flex;
    gap: 10px;
    justify-content: center;
    flex-wrap: wrap;
  }

  .checkout-btn, .continue-btn {
    padding: 12px 30px;
    border: none;
    border-radius: 4px;
    font-weight: bold;
    font-size: 1.1em;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
  }

  .checkout-btn {
    background: #d4af37;
    color: #000;
  }

  .checkout-btn:hover {
    opacity: 0.8;
  }

  .continue-btn {
    background: #333;
    color: #d4af37;
    border: 1px solid #d4af37;
  }

  .continue-btn:hover {
    background: #d4af37;
    color: #000;
  }
</style>

<?php
$cart = json_decode($_COOKIE['cart'] ?? '[]', true);
if (!is_array($cart)) {
  $cart = [];
}
?>

<div class="cart-container">
  <h1 class="cart-title">Your Cart</h1>

  <?php
  if (empty($cart)) {
    echo "<div class='cart-empty'><p>Your cart is empty</p></div>";
  } else {
    $total = 0;
    echo "<div class='cart-items'>";
    foreach ($cart as $index => $item) {
      $total += $item['price'];
      $qty = $item['quantity'] ?? 1;
      $unitPrice = $item['unitPrice'] ?? $item['price'];
      echo "
      <div class='cart-item'>
        <span class='item-name'>{$item['name']}</span>
        <div class='item-details'>
          <span>{$unitPrice} XAF × {$qty}</span>
        </div>
        <span class='item-price'>{$item['price']} XAF</span>
        <button class='remove-btn' onclick='removeFromCart({$index})'>Remove</button>
      </div>";
    }
    echo "</div>";
    
    echo "<div class='cart-total'><div class='total-amount'>Total: {$total} XAF</div></div>";
  }
  ?>

  <div class="cart-actions">
    <a href="index.php" class="continue-btn">← Continue Shopping</a>
    <?php if (!empty($cart)): ?>
    <form action="checkout.php" method="POST" style="margin:0;">
      <button type="submit" class="checkout-btn">Proceed to Checkout →</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<script>
document.cookie = "cart=" + localStorage.getItem('cart');

function removeFromCart(index) {
  let cart = JSON.parse(localStorage.getItem('cart')) || [];
  cart.splice(index, 1);
  localStorage.setItem('cart', JSON.stringify(cart));
  document.cookie = "cart=" + JSON.stringify(cart);
  location.reload();
}
</script>

<?php include "../includes/footer.php"; ?>