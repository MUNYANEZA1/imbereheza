<?php
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

// Handle report downloads
if (isset($_GET['download'])) {
    $report_type = $_GET['download'];
    
    // Disable output buffering for downloads
    if (ob_get_level()) {
        ob_end_clean();
    }
    
    if ($report_type === 'members') {
        // Members CSV export
        $stmt = $pdo->query("SELECT m.id, m.full_name, m.national_id, m.phone, m.address, m.gender, m.created_at, u.username 
                             FROM members m LEFT JOIN users u ON m.user_id = u.id ORDER BY m.full_name");
        $members = $stmt->fetchAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="members_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Full Name', 'National ID', 'Phone', 'Address', 'Gender', 'Username', 'Created Date']);
        
        foreach ($members as $row) {
            fputcsv($output, [
                $row['id'],
                $row['full_name'],
                $row['national_id'],
                $row['phone'],
                $row['address'],
                $row['gender'],
                $row['username'] ?? 'Not assigned',
                date('Y-m-d', strtotime($row['created_at']))
            ]);
        }
        fclose($output);
        exit();
        
    } elseif ($report_type === 'loans') {
        // Loans CSV export with interest calculations
        $stmt = $pdo->query("SELECT l.id, l.amount, l.interest_rate, l.loan_date, l.due_date, l.status, m.full_name
                             FROM loans l JOIN members m ON l.member_id = m.id ORDER BY l.loan_date DESC");
        $loans = $stmt->fetchAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="loans_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Loan ID', 'Member', 'Amount', 'Interest Rate (%)', 'Interest Amount', 'Total Payable', 'Loan Date', 'Due Date', 'Status']);
        
        foreach ($loans as $row) {
            $interest = 0;
            if (!empty($row['loan_date']) && !empty($row['due_date'])) {
                $d1 = new DateTime($row['loan_date']);
                $d2 = new DateTime($row['due_date']);
                $diffDays = (int)$d2->diff($d1)->format('%a');
                $interest = $row['amount'] * ($row['interest_rate'] / 100) * ($diffDays / 365.0);
            }
            fputcsv($output, [
                $row['id'],
                $row['full_name'],
                $row['amount'],
                $row['interest_rate'],
                round($interest, 2),
                round($row['amount'] + $interest, 2),
                $row['loan_date'],
                $row['due_date'],
                $row['status']
            ]);
        }
        fclose($output);
        exit();
        
    } elseif ($report_type === 'repayments') {
        // Repayments CSV export
        $stmt = $pdo->query("SELECT r.id, r.amount_paid, r.payment_date, m.full_name, l.id as loan_id
                             FROM repayments r JOIN loans l ON r.loan_id = l.id JOIN members m ON l.member_id = m.id 
                             ORDER BY r.payment_date DESC");
        $repayments = $stmt->fetchAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="repayments_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['Payment ID', 'Member', 'Loan ID', 'Amount Paid', 'Payment Date']);
        
        foreach ($repayments as $row) {
            fputcsv($output, [
                $row['id'],
                $row['full_name'],
                $row['loan_id'],
                $row['amount_paid'],
                date('Y-m-d', strtotime($row['payment_date']))
            ]);
        }
        fclose($output);
        exit();
        
    } elseif ($report_type === 'transactions') {
        // Transactions CSV export
        $stmt = $pdo->query("SELECT t.id, t.type, t.amount, t.description, t.transaction_date, m.full_name
                             FROM transactions t
                             LEFT JOIN loans l ON t.related_loan_id = l.id
                             LEFT JOIN repayments r ON t.related_repayment_id = r.id
                             LEFT JOIN members m ON COALESCE(l.member_id, (SELECT member_id FROM loans WHERE id = r.loan_id)) = m.id
                             ORDER BY t.transaction_date DESC");
        $transactions = $stmt->fetchAll();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="financial_transactions_report_' . date('Y-m-d') . '.csv"');
        
        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Type', 'Amount', 'Description', 'Date', 'Related Member']);
        
        foreach ($transactions as $row) {
            fputcsv($output, [
                $row['id'],
                $row['type'],
                $row['amount'],
                $row['description'],
                date('Y-m-d H:i:s', strtotime($row['transaction_date'])),
                $row['full_name'] ?? 'N/A'
            ]);
        }
        fclose($output);
        exit();
    }
}

// Handle manual transaction addition
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_transaction'])) {
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);
    $description = trim($_POST['description']);
    $transaction_date = $_POST['transaction_date'];
    
    if ($amount <= 0) {
        $message = "Amount must be greater than 0.";
    } elseif (empty($description)) {
        $message = "Description is required.";
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO transactions (type, amount, description, transaction_date) VALUES (?, ?, ?, ?)");
            $stmt->execute([$type, $amount, $description, $transaction_date]);
            $message = "Transaction added successfully!";
        } catch (Exception $e) {
            $message = "Error adding transaction: " . $e->getMessage();
        }
    }
}

// Financial Reports
$total_members = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$total_expense = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'expense'")->fetchColumn() ?: 0;
$total_income = $pdo->query("SELECT SUM(amount) FROM transactions WHERE type = 'income'")->fetchColumn() ?: 0;
$current_balance = $total_income - $total_expense;

// Members with active loans
$stmt = $pdo->query("SELECT m.full_name, l.amount, (SELECT SUM(amount_paid) FROM repayments WHERE loan_id = l.id) as paid 
                     FROM loans l JOIN members m ON l.member_id = m.id 
                     WHERE l.status = 'Approved'");
$active_loan_members = $stmt->fetchAll();

// Overdue loans (simplified: status is Approved and due_date < today)
$today = date('Y-m-d');
$stmt = $pdo->query("SELECT m.full_name, l.amount, l.due_date 
                     FROM loans l JOIN members m ON l.member_id = m.id 
                     WHERE l.status = 'Approved' AND l.due_date < '$today'");
$overdue_loans = $stmt->fetchAll();
?>

<h2>Financial Reports</h2>

<?php if ($message): ?>
    <div style="background: <?php echo strpos($message, 'successfully') !== false ? '#d4edda' : '#f8d7da'; ?>; border: 1px solid <?php echo strpos($message, 'successfully') !== false ? '#c3e6cb' : '#f5c6cb'; ?>; color: <?php echo strpos($message, 'successfully') !== false ? '#155724' : '#721c24'; ?>; padding: 15px; margin-bottom: 20px; border-radius: 4px;">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div style="margin-bottom: 25px; padding: 15px; background: #f9f9f9; border-radius: 4px; border: 1px solid #ddd;">
    <h3 style="margin-top: 0; margin-bottom: 15px;">Download Reports</h3>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="reports.php?download=members" style="background: #28a745; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;">Download Members CSV</a>
        <a href="reports.php?download=loans" style="background: #0066cc; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;">Download Loans CSV</a>
        <a href="reports.php?download=repayments" style="background: #ff9800; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;">Download Repayments CSV</a>
        <a href="reports.php?download=transactions" style="background: #17a2b8; color: white; padding: 10px 15px; border-radius: 4px; text-decoration: none; font-weight: bold;">Download Financial Transactions CSV</a>
    </div>
</div>

<div class="card-grid">
    <div class="card">
        <h3>Total Members</h3>
        <div class="value"><?php echo $total_members; ?></div>
    </div>
    <div class="card">
        <h3>Total Expenses</h3>
        <div class="value">RWF <?php echo number_format($total_expense, 2); ?></div>
    </div>
    <div class="card">
        <h3>Total Income</h3>
        <div class="value">RWF <?php echo number_format($total_income, 2); ?></div>
    </div>
</div>

<h3>Members with Active Loans</h3>
<div class="table-responsive">
<table>
    <thead>
        <tr>
            <th>Member</th>
            <th>Loan Amount</th>
            <th>Paid</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($active_loan_members as $m): ?>
        <tr>
            <td><?php echo htmlspecialchars($m['full_name']); ?></td>
            <td>RWF <?php echo number_format($m['amount'], 2); ?></td>
            <td>RWF <?php echo number_format($m['paid'] ?: 0, 2); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<div style="margin: 30px 0; padding: 20px; background: #f8f9fa; border: 2px solid #dee2e6; border-radius: 8px; text-align: center;">
    <h3 style="margin: 0; color: #495057;">Current Company Balance</h3>
    <div style="font-size: 24px; font-weight: bold; margin-top: 10px; color: <?php echo $current_balance >= 0 ? '#28a745' : '#dc3545'; ?>">
        RWF <?php echo number_format($current_balance, 2); ?>
    </div>
</div>

<h3>Add Manual Transaction</h3>
<div style="margin-bottom: 30px; padding: 20px; background: #f9f9f9; border-radius: 8px; border: 1px solid #ddd;">
    <form method="POST" style="display: grid; grid-template-columns: 1fr 1fr 2fr 1fr auto; gap: 15px; align-items: end;">
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Type</label>
            <select name="type" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                <option value="income">Income</option>
                <option value="expense">Expense</option>
            </select>
        </div>
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Amount (RWF)</label>
            <input type="number" name="amount" step="0.01" min="0.01" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Description</label>
            <input type="text" name="description" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;" placeholder="e.g., Initial capital, Donation, Office supplies">
        </div>
        <div>
            <label style="display: block; margin-bottom: 5px; font-weight: bold;">Date</label>
            <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
        </div>
        <div>
            <button type="submit" name="add_transaction" style="background: #007bff; color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: bold;">Add Transaction</button>
        </div>
    </form>
</div>

<h3>Overdue Loans</h3>
        <div class="table-responsive">
        <table>
            <thead>
                <tr>
                    <th>Member</th>
                    <th>Amount</th>
                    <th>Due Date</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overdue_loans as $ol): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ol['full_name']); ?></td>
                    <td>RWF <?php echo number_format($ol['amount'], 2); ?></td>
                    <td style="color: red;"><?php echo $ol['due_date']; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
