<?php
/**
 * Quick SendGrid test script
 * Run from command line: php test_sendgrid.php
 */

require_once __DIR__ . '/vendor/autoload.php';

echo "[TEST] Loading .env file...\n";
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "[TEST] Loading SendGrid helper...\n";
require_once __DIR__ . '/includes/sendgrid.php';

echo "[TEST] Environment values loaded:\n";
echo "  SENDGRID_API_KEY: " . (getenv('SENDGRID_API_KEY') ? 'SET' : 'NOT SET') . "\n";
echo "  MAIL_FROM: " . (getenv('MAIL_FROM') ?: 'NOT SET') . "\n";
echo "  MAIL_FROM_NAME: " . (getenv('MAIL_FROM_NAME') ?: 'NOT SET') . "\n";

echo "\n[TEST] Attempting to send OTP email...\n";
$testEmail = 'enezaa4@gmail.com';
$testOTP = '123456';

$result = \App\Email\sendOTPEmail($testEmail, $testOTP);

if ($result) {
    echo "[SUCCESS] Email was sent! Check your inbox.\n";
} else {
    echo "[FAILED] Email could not be sent. Check logs/email_errors.log and logs/sendgrid.log\n";
}

echo "\n[TEST] Checking log files...\n";
if (file_exists(__DIR__ . '/logs/sendgrid.log')) {
    echo "\n--- sendgrid.log ---\n";
    echo file_get_contents(__DIR__ . '/logs/sendgrid.log');
}
if (file_exists(__DIR__ . '/logs/email_errors.log')) {
    echo "\n--- email_errors.log ---\n";
    echo file_get_contents(__DIR__ . '/logs/email_errors.log');
}

echo "\nDone.\n";
?>
