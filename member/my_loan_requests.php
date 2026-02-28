<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'member') {
    header("Location: ../admin/dashboard.php");
    exit();
}

// Get member details
$stmt = $pdo->prepare("SELECT id FROM members WHERE user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$member_id = $stmt->fetchColumn();

if (!$member_id) {
    echo "<h2>Error: Member profile not found.</h2>";
    include '../includes/footer.php';
    exit();
}

// Fetch all loan requests for this member
$stmt = $pdo->prepare("SELECT * FROM loan_requests WHERE member_id = ? ORDER BY request_date DESC");
$stmt->execute([$member_id]);
$requests = $stmt->fetchAll();
?>

<div style="max-width: 1000px; margin: 0 auto;">
    <h2><?php echo t('requests.my_requests'); ?></h2>

    <?php if (!empty($requests)): ?>
        <div style="overflow-x: auto; background: white; border-radius: 5px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                        <th style="padding: 12px; text-align: left; font-weight: bold;"><?php echo t('loans.loan_id'); ?></th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;"><?php echo t('loans.amount'); ?> (RWF)</th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;"><?php echo t('requests.purpose'); ?></th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;"><?php echo t('requests.repayment_period'); ?></th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;"><?php echo t('requests.request_date'); ?></th>
                        <th style="padding: 12px; text-align: left; font-weight: bold;"><?php echo t('loans.status'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($requests as $request): ?>
                        <tr style="border-bottom: 1px solid #eee; hover: background #f9f9f9;">
                            <td style="padding: 12px; font-weight: bold;">#<?php echo $request['id']; ?></td>
                            <td style="padding: 12px; font-weight: bold; color: #ff6b6b;">
                                <?php echo t('common.rwf'); ?> <?php echo number_format($request['amount_requested'], 2); ?>
                            </td>
                            <td style="padding: 12px;">
                                <small><?php echo htmlspecialchars(substr($request['purpose'], 0, 50)) . (strlen($request['purpose']) > 50 ? '...' : ''); ?></small>
                            </td>
                            <td style="padding: 12px; text-align: center;">
                                <?php echo $request['repayment_period']; ?> <?php echo t('requests.repayment_period'); ?>
                            </td>
                            <td style="padding: 12px;">
                                <?php echo date('Y-m-d', strtotime($request['request_date'])); ?>
                            </td>
                            <td style="padding: 12px;">
                                <span style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;
                                           background: <?php 
                                               echo $request['status'] == 'Approved' ? '#d4edda' : 
                                                    ($request['status'] == 'Rejected' ? '#f8d7da' : '#fff3cd');
                                           ?>;
                                           color: <?php 
                                               echo $request['status'] == 'Approved' ? '#155724' : 
                                                    ($request['status'] == 'Rejected' ? '#721c24' : '#856404');
                                           ?>;">
                                    <?php 
                                        echo $request['status'] == 'Pending' ? 'Pending' : 
                                             ($request['status'] == 'Approved' ? 'Approved' : 'Rejected');
                                    ?>
                                </span>

                                <?php if ($request['status'] == 'Approved'): ?>
                                    <br><small style="color: #666; margin-top: 5px;"><?php echo t('requests.submit'); ?></small>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <a href="dashboard.php" style="display: inline-block; margin-top: 20px; color: #0066cc; text-decoration: none;">
            <?php echo t('common.back'); ?>
        </a>
    <?php else: ?>
        <div style="background: #f0f0f0; padding: 40px; text-align: center; border-radius: 5px;">
            <p style="font-size: 18px; color: #666; margin-bottom: 10px;">
                <?php echo t('requests.no_requests'); ?>
            </p>
            <a href="request_loan.php" style="background: #28a745; color: white; padding: 10px 20px; border-radius: 4px; text-decoration: none; display: inline-block;">
                <?php echo t('buttons.request_loan'); ?>
            </a>
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
