<?php
// Seed shipping_countries table with default country list (idempotent)
require_once __DIR__ . '/../includes/db.php';

$countries = [
    ['Cameroon','CM','XAF','XAF',0,3,1],
    ['Nigeria','NG','NGN','₦',8000,5,1],
    ['Ghana','GH','GHS','GHS',35,5,1],
    ['Kenya','KE','KES','KES',1500,7,1],
    ['South Africa','ZA','ZAR','R',250,7,1],
    ['France','FR','EUR','€',45,10,1],
    ['United Kingdom','GB','GBP','£',35,10,1],
    ['United States','US','USD','$',50,10,1],
    ['Canada','CA','CAD','C$',45,10,1],
    ['Australia','AU','AUD','A$',60,14,1],
    ['India','IN','INR','₹',800,14,1],
    ['China','CN','CNY','¥',600,14,1],
    ['Japan','JP','JPY','¥',3000,12,1],
    ['Brazil','BR','BRL','R$',120,14,1],
    ['Mexico','MX','MXN','$',800,10,1],
    ['Singapore','SG','SGD','S$',35,7,1],
    ['Malaysia','MY','MYR','RM',40,7,1],
    ['UAE','AE','AED','د.إ',80,7,1],
    ['Saudi Arabia','SA','SAR','ر.س',180,10,1],
    ['Egypt','EG','EGP','E£',400,7,1]
];

$stmt = $conn->prepare(
    "INSERT INTO shipping_countries (country_name,country_code,currency_code,currency_symbol,shipping_fee,estimated_days,is_active) VALUES (?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE country_name=VALUES(country_name), currency_code=VALUES(currency_code), currency_symbol=VALUES(currency_symbol), shipping_fee=VALUES(shipping_fee), estimated_days=VALUES(estimated_days), is_active=VALUES(is_active)"
);
if (!$stmt) {
    echo "Prepare failed: (" . $conn->errno . ") " . $conn->error . "\n";
    exit(1);
}

$count = 0;
foreach ($countries as $c) {
    [$name,$code,$curCode,$curSym,$fee,$days,$active] = $c;
    $stmt->bind_param('ssssiii', $name, $code, $curCode, $curSym, $fee, $days, $active);
    if ($stmt->execute()) {
        $count++;
    } else {
        // continue on error but show it
        echo "Failed to insert/update {$name}: (" . $stmt->errno . ") " . $stmt->error . "\n";
    }
}

echo "Seed complete. Processed: {$count} entries.\n";
$stmt->close();
$conn->close();

// Usage: php tools/seed_shipping_countries.php OR visit in browser (not recommended in production)

?>
