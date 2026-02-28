<?php

declare(strict_types=1);

namespace App\Email;

use SendGrid;
use SendGrid\Mail\Mail;

/**
 * Send a one-time password email using SendGrid.
 *
 * @param string $toEmail recipient address
 * @param string $otp     six-digit code
 * @return bool          true on success, false otherwise
 */
function sendOTPEmail(string $toEmail, string $otp): bool
{
    // Load environment variables - check getenv, $_ENV, and $_SERVER
    $apiKey = getenv('SENDGRID_API_KEY');
    if (!$apiKey) {
        $apiKey = $_ENV['SENDGRID_API_KEY'] ?? null;
    }
    if (!$apiKey) {
        $apiKey = $_SERVER['SENDGRID_API_KEY'] ?? null;
    }

    if (!$apiKey) {
        $msg = sprintf("[%s] ERROR: SENDGRID_API_KEY not found in .env or environment%s",
            date('Y-m-d H:i:s'), PHP_EOL);
        @file_put_contents(__DIR__ . '/../logs/email_errors.log', $msg, FILE_APPEND | LOCK_EX);
        error_log('SendGrid API key not configured in .env');
        return false;
    }

    // Get sender email and name from environment
    $from = getenv('MAIL_FROM');
    if (!$from) {
        $from = $_ENV['MAIL_FROM'] ?? null;
    }
    if (!$from) {
        $from = $_SERVER['MAIL_FROM'] ?? null;
    }

    $fromName = getenv('MAIL_FROM_NAME');
    if (!$fromName) {
        $fromName = $_ENV['MAIL_FROM_NAME'] ?? null;
    }
    if (!$fromName) {
        $fromName = $_SERVER['MAIL_FROM_NAME'] ?? null;
    }
    if (!$fromName) {
        $fromName = 'OTP Sender';
    }

    // validate sender email
    if (!$from || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
        $msg = sprintf("[%s] ERROR: MAIL_FROM not set or invalid ('%s'), cannot send to %s%s",
            date('Y-m-d H:i:s'), $from ?? '(empty)', $toEmail, PHP_EOL);
        @file_put_contents(__DIR__ . '/../logs/email_errors.log', $msg, FILE_APPEND | LOCK_EX);
        error_log("MAIL_FROM config error: '" . ($from ?? 'NULL') . "'");
        return false;
    }

    // Log that we're about to send
    $debugMsg = sprintf("[%s] DEBUG: About to send to %s from %s%s", 
        date('Y-m-d H:i:s'), $toEmail, $from, PHP_EOL);
    @file_put_contents(__DIR__ . '/../logs/sendgrid.log', $debugMsg, FILE_APPEND | LOCK_EX);

    $email = new Mail();
    $email->setFrom($from, $fromName);
    $email->setSubject('Password Reset OTP');
    $email->addTo($toEmail);
    // provide both HTML and plain-text bodies
    $email->addContent('text/plain', "Your one-time password is {$otp}. This code expires in 5 minutes.");
    $email->addContent(
        'text/html',
        "<p>Your one-time password is <strong>{$otp}</strong>.</p>"
      . "<p>This code expires in <strong>5 minutes</strong>.</p>"
    );

    $sendgrid = new SendGrid($apiKey);

    try {
        $response = $sendgrid->send($email);
        $code = $response->statusCode();

        if ($code >= 200 && $code < 300) {
            // log success for debugging
            $msg = sprintf("[%s] SendGrid success (%d) to %s OTP=%s%s", date('Y-m-d H:i:s'), $code, $toEmail, substr($otp,0,2) . '****', PHP_EOL);
            @file_put_contents(__DIR__ . '/../logs/sendgrid.log', $msg, FILE_APPEND | LOCK_EX);
            return true;
        }

        throw new \RuntimeException(
            "SendGrid returned HTTP {$code}: " . $response->body()
        );
    } catch (\Throwable $e) {
        $msg = sprintf(
            "[%s] SendGrid error sending to %s: %s%s",
            date('Y-m-d H:i:s'),
            $toEmail,
            $e->getMessage(),
            PHP_EOL
        );
        @file_put_contents(__DIR__ . '/../logs/email_errors.log',
            $msg, FILE_APPEND | LOCK_EX);
        return false;
    }
}
