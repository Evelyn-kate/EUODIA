<?php
// PayUnit Configuration (Replace with real credentials when available)
$payunit_api_key = "test_key_demo_12345";
$payunit_user_id = "test_user_demo";
$payunit_password = "test_password_demo";
$payunit_mode = "test";

function payunit_charge($amount, $email, $name, $txid) {
  global $payunit_api_key, $payunit_user_id, $payunit_password, $payunit_mode;

  // Mock payment for testing - Replace with real PayUnit API when credentials are available
  // For now, we'll return a mock payment URL
  
  // In production, uncomment and use real PayUnit API:
  /*
  $payload = [
    "amount" => $amount,
    "currency" => "XAF",
    "transaction_id" => $txid,
    "email" => $email,
    "name" => $name,
    "callback" => "http://localhost/euodia/uploads/success.php"
  ];

  $curl = curl_init("https://api.payunit.net/api/v2/payment");
  curl_setopt($curl, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "x-api-key: $payunit_api_key",
    "x-api-user-id: $payunit_user_id",
    "x-api-password: $payunit_password",
    "mode: $payunit_mode"
  ]);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($curl, CURLOPT_POST, true);
  curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload));
  return json_decode(curl_exec($curl), true);
  */

  // Mock payment response for testing
  $params = http_build_query([
    "txid" => $txid,
    "amount" => $amount,
    "email" => $email,
    "name" => $name
  ]);
  
  return [
    "payment_url" => "success.php?" . $params,
    "transaction_id" => $txid,
    "status" => "pending"
  ];
}
?>
