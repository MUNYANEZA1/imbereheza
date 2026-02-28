<?php
session_start();
require_once '../config/db.php';
include '../includes/header.php';

if ($_SESSION['role'] != 'member') {
    header("Location: ../admin/dashboard.php");
    exit();
}
// Members should change their password while logged in via their profile page.
header('Location: profile.php');
exit();
?>

<div style="max-width: 900px; margin: 0 auto;">
    <h2>Password Reset Request</h2>

    <?php if ($message): ?>
        <div style="background: #d4edda; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
            <?php echo $message; ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div style="background: #f8d7da; padding: 12px; border-radius: 4px; margin-bottom: 12px;">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div style="background: #fff9e6; padding: 20px; border-radius: 6px; border-left: 4px solid #ff9800; margin-bottom: 20px;">
        <h3 style="margin-top: 0;">Request Procedure</h3>
        <p>If you have forgotten your password or need to change it for security reasons, please submit a request below. Include a brief reason and the admin will review your request. Once approved, you will receive a temporary password via email or can log in with the new password set by the admin.</p>
    </div>

    <form method="POST" style="background: #fff; padding: 20px; border-radius: 6px; border: 1px solid #eee; margin-bottom: 25px;">
        <div style="margin-bottom: 15px;">
            <label style="font-weight: bold; display: block; margin-bottom: 5px;">Reason for Password Reset *</label>
            <textarea name="reason" rows="5" required style="width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-family: Arial, sans-serif;" placeholder="E.g., I forgot my password, I need to change it for security reasons, etc."></textarea>
        </div>

        <button type="submit" style="background: #0066cc; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Submit Request</button>
    </form>

    <h3>Your Password Reset Requests</h3>
    <?php if (!empty($requests)): ?>
        <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 5px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <thead>
                <tr style="background: #f5f5f5; border-bottom: 2px solid #ddd;">
                    <th style="padding: 12px; text-align: left;">Request Date</th>
                    <th style="padding: 12px; text-align: left;">Reason</th>
                    <th style="padding: 12px; text-align: left;">Status</th>
                    <th style="padding: 12px; text-align: left;">Admin Comment</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $req): ?>
                <tr style="border-bottom: 1px solid #eee;">
                    <td style="padding: 12px;"><?php echo date('Y-m-d H:i', strtotime($req['request_date'])); ?></td>
                    <td style="padding: 12px;"><small><?php echo htmlspecialchars(substr($req['reason'], 0, 50)) . (strlen($req['reason']) > 50 ? '...' : ''); ?></small></td>
                    <td style="padding: 12px;">
                        <span style="display: inline-block; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: bold;
                                     background: <?php 
                                         echo $req['status'] == 'Approved' ? '#d4edda' : 
                                              ($req['status'] == 'Rejected' ? '#f8d7da' : '#fff3cd');
                                     ?>;
                                     color: <?php 
                                         echo $req['status'] == 'Approved' ? '#155724' : 
                                              ($req['status'] == 'Rejected' ? '#721c24' : '#856404');
                                     ?>;">
                            <?php echo $req['status']; ?>
                        </span>
                    </td>
                    <td style="padding: 12px;"><small><?php echo htmlspecialchars($req['admin_comment'] ?? 'N/A'); ?></small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="background: #f0f0f0; padding: 20px; text-align: center; border-radius: 4px;">
            <p style="color: #666;">No password reset requests yet.</p>
        </div>
    <?php endif; ?>

    <div style="margin-top: 20px;">
        <a href="dashboard.php" style="color: #0066cc; text-decoration: none;">Back to Dashboard</a>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
