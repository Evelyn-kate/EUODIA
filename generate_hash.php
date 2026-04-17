<?php
// Define your new secure password
$new_password = 'YourNewSecurePassword123!'; 

// Generate the Argon2id hash
$secure_hash = password_hash($new_password, PASSWORD_ARGON2ID);

echo "Copy this hash: " . $secure_hash;
?>