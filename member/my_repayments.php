<?php
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'member') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Get member ID
$stmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member_id = $stmt->fetchColumn();

// Fetch all repayments for this member along with loan details
$stmt = $pdo->prepare("SELECT r.*, l.amount as loan_amount, l.interest_rate, l.loan_date, l.due_date
                       FROM repayments r 
                       JOIN loans l ON r.loan_id = l.id 
                       WHERE l.member_id = ? 
                       ORDER BY r.payment_date DESC");
$stmt->execute([$member_id]);
$repayments = $stmt->fetchAll();
?>

<h2><?php echo t('repayments.title'); ?></h2>

<div class="table-responsive">
<table>
    <thead>
        <tr>
            <th><?php echo t('repayments.payment_id'); ?></th>
            <th><?php echo t('repayments.loan_id'); ?></th>
            <th><?php echo t('repayments.amount_paid'); ?></th>
            <th><?php echo t('repayments.remaining'); ?></th>
            <th><?php echo t('repayments.payment_date'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($repayments as $r): ?>
        <tr>
            <td>#<?php echo $r['id']; ?></td>
            <td><?php echo t('common.rwf'); ?> <?php echo number_format($r['loan_amount'], 2); ?></td>
            <td><?php echo t('common.rwf'); ?> <?php echo number_format($r['amount_paid'], 2); ?></td>
            <?php
                // calculate total due for loan
                $principal = (float)$r['loan_amount'];
                $rate = (float)$r['interest_rate'];
                $interest = 0;
                if (!empty($r['loan_date']) && !empty($r['due_date']) && $rate > 0) {
                    try {
                        $d1 = new DateTime($r['loan_date']);
                        $d2 = new DateTime($r['due_date']);
                        $diffDays = (int)$d2->diff($d1)->format('%a');
                        $years = $diffDays / 365.0;
                        $interest = $principal * ($rate / 100) * $years;
                    } catch (Exception $e) {
                        // ignore
                    }
                }
                $totalDue = $principal + $interest;
                // sum repayments for this loan
                $stmt2 = $pdo->prepare("SELECT SUM(amount_paid) FROM repayments WHERE loan_id = ?");
                $stmt2->execute([$r['loan_id']]);
                $paidSoFar = (float)$stmt2->fetchColumn();
                $remaining = max(0, $totalDue - $paidSoFar);
            ?>
            <td><?php echo t('common.rwf'); ?> <?php echo number_format($remaining, 2); ?></td>
            <td><?php echo $r['payment_date']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include '../includes/footer.php'; ?>
