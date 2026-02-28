<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

$loan_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);
if (!$loan_id) {
    header('Location: loans.php');
    exit();
}

// Fetch loan
$stmt = $pdo->prepare("SELECT l.*, m.full_name FROM loans l JOIN members m ON l.member_id = m.id WHERE l.id = ?");
$stmt->execute([$loan_id]);
$loan = $stmt->fetch();
if (!$loan) {
    echo "<div style='padding:20px; background:#f8d7da;'>Loan not found. <a href='loans.php'>Back</a></div>";
    include '../includes/footer.php';
    exit();
}

$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $interest_rate = isset($_POST['interest_rate']) ? floatval($_POST['interest_rate']) : 0;
    $due_date = isset($_POST['due_date']) ? $_POST['due_date'] : $loan['due_date'];
    $admin_comment = isset($_POST['admin_comment']) ? trim($_POST['admin_comment']) : '';

    try {
        $stmt = $pdo->prepare("UPDATE loans SET interest_rate = ?, due_date = ?, admin_comment = ? WHERE id = ?");
        $stmt->execute([$interest_rate, $due_date, $admin_comment, $loan_id]);
        $message = 'Loan updated successfully.';
        // refresh loan
        $stmt = $pdo->prepare("SELECT l.*, m.full_name FROM loans l JOIN members m ON l.member_id = m.id WHERE l.id = ?");
        $stmt->execute([$loan_id]);
        $loan = $stmt->fetch();
    } catch (PDOException $e) {
        $error = 'Error updating loan: ' . htmlspecialchars($e->getMessage());
    }
}
?>

<div style="max-width:800px; margin:0 auto;">
    <h2>Edit Loan #<?php echo $loan['id']; ?> - <?php echo htmlspecialchars($loan['full_name']); ?></h2>

    <?php if ($message): ?>
        <div style="background:#d4edda; padding:12px; border-radius:4px; margin-bottom:12px;"><?php echo $message; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background:#f8d7da; padding:12px; border-radius:4px; margin-bottom:12px;"><?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" style="background:#fff; padding:20px; border-radius:6px; border:1px solid #eee;">
        <input type="hidden" name="id" value="<?php echo $loan['id']; ?>">
        <div style="margin-bottom:12px;">
            <label style="font-weight:bold;">Amount (RWF)</label>
            <div style="padding:10px; background:#f5f5f5; border-radius:4px;">RWF <?php echo number_format($loan['amount'],2); ?></div>
        </div>

        <div style="margin-bottom:12px;">
            <label style="font-weight:bold;">Interest Rate (%)</label>
            <input type="number" name="interest_rate" step="0.01" value="<?php echo htmlspecialchars($loan['interest_rate']); ?>" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
        </div>

        <div style="margin-bottom:12px;">
            <label style="font-weight:bold;">Due Date</label>
            <input type="date" name="due_date" value="<?php echo htmlspecialchars($loan['due_date']); ?>" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;">
        </div>

        <div style="margin-bottom:12px;">
            <label style="font-weight:bold;">Admin Comment</label>
            <textarea name="admin_comment" rows="4" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:4px;"><?php echo htmlspecialchars($loan['admin_comment']); ?></textarea>
        </div>

        <div style="display:flex; gap:10px; align-items:center; margin-top:15px;">
            <button type="submit" class="btn-success" style="padding:10px 16px;">Save Changes</button>
            <a href="loans.php" class="btn-secondary" style="padding:10px 16px; text-decoration:none;">Back</a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
