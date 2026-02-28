<?php
session_start();
require_once 'config/db.php';

$message = '';
$error = '';

// Pre-fill username and notify when redirected from forgot_password
$prefill_username = '';
if (isset($_GET['username'])) {
    $prefill_username = trim($_GET['username']);
}
if (isset($_GET['sent']) && $_GET['sent'] == '1') {
    $message = 'An OTP has been generated and sent to the admin email address. Enter it below to login. Using the code will make it your new password.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $otp = isset($_POST['otp']) ? trim($_POST['otp']) : '';

    if (empty($username) || empty($otp)) {
        $error = 'Please provide both username and OTP.';
    } else {
        // find user
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'Invalid username or OTP.';
        } else {
            // find OTP
            $stmt = $pdo->prepare("SELECT * FROM password_otps WHERE user_id = ? AND otp = ? AND used = 0 ORDER BY id DESC LIMIT 1");
            $stmt->execute([$user['id'], $otp]);
            $row = $stmt->fetch();

            if (!$row) {
                $error = 'Invalid OTP or already used.';
            } else {
                if (strtotime($row['expires_at']) < time()) {
                    $error = 'OTP has expired. Please request a new one.';
                } else {
                    // mark OTP used
                    $upd = $pdo->prepare("UPDATE password_otps SET used = 1 WHERE id = ?");
                    $upd->execute([$row['id']]);

                    // If admin logged in via OTP, update the account password to exactly the OTP used
                    if ($user['role'] === 'admin') {
                        $hashed = password_hash($otp, PASSWORD_DEFAULT);
                        $up = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
                        $up->execute([$hashed, $user['id']]);

                        // audit log for debugging/records
                        error_log('Admin ' . $user['username'] . ' password reset to OTP via OTP login.');
                        @file_put_contents(__DIR__ . '/last_otp.txt', date('Y-m-d H:i:s') . "\tPWD_RESET\t" . $user['username'] . "\t" . $otp . "\n", FILE_APPEND | LOCK_EX);
                    }

                    // set session and redirect
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];

                    if ($user['role'] === 'admin') {
                        header('Location: admin/dashboard.php');
                        exit();
                    } else {
                        header('Location: member/dashboard.php');
                        exit();
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
    <title>OTP Login - Agricultural Loan System</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="login-page">
    <div class="login-wrapper">
        <div class="login-container" style="max-width: 480px;">
            <div class="login-header">
                <h1>OTP Login</h1>
                <p class="subtitle">Enter your username and one-time code</p>
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

            <form method="POST" class="login-form">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" name="username" id="username" placeholder="Enter your username" required value="<?php echo htmlspecialchars($prefill_username); ?>">
                            </div>

                <div class="form-group">
                    <label for="otp">One-Time Code (OTP)</label>
                    <input type="text" name="otp" id="otp" placeholder="Enter the 6-digit code" required>
                </div>

                <button type="submit" class="btn-login">Login with OTP</button>
            </form>

            <div style="margin-top: 16px; text-align: center;">
                <a href="forgot_password.php" style="color: #0066cc; text-decoration: none;">Request a new OTP</a>
            </div>

            <div class="login-footer" style="margin-top: 20px;">
                <p>&copy; <?php echo date('Y'); ?> Agricultural Digital Financial Solutions</p>
            </div>
        </div>
    </div>
</body>
</html>
