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

// Fetch all loans for this member with request details
$stmt = $pdo->prepare("SELECT l.*, COALESCE(lr.purpose, 'N/A') as purpose 
                        FROM loans l 
                        LEFT JOIN loan_requests lr ON l.request_id = lr.id 
                        WHERE l.member_id = ? 
                        ORDER BY l.created_at DESC");
$stmt->execute([$member_id]);
$loans = $stmt->fetchAll();
?>

<h2><?php echo t('loans.title'); ?></h2>

<div class="table-responsive">
<table>
    <thead>
        <tr>
            <th><?php echo t('loans.loan_id'); ?></th>
            <th><?php echo t('loans.amount'); ?></th>
            <th><?php echo t('loans.interest_rate'); ?></th>
            <th><?php echo t('loans.interest_amount'); ?></th>
            <th><?php echo t('loans.total_payable'); ?></th>
            <th><?php echo t('cards.remaining_balance'); ?></th>
            <th><?php echo t('loans.date'); ?></th>
            <th><?php echo t('loans.due_date'); ?></th>
            <th><?php echo t('loans.purpose'); ?></th>
            <th><?php echo t('loans.status'); ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($loans as $loan): ?>
        <tr>
            <td>#<?php echo $loan['id']; ?></td>
            <td><?php echo t('common.rwf'); ?> <?php echo number_format($loan['amount'], 2); ?></td>
            <td><?php echo $loan['interest_rate']; ?>%</td>
            <?php
                // Calculate interest amount and total payable based on loan period
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
                        // fallback keep zeros
                    }
                }
            ?>
            <td><?php echo t('common.rwf'); ?> <?php echo number_format($interest_amount, 2); ?></td>
            <td><?php echo t('common.rwf'); ?> <?php echo number_format($total_payable, 2); ?></td>
            <?php
                // calculate total paid for this loan
                $stmt2 = $pdo->prepare("SELECT SUM(amount_paid) FROM repayments WHERE loan_id = ?");
                $stmt2->execute([$loan['id']]);
                $paid = (float)$stmt2->fetchColumn();
                $remaining = max(0, $total_payable - $paid);
            ?>
            <td><?php echo t('common.rwf'); ?> <?php echo number_format($remaining, 2); ?></td>
            <td><?php echo $loan['loan_date']; ?></td>
            <td><?php echo $loan['due_date']; ?></td>
            <td><small><?php echo htmlspecialchars(substr($loan['purpose'], 0, 40)) . (strlen($loan['purpose']) > 40 ? '...' : ''); ?></small></td>
            <td>
                <span style="padding: 0.2rem 0.5rem; border-radius: 3px; font-size: 0.8rem; background: <?php 
                    echo $loan['status'] == 'Approved' ? '#d4edda' : ($loan['status'] == 'Rejected' ? '#f8d7da' : '#fff3cd'); 
                ?>">
                    <?php echo $loan['status']; ?>
                </span>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
</div>

<h3 style="margin-top: 30px;"><?php echo t('loans.loan_breakdown'); ?></h3>
<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr)); gap: 20px;">
    <?php foreach ($loans as $loan): ?>
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
                    // fallback
                }
            }
        ?>
        <div style="background: white; padding: 20px; border-radius: 5px; border-left: 4px solid #0066cc; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h4 style="margin-top: 0; color: #0066cc;"><?php echo t('loans.loan_id'); ?> #<?php echo $loan['id']; ?></h4>
            <p style="margin: 10px 0; color: #666;"><small><?php echo htmlspecialchars($loan['purpose']); ?></small></p>
            
            <div style="background: #f9f9f9; padding: 15px; border-radius: 4px; margin-bottom: 15px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #ddd;">
                    <span style="font-weight: bold;"><?php echo t('loans.principal'); ?>:</span>
                    <span style="font-weight: bold; color: #0066cc;"><?php echo t('common.rwf'); ?> <?php echo number_format($loan['amount'], 2); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #ddd;">
                    <span><?php echo t('loans.interest_rate'); ?>:</span>
                    <span><?php echo $loan['interest_rate']; ?>%</span>
                </div>
                
                <div style="display: flex; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px solid #ddd;">
                    <span><?php echo t('loans.interest_amount'); ?>:</span>
                    <span style="color: #ff6b6b; font-weight: bold;"><?php echo t('common.rwf'); ?> <?php echo number_format($interest_amount, 2); ?></span>
                </div>
                
                <div style="display: flex; justify-content: space-between; background: #e8f5e9; padding: 10px; border-radius: 3px;">
                    <span style="font-weight: bold; color: #2e7d32;"><?php echo t('loans.total_payable'); ?>:</span>
                    <span style="font-weight: bold; color: #2e7d32; font-size: 16px;"><?php echo t('common.rwf'); ?> <?php echo number_format($total_payable, 2); ?></span>
                </div>
            </div>
            
            <div style="font-size: 12px; color: #666;">
                <p style="margin: 5px 0;"><?php echo t('loans.loan_id'); ?>: <?php echo $loan['loan_date']; ?> to <?php echo $loan['due_date']; ?></p>
                <p style="margin: 5px 0;"><?php echo t('loans.status'); ?>: 
                    <span style="display: inline-block; padding: 3px 8px; border-radius: 3px; background: <?php 
                        echo $loan['status'] == 'Approved' ? '#d4edda' : ($loan['status'] == 'Rejected' ? '#f8d7da' : '#fff3cd'); 
                    ?>; color: <?php
                        echo $loan['status'] == 'Approved' ? '#155724' : ($loan['status'] == 'Rejected' ? '#721c24' : '#856404');
                    ?>;">
                        <?php echo $loan['status']; ?>
                    </span>
                </p>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php include '../includes/footer.php'; ?>
