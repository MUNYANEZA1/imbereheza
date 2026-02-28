<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

$success_msg = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error_msg = 'Please fill all password fields.';
    } elseif ($new !== $confirm) {
        $error_msg = 'New password and confirmation do not match.';
    } elseif (strlen($new) < 6) {
        $error_msg = 'Password must be at least 6 characters long.';
    } else {
        $uStmt = $pdo->prepare('SELECT password, username FROM users WHERE id = ? AND role = "admin"');
        $uStmt->execute([$_SESSION['user_id']]);
        $user = $uStmt->fetch();
        if (!$user || !password_verify($current, $user['password'])) {
            $error_msg = 'Current password is incorrect.';
        } else {
            $hashed = password_hash($new, PASSWORD_DEFAULT);
            $up = $pdo->prepare('UPDATE users SET password = ? WHERE id = ?');
            if ($up->execute([$hashed, $_SESSION['user_id']])) {
                $success_msg = 'Password updated successfully.';
            } else {
                $error_msg = 'Failed to update password. Please try again.';
            }
        }
    }
}

// Fetch admin username for display
$uStmt = $pdo->prepare('SELECT username FROM users WHERE id = ?');
$uStmt->execute([$_SESSION['user_id']]);
$admin = $uStmt->fetch();
?>

<div style="max-width:800px;margin:0 auto;">
    <h2>Admin Profile: <?php echo htmlspecialchars($admin['username'] ?? ''); ?></h2>

    <?php
    // Handle profile update (username)
    $profile_msg = '';
    $profile_err = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $new_username = trim($_POST['username'] ?? '');
        if (empty($new_username)) {
            $profile_err = 'Username cannot be empty.';
        } else {
            // Check uniqueness
            $check = $pdo->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
            $check->execute([$new_username, $_SESSION['user_id']]);
            if ($check->fetch()) {
                $profile_err = 'Username is already taken.';
            } else {
                $up = $pdo->prepare('UPDATE users SET username = ? WHERE id = ?');
                if ($up->execute([$new_username, $_SESSION['user_id']])) {
                    $profile_msg = 'Profile updated successfully.';
                    $_SESSION['username'] = $new_username;
                    // refresh admin display
                    $admin['username'] = $new_username;
                } else {
                    $profile_err = 'Failed to update profile. Please try again.';
                }
            }
        }
    }
    ?>

    <?php if ($profile_msg): ?>
        <div style="background: #d4edda; padding: 12px; border-radius: 4px; margin-bottom: 12px;"><?php echo htmlspecialchars($profile_msg); ?></div>
    <?php endif; ?>
    <?php if ($profile_err): ?>
        <div style="background: #f8d7da; padding: 12px; border-radius: 4px; margin-bottom: 12px;"><?php echo htmlspecialchars($profile_err); ?></div>
    <?php endif; ?>

    <?php if ($success_msg): ?>
        <div style="background: #d4edda; padding: 12px; border-radius: 4px; margin-bottom: 12px;"><?php echo htmlspecialchars($success_msg); ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div style="background: #f8d7da; padding: 12px; border-radius: 4px; margin-bottom: 12px;"><?php echo htmlspecialchars($error_msg); ?></div>
    <?php endif; ?>

    <form method="post" style="background: #fff; padding: 20px; border-radius:6px; border:1px solid #eee;">
        <input type="hidden" name="action" value="change_password">
        <div style="margin-bottom:12px;">
            <label style="font-weight:bold;">Current Password</label>
            <input type="password" name="current_password" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
        </div>
        <div style="margin-bottom:12px;">
            <label style="font-weight:bold;">New Password</label>
            <input type="password" name="new_password" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
        </div>
        <div style="margin-bottom:12px;">
            <label style="font-weight:bold;">Confirm New Password</label>
            <input type="password" name="confirm_password" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
        </div>
        <div>
            <button type="submit" style="background:#007bff;color:#fff;padding:10px 16px;border:none;border-radius:4px;">Update Password</button>
            <a href="dashboard.php" style="margin-left:10px;color:#0066cc;">Back to Dashboard</a>
        </div>
    </form>

    <hr style="margin: 20px 0;">

    <h3>Update Profile</h3>
    <form method="post" style="background: #fff; padding: 20px; border-radius:6px; border:1px solid #eee; margin-top:10px;">
        <input type="hidden" name="action" value="update_profile">
        <div style="margin-bottom:12px;">
            <label style="font-weight:bold;">Username</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($admin['username'] ?? ''); ?>" required style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;">
        </div>
        <div>
            <button type="submit" style="background:#28a745;color:#fff;padding:10px 16px;border:none;border-radius:4px;">Save Profile</button>
            <a href="dashboard.php" style="margin-left:10px;color:#0066cc;">Back to Dashboard</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
