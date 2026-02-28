<?php
// send_test_mail.php - simple helper to test mail sending using config/mail.php
require_once __DIR__ . '/config/db.php';
$mailConfig = [];
if (file_exists(__DIR__ . '/config/mail.php')) {
    $mailConfig = include __DIR__ . '/config/mail.php';
}

$to = isset($argv[1]) ? $argv[1] : (isset($_GET['to']) ? $_GET['to'] : '');
if (empty($to)) {
    echo "Usage: php send_test_mail.php you@example.com\n";
    exit(1);
}
$subject = 'Test email from Agricultural Loan System';
$body = "This is a test message sent at " . date('Y-m-d H:i:s') . "\n";

// Try PHPMailer if configured
if (!empty($mailConfig) && !empty($mailConfig['use_smtp']) && file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $mailConfig['host'];
        $mail->SMTPAuth = true;
        $mail->Username = $mailConfig['username'];
        $mail->Password = $mailConfig['password'];
        $mail->SMTPSecure = $mailConfig['encryption'] ?? 'tls';
        $mail->Port = $mailConfig['port'] ?? 587;
        $mail->setFrom($mailConfig['from_email'] ?? 'no-reply@localhost', $mailConfig['from_name'] ?? 'No Reply');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        echo "PHPMailer: test email sent to $to\n";
    } catch (Exception $e) {
        echo "PHPMailer error: " . $mail->ErrorInfo . "\n";
    }
} else {
    // Fallback to mail()
    $headers = 'From: ' . ($mailConfig['from_email'] ?? 'no-reply@localhost') . "\r\n" . 'Content-Type: text/plain; charset=utf-8';
    $ok = @mail($to, $subject, $body, $headers);
    if ($ok) {
        echo "mail(): test email accepted by local MTA for $to\n";
    } else {
        echo "mail(): failed to send test email to $to (no local mailserver).\n";
    }
}
