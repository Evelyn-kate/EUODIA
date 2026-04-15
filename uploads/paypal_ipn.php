<?php
/**
 * PayPal IPN (Instant Payment Notification) Handler
 * This receives notifications from PayPal about payment status
 * For production use only
 */

include "../includes/db.php";

// Get POST data from PayPal
$raw_post_data = file_get_contents('php://input');
$raw_post_array = explode('&', $raw_post_data);
$myPost = array();

foreach ($raw_post_array as $keyval) {
    $keyval = explode('=', $keyval);
    if (count($keyval) == 2)
        $myPost[$keyval[0]] = urldecode($keyval[1]);
}

// Build the request string to verify with PayPal
$req = 'cmd=_notify-validate';
foreach ($myPost as $key => $value) {
    $req .= "&$key=" . urlencode($value);
}

// Verify with PayPal
$sandbox = true; // Change to false for production
$paypal_url = $sandbox ? "https://www.sandbox.paypal.com/cgi-bin/webscr" : "https://www.paypal.com/cgi-bin/webscr";

$ch = curl_init($paypal_url);
curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, $req);
curl_setopt($ch, CURLOPT_SSLVERSION, 6);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
curl_setopt($ch, CURLOPT_FORBID_REUSE, 1);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
curl_setopt($ch, CURLOPT_TIMEOUT, 60);
curl_setopt($ch, CURLOPT_USERAGENT, "PHP-IPN-Verification/1.0");

$res = curl_exec($ch);

if (!$res) {
    // Log error
    error_log("PayPal IPN: cURL error");
    http_response_code(400);
    exit;
}

curl_close($ch);

// Check PayPal response
if (strcmp($res, "VERIFIED") == 0) {
    // IPN signature verified
    
    // Get IPN data
    $txn_id = $_POST['txn_id'] ?? '';
    $payment_status = $_POST['payment_status'] ?? '';
    $invoice = $_POST['invoice'] ?? '';
    $receiver_email = $_POST['receiver_email'] ?? '';
    $mc_gross = $_POST['mc_gross'] ?? 0;
    $custom = $_POST['custom'] ?? '';
    
    try {
        $transaction_id = base64_decode($custom);
        
        // Only process if payment status is Completed
        if ($payment_status == 'Completed') {
            // Check if order already exists
            $check = $conn->query("SELECT id FROM orders WHERE transaction_id = '$transaction_id'");
            
            if ($check->num_rows == 0) {
                // Get user by email from PayPal
                $user_result = $conn->query("SELECT id FROM users WHERE email = '" . $_POST['payer_email'] . "'");
                
                if ($user_result->num_rows > 0) {
                    $user = $user_result->fetch_assoc();
                    $user_id = $user['id'];
                    
                    // Insert order
                    $conn->query("INSERT INTO orders (user_id, total, transaction_id, payment_method, status) 
                                 VALUES ($user_id, $mc_gross, '$transaction_id', 'PayPal', 'Completed')");
                    
                    // Log successful payment
                    error_log("PayPal Payment Verified: TXN=$txn_id");
                }
            }
        } else if ($payment_status == 'Pending') {
            // Handle pending payment
            error_log("PayPal Payment Pending: TXN=$txn_id, Reason=" . $_POST['pending_reason']);
        } else if ($payment_status == 'Failed' || $payment_status == 'Denied' || $payment_status == 'Refunded') {
            // Handle failed payment
            error_log("PayPal Payment Failed/Refunded: TXN=$txn_id, Status=$payment_status");
        }
        
    } catch (Exception $e) {
        error_log("PayPal IPN Error: " . $e->getMessage());
    }
    
    http_response_code(200);
    exit;
    
} else if (strcmp($res, "INVALID") == 0) {
    // Invalid IPN signature
    error_log("PayPal IPN: Invalid signature");
    http_response_code(400);
    exit;
}
?>
