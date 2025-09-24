<?php
require_once 'index.php';

echo "=== EMAIL CONFIGURATION TEST ===\n";

// Test 1: Check email config
$email_config = option('email');
echo "Email config: ";
var_dump($email_config);

// Test 2: Check if it's array
echo "\nIs array: " . (is_array($email_config) ? 'YES' : 'NO');

// Test 3: Try to create Fastmail instance
try {
    $mailer = new \App\Libraries\Fastmail();
    echo "\nFastmail instance created: SUCCESS";
} catch (Exception $e) {
    echo "\nFastmail error: " . $e->getMessage();
}

// Test 4: Check required email settings
$required_settings = ['mail_host', 'mail_username', 'mail_password', 'mail_from_address'];
echo "\n\nRequired email settings:\n";
foreach ($required_settings as $setting) {
    $value = $email_config[$setting] ?? 'NOT SET';
    echo "- $setting: " . (empty($value) ? 'EMPTY' : 'SET') . "\n";
}

echo "\n=== END TEST ===\n";
?>
