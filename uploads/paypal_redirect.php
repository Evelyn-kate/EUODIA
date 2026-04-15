<?php
include "../includes/db.php";
include "../includes/paypal.php";

$order_data = $_GET['order'] ?? '';

if (!$order_data) {
    die("Invalid order data");
}

try {
    $data = json_decode(base64_decode($order_data), true);
} catch (Exception $e) {
    die("Error processing payment");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PayPal Payment — Euodia</title>
    <style>
        * { box-sizing: border-box; }
        body {
            background: #111;
            color: #eee;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
        }
        .payment-container {
            max-width: 600px;
            margin: 50px auto;
            background: #1a1a1a;
            border: 1px solid #333;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
        }
        .payment-header {
            color: #d4af37;
            font-size: 1.8em;
            margin-bottom: 30px;
            text-shadow: 1px 1px 3px #000;
        }
        .order-info {
            background: #0a0a0a;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
            text-align: left;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #333;
            color: #eee;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            color: #999;
            font-weight: bold;
        }
        .info-value {
            color: #d4af37;
            font-weight: bold;
        }
        .payment-options {
            margin: 30px 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .payment-btn {
            padding: 15px;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 1.1em;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }
        .btn-paypal {
            background: #0070ba;
            color: white;
        }
        .btn-paypal:hover {
            background: #005ea6;
        }
        .btn-demo {
            background: #27ae60;
            color: white;
        }
        .btn-demo:hover {
            background: #229954;
        }
        .btn-cancel {
            background: #555;
            color: white;
        }
        .btn-cancel:hover {
            background: #666;
        }
        .info-text {
            background: #111;
            border-left: 3px solid #d4af37;
            padding: 15px;
            margin: 20px 0;
            text-align: left;
            font-size: 0.9em;
            color: #999;
        }
    </style>
</head>
<body>
    <div class="payment-container">
        <div class="payment-header">💳 Complete Payment</div>
        
        <div class="order-info">
            <div class="info-row">
                <span class="info-label">Order ID:</span>
                <span class="info-value"><?php echo htmlspecialchars($data['txid'] ?? ''); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Amount:</span>
                <span class="info-value"><?php echo htmlspecialchars($data['amount'] ?? 0); ?> XAF</span>
            </div>
            <div class="info-row">
                <span class="info-label">Customer:</span>
                <span class="info-value"><?php echo htmlspecialchars($data['name'] ?? ''); ?></span>
            </div>
            <div class="info-row">
                <span class="info-label">Email:</span>
                <span class="info-value"><?php echo htmlspecialchars($data['email'] ?? ''); ?></span>
            </div>
        </div>

        <div class="info-text">
            <strong>ℹ️ Demo Mode:</strong> For testing purposes, you can use either option below. In production, you would be redirected to PayPal for live payment.
        </div>

        <div class="payment-options">
            <!-- Live PayPal Option (commented for demo) -->
            <button class="payment-btn btn-paypal" onclick="window.location.href='paypal_return.php?success=1&order=<?php echo urlencode($order_data); ?>'">
                🔵 Complete with PayPal (Demo)
            </button>

            <!-- Demo Success -->
            <a href="paypal_return.php?success=1&order=<?php echo urlencode($order_data); ?>" class="payment-btn btn-demo">
                ✓ Demo Success Payment
            </a>

            <!-- Cancel -->
            <a href="../uploads/cart.php" class="payment-btn btn-cancel">
                ✕ Cancel & Return to Cart
            </a>
        </div>

        <div class="info-text" style="margin-top: 30px;">
            <strong>Note:</strong> This is a demo/sandbox environment. To enable live PayPal payments, add your PayPal credentials in includes/paypal.php
        </div>
    </div>
</body>
</html>
