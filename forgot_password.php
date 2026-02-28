<?php
session_start();

// load Composer autoloader + environment variables (SendGrid key, etc.)
require_once __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

require_once __DIR__ . '/includes/sendgrid.php';
use function App\Email\sendOTPEmail;

require_once 'config/db.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] == 'admin') {
        header("Location: admin/dashboard.php");
    } else {
        header("Location: member/dashboard.php");
    }
    exit();
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $reason = isset($_POST['reason']) ? trim($_POST['reason']) : '';

    if (empty($username)) {
        $error = 'Please enter your username.';
    } elseif (empty($reason)) {
        $error = 'Please provide a reason for the password reset.';
    } else {
        // First try to find a user account (admin or member) by username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            // fallback: original flow - try to find member by username
            $stmt = $pdo->prepare("SELECT m.id FROM members m JOIN users u ON m.user_id = u.id WHERE u.username = ?");
            $stmt->execute([$username]);
            $member = $stmt->fetch();

            if (!$member) {
                $error = 'Username not found. Please check and try again.';
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO password_reset_requests (member_id, reason, status) VALUES (?, ?, 'Pending')");
                    $stmt->execute([$member['id'], $reason]);
                    $message = 'Your password reset request has been submitted successfully. Our admin will review and respond to your request shortly.';
                } catch (PDOException $e) {
                    $error = 'Error submitting request. Please try again later.';
                }
            }
        } else {
            // If user exists and is admin, generate and send OTP to admin email for recovery
            if ($user['role'] === 'admin') {
                // Ensure user has an email
                if (empty($user['email'])) {
                    $error = 'No email configured for this admin account. Contact system administrator.';
                } else {
                    // generate 6-digit OTP
                    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    $expires_at = date('Y-m-d H:i:s', time() + 15 * 60); // 15 minutes

                    try {
                        $ins = $pdo->prepare("INSERT INTO password_otps (user_id, otp, expires_at) VALUES (?, ?, ?)");
                        $ins->execute([$user['id'], $otp, $expires_at]);

                        // send email via SendGrid helper
                        if (!sendOTPEmail($user['email'], $otp)) {
                            // show error to admin (will render later)
                            $error = 'Unable to deliver OTP email. Please contact support. Check logs.';
                        } else {
                            // Always write OTP to local log for development/testing so you can retrieve it immediately.
                            $logLine = date('Y-m-d H:i:s') . "\t" . $user['username'] . "\t" . $user['email'] . "\tOTP:" . $otp . "\tExpires:" . $expires_at . "\n";
                            @file_put_contents(__DIR__ . '/last_otp.txt', $logLine, FILE_APPEND | LOCK_EX);

                            // Redirect admin to OTP login page so they can enter the code immediately.
                            $redirect = 'otp_login.php?username=' . urlencode($user['username']) . '&sent=1';
                            header('Location: ' . $redirect);
                            exit();
                        }
                    } catch (PDOException $e) {
                        $error = 'Error generating OTP. Please try again later.';
                    }
                }
            } else {
                // Non-admin user - create a member password reset request as before
                $stmt = $pdo->prepare("SELECT m.id FROM members m WHERE m.user_id = ?");
                $stmt->execute([$user['id']]);
                $member = $stmt->fetch();

                if (!$member) {
                    $error = 'Account not linked to a member record. Contact admin.';
                } else {
                    try {
                        $stmt = $pdo->prepare("INSERT INTO password_reset_requests (member_id, reason, status) VALUES (?, ?, 'Pending')");
                        $stmt->execute([$member['id'], $reason]);
                        $message = 'Your password reset request has been submitted successfully. Our admin will review and respond to your request shortly.';
                    } catch (PDOException $e) {
                        $error = 'Error submitting request. Please try again later.';
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Agricultural Loan System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-container" style="max-width: 500px;">
            <div class="login-header">
                <h1>Reset Your Password</h1>
                <p class="subtitle">Cooperative Imbere Heza Mwaro</p>
            </div>

            <?php if ($message): ?>
                <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 4px;">
                    <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 4px;">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!$message): ?>
                <form method="POST" class="login-form">
                    <div class="form-group">
                        <label for="username">Username</label>
                        <div class="input-wrapper">
                            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                                <circle cx="12" cy="7" r="4"/>
                            </svg>
                            <input type="text" name="username" id="username" placeholder="Enter your username" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reason">Reason for Password Reset</label>
                        <textarea name="reason" id="reason" placeholder="E.g., I forgot my password or I need to change it for security reasons" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif; height: 100px;"></textarea>
                    </div>

                    <button type="submit" class="btn-login">
                        <span>Submit Reset Request</span>
                        <svg class="btn-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </button>
                </form>
            <?php endif; ?>

            <div style="margin-top: 20px; text-align: center;">
                <a href="index.php" style="color: #0066cc; text-decoration: none;">Back to Login</a>
            </div>

            <div class="login-footer">
                <p>&copy; <?php echo date('Y'); ?> Agricultural Digital Financial Solutions</p>
            </div>
        </div>
    </div>
</body>
</html>
