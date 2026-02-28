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

$stmt = $pdo->prepare("SELECT m.*, u.username FROM members m LEFT JOIN users u ON m.user_id = u.id WHERE m.id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();
if (!$member) {
    echo "<div style='padding:20px; background:#f8d7da;'>Member not found. <a href='members.php'>Back</a></div>";
    include '../includes/footer.php';
    exit();
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = isset($_POST['full_name']) ? trim($_POST['full_name']) : '';
    $national_id = isset($_POST['national_id']) ? trim($_POST['national_id']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    $address = isset($_POST['address']) ? trim($_POST['address']) : '';
    $gender = isset($_POST['gender']) ? $_POST['gender'] : NULL;

    if (empty($full_name) || empty($national_id)) {
        $error = 'Full name and national ID are required.';
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE members SET full_name = ?, national_id = ?, phone = ?, address = ?, gender = ? WHERE id = ?");
            $stmt->execute([$full_name, $national_id, $phone, $address, $gender, $member_id]);
            $message = 'Member details updated successfully.';
            // refresh
            $stmt = $pdo->prepare("SELECT m.*, u.username FROM members m LEFT JOIN users u ON m.user_id = u.id WHERE m.id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
        } catch (PDOException $e) {
            $error = 'Error: ' . htmlspecialchars($e->getMessage());
        }
    }
}
?>

<div style="max-width: 800px; margin: 0 auto;">
    <h2>Edit Member: <?php echo htmlspecialchars($member['full_name']); ?></h2>

    <?php if ($message): ?>
        <div style="background: #d4edda; padding: 12px; border-radius: 4px; margin-bottom: 12px;"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: #f8d7da; padding: 12px; border-radius: 4px; margin-bottom: 12px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" style="background: #fff; padding: 20px; border-radius: 6px; border: 1px solid #eee;">
        <div style="margin-bottom: 12px;">
            <label style="font-weight: bold;">Full Name *</label>
            <input type="text" name="full_name" value="<?php echo htmlspecialchars($member['full_name']); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 12px;">
            <label style="font-weight: bold;">National ID *</label>
            <input type="text" name="national_id" value="<?php echo htmlspecialchars($member['national_id']); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 12px;">
            <label style="font-weight: bold;">Phone</label>
            <input type="text" name="phone" value="<?php echo htmlspecialchars($member['phone'] ?? ''); ?>" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
        </div>

        <div style="margin-bottom: 12px;">
            <label style="font-weight: bold;">Gender</label>
            <select name="gender" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                <option value="">Select...</option>
                <option value="Male" <?php echo $member['gender'] === 'Male' ? 'selected' : ''; ?>>Male</option>
                <option value="Female" <?php echo $member['gender'] === 'Female' ? 'selected' : ''; ?>>Female</option>
                <option value="Other" <?php echo $member['gender'] === 'Other' ? 'selected' : ''; ?>>Other</option>
            </select>
        </div>

        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold;">Address</label>
            <textarea name="address" style="width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; height: 100px;"><?php echo htmlspecialchars($member['address'] ?? ''); ?></textarea>
        </div>

        <div style="display: flex; gap: 10px;">
            <button type="submit" style="background: #28a745; color: white; padding: 10px 16px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Save Changes</button>
            <a href="members.php" style="background: #6c757d; color: white; padding: 10px 16px; border-radius: 4px; text-decoration: none; font-weight: bold;">Back</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
