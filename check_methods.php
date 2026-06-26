<?php
require 'vendor/autoload.php';

$webAuthn = new \lbuchs\WebAuthn\WebAuthn('EUODIA', 'localhost');
echo "Available methods:\n";
print_r(get_class_methods($webAuthn));