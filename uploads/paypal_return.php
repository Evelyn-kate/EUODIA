<?php
ob_start();
include "../includes/db.php";
include "../includes/paypal.php";

$order_data = $_GET['order'] ?? '';
$success = $_GET['success'] ?? 0;

// Verify payment
if ($success == 1 && $order_data) {
    $payment_result = PayPalHandler::verifyPayment($order_data);
    
    if ($payment_result['success']) {
        // Clear cart cookies
        setcookie("cart", "", time() - 3600);
        
        // Get user from session
        $user_id = $_SESSION['user']['id'] ?? 0;
        
        // Insert order into database
        if ($user_id) {
            $total = $payment_result['amount'];
            $transaction_id = $payment_result['transaction_id'];
            
            $conn->query("INSERT INTO orders (user_id, total, transaction_id, payment_method) 
                         VALUES ($user_id, $total, '$transaction_id', 'PayPal')");
        }
        
        // Store transaction info for display
        $_SESSION['last_transaction'] = $payment_result;
        
        // Redirect to success page
        header("Location: success.php?txid=" . $payment_result['transaction_id'] . "&method=paypal");
        exit;
    }
}

// Payment failed or cancelled
include "../includes/header.php";
?>
<link rel="stylesheet" href="style.css">

<style>
    .payment-result {
        max-width: 600px;
        margin: 50px auto;
        padding: 30px;
        text-align: center;
        background: #1a1a1a;
        border: 1px solid #333;
        border-radius: 8px;
    }
    
    .result-icon {
        font-size: 3em;
        margin-bottom: 15px;
    }
    
    .result-title {
        color: #d4af37;
        font-size: 1.6em;
        margin: 15px 0;
        text-shadow: 1px 1px 3px #000;
    }
    
    .result-message {
        color: #eee;
        font-size: 1.1em;
        margin: 15px 0;
    }
    
    .result-action {
        margin-top: 30px;
    }
    
    .result-action a {
        display: inline-block;
        padding: 12px 30px;
        background: #d4af37;
        color: #000;
        text-decoration: none;
        border-radius: 4px;
        font-weight: bold;
        transition: all 0.3s ease;
    }
    
    .result-action a:hover {
        opacity: 0.8;
        transform: translateY(-2px);
    }
</style>

<div class="payment-result">
    <div class="result-icon">❌</div>
    <div class="result-title">Payment Not Completed</div>
    <div class="result-message">
        <?php 
        if ($success == 0) {
            echo "Your payment was cancelled. Please try again.";
        } else {
            echo "There was an issue processing your payment. Please try again.";
        }
        ?>
    </div>
    <div class="result-action">
        <a href="cart.php">← Return to Cart</a>
    </div>
</div>

<?php include "../includes/footer.php"; ?>
