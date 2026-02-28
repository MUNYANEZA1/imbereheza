<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'member') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Get member details
$stmt = $pdo->prepare("SELECT * FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member = $stmt->fetch();

if (!$member) {
    echo "<h2>Error: Member profile not found.</h2>";
    include '../includes/footer.php';
    exit();
}

$member_id = $member['id'];

// Fetch stats for this member
$stmt = $pdo->prepare("SELECT COUNT(*) FROM loans WHERE member_id = ?");
$stmt->execute([$member_id]);
$total_loans = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT * FROM loans WHERE member_id = ? AND status != 'Rejected'");
$stmt->execute([$member_id]);
$all_loans = $stmt->fetchAll();

$total_loan_amount = 0;
$total_interest = 0;
foreach ($all_loans as $loan) {
    $amount = (float)$loan['amount'];
    $total_loan_amount += $amount;
    if (!empty($loan['loan_date']) && !empty($loan['due_date']) && is_numeric($loan['interest_rate'])) {
        try {
            $d1 = new DateTime($loan['loan_date']);
            $d2 = new DateTime($loan['due_date']);
            $diffDays = (int)$d2->diff($d1)->format('%a');
            $years = $diffDays / 365.0;
            $interest_amount = $amount * ($loan['interest_rate'] / 100) * $years;
            $total_interest += $interest_amount;
        } catch (Exception $e) {
            // skip
        }
    }
}

$total_amount_to_pay = $total_loan_amount + $total_interest;

// only count repayments for non‑rejected loans
$stmt = $pdo->prepare("SELECT SUM(r.amount_paid) FROM repayments r 
    JOIN loans l ON r.loan_id = l.id 
    WHERE l.member_id = ? AND l.status != 'Rejected'");
$stmt->execute([$member_id]);
$total_repaid = $stmt->fetchColumn() ?: 0;

// remaining balance is simply total amount due minus what has been paid
$balance = max(0, $total_amount_to_pay - $total_repaid);


// Get pending loan requests
$stmt = $pdo->prepare("SELECT COUNT(*) FROM loan_requests WHERE member_id = ? AND status = 'Pending'");
$stmt->execute([$member_id]);
$pending_requests = $stmt->fetchColumn();

// Recent loans for this member (not needed on dashboard anymore)
// the detailed list is available on My Loans page

?>

<h2><?php echo t('dashboard.title'); ?></h2>
<p><?php echo t('dashboard.welcome'); ?>, <?php echo htmlspecialchars($member['full_name']); ?>!</p>

<?php if ($pending_requests > 0): ?>
    <div style="background: #fff3cd; border: 1px solid #ffc107; color: #856404; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <strong><?php echo t('dashboard.pending_requests'); ?> <?php echo $pending_requests; ?> <?php echo t('dashboard.pending_requests_suffix'); ?></strong>
        <a href="my_loan_requests.php" style="color: #856404; text-decoration: underline; margin-left: 10px;"><?php echo t('dashboard.view_requests'); ?></a>
    </div>
<?php endif; ?>

<div class="btn-group" style="margin-bottom: 30px;">
    <a href="request_loan.php" class="btn-primary"><?php echo t('buttons.request_loan'); ?></a>
    <a href="my_loans.php" class="btn-primary"><?php echo t('buttons.view_loans'); ?></a>
    <a href="my_repayments.php" class="btn-success"><?php echo t('buttons.view_repayments'); ?></a>
    <a href="profile.php" class="btn-info"><?php echo t('buttons.profile'); ?></a>
</div>

<div class="card-grid">
    <div class="card">
        <h3><?php echo t('cards.total_loans'); ?></h3>
        <div class="value"><?php echo $total_loans; ?></div>
    </div>
    <div class="card">
        <h3><?php echo t('cards.total_borrowed'); ?></h3>
        <div class="value"><?php echo t('common.rwf'); ?> <?php echo number_format($total_loan_amount, 2); ?></div>
    </div>
    <div class="card">
        <h3><?php echo t('cards.total_interest'); ?></h3>
        <div class="value" style="color: #ff6b6b;"><?php echo t('common.rwf'); ?> <?php echo number_format($total_interest, 2); ?></div>
    </div>
    <div class="card">
        <h3><?php echo t('cards.total_repaid'); ?></h3>
        <div class="value"><?php echo t('common.rwf'); ?> <?php echo number_format($total_repaid, 2); ?></div>
    </div>
    <div class="card">
        <h3><?php echo t('cards.total_amount_to_pay'); ?></h3>
        <div class="value"><?php echo t('common.rwf'); ?> <?php echo number_format($total_amount_to_pay, 2); ?></div>
    </div>
    <div class="card">
        <h3><?php echo t('cards.remaining_balance'); ?></h3>
        <div class="value"><?php echo t('common.rwf'); ?> <?php echo number_format($balance, 2); ?></div>
    </div>
</div>


<?php include '../includes/footer.php'; ?>
