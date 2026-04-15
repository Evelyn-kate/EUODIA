<?php
/**
 * PayPal Payment Handler
 * Uses PayPal Hosted Checkout (no API required)
 */

class PayPalHandler {
    private $sandbox_url = "https://www.sandbox.paypal.com/checkoutnow";
    private $live_url = "https://www.paypal.com/checkoutnow";
    private $client_id = "YOUR_PAYPAL_CLIENT_ID"; // You'll need to set this
    private $is_sandbox = true; // Set to false for production
    
    /**
     * Generate PayPal payment redirect URL
     * For demo/sandbox purposes, we'll use a simple redirect
     */
    public static function createPayment($amount, $email, $name, $transaction_id) {
        // Encode order data for return URL
        $order_data = base64_encode(json_encode([
            'amount' => $amount,
            'email' => $email,
            'name' => $name,
            'txid' => $transaction_id,
            'timestamp' => date('Y-m-d H:i:s')
        ]));
        
        // Return mock payment URL (for testing)
        // In production, this would redirect to actual PayPal
        return [
            'success' => true,
            'redirect_url' => 'paypal_redirect.php?order=' . urlencode($order_data),
            'method' => 'PayPal',
            'message' => 'Redirecting to PayPal...'
        ];
    }
    
    /**
     * Verify PayPal payment (IPN Simulation)
     */
    public static function verifyPayment($order_data) {
        // Validate order data
        if (!$order_data) {
            return ['success' => false, 'message' => 'Invalid order data'];
        }
        
        try {
            $data = json_decode(base64_decode($order_data), true);
            
            // Simulate payment verification
            // In production, you would verify with PayPal's IPN service
            return [
                'success' => true,
                'transaction_id' => $data['txid'] ?? '',
                'amount' => $data['amount'] ?? 0,
                'email' => $data['email'] ?? '',
                'name' => $data['name'] ?? '',
                'payment_method' => 'PayPal',
                'status' => 'Completed'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Payment verification failed'];
        }
    }
    
    /**
     * Generate PayPal Standard Form (Alternative method)
     */
    public static function getPayPalForm($amount, $email, $name, $transaction_id, $website_url) {
        $form = '
        <form action="https://www.sandbox.paypal.com/cgi-bin/webscr" method="post" id="paypal-form" style="display:none;">
            <input type="hidden" name="cmd" value="_xclick">
            <input type="hidden" name="business" value="YOUR_PAYPAL_EMAIL">
            <input type="hidden" name="item_name" value="Euodia Order #' . $transaction_id . '">
            <input type="hidden" name="item_number" value="' . $transaction_id . '">
            <input type="hidden" name="amount" value="' . ($amount / 650) . '"> <!-- Convert XAF to USD approx -->
            <input type="hidden" name="currency_code" value="USD">
            <input type="hidden" name="invoice" value="' . $transaction_id . '">
            <input type="hidden" name="custom" value="' . base64_encode($transaction_id) . '">
            <input type="hidden" name="return" value="' . $website_url . '/paypal_return.php?success=1">
            <input type="hidden" name="cancel_return" value="' . $website_url . '/paypal_return.php?success=0">
            <input type="hidden" name="notify_url" value="' . $website_url . '/paypal_ipn.php">
            <input type="hidden" name="email" value="' . htmlspecialchars($email) . '">
            <input type="hidden" name="first_name" value="' . htmlspecialchars(explode(' ', $name)[0]) . '">
            <input type="hidden" name="last_name" value="' . htmlspecialchars(end(explode(' ', $name))) . '">
            <input type="hidden" name="no_shipping" value="2">
        </form>
        ';
        return $form;
    }
}
?>
