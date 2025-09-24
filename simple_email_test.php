<?php
// Simple email config test
echo "=== EMAIL CONFIG TEST ===\n";

// Load only the essential parts
define('PATH_ROOT', __DIR__);
require_once PATH_ROOT . '/system/Helpers/Core_helper.php';
require_once PATH_ROOT . '/helpers/Backend_helper.php';

// Test email config
$email_config = option('email');
echo "Email config type: " . gettype($email_config) . "\n";

if (is_array($email_config)) {
    echo "Email config is array with " . count($email_config) . " items\n";
    foreach ($email_config as $key => $value) {
        echo "- $key: " . (empty($value) ? 'EMPTY' : 'SET') . "\n";
    }
} else {
    echo "Email config is: " . $email_config . "\n";
}

echo "=== END TEST ===\n";
?>
