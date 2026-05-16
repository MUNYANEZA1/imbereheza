<?php
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

$message = '';
$selected_loan_id = isset($_GET['loan_id']) ? $_GET['loan_id'] : null;

// Handle Record Repayment
if (isset($_POST['record_repayment'])) {
    $loan_id = $_POST['loan_id'];
    $amount_paid = $_POST['amount_paid'];
    $payment_date = $_POST['payment_date'];

    try {
        $pdo->beginTransaction();
        
        // Insert repayment
        $stmt = $pdo->prepare("INSERT INTO repayments (loan_id, amount_paid, payment_date) VALUES (?, ?, ?)");
        $stmt->execute([$loan_id, $amount_paid, $payment_date]);

        // Record income transaction
        $repayment_id = $pdo->lastInsertId();
        $stmt = $pdo->prepare("INSERT INTO transactions (type, amount, description, related_repayment_id) VALUES ('income', ?, 'Repayment from member', ?)");
        $stmt->execute([$amount_paid, $repayment_id]);

        // Check if loan is fully repaid (principal + interest)
        $stmt = $pdo->prepare("SELECT amount, interest_rate, loan_date, due_date,
            (SELECT SUM(amount_paid) FROM repayments WHERE loan_id = ?) as total_paid
            FROM loans WHERE id = ?");
        $stmt->execute([$loan_id, $loan_id]);
        $loan_info = $stmt->fetch();

        if ($loan_info) {
            $principal = (float)$loan_info['amount'];
            $rate = (float)$loan_info['interest_rate'];
            $interest = 0.0;
            if (!empty($loan_info['loan_date']) && !empty($loan_info['due_date']) && $rate > 0) {
                try {
                    $d1 = new DateTime($loan_info['loan_date']);
                    $d2 = new DateTime($loan_info['due_date']);
                    $diffDays = (int)$d2->diff($d1)->format('%a');
                    $years = $diffDays / 365.0;
                    $interest = $principal * ($rate / 100) * $years;
                } catch (Exception $e) {
                    // ignore and keep interest 0
                }
            }
            $totalDue = $principal + $interest;
            if ((float)$loan_info['total_paid'] >= $totalDue) {
                $stmt = $pdo->prepare("UPDATE loans SET status = 'Completed' WHERE id = ?");
                $stmt->execute([$loan_id]);
            }
        }

        $pdo->commit();
        $message = "Repayment recorded successfully!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $message = "Error: " . $e->getMessage();
    }
}

// Fetch approved loans for dropdown
$approved_loans = $pdo->query("SELECT l.id, m.full_name, l.amount FROM loans l JOIN members m ON l.member_id = m.id WHERE l.status = 'Approved'")->fetchAll();

// Fetch repayment history
$query = "SELECT r.*, m.full_name, l.amount as loan_amount FROM repayments r 
          JOIN loans l ON r.loan_id = l.id 
          JOIN members m ON l.member_id = m.id";
if ($selected_loan_id) {
    $query .= " WHERE r.loan_id = " . intval($selected_loan_id);
}
$query .= " ORDER BY r.payment_date DESC";
$repayments = $pdo->query($query)->fetchAll();
?>

<h2>Loan Repayments</h2>

<?php if ($message): ?>
    <div class="alert alert-success" style="margin-bottom: 20px;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="repayment-form">
    <h3>Record New Repayment</h3>
    <form method="POST" class="repayment-form-grid">
        <div class="form-group">
            <label>Select Loan</label>
            <select name="loan_id" required class="form-control">
                <?php foreach ($approved_loans as $al): ?>
                    <option value="<?php echo $al['id']; ?>" <?php echo ($selected_loan_id == $al['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($al['full_name']); ?> (RWF <?php echo number_format($al['amount'], 2); ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Amount Paid (RWF)</label>
            <input type="number" name="amount_paid" step="0.01" required class="form-control">
        </div>
        <div class="form-group">
            <label>Payment Date</label>
            <input type="date" name="payment_date" value="<?php echo date('Y-m-d'); ?>" required class="form-control">
        </div>
        <div class="form-group-full">
            <button type="submit" name="record_repayment" class="btn-success">Record Repayment</button>
        </div>
    </form>
</div>

<h3>Repayment History <?php echo $selected_loan_id ? "(Loan #$selected_loan_id)" : ""; ?></h3>
<div class="table-responsive">
<table>
    <thead>
        <tr>
            <th>Member</th>
            <th>Loan Amount</th>
            <th>Amount Paid</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($repayments as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['full_name']); ?></td>
            <td>RWF <?php echo number_format($r['loan_amount'], 2); ?></td>
            <td>RWF <?php echo number_format($r['amount_paid'], 2); ?></td>
            <td><?php echo $r['payment_date']; ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<?php include '../includes/footer.php'; ?>
