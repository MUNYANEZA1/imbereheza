<?php
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'admin') {
    header("Location: ../member/dashboard.php");
    exit();
}

$message = '';

// Handle Create Loan
if (isset($_POST['create_loan'])) {
    $member_id = $_POST['member_id'];
    $amount = $_POST['amount'];
    $interest_rate = $_POST['interest_rate'];
    $loan_date = $_POST['loan_date'];
    $due_date = $_POST['due_date'];

    $stmt = $pdo->prepare("INSERT INTO loans (member_id, amount, interest_rate, loan_date, due_date, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
    if ($stmt->execute([$member_id, $amount, $interest_rate, $loan_date, $due_date])) {
        $message = "Loan application created successfully!";
    } else {
        $message = "Error creating loan.";
    }
}

// Handle Approve
if (isset($_GET['action']) && $_GET['action'] == 'approve' && isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE loans SET status = 'Approved' WHERE id = ?");
    $stmt->execute([$id]);
    $message = "Loan approved successfully.";
}

// Handle Reject (POST with reason)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_loan'])) {
    $loan_id = isset($_POST['loan_id']) ? intval($_POST['loan_id']) : 0;
    $admin_comment = isset($_POST['admin_comment']) ? trim($_POST['admin_comment']) : '';

    if (empty($admin_comment)) {
        $message = "Error: Please provide a reason for rejection.";
    } else {
        try {
            $stmt = $pdo->prepare("UPDATE loans SET status = 'Rejected', admin_comment = ? WHERE id = ?");
            $stmt->execute([$admin_comment, $loan_id]);
            $message = "Loan rejected successfully.";
        } catch (PDOException $e) {
            $message = "Error rejecting loan: " . htmlspecialchars($e->getMessage());
        }
    }
}

// Search handling
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $pdo->prepare("SELECT l.*, m.full_name FROM loans l JOIN members m ON l.member_id = m.id
        WHERE m.full_name LIKE ? OR CAST(l.id AS CHAR) LIKE ? OR l.status LIKE ? OR m.national_id LIKE ? OR CAST(l.amount AS CHAR) LIKE ?
        ORDER BY l.created_at DESC");
    $stmt->execute([$like, $like, $like, $like, $like]);
} else {
    $stmt = $pdo->query("SELECT l.*, m.full_name FROM loans l JOIN members m ON l.member_id = m.id ORDER BY l.created_at DESC");
}
$loans = $stmt->fetchAll();

// Fetch members for dropdown
$members = $pdo->query("SELECT id, full_name FROM members")->fetchAll();
?>

<h2>Manage Loans</h2>

<?php if ($message): ?>
    <div class="alert alert-success">
        <?php echo htmlspecialchars($message); ?>
    </div>
<?php endif; ?>

<div class="repayment-form">
    <h3>Create New Loan</h3>
    <form method="POST" class="repayment-form-grid">
        <div class="form-group">
            <label>Select Member</label>
            <select name="member_id" required class="form-control">
                <?php foreach ($members as $m): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo htmlspecialchars($m['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Loan Amount (RWF)</label>
            <input type="number" name="amount" step="0.01" required class="form-control">
        </div>
        <div class="form-group">
            <label>Interest Rate (%)</label>
            <input type="number" name="interest_rate" step="0.01" value="0.00" class="form-control">
        </div>
        <div class="form-group">
            <label>Loan Date</label>
            <input type="date" name="loan_date" value="<?php echo date('Y-m-d'); ?>" required class="form-control">
        </div>
        <div class="form-group">
            <label>Due Date</label>
            <input type="date" name="due_date" required class="form-control">
        </div>
        <div class="form-group-full">
            <button type="submit" name="create_loan" class="btn-success">Create Loan</button>
        </div>
    </form>
</div>

<h3>Loan List</h3>
<div style="margin-bottom:12px; display:flex; gap:8px; align-items:center;">
    <form method="get" action="" style="display:flex; gap:8px; align-items:center;">
        <input type="search" name="q" placeholder="Search loans by member, ID, status, national ID or amount" value="<?php echo htmlspecialchars($search ?? ''); ?>" style="padding:8px; border:1px solid #ddd; border-radius:4px; width:420px;">
        <button type="submit" style="background:#0066cc; color:white; padding:8px 12px; border-radius:4px; border:none; cursor:pointer;">Search</button>
        <?php if (!empty($search)): ?>
            <a href="loans.php" style="display:inline-block; margin-left:6px; color:#0066cc; text-decoration:none;">Clear</a>
        <?php endif; ?>
    </form>
</div>
<div class="table-responsive">
<table>
    <thead>
        <tr>
            <th>Member</th>
            <th>Amount</th>
            <th>Interest Rate</th>
            <th>Interest Amount</th>
            <th>Total Payable</th>
            <th>Date</th>
            <th>Due Date</th>
            <th>Reason</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($loans as $loan): ?>
        <tr>
            <td><?php echo htmlspecialchars($loan['full_name']); ?></td>
            <td>RWF <?php echo number_format($loan['amount'], 2); ?></td>
            <td><?php echo $loan['interest_rate']; ?>%</td>
            <?php
                $interest_amount = 0;
                $total_payable = $loan['amount'];
                if (!empty($loan['loan_date']) && !empty($loan['due_date']) && is_numeric($loan['interest_rate'])) {
                    try {
                        $d1 = new DateTime($loan['loan_date']);
                        $d2 = new DateTime($loan['due_date']);
                        $diffDays = (int)$d2->diff($d1)->format('%a');
                        $years = $diffDays / 365.0;
                        $interest_amount = $loan['amount'] * ($loan['interest_rate'] / 100) * $years;
                        $total_payable = $loan['amount'] + $interest_amount;
                    } catch (Exception $e) {
                        // ignore
                    }
                }
            ?>
            <td>RWF <?php echo number_format($interest_amount, 2); ?></td>
            <td>RWF <?php echo number_format($total_payable, 2); ?></td>
            <td><?php echo $loan['loan_date']; ?></td>
            <td><?php echo $loan['due_date']; ?></td>
            <td><small><?php echo htmlspecialchars(substr($loan['reason'] ?? 'N/A', 0, 40)) . (strlen($loan['reason'] ?? '') > 40 ? '...' : ''); ?></small></td>
            <td>
                <span class="status-badge <?php 
                    echo $loan['status'] == 'Approved' ? 'status-approved' : ($loan['status'] == 'Rejected' ? 'status-rejected' : 'status-pending'); 
                ?>">
                    <?php echo $loan['status']; ?>
                </span>
            </td>
            <td>
                <div class="btn-group">
                    <?php if ($loan['status'] == 'Pending'): ?>
                        <a href="loans.php?action=approve&id=<?php echo $loan['id']; ?>" class="btn-success btn-sm">Approve</a>
                        <button type="button" onclick="openRejectModal(<?php echo $loan['id']; ?>)" class="btn-danger btn-sm" style="background: #dc3545; color: white; padding: 6px 12px; border-radius: 3px; border: none; font-size: 12px; font-weight: bold; cursor: pointer;">Reject</button>
                    <?php endif; ?>
                    <a href="repayments.php?loan_id=<?php echo $loan['id']; ?>" class="btn-primary btn-sm">Repayments</a>
                    <a href="edit_loan.php?id=<?php echo $loan['id']; ?>" class="btn-secondary btn-sm">Edit</a>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<!-- Rejection Modal -->
<div id="rejectModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; padding: 30px; border-radius: 6px; box-shadow: 0 4px 20px rgba(0,0,0,0.2); max-width: 500px; width: 90%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0;">Reject Loan</h3>
            <button type="button" onclick="closeRejectModal()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
        </div>

        <form method="POST" style="display: flex; flex-direction: column; gap: 15px;">
            <input type="hidden" name="loan_id" id="rejectLoanId" value="">
            <input type="hidden" name="reject_loan" value="1">

            <div>
                <label style="font-weight: bold; display: block; margin-bottom: 8px;">Reason for Rejection *</label>
                <textarea name="admin_comment" id="rejectReason" required rows="4" style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif;" placeholder="Please explain why this loan is being rejected..."></textarea>
            </div>

            <div style="display: flex; gap: 10px;">
                <button type="submit" style="background: #dc3545; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Reject Loan</button>
                <button type="button" onclick="closeRejectModal()" style="background: #6c757d; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(loanId) {
        document.getElementById('rejectLoanId').value = loanId;
        document.getElementById('rejectReason').value = '';
        document.getElementById('rejectModal').style.display = 'flex';
    }

    function closeRejectModal() {
        document.getElementById('rejectModal').style.display = 'none';
    }

    // Close modal when clicking outside of it
    document.getElementById('rejectModal').addEventListener('click', function(event) {
        if (event.target === this) {
            closeRejectModal();
        }
    });
</script>

<?php include '../includes/footer.php'; ?>
