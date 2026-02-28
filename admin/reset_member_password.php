<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

$member_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if (!$member_id) {
    header('Location: members.php');
    exit();
}

$stmt = $pdo->prepare("SELECT m.*, u.id as user_id, u.username FROM members m LEFT JOIN users u ON m.user_id = u.id WHERE m.id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();
if (!$member || !$member['user_id']) {
    echo "<div style='padding:20px; background:#f8d7da;'>Member or user account not found. <a href='members.php'>Back</a></div>";
    include '../includes/footer.php';
    exit();
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_password = isset($_POST['new_password']) ? trim($_POST['new_password']) : '';
    $confirm_password = isset($_POST['confirm_password']) ? trim($_POST['confirm_password']) : '';

    if (empty($new_password) || strlen($new_password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        try {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $member['user_id']]);
            $message = 'Password reset successfully for ' . htmlspecialchars($member['full_name']) . '.';
        } catch (PDOException $e) {
            $error = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<div style="max-width: 600px; margin: 0 auto;">
    <h2>Reset Password: <?php echo htmlspecialchars($member['full_name']); ?></h2>

    <?php if ($message): ?>
        <div style="background: #d4edda; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: #f8d7da; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 15px; border-left: 4px solid #0066cc;">
        <p><strong>Username:</strong> <?php echo htmlspecialchars($member['username']); ?></p>
        <p style="margin: 0;"><strong>Full Name:</strong> <?php echo htmlspecialchars($member['full_name']); ?></p>
    </div>

    <form method="POST" style="background: #fff; padding: 20px; border-radius: 6px; border: 1px solid #eee;">
        <div style="margin-bottom: 12px;">
            <label style="font-weight: bold;">New Password *</label>
            <input type="password" name="new_password" required minlength="6" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
            <small style="color: #666;">Minimum 6 characters</small>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold;">Confirm Password *</label>
            <input type="password" name="confirm_password" required minlength="6" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" style="background: #dc3545; color: white; padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Reset Password</button>
            <a href="members.php" style="background: #6c757d; color: white; padding: 10px 16px; border-radius: 4px; text-decoration: none; font-weight: bold;">Back</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
