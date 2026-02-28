<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

// Fetch stats
$total_members = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
// do not count rejected loans in totals
$total_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status != 'Rejected'")->fetchColumn();
// repaid only on non-rejected loans
$total_repaid = $pdo->query("SELECT SUM(r.amount_paid) FROM repayments r JOIN loans l ON r.loan_id = l.id WHERE l.status != 'Rejected'")->fetchColumn() ?: 0;
$active_loans = $pdo->query("SELECT COUNT(*) FROM loans WHERE status = 'Approved'")->fetchColumn();
$pending_requests = $pdo->query("SELECT COUNT(*) FROM loan_requests WHERE status = 'Pending'")->fetchColumn();

// Get total loan amounts and calculate interest (skip rejected)
$stmt = $pdo->query("SELECT l.*, m.full_name FROM loans l JOIN members m ON l.member_id = m.id WHERE l.status != 'Rejected' ORDER BY l.created_at DESC");
$all_loans = $stmt->fetchAll();

$total_loan_amount = 0;
$total_interest_amount = 0;
foreach ($all_loans as $loan) {
    $total_loan_amount += $loan['amount'];
    if (!empty($loan['loan_date']) && !empty($loan['due_date']) && is_numeric($loan['interest_rate'])) {
        try {
            $d1 = new DateTime($loan['loan_date']);
            $d2 = new DateTime($loan['due_date']);
            $diffDays = (int)$d2->diff($d1)->format('%a');
            $years = $diffDays / 365.0;
            $interest = $loan['amount'] * ($loan['interest_rate'] / 100) * $years;
            $total_interest_amount += $interest;
        } catch (Exception $e) {
            // skip
        }
    }
}
$total_amount_to_collect = $total_loan_amount + $total_interest_amount;

// Recent loans
$recent_loans = array_slice($all_loans, 0, 5);
?>

<h2>Admin Dashboard</h2>

<?php if ($pending_requests > 0): ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <strong>You have <?php echo $pending_requests; ?> pending loan request(s) waiting for review.</strong>
        <a href="loan_requests.php" style="color: #856404; text-decoration: underline; margin-left: 10px;">Review now</a>
    </div>
<?php endif; ?>

<div class="btn-group" style="margin-bottom: 30px;">
    <a href="loan_requests.php" class="btn-warning">Loan Requests</a>
    <a href="members.php" class="btn-primary">Manage Members</a>
    <a href="loans.php" class="btn-info">Manage Loans</a>
    <!-- OTP Logs removed as per user request -->
    <a href="repayments.php" class="btn-success">View Repayments</a>
</div>

<div class="card-grid">
    <div class="card">
        <h3>Total Members</h3>
        <div class="value"><?php echo $total_members; ?></div>
    </div>
    <div class="card">
        <h3>Pending Requests</h3>
        <div class="value" style="color: #ff9800;"><?php echo $pending_requests; ?></div>
    </div>
    <div class="card">
        <h3>Total Loans Issued</h3>
        <div class="value">RWF <?php echo number_format($total_loan_amount, 2); ?></div>
    </div>
    <div class="card">
        <h3>Total Interest</h3>
        <div class="value" style="color: #ff6b6b;">RWF <?php echo number_format($total_interest_amount, 2); ?></div>
    </div>
    <div class="card">
        <h3>Total Repaid</h3>
        <div class="value">RWF <?php echo number_format($total_repaid, 2); ?></div>
    </div>
    <div class="card" style="background: #e8f5e9; border-left: 4px solid #2e7d32;">
        <h3 style="color: #2e7d32;">Total to Collect (Principal + Interest)</h3>
        <div class="value" style="color: #2e7d32; font-size: 18px;">RWF <?php echo number_format($total_amount_to_collect, 2); ?></div>
    </div>
</div>

<h3>Recent Loan Applications</h3>
<div class="table-responsive">
<table>
    <thead>
        <tr>
            <th>Member</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($recent_loans as $loan): ?>
        <tr>
            <td><?php echo htmlspecialchars($loan['full_name']); ?></td>
            <td>RWF <?php echo number_format($loan['amount'], 2); ?></td>
            <td><?php echo $loan['loan_date']; ?></td>
            <td>
                <span class="status-badge <?php 
                    echo $loan['status'] == 'Approved' ? 'status-approved' : ($loan['status'] == 'Rejected' ? 'status-rejected' : 'status-pending'); 
                ?>">
                    <?php echo $loan['status']; ?>
                </span>
            </td>
            <td>
                <a href="loans.php?id=<?php echo $loan['id']; ?>" class="btn-primary btn-sm">View</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include '../includes/footer.php'; ?>
