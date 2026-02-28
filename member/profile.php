<?php
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'member') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Handle password change when member is logged in
$success_msg = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($current) || empty($new) || empty($confirm)) {
        $error_msg = t('profile.update_password'); // or use a specific key
    } elseif ($new !== $confirm) {
        $error_msg = 'New password and confirmation do not match.';
    } elseif (strlen($new) < 6) {
        $error_msg = 'Password must be at least 6 characters long.';
    } else {
        // Verify current password
        $uStmt = $pdo->prepare('SELECT password FROM users WHERE id = ?');
        $uStmt->execute([$_SESSION['user_id']]);
        $user = $uStmt->fetch();
        if (!$user || !password_verify($current, $user['password'])) {
            $error_msg = 'Current password is incorrect.';
        } else {
            // Update password
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

// Get member details
$stmt = $pdo->prepare("SELECT m.*, u.username FROM members m JOIN users u ON m.user_id = u.id WHERE m.user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();
?>

<h2><?php echo t('profile.title'); ?></h2>

<div class="profile-card">
    <table class="profile-table">
        <tr>
            <th><?php echo t('profile.full_name'); ?>:</th>
            <td><?php echo htmlspecialchars($member['full_name']); ?></td>
        </tr>
        <tr>
            <th><?php echo t('profile.username'); ?>:</th>
            <td><?php echo htmlspecialchars($member['username']); ?></td>
        </tr>
        <tr>
            <th><?php echo t('profile.national_id'); ?>:</th>
            <td><?php echo htmlspecialchars($member['national_id']); ?></td>
        </tr>
        <tr>
            <th><?php echo t('profile.phone'); ?>:</th>
            <td><?php echo htmlspecialchars($member['phone']); ?></td>
        </tr>
        <tr>
            <th><?php echo t('profile.gender'); ?>:</th>
            <td><?php echo $member['gender']; ?></td>
        </tr>
        <tr>
            <th><?php echo t('profile.address'); ?>:</th>
            <td><?php echo nl2br(htmlspecialchars($member['address'])); ?></td>
        </tr>
        <tr>
            <th><?php echo t('profile.member_since'); ?>:</th>
            <td><?php echo date('F j, Y', strtotime($member['created_at'])); ?></td>
        </tr>
    </table>
</div>

<h3><?php echo t('profile.change_password'); ?></h3>
<?php if ($success_msg): ?>
    <div class="alert success"><?php echo htmlspecialchars($success_msg); ?></div>
<?php endif; ?>
<?php if ($error_msg): ?>
    <div class="alert error"><?php echo htmlspecialchars($error_msg); ?></div>
<?php endif; ?>

<form method="post" action="">
    <input type="hidden" name="action" value="change_password">
    <div class="form-group">
        <label><?php echo t('profile.current_password'); ?></label>
        <input type="password" name="current_password" required>
    </div>
    <div class="form-group">
        <label><?php echo t('profile.new_password'); ?></label>
        <input type="password" name="new_password" required>
    </div>
    <div class="form-group">
        <label><?php echo t('profile.confirm_password'); ?></label>
        <input type="password" name="confirm_password" required>
    </div>
    <div class="form-group">
        <button type="submit" class="btn-primary"><?php echo t('profile.update_password'); ?></button>
    </div>
</form>

<?php include '../includes/footer.php'; ?>
