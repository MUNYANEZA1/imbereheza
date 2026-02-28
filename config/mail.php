<?php
// Mail configuration used by forgot_password.php when PHPMailer is enabled.
// Copy this file and fill with your SMTP provider credentials.

return [
    // If you want to use SMTP via PHPMailer, set 'use_smtp' => true and fill details.
    'use_smtp' => false,
    'host' => 'smtp.example.com',
    'username' => 'smtp-user@example.com',
    'password' => 'smtp-password',
    'port' => 587,
    'encryption' => 'tls', // 'tls' or 'ssl'
    'from_email' => 'no-reply@example.com',
    'from_name' => 'Agricultural Loan System'
];
